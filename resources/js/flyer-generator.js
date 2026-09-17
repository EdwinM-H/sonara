/**
 * SONARA — Generador de flyers con IA.
 * Interactúa con el endpoint de generación y muestra el resultado.
 */
export function registerFlyerGenerator() {
    window.Alpine.data('flyerGenerator', () => ({
        style: '',
        loading: false,
        message: '',
        resultImage: '',
        remaining: 0,
        init() {
            this.remaining = Number(this.$root?.dataset?.remaining ?? 0);
        },
        async generate() {
            const root = this.$root.closest('[data-generate-route]');
            const route = root?.dataset.generateRoute;
            const remainingEl = root?.querySelector('x-text');
            if (!route) {
                this.message = 'No se encontró la ruta de generación.';
                return;
            }
            this.loading = true;
            this.message = 'Generando tu flyer, esto puede tardar unos segundos…';
            this.resultImage = '';
            try {
                const res = await fetch(route, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? ''
                    },
                    body: JSON.stringify({ style: this.style }),
                });
                const data = await res.json();
                if (data.success) {
                    this.resultImage = data.generation.image;
                    this.message = data.message;
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    this.message = data.message || 'No se pudo generar el flyer.';
                }
            } catch (e) {
                this.message = 'Error de conexión al generar el flyer.';
            } finally {
                this.loading = false;
            }
        },
    }));
}