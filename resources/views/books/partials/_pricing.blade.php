<div class="tab-pane d-none" id="tab-pricing">
    <p class="text-muted mb-3">Set a price per country for each enabled format.</p>
    <div id="pricingContainer">
        <div class="text-center text-muted py-4" id="pricingEmptyHint">
            <i class="feather-alert-circle fs-30 d-block mb-2 opacity-50"></i>
            Go back to <strong>Formats</strong> and select at least one format first.
        </div>
    </div>
    <div class="d-flex justify-content-between mt-4">
       <button type="button" class="btn btn-light-brand" onclick="goToTab('tab-media')">Previous</button>
        {{-- <button type="button" class="btn btn-primary" onclick="goToTab('tab-inventory')">Save & Continue <i class="feather-arrow-right ms-1"></i></button> --}}
        <button type="button" class="btn btn-primary" onclick="saveTabAjax('pricing','tab-inventory', this)">Save & Continue <i class="feather-arrow-right ms-1"></i></button>
    </div>
</div>