@props(['totales'])

<table class="table table-sm mb-0">
    <thead>
        <tr class="small text-body-secondary">
            <th scope="col" class="fw-normal"></th>
            <th scope="col" class="fw-normal text-end">MXN</th>
            <th scope="col" class="fw-normal text-end">USD</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <th scope="row" class="fw-normal">Subtotal</th>
            <td class="text-end">${{ number_format($totales['subtotal_mxn'], 2) }}</td>
            <td class="text-end">${{ number_format($totales['subtotal_usd'], 2) }}</td>
        </tr>
        <tr>
            <th scope="row" class="fw-normal">IVA ({{ config('tienda.iva_tasa') * 100 }} %)</th>
            <td class="text-end">${{ number_format($totales['iva_mxn'], 2) }}</td>
            <td class="text-end">${{ number_format($totales['iva_usd'], 2) }}</td>
        </tr>
        <tr class="fs-5">
            <th scope="row">Total</th>
            <td class="text-end fw-bold text-primary">${{ number_format($totales['total_mxn'], 2) }}</td>
            <td class="text-end fw-semibold">${{ number_format($totales['total_usd'], 2) }}</td>
        </tr>
    </tbody>
</table>
