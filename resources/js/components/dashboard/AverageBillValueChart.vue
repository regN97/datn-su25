<script setup lang="ts">
import { Chart, registerables } from 'chart.js';
import { onMounted, ref, watch } from 'vue';
Chart.register(...registerables);

const props = defineProps<{
    labels: string[];
    data: number[];
}>();

const canvasRef = ref<HTMLCanvasElement | null>(null);
let chart: Chart<'line'> | null = null;

onMounted(() => {
    watch(
        () => [props.labels, props.data],
        () => {
            if (!props.labels?.length || !props.data?.length) return;
            if (!canvasRef.value) return;

            if (chart) {
                chart.destroy();
                chart = null;
            }

            chart = new Chart(canvasRef.value, {
                type: 'line',
                data: {
                    labels: props.labels,
                    datasets: [
                        {
                            label: 'Giá trị đơn hàng trung bình',
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
                            ticks: {
                                callback: (value) => new Intl.NumberFormat('vi-VN').format(value as number) + ' đ',
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
    <div class="rounded-xl bg-white p-4 shadow">
        <h2 class="mb-2 font-semibold">Giá trị đơn hàng trung bình theo thời gian</h2>
        <canvas ref="canvasRef"></canvas>
    </div>
</template>
