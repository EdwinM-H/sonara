/**
 * Gráficos del panel de estadísticas del admin. Chart.js se importa de
 * forma diferida: solo lo descarga la página que tiene un gráfico.
 *
 * Uso: <canvas x-data="statsChart(config)"></canvas>, donde config es
 * { type, labels, values, label }.
 */
const PALETTE = ['#7C3AED', '#A78BFA', '#5B21B6', '#DDD6FE'];
const BORDER = '#5B21B6';

export function registerAdminStats() {
    window.Alpine.data('statsChart', (config) => {
        // Fuera del estado de Alpine: el proxy reactivo rompe a Chart.js.
        let chart = null;

        return {
            async init() {
                const { default: Chart } = await import('chart.js/auto');
                const isPie = config.type === 'doughnut' || config.type === 'pie';
                chart = new Chart(this.$el, {
                    type: config.type,
                    data: {
                        labels: config.labels,
                        datasets: [{
                            label: config.label,
                            data: config.values,
                            backgroundColor: isPie ? PALETTE : PALETTE[0],
                            borderColor: BORDER,
                            borderWidth: 2,
                            fill: true,
                            tension: 0.3,
                            pointRadius: config.labels.length > 40 ? 0 : 3,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: isPie, position: 'bottom' } },
                        scales: isPie ? {} : {
                            y: { beginAtZero: true, ticks: { precision: 0 } },
                            x: { ticks: { maxRotation: 0, autoSkip: true } },
                        },
                    },
                });
            },
            destroy() {
                chart?.destroy();
            },
        };
    });
}
