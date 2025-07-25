<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import { Chart, registerables } from 'chart.js'
Chart.register(...registerables)

const props = defineProps<{
    labels: string[]
    multiData: number[]   // Khách mua nhiều lần
    oneData: number[]     // Khách mua 1 lần
}>()

const canvasRef = ref<HTMLCanvasElement | null>(null)
let chart: Chart<'line'> | null = null

const renderChart = () => {
    if (!canvasRef.value) return
    if (chart) {
        chart.destroy()
        chart = null
    }

    chart = new Chart(canvasRef.value, {
        type: 'line',
        data: {
            labels: props.labels,
            datasets: [
                {
                    label: 'Khách mua nhiều lần',
                    data: props.multiData,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59,130,246,0.2)', // màu fill mờ
                    borderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.4, // độ cong
                    fill: true,   // bật fill
                },
                {
                    label: 'Khách mua một lần',
                    data: props.oneData,
                    borderColor: '#06b6d4',
                    backgroundColor: 'rgba(6,182,212,0.2)', // màu fill mờ
                    borderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.4, // độ cong
                    fill: true,
                }
            ]
        },
        options: {
            layout: {
                padding: {
                    bottom: 20
                }
            },
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'line'
                    }
                },
                tooltip: {
                    callbacks: {
                        label: (context) => {
                            const value = context.raw as number
                            return value.toLocaleString('vi-VN') + ' đ'
                        }
                    }
                },
                title: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: (value) => {
                            const val = Number(value)
                            return (val / 1000) + 'k đ'
                        }
                    },
                    grid: {
                        color: '#e5e7eb'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        autoSkip: true,
                        maxRotation: 0,
                        minRotation: 0
                    }
                }
            }
        }
    })
}

onMounted(() => renderChart())
watch(() => [props.labels, props.multiData, props.oneData], () => renderChart(), { deep: true })
onBeforeUnmount(() => { if (chart) chart.destroy() })
</script>

<template>
    <div class="bg-white p-4 rounded-xl shadow h-96">
        <h2 class="text-lg font-semibold mb-2">Chi tiêu khách hàng theo thời gian</h2>
        <canvas ref="canvasRef"></canvas>
    </div>
</template>
