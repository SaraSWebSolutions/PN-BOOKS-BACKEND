{{-- resources/views/support-tickets/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Help & Support Tickets')

@section('content')

<div class="ajax-toast" id="ajaxToast">
    <span id="toastIcon"></span>
    <span id="toastMsg"></span>
    <button class="toast-close" onclick="hideToast()">✕</button>
</div>

{{-- ══ OFFCANVAS — View / Reply ══ --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="ticketCanvas" style="width:460px;">
    <div class="offcanvas-header border-bottom py-3">
        <h5 class="offcanvas-title fw-bold mb-0">Ticket Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <form id="replyForm" novalidate>
            <input type="hidden" id="ticketId">
            <div class="mb-2"><strong>From:</strong> <span id="t_name"></span> (<span id="t_email"></span>)</div>
            <div class="mb-2"><strong>Subject:</strong> <span id="t_subject"></span></div>
            <div class="mb-3"><strong>Message:</strong><p id="t_message" class="text-muted"></p></div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                <select id="t_status" class="form-control">
                    <option value="open">Open</option>
                    <option value="in_progress">In Progress</option>
                    <option value="resolved">Resolved</option>
                </select>
                <div class="field-error" id="statusError"></div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Admin Reply <span class="text-danger">*</span></label>
                <textarea id="t_admin_reply" class="form-control" rows="4"></textarea>
                <div class="field-error" id="admin_replyError"></div>
            </div>

            <button type="submit" class="btn btn-primary w-100" id="replyBtn">
                <span class="spinner-border spinner-border-sm me-2 d-none" id="replySpinner"></span>
                <span id="replyBtnText">Save Reply</span>
            </button>
        </form>
    </div>
</div>

<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title"><h5 class="m-b-10">Help & Support Tickets</h5></div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">Website</li>
            <li class="breadcrumb-item">Support Tickets</li>
        </ul>
    </div>
</div>

<div class="main-content">
    <div class="row g-3 mb-4">
        @foreach([
            ['label'=>'Total','value'=>$stats['total'],'icon'=>'feather-inbox','bg'=>'rgba(99,102,241,.1)','color'=>'#6366f1'],
            ['label'=>'Open','value'=>$stats['open'],'icon'=>'feather-alert-circle','bg'=>'rgba(239,68,68,.1)','color'=>'#ef4444'],
            ['label'=>'In Progress','value'=>$stats['in_progress'],'icon'=>'feather-clock','bg'=>'rgba(234,179,8,.1)','color'=>'#eab308'],
            ['label'=>'Resolved','value'=>$stats['resolved'],'icon'=>'feather-check-circle','bg'=>'rgba(16,185,129,.1)','color'=>'#10b981'],
        ] as $s)
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3">
                    <div class="hstack justify-content-between">
                        <div>
                            <div class="text-muted fs-12 mb-1">{{ $s['label'] }}</div>
                            <div class="fs-22 fw-bold">{{ $s['value'] }}</div>
                        </div>
                        <div style="width:42px;height:42px;border-radius:10px;background:{{ $s['bg'] }};display:flex;align-items:center;justify-content:center;color:{{ $s['color'] }};">
                            <i class="{{ $s['icon'] }}"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="card stretch stretch-full">
        <div class="card-header py-3"><span class="fw-semibold">All Tickets</span></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th><th>Name</th><th>Email</th><th>Subject</th><th>Status</th><th>Date</th><th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tickets as $ticket)
                        <tr id="ticket-row-{{ $ticket->id }}"
                            data-id="{{ $ticket->id }}"
                            data-name="{{ $ticket->name }}"
                            data-email="{{ $ticket->email }}"
                            data-subject="{{ $ticket->subject }}"
                            data-message="{{ $ticket->message }}"
                            data-status="{{ $ticket->status }}"
                            data-admin-reply="{{ $ticket->admin_reply }}">
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $ticket->name }}</td>
                            <td>{{ $ticket->email }}</td>
                            <td>{{ $ticket->subject }}</td>
                            <td>
                                <span class="badge bg-{{ $ticket->status === 'open' ? 'danger' : ($ticket->status === 'resolved' ? 'success' : 'warning') }}">
                                    {{ ucfirst(str_replace('_',' ', $ticket->status)) }}
                                </span>
                            </td>
                            <td>{{ $ticket->created_at->format('d M Y') }}</td>
                            <td class="text-end">
                                <a href="javascript:void(0);" title="View / Reply" onclick="openTicket({{ $ticket->id }})">
                                    <i class="feather feather-eye"></i>
                                </a>
                                <a href="javascript:void(0);" class="text-danger ms-2" title="Delete" onclick="deleteTicket({{ $ticket->id }})">
                                    <i class="feather feather-trash-2"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr id="noDataRow">
                            <td colspan="7" class="text-center py-5 text-muted">No tickets yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.ajax-toast{position:fixed;top:20px;right:20px;z-index:9999;min-width:300px;max-width:400px;border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:500;box-shadow:0 8px 32px rgba(0,0,0,.15);transform:translateX(120%);transition:transform .4s cubic-bezier(.34,1.56,.64,1);}
.ajax-toast.show{transform:translateX(0);}
.ajax-toast.toast-success{background:#d1fae5;border-left:4px solid #10b981;color:#065f46;}
.ajax-toast.toast-error{background:#fee2e2;border-left:4px solid #ef4444;color:#991b1b;}
.ajax-toast .toast-close{margin-left:auto;background:none;border:none;cursor:pointer;font-size:16px;color:inherit;opacity:.6;}
.field-error{font-size:12px;color:#ef4444;margin-top:4px;display:none;}
.field-error.show{display:block;}
.form-control.input-error{border-color:#ef4444!important;}
</style>
@endpush

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const replyUrlTemplate   = "{{ route('support-tickets.reply', ['support_ticket' => '__ID__']) }}";
const destroyUrlTemplate = "{{ route('support-tickets.destroy', ['support_ticket' => '__ID__']) }}";

let _tt;
function showToast(msg, type = 'success') {
    const t = document.getElementById('ajaxToast');
    document.getElementById('toastMsg').textContent  = msg;
    document.getElementById('toastIcon').textContent = type === 'success' ? '✅' : '❌';
    t.className = 'ajax-toast ' + (type === 'success' ? 'toast-success' : 'toast-error');
    t.classList.add('show');
    clearTimeout(_tt);
    _tt = setTimeout(() => t.classList.remove('show'), 4000);
}
function hideToast() { document.getElementById('ajaxToast').classList.remove('show'); }

function clearErrors() {
    document.querySelectorAll('.field-error').forEach(e => { e.textContent = ''; e.classList.remove('show'); });
    document.querySelectorAll('.form-control').forEach(e => e.classList.remove('input-error'));
}
function fieldErr(fId, eId, msg) {
    document.getElementById(fId)?.classList.add('input-error');
    const e = document.getElementById(eId);
    if (e) { e.textContent = msg; e.classList.add('show'); }
}

function openTicket(id) {
    clearErrors();
    const d = document.getElementById(`ticket-row-${id}`).dataset;
    document.getElementById('ticketId').value = d.id;
    document.getElementById('t_name').textContent = d.name;
    document.getElementById('t_email').textContent = d.email;
    document.getElementById('t_subject').textContent = d.subject;
    document.getElementById('t_message').textContent = d.message;
    document.getElementById('t_status').value = d.status;
    document.getElementById('t_admin_reply').value = d.adminReply || '';
    new bootstrap.Offcanvas(document.getElementById('ticketCanvas')).show();
}

document.getElementById('replyForm').addEventListener('submit', function (e) {
    e.preventDefault();
    clearErrors();

    const id  = document.getElementById('ticketId').value;
    const url = replyUrlTemplate.replace('__ID__', id);

    const btn = document.getElementById('replyBtn');
    const sp  = document.getElementById('replySpinner');
    const tx  = document.getElementById('replyBtnText');

    btn.disabled = true;
    sp.classList.remove('d-none');
    tx.textContent = 'Saving…';

    fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({
            _method: 'PATCH',
            status: document.getElementById('t_status').value,
            admin_reply: document.getElementById('t_admin_reply').value,
        }),
    })
    .then(r => r.json().then(d => ({ ok: r.ok, d })))
    .then(({ d }) => {
        btn.disabled = false;
        sp.classList.add('d-none');
        tx.textContent = 'Save Reply';

        if (d.status === 'success') {
            showToast(d.message, 'success');
            bootstrap.Offcanvas.getInstance(document.getElementById('ticketCanvas'))?.hide();
            setTimeout(() => location.reload(), 1000);
        } else if (d.errors) {
            // maps validation keys (status, admin_reply) to the matching field + error div
            Object.keys(d.errors).forEach(f => fieldErr('t_' + f, f + 'Error', d.errors[f][0]));
            showToast('Please fix the errors below.', 'error');
        } else {
            showToast(d.message || 'Something went wrong!', 'error');
        }
    })
    .catch(() => {
        btn.disabled = false;
        sp.classList.add('d-none');
        tx.textContent = 'Save Reply';
        showToast('Network error — try again.', 'error');
    });
});

function deleteTicket(id) {
    if (!confirm('Delete this ticket?')) return;
    fetch(destroyUrlTemplate.replace('__ID__', id), {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(d => {
        if (d.status === 'success') {
            document.getElementById(`ticket-row-${id}`)?.remove();
            showToast(d.message, 'success');
        } else {
            showToast(d.message, 'error');
        }
    })
    .catch(() => showToast('Something went wrong!', 'error'));
}
</script>
@endpush