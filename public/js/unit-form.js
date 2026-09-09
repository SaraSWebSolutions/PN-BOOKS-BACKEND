// public/js/unit-form.js
// Powers both units/create.blade.php and units/edit.blade.php
// Reads config from window.UNIT_FORM_CONFIG (and window.UNIT_EDIT_PREFILL when editing)

(function () {
    const CSRF   = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const CONFIG = window.UNIT_FORM_CONFIG || { mode: 'create', routes: {}, redirectUrl: '/units' };
    const MODE   = CONFIG.mode;
    const ROUTES = CONFIG.routes;

    let addMode   = 'single';
    let bulkUnits = []; // [{ unit_number, floor_id, carpet_area, bedrooms, bathrooms, balconies, facing, rate_per_unit_area, total_price }]
    let currentPropertyTypeSlug = '';

    /* ════════════════════════════════════════════
       UNIT TYPE CONFIG per property type
    ════════════════════════════════════════════ */
    const UNIT_TYPE_CONFIG = {
        apartment: [
            { val: 'apartment', icon: 'feather-home',     label: 'Apartment' },
            { val: 'penthouse', icon: 'feather-star',     label: 'Penthouse' },
            { val: 'studio',    icon: 'feather-square',   label: 'Studio' },
        ],
        plot: [
            { val: 'residential_plot', icon: 'feather-map',                label: 'Residential Plot' },
            { val: 'commercial_plot',  icon: 'feather-briefcase',          label: 'Commercial Plot' },
            { val: 'corner_plot',      icon: 'feather-corner-up-right',    label: 'Corner Plot' },
        ],
        villa: [
            { val: 'villa',     icon: 'feather-layers',  label: 'Villa' },
            { val: 'duplex',    icon: 'feather-copy',    label: 'Duplex' },
            { val: 'bungalow',  icon: 'feather-home',    label: 'Bungalow' },
            { val: 'row_house', icon: 'feather-grid',    label: 'Row House' },
        ],
        shop: [
            { val: 'shop',     icon: 'feather-shopping-bag', label: 'Shop' },
            { val: 'showroom', icon: 'feather-monitor',      label: 'Showroom' },
        ],
        office: [
            { val: 'office',    icon: 'feather-briefcase', label: 'Office' },
            { val: 'coworking', icon: 'feather-users',     label: 'Co-working' },
        ],
        default: [
            { val: 'unit', icon: 'feather-home', label: 'Unit' },
        ],
    };

    const FACING_OPTIONS  = ['East','West','North','South','North-East','North-West','South-East','South-West'];
    const BEDROOM_OPTIONS = [1,2,3,4,5,6];

    /* ════════════════════════════════════════════
       TOAST
    ════════════════════════════════════════════ */
    let toastTimer;
    function showToast(msg, type = 'success') {
        const t = document.getElementById('ajaxToast');
        if (!t) return;
        t.className = `ajax-toast toast-${type} show`;
        document.getElementById('toastIcon').textContent = type === 'success' ? '✅' : '❌';
        document.getElementById('toastMsg').textContent  = msg;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(hideToast, 4500);
    }
    function hideToast() {
        document.getElementById('ajaxToast')?.classList.remove('show');
    }
    window.hideToast = hideToast;

    /* ════════════════════════════════════════════
       FIELD ERRORS
    ════════════════════════════════════════════ */
    function clearErrors() {
        document.querySelectorAll('.field-error').forEach(el => { el.textContent = ''; el.classList.remove('show'); });
        document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
    }
    function fieldErr(inputId, errorId, message) {
        const el = document.getElementById(inputId);
        const er = document.getElementById(errorId);
        if (el) el.classList.add('input-error');
        if (er) { er.textContent = message; er.classList.add('show'); }
    }

    /* ════════════════════════════════════════════
       PROJECT CHANGE → load towers / detect type
    ════════════════════════════════════════════ */
    async function onProjectChange(projectId, opts = {}) {
        if (!opts.skipReset) {
            resetTowerFloor();
            renderUnitTypeBtns('default');
            document.getElementById('unit_unit_type').value = '';
        }

        if (!projectId) return;

        const info = await fetch(ROUTES.projectInfo(projectId)).then(r => r.json()).catch(() => null);
        if (!info) return;

        currentPropertyTypeSlug = info.property_type_slug || 'default';
        document.getElementById('unit_property_type_slug').value = currentPropertyTypeSlug;

        renderUnitTypeBtns(currentPropertyTypeSlug);

        if (currentPropertyTypeSlug === 'apartment') {
            document.getElementById('towerSection').style.display = '';
            document.getElementById('unitTypeStepNum').textContent = '4';
            await loadTowers(projectId);
            document.getElementById('manualFloorWrap').style.display = 'none';
        } else {
            document.getElementById('towerSection').style.display = 'none';
            document.getElementById('floorSection').style.display = 'none';
            document.getElementById('unitTypeStepNum').textContent = '2';
            document.getElementById('manualFloorWrap').style.display =
                ['plot', 'villa'].includes(currentPropertyTypeSlug) ? '' : 'none';
        }

        adjustFieldsForType(currentPropertyTypeSlug);
    }

    async function loadTowers(projectId) {
        const sel = document.getElementById('unit_tower_id');
        sel.innerHTML = '<option value="">Loading towers…</option>';
        sel.classList.add('select-loading');

        const towers = await fetch(ROUTES.towers(projectId)).then(r => r.json()).catch(() => []);
        sel.classList.remove('select-loading');
        sel.innerHTML = '<option value="">— Select tower —</option>';

        if (!towers.length) {
            sel.innerHTML = '<option value="">No towers found — add towers first</option>';
            return;
        }
        towers.forEach(t => {
            const opt = document.createElement('option');
            opt.value = t.id;
            opt.textContent = `${t.name}${t.code ? ' (' + t.code + ')' : ''} — ${t.total_floors} floors`;
            sel.appendChild(opt);
        });
    }

    async function onTowerChange(towerId) {
        const floorSec = document.getElementById('floorSection');
        if (!towerId) { floorSec.style.display = 'none'; return; }

        floorSec.style.display = '';
        const sel = document.getElementById('unit_floor_id');
        sel.innerHTML = '<option value="">Loading floors…</option>';
        sel.classList.add('select-loading');

        const floors = await fetch(ROUTES.floors(towerId)).then(r => r.json()).catch(() => []);
        sel.classList.remove('select-loading');
        sel.innerHTML = '<option value="">— Select floor —</option>';

        floors.forEach(f => {
            const opt = document.createElement('option');
            opt.value = f.id;
            opt.textContent = `${f.display_name}  (${f.used_units}/${f.total_units} units used)`;
            opt.dataset.totalUnits = f.total_units;
            opt.dataset.usedUnits  = f.used_units;
            sel.appendChild(opt);
        });
    }

    function onFloorChange(floorId) {
        const sel  = document.getElementById('unit_floor_id');
        const opt  = sel.options[sel.selectedIndex];
        const info = document.getElementById('floorUnitCount');
        if (floorId && opt && opt.dataset.totalUnits !== undefined) {
            const used  = parseInt(opt.dataset.usedUnits);
            const total = parseInt(opt.dataset.totalUnits);
            const free = total - used;
            info.textContent = `${used}/${total} used — ${free} slots free`;
            info.style.color = free <= 0 ? '#ef4444' : '#10b981';
        } else {
            info.textContent = '';
        }
    }

    function resetTowerFloor() {
        document.getElementById('unit_tower_id').innerHTML = '<option value="">— Select tower —</option>';
        document.getElementById('unit_floor_id').innerHTML = '<option value="">— Select floor —</option>';
        document.getElementById('towerSection').style.display = 'none';
        document.getElementById('floorSection').style.display = 'none';
        document.getElementById('floorUnitCount').textContent = '';
    }

    /* ════════════════════════════════════════════
       UNIT TYPE BUTTONS
    ════════════════════════════════════════════ */
    function renderUnitTypeBtns(slug) {
        const types = UNIT_TYPE_CONFIG[slug] || UNIT_TYPE_CONFIG['default'];
        const wrap  = document.getElementById('unitTypeBtns');
        wrap.innerHTML = '';
        types.forEach(t => {
            const btn = document.createElement('button');
            btn.type        = 'button';
            btn.className   = 'btn btn-light border unit-type-btn';
            btn.dataset.val = t.val;
            btn.innerHTML   = `<i class="${t.icon} me-1 fs-13"></i>${t.label}`;
            btn.onclick     = () => selectUnitType(t.val);
            wrap.appendChild(btn);
        });
        if (types.length === 1) selectUnitType(types[0].val);
    }

    function selectUnitType(val) {
        document.getElementById('unit_unit_type').value = val;
        document.querySelectorAll('.unit-type-btn').forEach(b =>
            b.classList.toggle('active', b.dataset.val === val)
        );
    }

    /* ════════════════════════════════════════════
       ADJUST FIELDS based on property type
    ════════════════════════════════════════════ */
    function adjustFieldsForType(slug) {
        const isPlotOrVilla = ['plot', 'villa'].includes(slug);

        const showBedrooms = !['plot', 'shop'].includes(slug);
        document.getElementById('bedroomsWrap').style.display = showBedrooms ? '' : 'none';
        document.getElementById('bathBalRow').style.display   = isPlotOrVilla ? 'none' : '';

        const areaLabel = document.querySelector('#areaFieldsRow .col-4:first-child label');
        if (areaLabel) areaLabel.textContent = isPlotOrVilla ? 'Plot Area' : 'Carpet Area';
    }

    /* ════════════════════════════════════════════
       AREA UNIT LABELS
    ════════════════════════════════════════════ */
    function updateAreaLabels() {
        const unit = document.getElementById('unit_area_unit').value;
        document.querySelectorAll('.input-group-text.fs-11').forEach(el => {
            if (el.id !== 'rateUnitLabel') el.textContent = unit;
        });
        document.getElementById('rateUnitLabel').textContent = unit;
        document.getElementById('areaUnitLabel').textContent = unit;
    }

    /* ════════════════════════════════════════════
       PRICE AUTO-CALC (single mode)
    ════════════════════════════════════════════ */
    function autoCalcPrice() {
        const area = parseFloat(document.getElementById('unit_carpet_area').value) ||
                     parseFloat(document.getElementById('unit_super_built_up_area').value) || 0;
        const rate = parseFloat(document.getElementById('unit_rate_per_unit_area').value) || 0;
        if (area > 0 && rate > 0) {
            document.getElementById('unit_total_price').value = (area * rate).toFixed(0);
        }
    }
    window.autoCalcPrice    = autoCalcPrice;
    window.updateAreaLabels = updateAreaLabels;

    /* ════════════════════════════════════════════
       ADD MODE (single / bulk) — create page only
    ════════════════════════════════════════════ */
    function setAddMode(mode) {
        addMode = mode;
        const isBulk = mode === 'bulk';

        document.getElementById('singleModeBlock').style.display     = isBulk ? 'none' : '';
        const genBlock = document.getElementById('bulkGeneratorBlock');
        if (genBlock) genBlock.style.display = isBulk ? '' : 'none';

        const detailsCol = document.getElementById('singleDetailsColumn');
        if (detailsCol) detailsCol.style.display = isBulk ? 'none' : '';

        const cardsSection = document.getElementById('bulkCardsSection');
        if (cardsSection) cardsSection.style.display = isBulk ? '' : 'none';

        document.getElementById('modeSingleBtn')?.classList.toggle('active-tab', !isBulk);
        document.getElementById('modeBulkBtn')?.classList.toggle('active-tab', isBulk);

        updateSaveBtn();
    }
    window.setAddMode = setAddMode;

    /* ════════════════════════════════════════════
       BULK UNITS — full per-unit detail cards
    ════════════════════════════════════════════ */
    function floorOptionsHTML(selectedFloorId) {
        const src = document.getElementById('unit_floor_id');
        let html = '<option value="">— No floor —</option>';
        Array.from(src.options).forEach(o => {
            if (!o.value) return;
            const sel = String(o.value) === String(selectedFloorId || '') ? 'selected' : '';
            html += `<option value="${o.value}" ${sel}>${o.textContent}</option>`;
        });
        return html;
    }

    function bedroomOptionsHTML(selected) {
        let html = '<option value="">—</option>';
        BEDROOM_OPTIONS.forEach(b => {
            html += `<option value="${b}" ${String(selected) === String(b) ? 'selected' : ''}>${b} BHK</option>`;
        });
        html += `<option value="0" ${String(selected) === '0' ? 'selected' : ''}>Studio</option>`;
        return html;
    }

    function facingOptionsHTML(selected) {
        let html = '<option value="">— Select —</option>';
        FACING_OPTIONS.forEach(f => {
            html += `<option value="${f}" ${selected === f ? 'selected' : ''}>${f}</option>`;
        });
        return html;
    }

   function generateBulkUnits() {
    const count  = parseInt(document.getElementById('bulk_count').value) || 10;
    const start  = parseInt(document.getElementById('bulk_unit_start').value) || 1;
    const prefix = document.getElementById('bulk_prefix').value.trim().toUpperCase();

    if (count > 200) { showToast('Maximum 200 units at once.', 'error'); return; }

    // ── Floor capacity check (apartment only) ──────────────────────────
    if (currentPropertyTypeSlug === 'apartment') {
        const floorSel = document.getElementById('unit_floor_id');
        const floorOpt = floorSel.options[floorSel.selectedIndex];

        if (!floorSel.value) {
            showToast('Please select a floor before generating units.', 'error');
            return;
        }

        const totalSlots = parseInt(floorOpt.dataset.totalUnits) || 0;
        const usedSlots  = parseInt(floorOpt.dataset.usedUnits)  || 0;
        const freeSlots  = totalSlots - usedSlots;

        if (count > freeSlots) {
            showToast(
                `Floor only has ${freeSlots} free slot${freeSlots !== 1 ? 's' : ''} `
                + `(${usedSlots}/${totalSlots} used). Reduce count to ${freeSlots} or less.`,
                'error'
            );
            return;
        }
    }
    // ───────────────────────────────────────────────────────────────────

    const defaultFloor    = document.getElementById('unit_floor_id').value || '';
    const defaultArea     = document.getElementById('unit_carpet_area').value || '';
    const defaultBedrooms = document.getElementById('unit_bedrooms').value || '';
    const defaultBath     = document.getElementById('unit_bathrooms').value || '';
    const defaultBalc     = document.getElementById('unit_balconies').value || '';
    const defaultFacing   = document.getElementById('unit_facing').value || '';
    const defaultRate     = document.getElementById('unit_rate_per_unit_area').value || '';
    const defaultTotal    = (defaultArea && defaultRate)
        ? (parseFloat(defaultArea) * parseFloat(defaultRate)).toFixed(0)
        : '';

    bulkUnits = [];
    for (let i = 0; i < count; i++) {
        bulkUnits.push({
            unit_number:        prefix + String(start + i).padStart(2, '0'),
            floor_id:           defaultFloor,
            carpet_area:        defaultArea,
            bedrooms:           defaultBedrooms,
            bathrooms:          defaultBath,
            balconies:          defaultBalc,
            facing:             defaultFacing,
            rate_per_unit_area: defaultRate,
            total_price:        defaultTotal,
        });
    }
    renderBulkCards();
    updateSaveBtn();
    document.getElementById('bulkCardsSection')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
    window.generateBulkUnits = generateBulkUnits;

   function renderBulkCards() {
    const wrap = document.getElementById('bulkCardsWrap');
    if (!wrap) return;
    wrap.innerHTML = '';

    const isPlot = ['plot'].includes(currentPropertyTypeSlug);
    const isVillaOrApt = ['villa', 'apartment'].includes(currentPropertyTypeSlug);
    // plots: no floor, no bedrooms, no bathrooms, no balconies
    // villa: no bathrooms/balconies row (same as adjustFieldsForType)

    bulkUnits.forEach((u, idx) => {
        const col = document.createElement('div');
        col.className = 'col-md-6 col-lg-4';
        col.innerHTML = `
            <div class="bulk-unit-card">
                <div class="card-head">
                    <input type="text" class="form-control form-control-sm bulk-unit-number" value="${u.unit_number}">
                    ${idx === 0 ? '<button type="button" class="btn btn-sm btn-light border fs-11 copy-all-btn"><i class="feather-copy me-1"></i>Copy to all</button>' : ''}
                    <button type="button" class="btn btn-sm btn-light border text-danger remove-btn" onclick="window.removeBulkUnit(${idx})"><i class="feather-x"></i></button>
                </div>
                <div class="row g-2">
                    ${!isPlot ? `
                    <div class="col-6">
                        <span class="bulk-field-label">Floor</span>
                        <select class="form-control form-control-sm bulk-floor">${floorOptionsHTML(u.floor_id)}</select>
                    </div>` : ''}
                    <div class="${isPlot ? 'col-12' : 'col-6'}">
                        <span class="bulk-field-label">Plot Area (sq.ft)</span>
                        <input type="number" class="form-control form-control-sm bulk-area" value="${u.carpet_area}" min="0" step="0.01">
                    </div>
                    ${!isPlot ? `
                    <div class="col-4">
                        <span class="bulk-field-label">Bedrooms</span>
                        <select class="form-control form-control-sm bulk-bedrooms">${bedroomOptionsHTML(u.bedrooms)}</select>
                    </div>
                    <div class="col-4">
                        <span class="bulk-field-label">Bathrooms</span>
                        <input type="number" class="form-control form-control-sm bulk-bathrooms" value="${u.bathrooms}" min="0" max="10">
                    </div>
                    <div class="col-4">
                        <span class="bulk-field-label">Balconies</span>
                        <input type="number" class="form-control form-control-sm bulk-balconies" value="${u.balconies}" min="0" max="10">
                    </div>` : ''}
                    <div class="col-6">
                        <span class="bulk-field-label">Facing</span>
                        <select class="form-control form-control-sm bulk-facing">${facingOptionsHTML(u.facing)}</select>
                    </div>
                    <div class="col-6">
                        <span class="bulk-field-label">Rate / sq.ft (₹)</span>
                        <input type="number" class="form-control form-control-sm bulk-rate" value="${u.rate_per_unit_area}" min="0" step="0.01">
                    </div>
                    <div class="col-12">
                        <span class="bulk-field-label">Total Price (₹) <small class="text-muted">(auto-calc)</small></span>
                        <input type="number" class="form-control form-control-sm bulk-total" value="${u.total_price}" min="0">
                    </div>
                </div>
            </div>
        `;
        wrap.appendChild(col);

        const unitInput  = col.querySelector('.bulk-unit-number');
        const floorSel   = col.querySelector('.bulk-floor');
        const areaInput  = col.querySelector('.bulk-area');
        const bedSel     = col.querySelector('.bulk-bedrooms');
        const bathInput  = col.querySelector('.bulk-bathrooms');
        const balcInput  = col.querySelector('.bulk-balconies');
        const facingSel  = col.querySelector('.bulk-facing');
        const rateInput  = col.querySelector('.bulk-rate');
        const totalInput = col.querySelector('.bulk-total');
        const copyBtn    = col.querySelector('.copy-all-btn');

        unitInput.addEventListener('input', () => { u.unit_number = unitInput.value.toUpperCase(); });
        if (floorSel) floorSel.addEventListener('change', () => { u.floor_id = floorSel.value; });
        if (bedSel)   bedSel.addEventListener('change', () => { u.bedrooms = bedSel.value; });
        if (bathInput) bathInput.addEventListener('input', () => { u.bathrooms = bathInput.value; });
        if (balcInput) balcInput.addEventListener('input', () => { u.balconies = balcInput.value; });
        facingSel.addEventListener('change', () => { u.facing = facingSel.value; });

        function recalcTotal() {
            const a = parseFloat(areaInput.value) || 0;
            const r = parseFloat(rateInput.value) || 0;
            if (a > 0 && r > 0) {
                totalInput.value = (a * r).toFixed(0);
                u.total_price = totalInput.value;
            }
        }
        areaInput.addEventListener('input',  () => { u.carpet_area = areaInput.value; recalcTotal(); });
        rateInput.addEventListener('input',  () => { u.rate_per_unit_area = rateInput.value; recalcTotal(); });
        totalInput.addEventListener('input', () => { u.total_price = totalInput.value; });

        if (copyBtn) {
            copyBtn.addEventListener('click', () => {
                bulkUnits.forEach((other, i) => {
                    if (i === 0) return;
                    other.floor_id           = u.floor_id;
                    other.carpet_area        = u.carpet_area;
                    other.bedrooms           = u.bedrooms;
                    other.bathrooms          = u.bathrooms;
                    other.balconies          = u.balconies;
                    other.facing             = u.facing;
                    other.rate_per_unit_area = u.rate_per_unit_area;
                    other.total_price        = u.total_price;
                });
                renderBulkCards();
                showToast('Applied to all units.', 'success');
            });
        }
    });

    const badge = document.getElementById('bulkCountBadge');
    if (badge) badge.textContent = `${bulkUnits.length} units`;
}
    function removeBulkUnit(idx) {
        bulkUnits.splice(idx, 1);
        renderBulkCards();
        updateSaveBtn();
    }
    window.removeBulkUnit = removeBulkUnit;

    function clearBulkUnits() {
        bulkUnits = [];
        renderBulkCards();
        updateSaveBtn();
    }
    window.clearBulkUnits = clearBulkUnits;

    function updateSaveBtn() {
        const tx = document.getElementById('unitBtnText');
        if (MODE === 'create' && addMode === 'bulk' && bulkUnits.length > 0) {
            tx.textContent = `Save ${bulkUnits.length} Units`;
        } else {
            tx.textContent = MODE === 'edit' ? 'Update Unit' : 'Save';
        }
    }

    /* ════════════════════════════════════════════
       EDIT MODE — prefill cascade on page load
    ════════════════════════════════════════════ */
    async function initEditPrefill() {
        const pre = window.UNIT_EDIT_PREFILL;
        if (!pre || !pre.project_id) return;

        $('#unit_project_id').val(pre.project_id).trigger('change.select2');
        await onProjectChange(pre.project_id);

        if (pre.tower_id) {
            document.getElementById('unit_tower_id').value = pre.tower_id;
            await onTowerChange(pre.tower_id);
            document.getElementById('unit_floor_id').value = pre.floor_id || '';
            onFloorChange(pre.floor_id);
        }

        if (pre.unit_type) selectUnitType(pre.unit_type);
        updateAreaLabels();
    }

    /* ════════════════════════════════════════════
       FORM SUBMIT
    ════════════════════════════════════════════ */
    function bindSubmit() {
        const form = document.getElementById('unitForm');
        if (!form) return;

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            clearErrors();

            const typeVal = document.getElementById('unit_unit_type').value;
            if (!typeVal) { showToast('Please select a unit type.', 'error'); return; }
            if (MODE === 'create' && addMode === 'bulk' && bulkUnits.length === 0) {
                showToast('Please generate units first.', 'error'); return;
            }

            const btn = document.getElementById('submitBtn');
            const sp  = document.getElementById('unitSpinner');
            const ic  = document.getElementById('unitBtnIcon');
            const tx  = document.getElementById('unitBtnText');

            btn.disabled = true;
            sp.classList.remove('d-none');
            ic.classList.add('d-none');
            tx.textContent = 'Saving…';

            const resetBtn = (label) => {
                btn.disabled = false;
                sp.classList.add('d-none');
                ic.classList.remove('d-none');
                tx.textContent = label;
            };

            // In single mode these come from the right-column fields.
            // In bulk mode, carpet_area/bedrooms/bathrooms/balconies/facing/
            // rate/total_price/floor_id are OVERRIDDEN per row below —
            // basePayload only supplies project/tower/unit_type/status/etc.
            const basePayload = {
                project_id:          document.getElementById('unit_project_id').value,
                tower_id:            document.getElementById('unit_tower_id').value || null,
                floor_id:            document.getElementById('unit_floor_id').value || null,
                unit_type:           typeVal,
                carpet_area:         document.getElementById('unit_carpet_area').value || null,
                built_up_area:       document.getElementById('unit_built_up_area').value || null,
                super_built_up_area: document.getElementById('unit_super_built_up_area').value || null,
                area_unit:           document.getElementById('unit_area_unit').value,
                facing:              document.getElementById('unit_facing').value || null,
                bedrooms:            document.getElementById('unit_bedrooms').value || null,
                bathrooms:           document.getElementById('unit_bathrooms').value || null,
                balconies:           document.getElementById('unit_balconies').value || null,
                is_corner_unit:      document.getElementById('unit_is_corner_unit').checked ? 1 : 0,
                has_parking:         document.getElementById('unit_has_parking').checked ? 1 : 0,
                parking_count:       document.getElementById('unit_parking_count').value || 0,
                rate_per_unit_area:  document.getElementById('unit_rate_per_unit_area').value || null,
                total_price:         document.getElementById('unit_total_price').value || null,
                unit_status_id:      document.getElementById('unit_unit_status_id').value || null,
                possession_date:     document.getElementById('unit_possession_date').value || null,
                description:         document.getElementById('unit_description').value || null,
                _token:              CSRF,
            };

            const errMap = {
                project_id:  'project_idError',
                tower_id:    'tower_idError',
                floor_id:    'floor_idError',
                unit_number: 'unit_numberError',
                unit_type:   'unit_typeError',
            };

            try {
                if (MODE === 'create' && addMode === 'bulk') {
                    const results = await Promise.all(
                        bulkUnits.map(u =>
                            fetch(ROUTES.store, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                                body: JSON.stringify({
                                    ...basePayload,
                                    unit_number: u.unit_number,
                                    floor_id: u.floor_id || null,
                                    carpet_area: u.carpet_area || null,
                                    bedrooms: u.bedrooms || null,
                                    bathrooms: u.bathrooms || null,
                                    balconies: u.balconies || null,
                                    facing: u.facing || null,
                                    rate_per_unit_area: u.rate_per_unit_area || null,
                                    total_price: u.total_price || null,
                                }),
                            }).then(r => r.json())
                        )
                    );
                    const saved  = results.filter(r => r.status === 'success').length;
                    const failed = results.filter(r => r.status !== 'success').length;

                    if (failed === 0) {
                        showToast(`${saved} units created! 🎉`, 'success');
                        setTimeout(() => { window.location.href = CONFIG.redirectUrl; }, 1000);
                    } else {
                        showToast(`${saved} saved, ${failed} failed (duplicate numbers?).`, 'error');
                        resetBtn(`Save ${bulkUnits.length} Units`);
                    }
                } else {
                    const payload = {
                        ...basePayload,
                        unit_number: document.getElementById('unit_unit_number').value.toUpperCase(),
                    };
                    const url    = MODE === 'edit' ? ROUTES.update : ROUTES.store;
                    const method = MODE === 'edit' ? 'PUT' : 'POST';

                    const r = await fetch(url, {
                        method,
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                        body: JSON.stringify(payload),
                    });
                    const d = await r.json();

                    if (d.status === 'success') {
                        showToast(d.message, 'success');
                        setTimeout(() => { window.location.href = CONFIG.redirectUrl; }, 1000);
                    } else if (d.errors) {
                        Object.keys(d.errors).forEach(f =>
                            fieldErr('unit_' + f, errMap[f] || f + 'Error', d.errors[f][0])
                        );
                        showToast('Please fix the errors below.', 'error');
                        resetBtn(MODE === 'edit' ? 'Update Unit' : 'Save');
                    } else {
                        showToast(d.message || 'Something went wrong!', 'error');
                        resetBtn(MODE === 'edit' ? 'Update Unit' : 'Save');
                    }
                }
            } catch (err) {
                showToast('Network error — please try again.', 'error');
                resetBtn(MODE === 'edit' ? 'Update Unit' : 'Save');
            }
        });
    }

    /* ════════════════════════════════════════════
       INIT
    ════════════════════════════════════════════ */
    $(function () {
        $('#unit_project_id').select2({
            placeholder: '— Select project —',
            allowClear: true,
        }).on('change', function () {
            onProjectChange(this.value);
        });

        document.getElementById('unit_tower_id').addEventListener('change', function () {
            onTowerChange(this.value);
        });
        document.getElementById('unit_floor_id').addEventListener('change', function () {
            onFloorChange(this.value);
        });

        renderUnitTypeBtns('default');
        bindSubmit();

        if (MODE === 'edit') {
            initEditPrefill();
        }
    });
})();