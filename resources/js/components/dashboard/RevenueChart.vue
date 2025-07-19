<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { Chart, registerables } from 'chart.js'
Chart.register(...registerables)

const props = defineProps<{
    labels: string[] | undefined,
    data: number[] | undefined
}>()

const canvasRef = ref<HTMLCanvasElement | null>(null)
let chart: Chart<'line'> | null = null

onMounted(() => {
    watch(() => [props.labels, props.data], () => {
        if (!canvasRef.value) return
        if (!props.labels || !props.data) return

        if (chart) chart.destroy()
        chart = new Chart(canvasRef.value, {
            type: 'line',
            data: {
                labels: props.labels,
                datasets: [{
                    label: 'Doanh thu',
                    data: props.data,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37,99,235,0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        grid: {
                            display: false,
                        },
                        ticks: {
                            maxRotation: 0,
                            minRotation: 0,
                            callback: function (value, index, ticks) {
                                const label = this.getLabelForValue(value as number)
                                const parts = label.split('-')
                                if (parts.length === 3) {
                                    return parts[2] + '/' + parts[1]
                                }
                                return label
                            }
                        }

                    }
                }
            }
        })
    }, { immediate: true })
})
</script>

<template>
    <div class="bg-white p-4 rounded-xl shadow border border-gray-300 rounded-xl p-4">
        <h2 class="font-semibold mb-2">Doanh thu theo thời gian</h2>
        <canvas ref="canvasRef"></canvas>
    </div>
</template>