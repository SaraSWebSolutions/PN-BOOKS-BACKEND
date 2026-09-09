<div class="tab-pane d-none" id="tab-inventory">
    <p class="text-muted mb-3">Manage stock for physical books. eBooks/Audiobooks are digital and don't require stock.</p>
    <div id="inventoryContainer">
        <div class="text-center text-muted py-4" id="inventoryEmptyHint">
            <i class="feather-alert-circle fs-30 d-block mb-2 opacity-50"></i>
            Enable Physical Book in <strong>Formats</strong> to manage inventory.
        </div>
    </div>
    <div class="d-flex justify-content-between mt-4">
        <button type="button" class="btn btn-light-brand" onclick="goToTab('tab-pricing')"><i class="feather-arrow-left me-1"></i> Previous</button>
        {{-- <button type="button" class="btn btn-primary" onclick="goToTab('tab-shipping')">Save & Continue <i class="feather-arrow-right ms-1"></i></button> --}}
        <button type="button" class="btn btn-primary" onclick="saveTabAjax('inventory','tab-shipping', this)">Save & Continue <i class="feather-arrow-right ms-1"></i></button>
    </div>
</div>