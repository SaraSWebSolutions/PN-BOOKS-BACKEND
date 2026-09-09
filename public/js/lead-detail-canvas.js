/**
 * lead-detail-canvas.js
 *
 *   PUT    /leads/{id}           — update (used for quick reassign / follow-up date)
 */

(function () {
    'use strict';

    /* ─── State ─────────────────────────────────────────────── */
    let _currentLeadId   = null;
    let _currentLeadData = null;   // raw data from server

    const CSRF = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    /* ─── Helper: close offcanvas + reload page ─────────────── */
    function closeAndReload(delay = 1200) {
        const canvas = document.getElementById('leadDetailCanvas');
        bootstrap.Offcanvas.getInstance(canvas)?.hide();
        setTimeout(() => location.reload(), delay);
    }

    /* ─── Open canvas ────────────────────────────────────────── */
    window.openDetail = function (leadId, seedData) {
        _currentLeadId = leadId;

        if (seedData) {
            renderOverview(seedData);
        } else {
            showSkeleton();
        }

        // Reset to Overview tab
        dcSwitchTab('overview');

        const canvas = document.getElementById('leadDetailCanvas');
        bootstrap.Offcanvas.getOrCreateInstance(canvas).show();

        // Always fetch fresh data from server
        fetchLeadDetail(leadId);
    };

    /* ─── Fetch from server ──────────────────────────────────── */
    async function fetchLeadDetail(id) {
        try {
            const r = await fetch(`/leads/${id}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF() }
            });
            if (!r.ok) throw new Error('HTTP ' + r.status);
            const d = await r.json();
            _currentLeadData = d.lead ?? d;
            renderOverview(_currentLeadData);
            renderTimeline(_currentLeadData.followups ?? []);
            document.getElementById('dcFollowupCount').textContent =
                (_currentLeadData.followups ?? []).length;
        } catch (err) {
            console.error('Lead detail fetch failed', err);
        }
    }

    /* ─── Render: Overview ───────────────────────────────────── */
function renderOverview(d) {
    // Header
    const initials = (d.name ?? '?').split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase();
    el('dcAvatar').textContent       = initials;
    el('dcLeadNumber').textContent   = d.lead_number ?? '—';
    el('dcName').textContent         = d.name ?? '—';

    const sc = statusConfig(d.status);
    el('dcStatusBadge').textContent  = sc.label;

    const pc = priorityConfig(d.priority);
    el('dcPriorityBadge').textContent = pc.label;

    el('dcCreatedAt').textContent    = d.created_at
        ? fmtDate(d.created_at)
        : '—';

    // Contact
    const phone = d.phone ?? '—';
    const phoneEl = el('dcPhone');
    phoneEl.textContent = phone;
    phoneEl.href = phone !== '—' ? `tel:${phone}` : '#';

    const email = d.email ?? null;
    const emailEl = el('dcEmail');
    emailEl.textContent = email ?? '—';
    emailEl.href = email ? `mailto:${email}` : '#';

    el('dcAssigned').textContent     = d.assigned_to_name ?? d.assignedTo?.name ?? '—';
    el('dcFollowupDate').textContent = d.follow_up_date
        ? fmtDate(d.follow_up_date, false)
        : '—';

    // Lead details
    el('dcProject').textContent  = d.project_name  ?? d.project?.project_name  ?? '—';
    el('dcSource').textContent   = d.source_name   ?? d.source?.name           ?? '—';
    el('dcUnitType').textContent = d.unit_type_interest ?? '—';

    const min = d.budget_min ? '₹' + fmt(d.budget_min) : null;
    const max = d.budget_max ? '₹' + fmt(d.budget_max) : null;
    el('dcBudget').textContent = min || max
        ? [min, max].filter(Boolean).join(' – ')
        : '—';

    el('dcNotes').textContent = d.notes ?? '—';

    // Full detail link
    el('dcViewFull').href = `/leads/${d.id ?? _currentLeadId}`;

    // Pre-fill Update tab
    el('dc_assign_to').value     = d.assigned_to ?? '';
    el('dc_followup_date').value = (d.follow_up_date ?? '').substring(0, 10);

    // Highlight current status button
    document.querySelectorAll('.dc-status-btn').forEach(btn => {
        btn.classList.toggle('active-status', btn.dataset.status === d.status);
    });

    // ── Sales handover lock logic ──
    const isHandedToSales = !!d.sales_assigned_to;
    const isSalesOwner    = isHandedToSales && Number(d.sales_assigned_to) === Number(window.CURRENT_USER_ID);
    const isUpdateLocked  = isHandedToSales && !window.IS_MANAGEMENT && !isSalesOwner;

    // Store for renderTimeline to use
    window._dcIsUpdateLocked = isUpdateLocked;

    const updateTabLink   = document.querySelector('#dcTabs [data-tab="update"]');
    const banner          = el('dcSalesLockedBanner');
    const updateStatusBtn = el('dcUpdateStatusBtn');
    const logFollowupBtn  = el('dcLogFollowupBtn');
    const formWrap        = el('dcFollowupFormWrap');

    if (isUpdateLocked) {
        // Lock Update tab + button
        if (updateTabLink?.parentElement) updateTabLink.parentElement.style.display = 'none';
        if (updateStatusBtn) updateStatusBtn.style.display = 'none';

        // Show banner
        banner.style.display = 'block';
        el('dcSalesPersonName').textContent   = d.sales_assigned_to_name ?? '—';
        el('dcSalesAssignedDate').textContent = d.sales_assigned_at
            ? ' on ' + fmtDate(d.sales_assigned_at, false)
            : '';

        // Hide follow-up FORM + Log Follow-up button
        if (formWrap)       formWrap.style.display       = 'none';
        if (logFollowupBtn) logFollowupBtn.style.display = 'none';

        // Kick back to Overview if currently on Update tab
        if (document.querySelector('#dcTabs .active')?.dataset?.tab === 'update') {
            dcSwitchTab('overview');
        }
    } else {
        if (updateTabLink?.parentElement) updateTabLink.parentElement.style.display = '';
        if (updateStatusBtn) updateStatusBtn.style.display = '';
        banner.style.display = 'none';

        // Show follow-up form + button for non-locked users
        if (formWrap)       formWrap.style.display       = '';
        if (logFollowupBtn) logFollowupBtn.style.display = '';
    }

    // Follow-ups TAB always visible
    const followupsTabLink = document.querySelector('#dcTabs [data-tab="followups"]');
    if (followupsTabLink?.parentElement) followupsTabLink.parentElement.style.display = '';
}
    /* ─── Render: Follow-up Timeline ────────────────────────── */
    function renderTimeline(followups) {
    const container = el('dcTimeline');

    // Telecaller locked = sees only their own follow-ups
    if (window._dcIsUpdateLocked && !window.IS_MANAGEMENT) {
        followups = followups.filter(fu => fu.user_id === window.CURRENT_USER_ID);
    }

    if (!followups.length) {
            container.innerHTML = `
                <div class="text-center py-4 text-muted fs-13">
                    <i class="feather-activity fs-24 d-block mb-2 opacity-50"></i>
                    No follow-ups yet.
                </div>`;
            return;
        }

        const typeMap = {
            call:       { ico: '📞', bg: 'rgba(59,130,246,.1)',  color: '#3b82f6' },
            whatsapp:   { ico: '💬', bg: 'rgba(16,185,129,.1)',  color: '#10b981' },
            email:      { ico: '📧', bg: 'rgba(99,102,241,.1)', color: '#6366f1' },
            meeting:    { ico: '🤝', bg: 'rgba(245,158,11,.1)',  color: '#f59e0b' },
            site_visit: { ico: '🏗️', bg: 'rgba(239,68,68,.1)',   color: '#ef4444' },
            note:       { ico: '📝', bg: 'rgba(107,114,128,.1)', color: '#6b7280' },
        };

        const outcomeLabel = {
            no_answer:    '📵 No Answer',
            callback:     '🔁 Callback',
            interested:   '⭐ Interested',
            not_interested: '👎 Not Interested',
            follow_up:    '📅 Follow-up',
            converted:    '🎉 Converted',
        };

        container.innerHTML = followups
            .slice()
            .reverse()
            .map(fu => {
                const tc = typeMap[fu.type] ?? typeMap.note;
                const outcomeHtml = fu.outcome
                    ? `<span class="badge fs-10 fw-semibold ms-2"
                              style="background:rgba(99,102,241,.1);color:#6366f1;border:1px solid rgba(99,102,241,.2);">
                           ${outcomeLabel[fu.outcome] ?? fu.outcome}
                       </span>`
                    : '';
                const nextHtml = fu.next_follow_up
                    ? `<div class="fs-11 text-muted mt-1">
                           <i class="feather-calendar me-1" style="font-size:10px;"></i>
                           Next: <strong>${fmtDate(fu.next_follow_up, false)}</strong>
                       </div>`
                    : '';
                return `
                <div class="dc-timeline-item">
                    <div class="dc-timeline-dot" style="background:${tc.bg};color:${tc.color};">
                        ${tc.ico}
                    </div>
                   <div class="dc-timeline-body" style="position:relative;">
    
   
    <div style="position:absolute;top:8px;right:10px;">
       ${roleBadge(fu)}
    </div>

    <div class="d-flex align-items-center gap-1 mb-1">
        <span class="fw-semibold fs-12 text-dark text-capitalize">${fu.type.replace('_',' ')}</span>
        ${outcomeHtml}
    </div>
    <p class="text-muted fs-12 mb-1" style="white-space:pre-wrap;">${escHtml(fu.notes)}</p>
    ${nextHtml}
    <div class="dc-timeline-meta mt-1 d-flex align-items-center gap-2">
        <span style="font-weight:700;color:#1e293b;font-size:12px;">${fu.user_name ?? 'Staff'}</span>
        <span style="color:#9ca3af;font-size:11px;">· ${fmtDate(fu.created_at)}</span>
    </div>
</div>
                </div>`;
            }).join('');
    }

    /* ─── Tab switching ──────────────────────────────────────── */
    window.dcSwitchTab = function (tab) {
        document.querySelectorAll('.dc-tab-pane').forEach(p => p.style.display = 'none');
        document.querySelectorAll('#dcTabs .nav-link').forEach(a => a.classList.remove('active'));

        const pane = document.getElementById('dcTab' + cap(tab));
        const link = document.querySelector(`#dcTabs [data-tab="${tab}"]`);
        if (pane) pane.style.display = '';
        if (link) link.classList.add('active');
    };

    document.querySelectorAll('#dcTabs .nav-link').forEach(a => {
        a.addEventListener('click', e => {
            e.preventDefault();
            dcSwitchTab(a.dataset.tab);
        });
    });

    /* ─── Submit Follow-up ───────────────────────────────────── */
    window.submitFollowup = async function () {
        if (!_currentLeadId) return;

        const notes = el('fu_notes').value.trim();
        el('fu_notesError').textContent = '';
        el('fu_notesError').classList.remove('show');

        if (!notes) {
            el('fu_notesError').textContent = 'Notes are required.';
            el('fu_notesError').classList.add('show');
            return;
        }

        const btn = el('fuSubmitBtn');
        const sp  = el('fuSpinner');
        const ic  = el('fuBtnIcon');

        btn.disabled = true;
        sp.classList.remove('d-none');
        ic.classList.add('d-none');

        const payload = {
            type:           el('fu_type').value,
            notes,
            outcome:        el('fu_outcome').value || null,
            next_follow_up: el('fu_next').value    || null,
        };

        try {
            const r = await fetch(`/leads/${_currentLeadId}/followups`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF(),
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            });
            const d = await r.json();
            if (d.status === 'success') {
                showToast(d.message, 'success');
                closeAndReload();
            } else {
                showToast(d.message ?? 'Error saving follow-up.', 'error');
            }
        } catch {
            showToast('Network error — try again.', 'error');
        } finally {
            btn.disabled = false;
            sp.classList.add('d-none');
            ic.classList.remove('d-none');
        }
    };

    /* ─── Change Status ──────────────────────────────────────── */
    document.querySelectorAll('.dc-status-btn').forEach(btn => {
        btn.addEventListener('click', async function () {
            if (!_currentLeadId) return;
            const status = this.dataset.status;

            document.querySelectorAll('.dc-status-btn').forEach(b => b.classList.remove('active-status'));
            this.classList.add('active-status');

            try {
                const r = await fetch(`/leads/${_currentLeadId}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF(),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ status }),
                });
                const d = await r.json();
                if (d.status === 'success') {
                    showToast(d.message, 'success');
                    closeAndReload();
                } else {
                    showToast(d.message ?? 'Status update failed.', 'error');
                }
            } catch {
                showToast('Network error.', 'error');
            }
        });
    });

    /* ─── Reassign Lead ──────────────────────────────────────── */
    window.dcReassign = async function () {
        if (!_currentLeadId) return;
        const assignedTo = el('dc_assign_to').value || null;

        try {
            const r = await fetch(`/leads/${_currentLeadId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF(),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    // Send existing data + new assigned_to
                    ...(pickLeadFields()),
                    assigned_to: assignedTo,
                }),
            });
            const d = await r.json();
            if (d.status === 'success') {
                showToast('Lead reassigned.', 'success');
                closeAndReload();
            } else {
                showToast(d.message ?? 'Reassign failed.', 'error');
            }
        } catch {
            showToast('Network error.', 'error');
        }
    };

    /* ─── Set Follow-up Date ─────────────────────────────────── */
    window.dcSetFollowupDate = async function () {
        if (!_currentLeadId) return;
        const date = el('dc_followup_date').value;
        if (!date) return;

        try {
            const r = await fetch(`/leads/${_currentLeadId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF(),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    ...(pickLeadFields()),
                    follow_up_date: date,
                }),
            });
            const d = await r.json();
            if (d.status === 'success') {
                showToast('Follow-up date updated.', 'success');
                closeAndReload();
            } else {
                showToast(d.message ?? 'Update failed.', 'error');
            }
        } catch {
            showToast('Network error.', 'error');
        }
    };

    /* ─── Helpers ────────────────────────────────────────────── */

    /** Pick current cached lead fields to send on partial PUTs */
    function pickLeadFields() {
        if (!_currentLeadData) return {};
        const d = _currentLeadData;
        return {
            name:               d.name,
            phone:              d.phone,
            email:              d.email              ?? null,
            project_id:         d.project_id         ?? null,
            lead_source_id:     d.lead_source_id     ?? null,
            assigned_to:        d.assigned_to        ?? null,
            status:             d.status             ?? 'new',
            priority:           d.priority           ?? 'medium',
            unit_type_interest: d.unit_type_interest ?? null,
            budget_min:         d.budget_min         ?? null,
            budget_max:         d.budget_max         ?? null,
            notes:              d.notes              ?? null,
            follow_up_date:     d.follow_up_date     ?? null,
        };
    }

    function showSkeleton() {
        el('dcName').textContent       = 'Loading…';
        el('dcLeadNumber').textContent = '…';
        el('dcStatusBadge').textContent = '…';
        el('dcPriorityBadge').textContent = '…';
    }

    /** Update a table row after status change without page reload */
    function updateTableRow(id, changes) {
        const row = document.getElementById(`lead-row-${id}`);
        if (!row) return;
        if (changes.status) row.dataset.status = changes.status;
        // filterAndPaginate is defined in the main index script
        if (typeof filterAndPaginate === 'function') filterAndPaginate();
    }

    function statusConfig(s) {
        const map = {
            new:                  { label: '🔵 New',                   color: '#3b82f6', bg: 'rgba(59,130,246,.1)'  },
            contacted:            { label: '📞 Contacted',             color: '#8b5cf6', bg: 'rgba(139,92,246,.1)'  },
            interested:           { label: '⭐ Interested',             color: '#f59e0b', bg: 'rgba(245,158,11,.1)'  },
            site_visit_scheduled: { label: '📅 Site Visit Scheduled',  color: '#06b6d4', bg: 'rgba(6,182,212,.1)'   },
            site_visit_done:      { label: '✅ Site Visit Done',        color: '#10b981', bg: 'rgba(16,185,129,.1)'  },
            negotiation:          { label: '🤝 Negotiation',           color: '#f97316', bg: 'rgba(249,115,22,.1)'  },
            booked:               { label: '🎉 Booked',                color: '#059669', bg: 'rgba(5,150,105,.1)'   },
            lost:                 { label: '❌ Lost',                   color: '#ef4444', bg: 'rgba(239,68,68,.1)'   },
        };
        return map[s] ?? { label: s ?? '—', color: '#6b7280', bg: 'rgba(107,114,128,.1)' };
    }


  

    function priorityConfig(p) {
        return {
            high:   { label: '🔴 High'   },
            medium: { label: '🟡 Medium' },
            low:    { label: '🟢 Low'    },
        }[p] ?? { label: p ?? '—' };
    }

    function fmtDate(str, withTime = true) {
        if (!str) return '—';
        const d = new Date(str);
        if (isNaN(d)) return str;
        const opts = { day: '2-digit', month: 'short', year: 'numeric' };
        if (withTime) { opts.hour = '2-digit'; opts.minute = '2-digit'; }
        return d.toLocaleString('en-IN', opts);
    }

    function fmt(n) {
        return Number(n).toLocaleString('en-IN');
    }

    function escHtml(str) {
        return (str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function cap(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function el(id) {
        return document.getElementById(id);
    }

    /* ─── Role color (same palette as index page) ────────────── */
const ROLE_COLORS = [
    { bg: '#ede9fe', color: '#7c3aed' },
    { bg: '#dcfce7', color: '#16a34a' },
    { bg: '#fef3c7', color: '#d97706' },
    { bg: '#fee2e2', color: '#dc2626' },
    { bg: '#e0f2fe', color: '#0284c7' },
    { bg: '#fce7f3', color: '#db2777' },
    { bg: '#f0fdf4', color: '#15803d' },
    { bg: '#fdf4ff', color: '#9333ea' },
    { bg: '#fff7ed', color: '#ea580c' },
    { bg: '#f0f9ff', color: '#0369a1' },
    { bg: '#fef9c3', color: '#ca8a04' },
    { bg: '#f1f5f9', color: '#475569' },
];

function getRoleColor(role) {
    if (!role) return ROLE_COLORS[ROLE_COLORS.length - 1];
    let hash = 0;
    for (let i = 0; i < role.length; i++) {
        hash = role.charCodeAt(i) + ((hash << 5) - hash);
    }
    return ROLE_COLORS[Math.abs(hash) % (ROLE_COLORS.length - 1)];
}

function roleBadge(fu) {
    if (!fu.user_role) return '';
    const bg    = fu.role_bg    ?? '#f1f5f9';
    const color = fu.role_color ?? '#475569';
    const label = fu.user_role.charAt(0).toUpperCase() + fu.user_role.slice(1);
    return `<span style="
        background:${bg};color:${color};
        font-size:10px;font-weight:600;
        padding:2px 8px;border-radius:20px;
        border:1px solid ${color}30;
    ">${label}</span>`;
}

    /* showToast is defined globally in the index page — fallback just in case */
    if (typeof window.showToast === 'undefined') {
        window.showToast = (msg, type) => {
            console.log(`[${type}]`, msg);
        };
    }

})();