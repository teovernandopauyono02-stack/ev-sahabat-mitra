@extends('layout.app')
@section('title', 'Detail Stasiun - EV Smart Energy')
@section('page-title', 'Station')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/station.css') }}">
<link rel="stylesheet" href="{{ asset('css/stasiun-detail.css') }}">
@endpush

@section('content')

{{-- Header --}}
<div class="detail-header">
    <a href="{{ route('station.index') }}" class="back-btn">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
    <div class="detail-title-wrap">
        <h2 class="detail-title">{{ $stasiun->nama_stasiun }}</h2>
        <p class="detail-subtitle"><i class="fas fa-map-marker-alt"></i> {{ $stasiun->lokasi }}</p>
    </div>
    <div style="margin-left:auto">
        @if($stasiun->status==='Active')
            <span class="badge badge-active">Active</span>
        @elseif($stasiun->status==='Maintenance')
            <span class="badge badge-maintenance">Maintenance</span>
        @else
            <span class="badge badge-inactive">Inactive</span>
        @endif
    </div>
</div>

{{-- Info Cards --}}
<div class="station-info-grid">
    <div class="info-card">
        <div class="info-icon" style="color:var(--cyan)"><i class="fas fa-plug"></i></div>
        <div>
            <div class="info-card-label">Total Unit</div>
            <div class="info-card-val" style="color:var(--cyan)">{{ $chargers->count() }}</div>
        </div>
    </div>
    <div class="info-card">
        <div class="info-icon" style="color:var(--green)"><i class="fas fa-check-circle"></i></div>
        <div>
            <div class="info-card-label">Available</div>
            <div class="info-card-val" style="color:var(--green)">{{ $chargers->where('status','Available')->count() }}</div>
        </div>
    </div>
    <div class="info-card">
        <div class="info-icon" style="color:var(--yellow)"><i class="fas fa-bolt"></i></div>
        <div>
            <div class="info-card-label">In Use</div>
            <div class="info-card-val" style="color:var(--yellow)">{{ $chargers->where('status','In Use')->count() }}</div>
        </div>
    </div>
    <div class="info-card">
        <div class="info-icon" style="color:var(--red)"><i class="fas fa-tools"></i></div>
        <div>
            <div class="info-card-label">Maintenance</div>
            <div class="info-card-val" style="color:var(--red)">{{ $chargers->where('status','Maintenance')->count() }}</div>
        </div>
    </div>
</div>

{{-- Charger Units --}}
<div class="card">
    <div class="card-header">
        <span class="card-title">
            Unit Charger
            <span class="unit-badge">{{ $chargers->count() }} Unit</span>
        </span>
        <button class="btn btn-primary btn-sm" onclick="openModal('addChargerModal')">
            <i class="fas fa-plus"></i> Tambah Unit
        </button>
    </div>

    <div class="charger-grid">
        @forelse($chargers as $ch)
        <div class="charger-card">
            <div class="charger-card-top">
                <div class="charger-code">{{ $ch->kode_unit }}</div>
                <span class="charger-tipe tipe-{{ strtolower(str_replace(' ','-',$ch->tipe)) }}">
                    {{ $ch->tipe }}
                </span>
            </div>
            <div class="charger-daya">
                {{ $ch->daya_kw }}
                <span class="charger-daya-unit">kW</span>
            </div>
            <div class="charger-status-wrap">
                @if($ch->status === 'Available')
                    <i class="fas fa-check-circle" style="color:#22c55e"></i>
                    <span class="charger-status-text" style="color:#22c55e">Available</span>
                @elseif($ch->status === 'In Use')
                    <i class="fas fa-bolt" style="color:var(--cyan)"></i>
                    <span class="charger-status-text" style="color:var(--cyan)">In Use</span>
                @elseif($ch->status === 'Maintenance')
                    <i class="fas fa-tools" style="color:var(--red)"></i>
                    <span class="charger-status-text" style="color:var(--red)">Maintenance</span>
                @endif
            </div>
            <div class="charger-actions">
                <button class="btn-charger-edit"
                    onclick="openEditCharger({{ $ch->id }},'{{ $ch->kode_unit }}','{{ $ch->tipe }}','{{ $ch->daya_kw }}','{{ $ch->status }}')">
                    <i class="fas fa-pencil-alt"></i> Edit
                </button>
                <form method="POST" action="{{ route('charger.destroy', $ch->id) }}" style="margin:0">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-charger-del"
                        onclick="return confirm('Hapus unit {{ $ch->kode_unit }}?')">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="empty-charger">
            <i class="fas fa-plug"></i>
            <p class="empty-charger-title">Belum ada unit charger</p>
            <p class="empty-charger-sub">Klik tombol Tambah Unit untuk menambahkan unit charger pertama</p>
            <button class="btn btn-primary" onclick="openModal('addChargerModal')" style="margin-top:12px">
                <i class="fas fa-plus"></i> Tambah Unit Sekarang
            </button>
        </div>
        @endforelse
    </div>
</div>

{{-- Modal Tambah Charger --}}
<div class="modal-overlay" id="addChargerModal">
    <div class="modal">
        <button class="btn-close-modal" onclick="closeModal('addChargerModal')"><i class="fas fa-times"></i></button>
        <div class="modal-title">Tambah Unit Charger</div>
        <form method="POST" action="{{ route('charger.store') }}">
            @csrf
            <input type="hidden" name="stasiun_pengisian_id" value="{{ $stasiun->id }}">
            <div class="form-group">
                <label class="form-label">Kode Unit</label>
                <input type="text" name="kode_unit" class="form-control" required placeholder="contoh: UNIT-A">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Tipe</label>
                    <select name="tipe" class="form-control" required>
                        <option value="AC">AC</option>
                        <option value="DC">DC</option>
                        <option value="Fast Charging">Fast Charging</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Daya (kW)</label>
                    <input type="number" step="0.1" name="daya_kw" class="form-control" required placeholder="contoh: 22">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-control" required>
                    <option value="Available">Available</option>
                    <option value="In Use">In Use</option>
                    <option value="Maintenance">Maintenance</option>
                </select>
            </div>
            <div class="modal-action">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addChargerModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Edit Charger --}}
<div class="modal-overlay" id="editChargerModal">
    <div class="modal">
        <button class="btn-close-modal" onclick="closeModal('editChargerModal')"><i class="fas fa-times"></i></button>
        <div class="modal-title">Edit Unit Charger</div>
        <form method="POST" id="editChargerForm">
            @csrf @method('PUT')
            <div class="form-group">
                <label class="form-label">Kode Unit</label>
                <input type="text" name="kode_unit" id="eCKode" class="form-control" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Tipe</label>
                    <select name="tipe" id="eCTipe" class="form-control" required>
                        <option value="AC">AC</option>
                        <option value="DC">DC</option>
                        <option value="Fast Charging">Fast Charging</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Daya (kW)</label>
                    <input type="number" step="0.1" name="daya_kw" id="eCDaya" class="form-control" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" id="eCStatus" class="form-control" required>
                    <option value="Available">Available</option>
                    <option value="In Use">In Use</option>
                    <option value="Maintenance">Maintenance</option>
                </select>
            </div>
            <div class="modal-action">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editChargerModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openEditCharger(id, kode, tipe, daya, status) {
    document.getElementById('editChargerForm').action = `/charger/${id}`;
    document.getElementById('eCKode').value   = kode;
    document.getElementById('eCTipe').value   = tipe;
    document.getElementById('eCDaya').value   = daya;
    document.getElementById('eCStatus').value = status;
    openModal('editChargerModal');
}
</script>
@endpush