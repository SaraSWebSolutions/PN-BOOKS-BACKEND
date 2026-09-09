@extends('layouts.app')
@section('title', 'View Contact')

@section('content')

<div class="ajax-toast" id="ajaxToast">
    <span class="toast-icon" id="toastIcon"></span>
    <span id="toastMessage"></span>
    <button class="toast-close" onclick="hideToast()">✕</button>
</div>

<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Contact Details</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('contacts.index') }}">Contacts</a></li>
            <li class="breadcrumb-item">View</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto d-flex gap-2">
        @can('edit contacts')
        <a href="{{ route('contacts.edit', $contact) }}" class="btn btn-primary">
            <i class="feather-edit-3 me-2"></i> Edit Contact
        </a>
        @endcan
        <a href="{{ route('contacts.index') }}" class="btn btn-light-brand">
            <i class="feather-arrow-left me-2"></i> Back
        </a>
    </div>
</div>

<div class="main-content">

    {{-- ── Profile Header Card ── --}}
    <div class="card mb-3">
        <div class="card-body py-3 px-4">
            <div class="d-flex align-items-center gap-3 flex-wrap">

                {{-- Avatar --}}
                <div class="d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0"
                     style="width:64px;height:64px;border-radius:50%;font-size:26px;
                            background:{{ $contact->type === 'supplier' ? '#7c3aed' : '#3b82f6' }};">
                    {{ strtoupper(substr($contact->name, 0, 1)) }}
                </div>

                {{-- Name + badges --}}
                <div class="flex-grow-1">
                    <h5 class="fw-bold mb-1">{{ $contact->name }}</h5>
                    <p class="text-muted fs-13 mb-2">
                        {{ $contact->email ?? '' }}
                        @if($contact->email && $contact->phone) · @endif
                        {{ $contact->phone ?? '' }}
                    </p>
                    <div class="d-flex gap-2 flex-wrap">
                        @php
                            $typeBadge = match($contact->type) {
                                'customer' => 'bg-soft-primary text-primary',
                                'lead'     => 'bg-soft-warning text-warning',
                                'supplier' => 'bg-soft-purple text-purple',
                                default    => 'bg-soft-info text-info',
                            };
                            $typeLabel = match($contact->type) {
                                'customer' => 'Customer',
                                'lead'     => 'Lead',
                                'supplier' => 'Supplier',
                                default    => 'Customer + Lead',
                            };
                        @endphp
                        <span class="badge {{ $typeBadge }} fs-12 px-3 py-2">{{ $typeLabel }}</span>
                        <span class="badge fs-12 px-3 py-2 {{ $contact->status === 'active' ? 'bg-soft-success text-success' : 'bg-soft-danger text-danger' }}">
                            {{ ucfirst($contact->status) }}
                        </span>
                    </div>
                </div>

                {{-- Quick action buttons --}}
                <div class="d-flex gap-2">
                    @if($contact->phone)
                    <a href="tel:{{ $contact->phone }}" class="btn btn-icon btn-light-brand" title="Call">
                        <i class="feather-phone"></i>
                    </a>
                    @endif
                    @if($contact->email)
                    <a href="mailto:{{ $contact->email }}" class="btn btn-icon btn-light-brand" title="Email">
                        <i class="feather-mail"></i>
                    </a>
                    @endif
                    @if($contact->whatsapp)
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $contact->whatsapp) }}"
                       target="_blank" class="btn btn-icon" title="WhatsApp"
                       style="background:rgba(37,211,102,.12);color:#25D366;border:none;">
                        <i class="bi bi-whatsapp"></i>
                    </a>
                    @endif
                </div>

                {{-- Meta stats --}}
                <div class="d-flex gap-2 ms-lg-3">
                    @foreach([
                        ['Code',    $contact->code ?? '—'],
                        ['Created', $contact->created_at->format('d M Y')],
                        ['Updated', $contact->updated_at->format('d M Y')],
                    ] as [$lbl, $val])
                    <div class="text-center px-3 py-2 rounded-3"
                         style="background:var(--color-background-secondary,#f8f9fa);min-width:90px;">
                        <div class="fs-11 text-muted mb-1">{{ $lbl }}</div>
                        <div class="fs-12 fw-semibold">{{ $val }}</div>
                    </div>
                    @endforeach
                </div>

            </div>
        </div>
    </div>

    {{-- ── Tab Navigation ── --}}
    <ul class="nav nav-pills mb-3 gap-1" id="contactTabs" role="tablist"
        style="background:var(--color-background-secondary,#f1f0ee);border-radius:10px;padding:4px;display:inline-flex;width:100%;">
        @foreach([
            ['contact',   'feather-phone',      'Contact'],
            ['address',   'feather-map-pin',    'Address'],
            ['financial', 'feather-dollar-sign','Financial & KYC'],
            ['notes',     'feather-file-text',  'Notes'],
        ] as [$id, $icon, $label])
        <li class="nav-item flex-fill" role="presentation">
            <button class="nav-link w-100 {{ $loop->first ? 'active' : '' }} py-2"
                    id="tab-{{ $id }}"
                    data-bs-toggle="pill"
                    data-bs-target="#pane-{{ $id }}"
                    type="button" role="tab" style="font-size:13px;">
                <i class="{{ $icon }} me-1" style="font-size:13px;"></i> {{ $label }}
            </button>
        </li>
        @endforeach
    </ul>

    {{-- ── Tab Panes ── --}}
    <div class="tab-content" id="contactTabContent">

        {{-- ①  Contact Details --}}
        <div class="tab-pane fade show active" id="pane-contact" role="tabpanel">
            <div class="row g-3">

                <div class="col-lg-8">
                    <div class="card h-100">
                        <div class="card-header py-3">
                            <h6 class="fw-semibold text-uppercase fs-11 text-muted mb-0">
                                <i class="feather-phone me-2"></i> Contact Details
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <p class="fs-12 text-muted mb-1">Phone</p>
                                    <p class="fw-semibold mb-0">
                                        @if($contact->phone)
                                            <a href="tel:{{ $contact->phone }}" class="text-dark">{{ $contact->phone }}</a>
                                        @else — @endif
                                    </p>
                                </div>
                                <div class="col-sm-6">
                                    <p class="fs-12 text-muted mb-1">WhatsApp</p>
                                    <p class="fw-semibold mb-0">
                                        @if($contact->whatsapp)
                                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $contact->whatsapp) }}"
                                               target="_blank" class="text-success">
                                                <i class="bi bi-whatsapp me-1"></i>{{ $contact->whatsapp }}
                                            </a>
                                        @else — @endif
                                    </p>
                                </div>
                                <div class="col-sm-6">
                                    <p class="fs-12 text-muted mb-1">Email</p>
                                    <p class="fw-semibold mb-0">
                                        @if($contact->email)
                                            <a href="mailto:{{ $contact->email }}" class="text-dark">{{ $contact->email }}</a>
                                        @else — @endif
                                    </p>
                                </div>
                                <div class="col-sm-6">
                                    <p class="fs-12 text-muted mb-1">Business Location</p>
                                    <p class="fw-semibold mb-0">{{ $contact->businessLocation->name ?? '—' }}</p>
                                </div>
                                <div class="col-sm-6">
                                    <p class="fs-12 text-muted mb-1">Gender</p>
                                    <p class="fw-semibold mb-0 text-capitalize">{{ $contact->gender ?? '—' }}</p>
                                </div>
                                <div class="col-sm-6">
                                    <p class="fs-12 text-muted mb-1">Date of Birth</p>
                                    <p class="fw-semibold mb-0">
                                        {{ $contact->date_of_birth
                                            ? \Carbon\Carbon::parse($contact->date_of_birth)->format('d M Y')
                                            : '—' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 d-flex flex-column gap-3">
                    <div class="card flex-grow-1">
                        <div class="card-header py-3">
                            <h6 class="fw-semibold text-uppercase fs-11 text-muted mb-0">
                                <i class="feather-info me-2"></i> Profile Meta
                            </h6>
                        </div>
                        <div class="card-body">
                            @foreach([
                                ['Contact Type', ucfirst($contact->type)],
                                ['Created By',   $contact->createdBy->name ?? '—'],
                                ['Contact Code', $contact->code ?? '—'],
                            ] as [$lbl, $val])
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="fs-12 text-muted">{{ $lbl }}</span>
                                <span class="fs-12 fw-semibold">{{ $val }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    @can('delete contacts')
                    <div class="card border-0" style="background:rgba(239,68,68,.05);border:1px solid rgba(239,68,68,.2)!important;">
                        <div class="card-body py-3 px-4">
                            <p class="fs-12 text-danger mb-1 fw-semibold">
                                <i class="feather-alert-triangle me-1"></i> Danger Zone
                            </p>
                            <p class="fs-12 text-muted mb-3">Permanently delete this contact. Cannot be undone.</p>
                            <button class="btn btn-sm btn-danger w-100" onclick="deleteContact({{ $contact->id }})">
                                <i class="feather-trash-2 me-2"></i> Delete Contact
                            </button>
                        </div>
                    </div>
                    @endcan
                </div>

            </div>
        </div>

        {{-- ②  Address --}}
        <div class="tab-pane fade" id="pane-address" role="tabpanel">
            <div class="card">
                <div class="card-header py-3">
                    <h6 class="fw-semibold text-uppercase fs-11 text-muted mb-0">
                        <i class="feather-map me-2"></i> Address
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <p class="fs-12 text-muted mb-1">Full Address</p>
                            <p class="fw-semibold mb-0">{{ $contact->address ?? '—' }}</p>
                        </div>
                        <div class="col-sm-3">
                            <p class="fs-12 text-muted mb-1">City</p>
                            <p class="fw-semibold mb-0">{{ $contact->city ?? '—' }}</p>
                        </div>
                        <div class="col-sm-3">
                            <p class="fs-12 text-muted mb-1">State</p>
                            <p class="fw-semibold mb-0">{{ $contact->state ?? '—' }}</p>
                        </div>
                        <div class="col-sm-3">
                            <p class="fs-12 text-muted mb-1">Pincode</p>
                            <p class="fw-semibold mb-0">{{ $contact->pincode ?? '—' }}</p>
                        </div>
                        <div class="col-sm-3">
                            <p class="fs-12 text-muted mb-1">Country</p>
                            <p class="fw-semibold mb-0">{{ $contact->country ?? '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ③  Financial & KYC --}}
        <div class="tab-pane fade" id="pane-financial" role="tabpanel">
            <div class="row g-3">

                {{-- Financial summary stats --}}
                <div class="col-12">
                    <div class="row g-3">
                        @foreach([
                            ['GSTIN',           $contact->gstin ?? '—',                                      'feather-file-text', '#3b82f6'],
                            ['Credit Limit',    '₹'.number_format($contact->credit_limit ?? 0, 2),          'feather-trending-up','#10b981'],
                            ['Opening Balance', '₹'.number_format($contact->opening_balance ?? 0, 2),       'feather-dollar-sign','#f59e0b'],
                        ] as [$lbl, $val, $icon, $color])
                        <div class="col-sm-4">
                            <div class="card">
                                <div class="card-body py-3 d-flex align-items-center gap-3">
                                    <div class="d-flex align-items-center justify-content-center rounded-3"
                                         style="width:42px;height:42px;background:{{ $color }}1a;color:{{ $color }};flex-shrink:0;">
                                        <i class="{{ $icon }}"></i>
                                    </div>
                                    <div>
                                        <p class="fs-12 text-muted mb-0">{{ $lbl }}</p>
                                        <p class="fw-bold mb-0 fs-14">{{ $val }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- KYC --}}
                <div class="col-12">
                    <div class="card">
                        <div class="card-header py-3">
                            <h6 class="fw-semibold text-uppercase fs-11 text-muted mb-0">
                                <i class="feather-shield me-2"></i> KYC Documents
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <div class="rounded-3 p-3" style="background:#E6F1FB;border:1px solid #B5D4F4;">
                                        <p class="fs-11 fw-semibold mb-2" style="color:#0C447C;">
                                            <i class="feather-credit-card me-1"></i> Aadhaar Number
                                        </p>
                                        <p class="fw-bold mb-0" style="font-size:18px;letter-spacing:2px;color:#0C447C;font-family:monospace;">
                                            @if($contact->aadhaar_number)
                                                {{ substr($contact->aadhaar_number,0,4) }}
                                                {{ substr($contact->aadhaar_number,4,4) }}
                                                {{ substr($contact->aadhaar_number,8,4) }}
                                            @else
                                                <span class="fs-13 fw-normal" style="letter-spacing:0;font-family:inherit;">Not provided</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="rounded-3 p-3" style="background:#FAEEDA;border:1px solid #FAC775;">
                                        <p class="fs-11 fw-semibold mb-2" style="color:#633806;">
                                            <i class="feather-file-text me-1"></i> PAN Number
                                        </p>
                                        <p class="fw-bold mb-0" style="font-size:18px;letter-spacing:3px;color:#633806;font-family:monospace;">
                                            @if($contact->pan_number)
                                                {{ strtoupper($contact->pan_number) }}
                                            @else
                                                <span class="fs-13 fw-normal" style="letter-spacing:0;font-family:inherit;">Not provided</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- ④  Notes --}}
        <div class="tab-pane fade" id="pane-notes" role="tabpanel">
            <div class="card">
                <div class="card-header py-3">
                    <h6 class="fw-semibold text-uppercase fs-11 text-muted mb-0">
                        <i class="feather-file-text me-2"></i> Notes
                    </h6>
                </div>
                <div class="card-body">
                    @if($contact->notes)
                        <p class="mb-0 text-muted" style="line-height:1.8;">{{ $contact->notes }}</p>
                    @else
                        <p class="mb-0 text-muted fst-italic">No notes added for this contact.</p>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>

@endsection

@push('styles')
<style>
.ajax-toast{position:fixed;top:20px;right:20px;z-index:9999;min-width:320px;max-width:400px;border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:500;box-shadow:0 8px 32px rgba(0,0,0,0.15);transform:translateX(120%);transition:transform 0.4s cubic-bezier(0.34,1.56,0.64,1);}
.ajax-toast.show{transform:translateX(0);}
.ajax-toast.toast-success{background:#d1fae5;border-left:4px solid #10b981;color:#065f46;}
.ajax-toast.toast-error{background:#fee2e2;border-left:4px solid #ef4444;color:#991b1b;}
.ajax-toast .toast-close{margin-left:auto;background:none;border:none;cursor:pointer;font-size:16px;color:inherit;opacity:0.6;}
.nav-pills .nav-link{color:var(--bs-secondary-color);border-radius:8px;font-weight:500;}
.nav-pills .nav-link.active{background:#fff;color:var(--bs-body-color);box-shadow:0 1px 3px rgba(0,0,0,.08);}
</style>
@endpush

@push('scripts')
<script>
let toastTimer = null;
function showToast(message, type='success'){
    const toast=document.getElementById('ajaxToast');
    document.getElementById('toastMessage').textContent=message;
    toast.className='ajax-toast';
    toast.classList.add(type==='success'?'toast-success':'toast-error');
    document.getElementById('toastIcon').textContent=type==='success'?'✅':'❌';
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer=setTimeout(()=>toast.classList.remove('show'),4000);
}
function hideToast(){document.getElementById('ajaxToast').classList.remove('show');}

function deleteContact(id){
    if(!confirm('Are you sure you want to delete this contact? This cannot be undone.'))return;
    fetch(`/contacts/${id}`,{
        method:'DELETE',
        headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json'}
    })
    .then(r=>r.json())
    .then(data=>{
        if(data.status==='success'){
            showToast(data.message,'success');
            setTimeout(()=>window.location.href="{{ route('contacts.index') }}",1500);
        } else { showToast(data.message,'error'); }
    })
    .catch(()=>showToast('Something went wrong!','error'));
}
</script>
@endpush