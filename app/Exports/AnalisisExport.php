<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AnalisisExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $resultados;

    public function __construct(array $resultados)
    {
        $this->resultados = $resultados;
    }

    public function array(): array
    {
        // Procesamos los datos para que se vean bien en Excel
        // Si la respuesta es un array (ej. horas), lo convertimos a texto
        return array_map(function ($row) {
            $respuesta = $row['respuesta'];
            
            if (is_array($respuesta)) {
                $texto = '';
                foreach ($respuesta as $key => $valor) {
                    // Formato: "00: 5 | 01: 3"
                    $texto .= "$key: $valor | ";
                }
                $respuesta = rtrim($texto, " | ");
            }

            return [
                $row['pregunta'],
                $respuesta
            ];
        }, $this->resultados);
    }

    public function headings(): array
    {
        return [
            'Pregunta de Negocio',
            'Resultado del Análisis'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Poner en negrita la fila 1
            1    => ['font' => ['bold' => true]],
        ];
    }
}