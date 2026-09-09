@extends('layouts.app')
@section('title', 'Contacts')

@section('content')

<div class="ajax-toast" id="ajaxToast">
    <span class="toast-icon" id="toastIcon"></span>
    <span id="toastMessage"></span>
    <button class="toast-close" onclick="hideToast()">✕</button>
</div>

<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Contacts</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">Contacts</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto">
        <div class="d-flex align-items-center gap-2">

            {{-- Filter --}}
            <div class="dropdown">
                <a class="btn btn-icon btn-light-brand" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                    <i class="feather-filter"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    <a href="javascript:void(0);" class="dropdown-item filter-status" data-status="all">
                        <span class="wd-7 ht-7 bg-secondary rounded-circle d-inline-block me-3"></span> All Status
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="javascript:void(0);" class="dropdown-item filter-status" data-status="active">
                        <span class="wd-7 ht-7 bg-success rounded-circle d-inline-block me-3"></span> Active
                    </a>
                    <a href="javascript:void(0);" class="dropdown-item filter-status" data-status="inactive">
                        <span class="wd-7 ht-7 bg-danger rounded-circle d-inline-block me-3"></span> Inactive
                    </a>
                    <div class="dropdown-divider"></div>
                    {{-- Type filters --}}
                    <a href="javascript:void(0);" class="dropdown-item filter-type" data-type="all">
                        <span class="wd-7 ht-7 bg-secondary rounded-circle d-inline-block me-3"></span> All Types
                    </a>
                    <a href="javascript:void(0);" class="dropdown-item filter-type" data-type="customer">
                        <span class="wd-7 ht-7 bg-primary rounded-circle d-inline-block me-3"></span> Customer
                    </a>
                    <a href="javascript:void(0);" class="dropdown-item filter-type" data-type="lead">
                        <span class="wd-7 ht-7 bg-warning rounded-circle d-inline-block me-3"></span> Lead
                    </a>
                    <a href="javascript:void(0);" class="dropdown-item filter-type" data-type="both">
                        <span class="wd-7 ht-7 bg-info rounded-circle d-inline-block me-3"></span> Both
                    </a>
                    <a href="javascript:void(0);" class="dropdown-item filter-type" data-type="supplier">
                        <span class="wd-7 ht-7 bg-purple rounded-circle d-inline-block me-3"
                              style="background:#7c3aed!important;"></span> Supplier
                    </a>
                </div>
            </div>

            {{-- Export --}}
            <div class="dropdown">
                <a class="btn btn-icon btn-light-brand" data-bs-toggle="dropdown">
                    <i class="feather-paperclip"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    <a href="javascript:void(0);" class="dropdown-item" onclick="exportCSV()">
                        <i class="bi bi-filetype-csv me-3"></i> CSV
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="javascript:void(0);" class="dropdown-item" onclick="window.print()">
                        <i class="bi bi-printer me-3"></i> Print
                    </a>
                </div>
            </div>

            {{-- ══ IMPORT (Offcanvas trigger) ══ --}}
            <a href="javascript:void(0);" class="btn btn-light-brand" data-bs-toggle="offcanvas" data-bs-target="#importOffcanvas">
                <i class="feather-upload me-2"></i> Import
            </a>

            @can('create contacts')
            <a href="{{ route('contacts.create') }}" class="btn btn-primary">
                <i class="feather-user-plus me-2"></i> Add Contact
            </a>
            @endcan
        </div>
    </div>
</div>

