<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SimpleXMLElement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PeajeController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard', [
            'resultados' => null
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'xml' => 'required|file|mimes:xml',
        ]);

        $file = $request->file('xml');
        $path = $file->storeAs('xml', $file->getClientOriginalName());
        $xmlContent = Storage::get($path);
        
        // Cargar XML evitando errores de parseo
        try {
            $xml = new SimpleXMLElement($xmlContent);
        } catch (\Exception $e) {
            return back()->withErrors(['xml' => 'Error al leer el archivo XML.']);
        }

        $registros = [];
        // Usamos transacciones para velocidad y seguridad
        DB::beginTransaction();
        
        try {
            foreach ($xml->Registro as $r) {
                $registros[] = [
                    'numero_tag' => (string)$r->Numero_TAG,
                    'concesion' => (int)$r->Concesion,
                    'tipo_tag' => (int)$r->Tipo_TAG,
                    'iut' => (string)$r->IUT,
                    'categoria' => (int)$r->Categoria,
                    'categoria_cobrada' => (int)$r->Categoria_Cobrada,
                    'categoria_detectada' => (int)$r->Categoria_Detectada,
                    'status' => (int)$r->Status,
                    'hora_peaje' => (string)$r->Hora_peaje,
                    'fecha_peaje' => (string)$r->Fecha_peaje,
                    'importe_peaje' => (int)$r->Importe_peaje,
                    'numero_reenvio' => (int)$r->Numero_Reenvio,
                    'entrada' => (string)$r->Ruta->Entrada,
                    'salida' => (string)$r->Ruta->Salida,
                    'sentido' => (int)$r->Ruta->Sentido,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Insertamos en lotes para mejor rendimiento
            foreach (array_chunk($registros, 100) as $chunk) {
                DB::table('peajes')->insert($chunk);
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['xml' => 'Error al guardar en base de datos: ' . $e->getMessage()]);
        }

        // --- CÁLCULOS PARA LAS PREGUNTAS ---

        // 1. ¿Cuántos cruces he recibido en el archivo?
        $totalCruces = count($registros);

        // 2. ¿Cuál es el importe total de los cruces recibidos?
        $importeTotal = array_sum(array_column($registros, 'importe_peaje'));

        // 3. ¿Cuántos cruces he recibido en cada hora del día?
        // Extraemos solo la hora (HH) del string "HH:MM:SS"
        $crucesPorHora = [];
        foreach ($registros as $r) {
            $horaFull = $r['hora_peaje']; // Ej: 00:57:35
            $horaSolo = explode(':', $horaFull)[0]; // Ej: 00
            
            if (!isset($crucesPorHora[$horaSolo])) {
                $crucesPorHora[$horaSolo] = 0;
            }
            $crucesPorHora[$horaSolo]++;
        }
        ksort($crucesPorHora); // Ordenar por hora (00, 01, 02...)

        // 4. ¿Cuántos cruces he recibido por cada tipo de TAG?
        $crucesPorTipoTAG = [];
        foreach ($registros as $r) {
            $tipo = $r['tipo_tag'];
            if (!isset($crucesPorTipoTAG[$tipo])) $crucesPorTipoTAG[$tipo] = 0;
            $crucesPorTipoTAG[$tipo]++;
        }

        // 5. Es necesario saber cuantos cruces he recibido por categoría cobrada.
        $crucesPorCategoria = [];
        foreach ($registros as $r) {
            $cat = $r['categoria_cobrada'];
            if (!isset($crucesPorCategoria[$cat])) $crucesPorCategoria[$cat] = 0;
            $crucesPorCategoria[$cat]++;
        }

        // 6. Discrepancias
        $discrepanciasIUTs = [];
        foreach ($registros as $r) {
            if (
                $r['categoria'] != $r['categoria_cobrada'] ||
                $r['categoria'] != $r['categoria_detectada'] ||
                $r['categoria_cobrada'] != $r['categoria_detectada']
            ) {
                $discrepanciasIUTs[] = $r['iut'];
            }
        }
        
        $totalDiscrepancias = count($discrepanciasIUTs);
        
        // Formateamos la respuesta de la pregunta 6
        $respuestaDiscrepancias = $totalDiscrepancias > 0 
            ? "Sí, $totalDiscrepancias discrepancias. IUTs: " . implode(', ', $discrepanciasIUTs)
            : "No existen discrepancias.";

        // Preparamos la estructura FINAL para la tabla
        $resultados = [
            [
                'pregunta' => '1. ¿Cuántos cruces he recibido en el archivo?',
                'respuesta' => $totalCruces
            ],
            [
                'pregunta' => '2. ¿Cuál es el importe total de los cruces recibidos?',
                'respuesta' => '$' . number_format($importeTotal, 0, ',', '.')
            ],
            [
                'pregunta' => '3. ¿Cuántos cruces he recibido en cada hora del día?',
                'respuesta' => $crucesPorHora // Enviaremos el array, Vue lo formateará
            ],
            [
                'pregunta' => '4. ¿Cuántos cruces he recibido por cada tipo de TAG?',
                'respuesta' => $crucesPorTipoTAG // Enviaremos el array
            ],
            [
                'pregunta' => '5. Es necesario saber cuantos cruces he recibido por categoría cobrada.',
                'respuesta' => $crucesPorCategoria // Enviaremos el array
            ],
            [
                'pregunta' => '6. ¿Existen discrepancias entre la categoría, categoría cobrada y categoría detectada?',
                'respuesta' => $respuestaDiscrepancias
            ]
        ];

        return Inertia::render('Dashboard', [
            'resultados' => $resultados
        ]);
    }
}