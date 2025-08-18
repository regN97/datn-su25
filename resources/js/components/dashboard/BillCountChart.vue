<script setup lang="ts">
import { Chart, registerables } from 'chart.js';
import { onMounted, ref, watch } from 'vue';
Chart.register(...registerables);

const props = defineProps<{
    labels: string[] | undefined;
    data: number[] | undefined;
}>();

const canvasRef = ref<HTMLCanvasElement | null>(null);
let chart: Chart<'line'> | null = null;

onMounted(() => {
    watch(
        () => [props.labels, props.data],
        () => {
            if (!canvasRef.value) return;
            if (!props.labels || !props.data) return;

            if (chart) chart.destroy();
            chart = new Chart(canvasRef.value, {
                type: 'line',
                data: {
                    labels: props.labels,
                    datasets: [
                        {
                            label: 'Đơn hàng',
                            data: props.data,
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37,99,235,0.1)',
                            fill: true,
                            tension: 0.4,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false,
                            },
                            ticks: {
                                maxRotation: 0,
                                minRotation: 0,
                                callback: function (value, index, ticks) {
                                    const label = this.getLabelForValue(value as number);
                                    const parts = label.split('-');
                                    if (parts.length === 3) {
                                        return parts[2] + '/' + parts[1];
                                    }
                                    return label;
                                },
                            },
                        },
                        y: {
                            min: 0,
                            beginAtZero: false,
                            ticks: {
                                stepSize: 1,
                                callback: function (value) {
                                    if (value === 0) return '';
                                    return value;
                                },
                            },
                            grid: {
                                color: 'rgba(0,0,0,0.05)',
                            },
                        },
                    },
                },
            });
        },
        { immediate: true },
    );
});
</script>

<template>
    <div class="rounded-xl border border-gray-300 bg-white p-4 shadow">
        <h2 class="mb-2 font-semibold">Số lượng đơn hàng theo thời gian</h2>
        <canvas ref="canvasRef"></canvas>
    </div>
</template>
