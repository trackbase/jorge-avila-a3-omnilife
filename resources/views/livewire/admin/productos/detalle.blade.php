<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <a href="{{ route('admin.productos.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Productos</a>
            <h1 class="h3 mb-0">{{ $producto->nombre }}</h1>
        </div>
        <a href="{{ route('admin.productos.editar', $producto) }}" class="btn btn-primary"><i class="bi bi-pencil"></i> Editar</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body row g-4">
            <div class="col-md-4">
                <img src="{{ $producto->imagen_url }}" alt="{{ $producto->nombre }}" class="img-fluid rounded">
            </div>
            <div class="col-md-8">
                <dl class="row mb-0">
                    <dt class="col-sm-4">SKU</dt>
                    <dd class="col-sm-8 font-monospace">{{ $producto->sku }}</dd>
                    <dt class="col-sm-4">Categoría</dt>
                    <dd class="col-sm-8">{{ $producto->categoria->nombre }}</dd>
                    <dt class="col-sm-4">Precio</dt>
                    <dd class="col-sm-8">${{ number_format($producto->precio_usd, 2) }} USD · ${{ number_format($producto->precio_mxn, 2) }} MXN</dd>
                    <dt class="col-sm-4">Stock</dt>
                    <dd class="col-sm-8">{{ $producto->stock }}</dd>
                    <dt class="col-sm-4">Vigencia</dt>
                    <dd class="col-sm-8">
                        {{ $producto->fecha_vigencia->format('d/m/Y') }}
                        @if ($producto->fecha_vigencia->lt(today()))
                            <span class="badge text-bg-warning">Vencido</span>
                        @endif
                    </dd>
                    <dt class="col-sm-4">Estado</dt>
                    <dd class="col-sm-8">
                        <span @class(['badge', 'text-bg-success' => $producto->activo, 'text-bg-secondary' => ! $producto->activo])>
                            {{ $producto->activo ? 'Activo' : 'Inactivo' }}
                        </span>
                    </dd>
                    <dt class="col-sm-4">Descripción corta</dt>
                    <dd class="col-sm-8">{{ $producto->descripcion_corta }}</dd>
                    <dt class="col-sm-4">Descripción larga</dt>
                    <dd class="col-sm-8">{!! nl2br(e($producto->descripcion_larga)) !!}</dd>
                    <dt class="col-sm-4">Actualizado</dt>
                    <dd class="col-sm-8 text-body-secondary small mb-0">{{ $producto->updated_at->format('d/m/Y H:i') }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>
