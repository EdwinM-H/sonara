@props(['title', 'text', 'url', 'image' => null])

{{--
    Compartir con la Web Share API: incluye el flyer como archivo cuando el
    navegador lo permite (móviles), si no comparte texto + enlace. Sin Web
    Share API copia el texto, el enlace del flyer y el de la página al
    portapapeles y lo confirma en pantalla y al lector de pantalla.
--}}
<div x-data="{
        status: '',
        payload: @js(['title' => $title, 'text' => $text, 'url' => $url, 'image' => $image]),
        fullText() {
            return [this.payload.text, this.payload.image ? 'Flyer: ' + this.payload.image : null, this.payload.url]
                .filter(Boolean).join('\n\n');
        },
        file: null,
        // El flyer se descarga antes del clic: navigator.share exige que se
        // llame justo tras el gesto del usuario, sin esperas de red.
        init() {
            if (!this.payload.image || !navigator.canShare) return;
            fetch(this.payload.image).then(r => r.ok ? r.blob() : null).then(blob => {
                if (!blob) return;
                const file = new File([blob], 'flyer.' + (blob.type.split('/')[1] || 'jpg'), { type: blob.type });
                if (navigator.canShare({ files: [file] })) this.file = file;
            }).catch(() => {});
        },
        async share() {
            const { title, text, url } = this.payload;
            if (navigator.share) {
                try {
                    await navigator.share(this.file
                        ? { title, text: text + '\n\n' + url, files: [this.file] }
                        : { title, text, url });
                    this.status = '';
                    return;
                } catch (e) {
                    if (e.name === 'AbortError') return; // la persona cerró el diálogo
                }
            }
            this.copy();
        },
        async copy() {
            try {
                await navigator.clipboard.writeText(this.fullText());
                this.status = 'Copiado al portapapeles. Ya puedes pegarlo donde quieras.';
            } catch (e) {
                this.status = 'No se pudo copiar automáticamente. Enlace: ' + this.payload.url;
            }
            setTimeout(() => this.status = '', 5000);
        },
    }" {{ $attributes->merge(['class' => 'relative']) }}>
    <button type="button" @click="share()" class="btn btn-secondary w-full sm:w-auto" data-share-button>
        <x-icon name="share" class="w-5 h-5" /> Compartir
    </button>
    <p role="status" aria-live="polite" x-text="status" x-show="status" x-cloak
       class="mt-2 rounded-xl bg-green-50 px-3 py-2 text-sm font-semibold text-green-800"></p>
</div>
