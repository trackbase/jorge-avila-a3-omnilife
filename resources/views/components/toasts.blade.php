{{-- Toasts con Alpine: escuchan el evento "toast" que lanza Livewire con $this->dispatch() o un flash de sesión tras redirigir. --}}
<div class="toast-container position-fixed bottom-0 end-0 p-3"
     x-data="{
        toasts: [],
        agregar(toast) {
            toast.id = Date.now() + Math.random();
            this.toasts.push(toast);
            setTimeout(() => this.quitar(toast.id), 4000);
        },
        quitar(id) {
            this.toasts = this.toasts.filter(toast => toast.id !== id);
        },
     }"
     x-init="@if (session('toast')) agregar(@js(session('toast'))) @endif"
     x-on:toast.window="agregar($event.detail)">
    <template x-for="toast in toasts" :key="toast.id">
        <div class="toast show align-items-center border-0" :class="'text-bg-' + (toast.tipo ?? 'success')" role="status">
            <div class="d-flex">
                <div class="toast-body" x-text="toast.mensaje"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" aria-label="Cerrar" @click="quitar(toast.id)"></button>
            </div>
        </div>
    </template>
</div>
