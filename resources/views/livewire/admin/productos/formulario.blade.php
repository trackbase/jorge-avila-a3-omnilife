<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <a href="{{ route('admin.productos.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Productos</a>
            <h1 class="h3 mb-0">{{ $form->producto ? 'Editar '.$form->producto->sku : 'Nuevo producto' }}</h1>
        </div>
    </div>

    <form wire:submit="guardar" novalidate>
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body row g-3">
                        <div class="col-md-4">
                            <label for="sku" class="form-label">SKU</label>
                            <input id="sku" type="text" wire:model.blur="form.sku" maxlength="30"
                                   class="form-control font-monospace @error('form.sku') is-invalid @enderror">
                            @error('form.sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-8">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input id="nombre" type="text" wire:model.blur="form.nombre" maxlength="120"
                                   class="form-control @error('form.nombre') is-invalid @enderror">
                            @error('form.nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Sin acentos ni caracteres especiales: letras, números, espacios y guiones.</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="categoria" class="form-label">Categoría</label>
                            <select id="categoria" wire:model.blur="form.categoria_id"
                                    class="form-select @error('form.categoria_id') is-invalid @enderror">
                                <option value="">Selecciona…</option>
                                @foreach ($categorias as $categoria)
                                    <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                                @endforeach
                            </select>
                            @error('form.categoria_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="vigencia" class="form-label">Fecha de vigencia</label>
                            <input id="vigencia" type="date" wire:model.blur="form.fecha_vigencia"
                                   class="form-control @error('form.fecha_vigencia') is-invalid @enderror">
                            @error('form.fecha_vigencia') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label for="corta" class="form-label">Descripción corta</label>
                            <input id="corta" type="text" wire:model.blur="form.descripcion_corta" maxlength="255"
                                   class="form-control @error('form.descripcion_corta') is-invalid @enderror">
                            @error('form.descripcion_corta') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label for="larga" class="form-label">Descripción larga</label>
                            <textarea id="larga" rows="5" wire:model.blur="form.descripcion_larga"
                                      class="form-control @error('form.descripcion_larga') is-invalid @enderror"></textarea>
                            @error('form.descripcion_larga') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body row g-3">
                        <div class="col-6 col-lg-12">
                            <label for="precio_usd" class="form-label">Precio USD</label>
                            <div class="input-group has-validation">
                                <span class="input-group-text">$</span>
                                <input id="precio_usd" type="number" step="0.01" min="0.01" wire:model.blur="form.precio_usd"
                                       class="form-control @error('form.precio_usd') is-invalid @enderror">
                                @error('form.precio_usd') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col-6 col-lg-12">
                            <label for="precio_mxn" class="form-label">Precio MXN</label>
                            <div class="input-group has-validation">
                                <span class="input-group-text">$</span>
                                <input id="precio_mxn" type="number" step="0.01" min="0.01" wire:model.blur="form.precio_mxn"
                                       class="form-control @error('form.precio_mxn') is-invalid @enderror">
                                @error('form.precio_mxn') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col-6 col-lg-12">
                            <label for="stock" class="form-label">Stock</label>
                            <input id="stock" type="number" step="1" min="0" wire:model.blur="form.stock"
                                   class="form-control @error('form.stock') is-invalid @enderror">
                            @error('form.stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6 col-lg-12 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input id="activo" type="checkbox" role="switch" wire:model="form.activo" class="form-check-input">
                                <label for="activo" class="form-check-label">Activo en la tienda</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm"
                     x-data="{ subiendo: false, progreso: 0 }"
                     x-on:livewire-upload-start="subiendo = true"
                     x-on:livewire-upload-finish="subiendo = false"
                     x-on:livewire-upload-error="subiendo = false"
                     x-on:livewire-upload-progress="progreso = $event.detail.progress">
                    <div class="card-body">
                        <label for="imagen" class="form-label">Imagen</label>
                        @if ($form->imagen && ! $errors->has('form.imagen'))
                            <img src="{{ $form->imagen->temporaryUrl() }}" alt="Vista previa" class="img-fluid rounded mb-2">
                        @elseif ($form->producto)
                            <img src="{{ $form->producto->imagen_url }}" alt="{{ $form->producto->nombre }}" class="img-fluid rounded mb-2">
                        @endif
                        <input id="imagen" type="file" accept="image/png,image/jpeg,image/webp" wire:model="form.imagen"
                               class="form-control @error('form.imagen') is-invalid @enderror">
                        @error('form.imagen')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @else
                            <div class="form-text">JPG, PNG o WEBP, máximo 2 MB.{{ $form->producto ? ' Déjalo vacío para conservar la actual.' : '' }}</div>
                        @enderror
                        <div class="progress mt-2" role="progressbar" x-show="subiendo" x-cloak style="height: 6px">
                            <div class="progress-bar" :style="`width: ${progreso}%`"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-3">
            <a href="{{ route('admin.productos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="guardar, form.imagen">
                <span wire:loading.remove wire:target="guardar"><i class="bi bi-check-lg"></i> Guardar</span>
                <span wire:loading wire:target="guardar"><span class="spinner-border spinner-border-sm"></span> Guardando…</span>
            </button>
        </div>
    </form>
</div>
