import Chart from 'chart.js/auto';

// Alpine viene incluido en Livewire 3: se registra el componente cuando Alpine arranca.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('graficaGanancias', (datosIniciales) => {
        // Fuera del estado reactivo: Chart.js no funciona bien envuelto en los proxies de Alpine.
        let grafica = null;

        return {
            init() {
                grafica = new Chart(this.$refs.lienzo, {
                    type: 'bar',
                    data: {
                        labels: datosIniciales.etiquetas,
                        datasets: [{
                            label: 'Ventas netas sin IVA (MXN)',
                            data: datosIniciales.valores,
                            backgroundColor: '#66308C',
                            hoverBackgroundColor: '#C8A5E0',
                            borderRadius: 6,
                        }],
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { callback: (valor) => '$' + valor.toLocaleString('es-MX') },
                            },
                        },
                    },
                });
            },

            actualizar(datos) {
                grafica.data.labels = datos.etiquetas;
                grafica.data.datasets[0].data = datos.valores;
                grafica.update();
            },
        };
    });
});