<div class="main-content">
    <div class="row">
        <div class="col-lg-12">
            <div class="card stretch stretch-full">

                <div class="card-header d-flex align-items-center justify-content-between gap-3 py-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted fs-12">Show</span>
                        <select id="perPageSelect" class="form-select form-select-sm" style="width:70px;">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span class="text-muted fs-12">entries</span>
                    </div>
                    <div class="input-group" style="max-width:250px;">
                        <span class="input-group-text"><i class="feather-search"></i></span>
                        <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search contacts...">
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th class="wd-30">
                                        <div class="custom-control custom-checkbox ms-1">
                                            <input type="checkbox" class="custom-control-input" id="checkAll">
                                            <label class="custom-control-label" for="checkAll"></label>
                                        </div>
                                    </th>
                                    <th>Contact</th>
                                    <th>Code</th>
                                    <th>Phone / WhatsApp</th>
                                    <th>Type</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="contactTableBody">
                                @forelse($contacts as $contact)
                                <tr class="contact-row"
                                    id="contact-row-{{ $contact->id }}"
                                    data-status="{{ $contact->status }}"
                                    data-type="{{ $contact->type }}">
                                    <td>
                                        <div class="custom-control custom-checkbox ms-1">
                                            <input type="checkbox" class="custom-control-input checkbox" id="check_{{ $contact->id }}">
                                            <label class="custom-control-label" for="check_{{ $contact->id }}"></label>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="{{ route('contacts.show', $contact) }}" class="hstack gap-3">
                                            <div class="avatar-text d-flex align-items-center justify-content-center text-white"
                                                 style="width:38px;height:38px;border-radius:50%;font-weight:bold;font-size:16px;flex-shrink:0;
                                                        background:{{ $contact->type === 'supplier' ? '#7c3aed' : '#3b82f6' }};">
                                                {{ strtoupper(substr($contact->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <span class="fw-semibold d-block">{{ $contact->name }}</span>
                                                <span class="fs-12 text-muted">{{ $contact->email ?? '—' }}</span>
                                            </div>
                                        </a>
                                    </td>
                                    <td><span class="badge bg-soft-info text-info">{{ $contact->code ?? '—' }}</span></td>
                                    <td>
                                        <span class="d-block">{{ $contact->phone ?? '—' }}</span>
                                        @if($contact->whatsapp)
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $contact->whatsapp) }}"
                                           target="_blank" class="fs-12 text-success">
                                            <i class="bi bi-whatsapp me-1"></i>{{ $contact->whatsapp }}
                                        </a>
                                        @endif
                                    </td>
                                    <td>
                                        @if($contact->type === 'customer')
                                            <span class="badge bg-soft-primary text-primary">Customer</span>
                                        @elseif($contact->type === 'lead')
                                            <span class="badge bg-soft-warning text-warning">Lead</span>
                                        @elseif($contact->type === 'supplier')
                                            <span class="badge" style="background:rgba(124,58,237,.15);color:#7c3aed;">Supplier</span>
                                        @else
                                            <span class="badge bg-soft-info text-info">Both</span>
                                        @endif
                                    </td>
                                    <td>{{ $contact->businessLocation->name ?? '—' }}</td>
                                    <td>
                                        @if($contact->status === 'active')
                                            <span class="badge bg-soft-success text-success">Active</span>
                                        @else
                                            <span class="badge bg-soft-danger text-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="hstack gap-2 justify-content-end">
                                            <a href="{{ route('contacts.show', $contact) }}" class="avatar-text avatar-md" title="View">
                                                <i class="feather feather-eye"></i>
                                            </a>

                                            @if(in_array($contact->type, ['customer', 'both']))
                                            <a href="{{ route('contacts.customer-ledger', $contact) }}"
                                               class="avatar-text avatar-md" title="Ledger">
                                                <i class="feather feather-book-open"></i>
                                            </a>
                                            @endif
                                            @if($contact->type === 'supplier')
                                            <a href="{{ route('contacts.supplier-ledger', $contact) }}"
                                               class="avatar-text avatar-md" title="Supplier Ledger">
                                                <i class="feather feather-book-open"></i>
                                            </a>
                                            @endif

                                            @can('edit contacts')
                                            <a href="{{ route('contacts.edit', $contact) }}" class="avatar-text avatar-md" title="Edit">
                                                <i class="feather feather-edit-3"></i>
                                            </a>
                                            @endcan
                                            @can('delete contacts')
                                            <a href="javascript:void(0);" class="avatar-text avatar-md text-danger"
                                               onclick="deleteContact({{ $contact->id }})" title="Delete">
                                                <i class="feather feather-trash-2"></i>
                                            </a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr id="noDataRow">
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="feather-users fs-30 d-block mb-2"></i>
                                        No contacts found.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer d-flex align-items-center justify-content-between py-3">
                    <div class="text-muted fs-12">
                        Showing <span id="showFrom">1</span> to <span id="showTo">10</span>
                        of <span id="totalRows">{{ $contacts->count() }}</span> entries
                    </div>
                    <ul class="pagination pagination-sm mb-0" id="paginationLinks"></ul>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- ══ IMPORT CONTACTS OFFCANVAS (Right side) ══ --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="importOffcanvas" aria-labelledby="importOffcanvasLabel" style="width:420px;">
    <div class="offcanvas-header border-bottom">
        <h6 class="offcanvas-title fw-bold" id="importOffcanvasLabel">
            <i class="feather-upload me-2"></i>Bulk Import Contacts
        </h6>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">

        <div class="alert alert-light-info d-flex align-items-start gap-2 mb-3" style="border-radius:10px;">
            <i class="feather-info mt-1"></i>
            <div class="fs-13">
                Download the sample template, fill in your contacts, then upload it below.
                Supported format: <strong>.csv</strong> (max 5MB). Open the downloaded file in Excel or Google Sheets, fill it in, and save/export as CSV again.
            </div>
        </div>

        <a href="{{ route('contacts.import.sample') }}" class="btn btn-sm btn-light-success mb-3">
            <i class="feather-download me-2"></i> Download Sample Template
        </a>

        <form id="importForm" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label class="form-label fs-13 fw-semibold">Select CSV file to import</label>
                <input type="file" name="import_file" id="importFileInput" class="form-control"
                       accept=".csv,.txt" required>
            </div>

            <div id="importProgress" style="display:none;" class="mb-3">
                <div class="progress" style="height:6px;border-radius:6px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width:100%;"></div>
                </div>
                <div class="fs-12 text-muted mt-1">Importing, please wait…</div>
            </div>

            <div id="importResult" style="display:none;" class="mb-2"></div>

            <div id="importErrorList" style="display:none;max-height:300px;overflow-y:auto;border:1px solid #fee2e2;border-radius:10px;padding:10px 12px;background:#fff5f5;"></div>
        </form>

        <div class="mt-auto pt-3 border-top d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-light" data-bs-dismiss="offcanvas">Close</button>
            <button type="button" class="btn btn-primary" id="importSubmitBtn" onclick="submitImport()">
                <i class="feather-upload me-2"></i> Import Now
            </button>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.ajax-toast {
    position:fixed;top:20px;right:20px;z-index:9999;min-width:320px;max-width:400px;
    border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:10px;
    font-size:14px;font-weight:500;box-shadow:0 8px 32px rgba(0,0,0,0.15);
    transform:translateX(120%);transition:transform 0.4s cubic-bezier(0.34,1.56,0.64,1);
}
.ajax-toast.show{transform:translateX(0);}
.ajax-toast.toast-success{background:#d1fae5;border-left:4px solid #10b981;color:#065f46;}
.ajax-toast.toast-error{background:#fee2e2;border-left:4px solid #ef4444;color:#991b1b;}
.ajax-toast .toast-close{margin-left:auto;background:none;border:none;cursor:pointer;font-size:16px;color:inherit;opacity:0.6;}

#importOffcanvas .offcanvas-body{padding:20px;}
</style>
@endpush

@push('scripts')
<script>
let toastTimer=null;
function showToast(message,type='success'){
    const toast=document.getElementById('ajaxToast');
    const msgEl=document.getElementById('toastMessage');
    const iconEl=document.getElementById('toastIcon');
    msgEl.textContent=message;
    toast.className='ajax-toast';
    toast.classList.add(type==='success'?'toast-success':'toast-error');
    iconEl.textContent=type==='success'?'✅':'❌';
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer=setTimeout(()=>toast.classList.remove('show'),4000);
}
function hideToast(){document.getElementById('ajaxToast').classList.remove('show');}

function deleteContact(id) {
    if (!confirm('Are you sure you want to delete this contact?')) return;
    fetch(`/contacts/${id}`, {
        method:'DELETE',
        headers:{
            'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,
            'Accept':'application/json'
        }
    })
    .then(res=>res.json())
    .then(data=>{
        if(data.status==='success'){
            document.getElementById(`contact-row-${id}`)?.remove();
            showToast(data.message,'success');
            filterAndPaginate();
        }else{
            showToast(data.message,'error');
        }
    })
    .catch(()=>showToast('Something went wrong!','error'));
}

document.getElementById('checkAll').addEventListener('change',function(){
    document.querySelectorAll('.checkbox').forEach(cb=>cb.checked=this.checked);
});

let currentPage=1, activeStatus='all', activeType='all';
function getAllRows(){return Array.from(document.querySelectorAll('.contact-row'));}

function filterAndPaginate(){
    const search=document.getElementById('searchInput').value.toLowerCase();
    const perPage=parseInt(document.getElementById('perPageSelect').value);
    const allRows=getAllRows();
    const filtered=allRows.filter(row=>{
        const matchSearch=!search||row.textContent.toLowerCase().includes(search);
        const matchStatus=activeStatus==='all'||row.dataset.status===activeStatus;
        const matchType=activeType==='all'||row.dataset.type===activeType;
        return matchSearch&&matchStatus&&matchType;
    });
    allRows.forEach(row=>row.style.display='none');
    const total=filtered.length;
    const totalPages=Math.ceil(total/perPage)||1;
    if(currentPage>totalPages)currentPage=1;
    const start=(currentPage-1)*perPage;
    const end=Math.min(start+perPage,total);
    filtered.forEach((row,i)=>{row.style.display=(i>=start&&i<end)?'':'none';});
    const noDataRow=document.getElementById('noDataRow');
    if(noDataRow)noDataRow.style.display=total===0?'':'none';
    document.getElementById('showFrom').textContent=total===0?0:start+1;
    document.getElementById('showTo').textContent=end;
    document.getElementById('totalRows').textContent=total;
    renderPagination(totalPages);
}

function renderPagination(totalPages){
    const ul=document.getElementById('paginationLinks');
    ul.innerHTML='';
    const prev=document.createElement('li');
    prev.className=`page-item ${currentPage===1?'disabled':''}`;
    prev.innerHTML=`<a class="page-link" href="javascript:void(0);">«</a>`;
    prev.addEventListener('click',()=>{if(currentPage>1){currentPage--;filterAndPaginate();}});
    ul.appendChild(prev);
    for(let i=1;i<=totalPages;i++){
        const li=document.createElement('li');
        li.className=`page-item ${i===currentPage?'active':''}`;
        li.innerHTML=`<a class="page-link" href="javascript:void(0);">${i}</a>`;
        li.addEventListener('click',()=>{currentPage=i;filterAndPaginate();});
        ul.appendChild(li);
    }
    const next=document.createElement('li');
    next.className=`page-item ${currentPage===totalPages?'disabled':''}`;
    next.innerHTML=`<a class="page-link" href="javascript:void(0);">»</a>`;
    next.addEventListener('click',()=>{if(currentPage<totalPages){currentPage++;filterAndPaginate();}});
    ul.appendChild(next);
}

document.getElementById('searchInput').addEventListener('input',()=>{currentPage=1;filterAndPaginate();});
document.getElementById('perPageSelect').addEventListener('change',()=>{currentPage=1;filterAndPaginate();});
document.querySelectorAll('.filter-status').forEach(el=>{
    el.addEventListener('click',function(){activeStatus=this.dataset.status;currentPage=1;filterAndPaginate();});
});
document.querySelectorAll('.filter-type').forEach(el=>{
    el.addEventListener('click',function(){activeType=this.dataset.type;currentPage=1;filterAndPaginate();});
});

function exportCSV(){
    const rows=getAllRows().filter(r=>r.style.display!=='none');
    let csv='Name,Code,Phone,WhatsApp,Type,Status\n';
    rows.forEach(row=>{
        const cells=row.querySelectorAll('td');
        csv+=[cells[1]?.textContent.trim(),cells[2]?.textContent.trim(),
              cells[3]?.textContent.trim(),cells[4]?.textContent.trim(),
              cells[6]?.textContent.trim()]
             .map(v=>`"${v}"`).join(',')+'\n';
    });
    const a=document.createElement('a');
    a.href=URL.createObjectURL(new Blob([csv],{type:'text/csv'}));
    a.download='contacts.csv';
    a.click();
}

/* ─── Bulk import (CSV, no package) — Offcanvas version ─── */
function submitImport(){
    const fileInput = document.getElementById('importFileInput');
    if (!fileInput.files.length){ showToast('Please select a CSV file first.', 'error'); return; }

    const form = document.getElementById('importForm');
    const fd = new FormData(form);
    fd.append('import_file', fileInput.files[0]);

    const btn = document.getElementById('importSubmitBtn');
    const progress = document.getElementById('importProgress');
    const resultBox = document.getElementById('importResult');
    const errorBox = document.getElementById('importErrorList');

    btn.disabled = true;
    progress.style.display = 'block';
    resultBox.style.display = 'none';
    errorBox.style.display = 'none';
    errorBox.innerHTML = '';

    fetch(`{{ route('contacts.import') }}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: fd,
    })
    .then(res => res.json())
    .then(data => {
        progress.style.display = 'none';
        btn.disabled = false;

        resultBox.style.display = 'block';
        resultBox.className = data.status === 'success'
            ? 'alert alert-light-success mb-2'
            : (data.status === 'partial' ? 'alert alert-light-warning mb-2' : 'alert alert-light-danger mb-2');
        resultBox.innerHTML = `<i class="feather-${data.status === 'success' ? 'check-circle' : 'alert-triangle'} me-2"></i>${data.message}`;

        if (data.errors && data.errors.length){
            errorBox.style.display = 'block';
            errorBox.innerHTML = '<div class="fw-semibold fs-12 text-danger mb-2">Skipped rows:</div>' +
                data.errors.map(e => `
                    <div class="fs-12 mb-2 pb-2" style="border-bottom:1px solid #fee2e2;">
                        <strong>Row ${e.row}:</strong> ${e.errors}
                    </div>
                `).join('');
        }

        if (data.imported > 0){
            showToast(`${data.imported} contact(s) imported!`, 'success');
            setTimeout(() => location.reload(), 1800);
        }
    })
    .catch(() => {
        progress.style.display = 'none';
        btn.disabled = false;
        showToast('Import failed. Please check your file and try again.', 'error');
    });
}

// Reset the import offcanvas fully whenever it is closed
document.getElementById('importOffcanvas').addEventListener('hidden.bs.offcanvas', function () {
    document.getElementById('importForm').reset();
    document.getElementById('importProgress').style.display = 'none';
    document.getElementById('importResult').style.display = 'none';
    document.getElementById('importErrorList').style.display = 'none';
    document.getElementById('importErrorList').innerHTML = '';
    document.getElementById('importSubmitBtn').disabled = false;
});

document.addEventListener('DOMContentLoaded',()=>filterAndPaginate());
</script>
@endpush