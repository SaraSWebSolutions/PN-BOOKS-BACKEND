@php $shipping = $book?->shipping; @endphp
<div class="tab-pane d-none" id="tab-shipping">
    <div class="row g-3" id="shippingBlock">
        <div class="col-12 form-check form-switch">
            <input class="form-check-input" type="checkbox" name="shipping[enable_shipping]" id="enable_shipping"
                {{ old('shipping.enable_shipping', $shipping->enable_shipping ?? true) ? 'checked' : '' }}>
            <label class="form-check-label fw-semibold" for="enable_shipping">Enable Shipping (Physical Book)</label>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Shipping Class</label>
            <input type="text" name="shipping[shipping_class]" class="form-control" placeholder="Books - Standard"
                value="{{ old('shipping.shipping_class', $shipping->shipping_class ?? '') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Weight (kg)</label>
            <input type="number" step="0.01" name="shipping[weight]" class="form-control"
                value="{{ old('shipping.weight', $shipping->weight ?? '') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Ships From</label>
            <input type="text" name="shipping[ships_from]" class="form-control" placeholder="Madurai, Tamil Nadu, India"
                value="{{ old('shipping.ships_from', $shipping->ships_from ?? '') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Length (cm)</label>
            <input type="number" step="0.1" name="shipping[length]" class="form-control"
                value="{{ old('shipping.length', $shipping->length ?? '') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Width (cm)</label>
            <input type="number" step="0.1" name="shipping[width]" class="form-control"
                value="{{ old('shipping.width', $shipping->width ?? '') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Height (cm)</label>
            <input type="number" step="0.1" name="shipping[height]" class="form-control"
                value="{{ old('shipping.height', $shipping->height ?? '') }}">
        </div>
    </div>

    <hr class="my-4">
    <h6 class="fw-semibold mb-3">Shipping Methods</h6>
    <div id="shippingMethodsContainer"></div>
    <button type="button" class="btn btn-light-brand btn-sm mt-2" onclick="addShippingMethodRow()"><i class="feather-plus me-1"></i>Add Shipping Method</button>

    <div class="d-flex justify-content-between mt-4">
        <button type="button" class="btn btn-light-brand" onclick="goToTab('tab-inventory')"><i class="feather-arrow-left me-1"></i> Previous</button>
        <button type="button" class="btn btn-primary" onclick="saveTabAjax('shipping','tab-seo', this)">Save & Continue <i class="feather-arrow-right ms-1"></i></button>
    </div>
</div>

@if($shipping && $shipping->methods->count())
<script>
document.addEventListener('DOMContentLoaded', function () {
    const existingMethods = {!! json_encode($shipping->methods) !!};
    existingMethods.forEach(m => {
        addShippingMethodRow();
        const rows = document.querySelectorAll('#shippingMethodsContainer .row');
        const row = rows[rows.length - 1];
        row.querySelector('input[type=text]').value = m.name;
        row.querySelector('input[type=number]').value = m.cost;
        row.querySelector('input[type=checkbox]').checked = !!m.is_active;
    });
});
</script>
@endif