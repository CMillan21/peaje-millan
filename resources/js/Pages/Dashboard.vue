<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

// Recibimos "resultados" como prop desde Laravel
defineProps({
    resultados: Array
});

const file = ref(null)

const handleFile = (event) => {Selec
    file.value = event.target.files[0]
}

const submit = () => {
    if (!file.value) {
        alert('Selecciona un archivo XML')
        return
    }
    
    const formData = new FormData();
    formData.append('xml', file.value);

    router.post('/upload-xml', formData, {
        forceFormData: true,
        preserveScroll: true, // Mantiene la posición de la pantalla
    });
};

// Función auxiliar para saber si la respuesta es un Objeto/Array y formatearlo
const isObject = (value) => {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
};
</script>

<template>
    <div style="padding: 20px; font-family: sans-serif;">
        <h1>Dashboard OTIS - Análisis de Peajes</h1>

        <div style="margin-bottom: 20px;">
            <input type="file" accept=".xml" @change="handleFile" />
            <button @click="submit" style="margin-left: 10px; padding: 5px 10px; cursor: pointer;">
                Analizar XML
            </button>
        </div>

        <table v-if="resultados" border="1" cellpadding="10" style="border-collapse: collapse; width: 100%;">
            <thead>
                <tr style="background-color: #f2f2f2;">
                    <th style="width: 50%; text-align: left;">Pregunta</th>
                    <th style="width: 50%; text-align: left;">Respuesta</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(item, index) in resultados" :key="index">
                    <td>{{ item.pregunta }}</td>
                    <td>
                        <div v-if="isObject(item.respuesta)">
                            <ul style="margin: 0; padding-left: 20px;">
                                <li v-for="(count, key) in item.respuesta" :key="key">
                                    <strong>{{ key }}:</strong> {{ count }} cruces
                                </li>
                            </ul>
                        </div>
                        <div v-else>
                            {{ item.respuesta }}
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>