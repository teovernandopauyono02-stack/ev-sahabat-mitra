@extends('layout.app')
@section('title', 'Station - EV Smart Energy')
@section('page-title', 'Station')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/station.css') }}">
<link rel="stylesheet" href="{{ asset('css/stasiun.css') }}">
@endpush

@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h2 style="display:flex;align-items:center;gap:12px;font-family:'Rajdhani',sans-serif;font-size:24px;font-weight:700;margin-bottom:4px">
            <span style="display:inline-flex;align-items:center;justify-content:center;width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#059669,#10b981);box-shadow:0 0 18px rgba(16,185,129,0.4);flex-shrink:0;animation:pageIconPulse 3s ease-in-out infinite">
                <i class="fas fa-charging-station" style="font-size:20px;color:#fff"></i>
            </span>
            <span style="background:linear-gradient(135deg,#f1f5f9,#34d399);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent">
                Station
            </span>
        </h2>
        <p style="font-size:12px;color:#94a3b8">Manajemen stasiun pengisian kendaraan listrik</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('addModal')">
        <i class="fas fa-plus"></i> Tambah Stasiun
    </button>
</div>

@if(request('highlight') === 'maintenance')
<div class="alert-banner">
    <i class="fas fa-tools"></i>
    <span>Stasiun berikut sedang dalam <strong>Maintenance</strong> — perlu diperbaiki segera!</span>
</div>
@endif

@if(request('highlight') === 'inactive')
<div class="alert-banner-warning">
    <i class="fas fa-pause-circle"></i>
    <span>Stasiun berikut sedang <strong>Tidak Aktif</strong> — perlu ditindaklanjuti!</span>
</div>
@endif

<div class="card">
    <div class="card-header">
        <span class="card-title">Daftar Stasiun (<span style="color:var(--yellow)">{{ $station->total() }}</span>)</span>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Cari stasiun..." class="form-control" style="width:220px" id="searchInput">
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Stasiun</th>
                    <th>Lokasi</th>
                    <th>Status</th>
                    <th>Unit Charger</th>
                    <th>Koordinat</th>
                    <th>Dibuat</th>
                    <th style="text-align:right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($station as $i => $st)
                @php
                    $highlightById   = request('highlight') == $st->id;
                    $highlightStatus = (request('highlight') === 'maintenance' && $st->status === 'Maintenance')
                                    || (request('highlight') === 'inactive' && $st->status === 'Inactive');
                    $isProblem = $highlightById || $highlightStatus;
                    $isMaintHl = $highlightById && $st->status === 'Maintenance' || (request('highlight') === 'maintenance' && $st->status === 'Maintenance');
                    $isInactiveHl = $highlightById && $st->status === 'Inactive' || (request('highlight') === 'inactive' && $st->status === 'Inactive');
                @endphp
                <tr class="@if($isMaintHl) highlight-row @elseif($isInactiveHl) highlight-row-warning @elseif($isProblem) highlight-row-info tier-{{ Illuminate\Support\Str::slug(request('tier', 'aktif')) }} @endif"
                    id="station-{{ $st->id }}">
                    <td style="color:#e2e8f0 !important">{{ $station->firstItem() + $i }}</td>
                    <td>
                        <a href="{{ route('station.show', $st->id) }}" class="station-link">
                            {{ $st->nama_stasiun }}
                        </a>
                        @if($isMaintHl)
                            <span class="badge-problem">BERMASALAH</span>
                        @elseif($isInactiveHl)
                            <span class="badge-inactive-warn">TIDAK AKTIF</span>
                        @elseif($isProblem)
                            @php
                                $tierLabel = strtoupper(request('tier', 'Aktif'));
                                $tierClass = 'badge-tier-default';
                                if (str_contains($tierLabel, 'SANGAT'))      $tierClass = 'badge-tier-1';
                                elseif (str_contains($tierLabel, 'CUKUP'))   $tierClass = 'badge-tier-3';
                                elseif (str_contains($tierLabel, 'NORMAL')) $tierClass = 'badge-tier-4';
                                elseif ($tierLabel === 'AKTIF')              $tierClass = 'badge-tier-2';
                            @endphp
                            <span class="badge-tier {{ $tierClass }}">{{ $tierLabel }}</span>
                        @endif
                    </td>
                    <td>{{ $st->lokasi }}</td>
                    <td>
                        @if($st->status==='Active')
                            <span class="badge badge-active">Active</span>
                        @elseif($st->status==='Maintenance')
                            <span class="badge badge-maintenance">Maintenance</span>
                        @else
                            <span class="badge badge-inactive">Inactive</span>
                        @endif
                    </td>
                    <td>
                        @if($st->chargers_count > 0)
                            <span class="unit-count">
                                <i class="fas fa-plug"></i> {{ $st->chargers_count }} Unit
                            </span>
                        @else
                            <span class="unit-count" style="background:rgba(148,163,184,0.1);color:#94a3b8;border-color:rgba(148,163,184,0.2)">
                                <i class="fas fa-info-circle"></i> Belum ada unit
                            </span>
                        @endif
                    </td>
                    <td class="coord-cell">
                        @if($st->latitude && $st->longitude)
                            {{ number_format($st->latitude,4) }}, {{ number_format($st->longitude,4) }}
                        @else —
                        @endif
                    </td>
                    <td class="date-cell">{{ $st->created_at->format('d/m/Y') }}</td>
                    <td>
                        <div class="action-cell">
                            <a href="{{ route('station.show', $st->id) }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-eye"></i> Detail
                            </a>
                            <button class="btn btn-secondary btn-sm"
                                onclick="openEdit({{ $st->id }},'{{ addslashes($st->nama_stasiun) }}','{{ addslashes($st->lokasi) }}','{{ $st->status }}','{{ $st->latitude }}','{{ $st->longitude }}')">
                                <i class="fas fa-pencil-alt"></i> Edit
                            </button>
                            <form method="POST" action="{{ route('station.destroy',$st->id) }}" style="margin:0">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm"
                                    onclick="return confirm('Hapus stasiun {{ $st->nama_stasiun }}?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="empty-cell">
                        <i class="fas fa-charging-station empty-icon"></i>
                        Belum ada stasiun. Tambahkan stasiun pertama!
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($station->hasPages())
    @php
        $current = $station->currentPage();
        $last    = $station->lastPage();
        $window  = 2;
        $pages   = [1];
        for ($i = max(2, $current - $window); $i <= min($last - 1, $current + $window); $i++) {
            $pages[] = $i;
        }
        if ($last > 1) $pages[] = $last;
        $pages = array_values(array_unique($pages));
    @endphp
    <div class="pagination-wrap">
        <span class="page-info">
            Halaman <strong style="color:var(--yellow,#FFD700)">{{ $current }}</strong> dari <strong>{{ $last }}</strong>
            · {{ $station->firstItem() }}–{{ $station->lastItem() }} dari {{ number_format($station->total(), 0, ',', '.') }}
        </span>
        <div class="pagination">
            {{-- First --}}
            @if($current > 2)
                <a class="page-link" href="{{ $station->url(1) }}" title="Halaman pertama">«</a>
            @else
                <span class="page-link" style="opacity:0.4">«</span>
            @endif

            {{-- Prev --}}
            @if($station->onFirstPage())
                <span class="page-link" style="opacity:0.4">‹</span>
            @else
                <a class="page-link" href="{{ $station->previousPageUrl() }}">‹</a>
            @endif

            {{-- Numbered with ellipsis --}}
            @php $prev = 0; @endphp
            @foreach($pages as $p)
                @if($prev && $p - $prev > 1)
                    <span class="page-link" style="opacity:0.4;cursor:default">…</span>
                @endif
                @if($p == $current)
                    <span class="page-link active">{{ $p }}</span>
                @else
                    <a class="page-link" href="{{ $station->url($p) }}">{{ $p }}</a>
                @endif
                @php $prev = $p; @endphp
            @endforeach

            {{-- Next --}}
            @if($station->hasMorePages())
                <a class="page-link" href="{{ $station->nextPageUrl() }}">›</a>
            @else
                <span class="page-link" style="opacity:0.4">›</span>
            @endif

            {{-- Last --}}
            @if($current < $last - 1)
                <a class="page-link" href="{{ $station->url($last) }}" title="Halaman terakhir">»</a>
            @else
                <span class="page-link" style="opacity:0.4">»</span>
            @endif
        </div>

        {{-- Jump to page (kalau halaman > 7) — auto submit on Enter / blur --}}
        @if($last > 7)
        <form method="GET" action="" id="jumpStationForm" style="display:inline-flex;align-items:center;gap:6px;margin-left:8px"
              onsubmit="return jumpStationPage(event, {{ $last }})">
            @foreach(request()->except('page') as $k => $v)
                @if(is_array($v))
                    @foreach($v as $vv)<input type="hidden" name="{{ $k }}[]" value="{{ $vv }}">@endforeach
                @else
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endif
            @endforeach
            <span style="font-size:11px;color:var(--text-muted)">Ke halaman</span>
            <input type="number" name="page" min="1" max="{{ $last }}" value="{{ $current }}"
                   class="form-control" style="width:70px;padding:4px 8px;font-size:12px;text-align:center"
                   onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()"
                   onkeydown="if(event.key==='Enter'){event.preventDefault();this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit();}"
                   title="Ketik nomor halaman lalu Enter atau klik di luar">
        </form>
        @endif
    </div>
    @endif
</div>

{{-- Modal Tambah --}}
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <button class="btn-close-modal" onclick="closeModal('addModal')"><i class="fas fa-times"></i></button>
        <div class="modal-title">Tambah Stasiun Baru</div>
        <form method="POST" action="{{ route('station.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Nama Stasiun</label>
                <input type="text" name="nama_stasiun" class="form-control" required placeholder="contoh: EV-13">
            </div>
            <div class="form-group">
                <label class="form-label">Lokasi</label>
                <input type="text" name="lokasi" id="addLokasi" class="form-control" required placeholder="contoh: Jakarta Selatan" autocomplete="off">
                <small id="addGeoHint" style="font-size:12px;margin-top:4px;display:none"></small>
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-control" required>
                    <option value="Active">Active</option>
                    <option value="Maintenance">Maintenance</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Latitude <small style="color:#64748b;font-weight:400">(opsional)</small></label>
                    <input type="number" step="any" name="latitude" id="addLat" class="form-control" placeholder="-6.2088">
                </div>
                <div class="form-group">
                    <label class="form-label">Longitude <small style="color:#64748b;font-weight:400">(opsional)</small></label>
                    <input type="number" step="any" name="longitude" id="addLng" class="form-control" placeholder="106.8456">
                </div>
            </div>
            <div class="modal-action">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Edit --}}
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <button class="btn-close-modal" onclick="closeModal('editModal')"><i class="fas fa-times"></i></button>
        <div class="modal-title">Edit Stasiun</div>
        <form method="POST" id="editForm">
            @csrf @method('PUT')
            <div class="form-group">
                <label class="form-label">Nama Stasiun</label>
                <input type="text" name="nama_stasiun" id="eNama" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Lokasi</label>
                <input type="text" name="lokasi" id="eLokasi" class="form-control" required autocomplete="off">
                <small id="editGeoHint" style="font-size:12px;margin-top:4px;display:none"></small>
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" id="eStatus" class="form-control" required>
                    <option value="Active">Active</option>
                    <option value="Maintenance">Maintenance</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Latitude <small style="color:#64748b;font-weight:400">(opsional)</small></label>
                    <input type="number" step="any" name="latitude" id="eLat" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Longitude <small style="color:#64748b;font-weight:400">(opsional)</small></label>
                    <input type="number" step="any" name="longitude" id="eLng" class="form-control">
                </div>
            </div>
            <div class="modal-action">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openEdit(id, nama, lokasi, status, lat, lng) {
    document.getElementById('editForm').action = '/station/' + id;
    document.getElementById('eNama').value   = nama;
    document.getElementById('eLokasi').value = lokasi;
    document.getElementById('eStatus').value = status;
    document.getElementById('eLat').value    = lat || '';
    document.getElementById('eLng').value    = lng || '';
    document.getElementById('editGeoHint').style.display = 'none';
    openModal('editModal');
}

function setupGeocode(inputId, latId, lngId, hintId) {
    const input = document.getElementById(inputId);
    const hint  = document.getElementById(hintId);
    if (!input) return;
    let timer;
    input.addEventListener('input', function () {
        clearTimeout(timer);
        const val = this.value.trim();
        if (val.length < 3) { hint.style.display = 'none'; return; }
        hint.style.display = 'block';
        hint.style.color   = '#60a5fa';
        hint.textContent   = 'Mencari koordinat...';
        timer = setTimeout(async function () {
            try {
                const res  = await fetch('https://nominatim.openstreetmap.org/search?q=' + encodeURIComponent(val) + '&format=json&limit=1');
                const data = await res.json();
                if (data.length > 0) {
                    document.getElementById(latId).value = parseFloat(data[0].lat).toFixed(6);
                    document.getElementById(lngId).value = parseFloat(data[0].lon).toFixed(6);
                    hint.style.color = '#22c55e';
                    hint.textContent = '✓ Koordinat ditemukan otomatis';
                } else {
                    hint.style.color = '#ef4444';
                    hint.textContent = 'Lokasi tidak ditemukan, isi koordinat manual.';
                }
            } catch (e) {
                hint.style.color = '#ef4444';
                hint.textContent = 'Gagal terhubung, coba lagi.';
            }
        }, 1000);
    });
}

setupGeocode('addLokasi', 'addLat', 'addLng', 'addGeoHint');
setupGeocode('eLokasi',   'eLat',   'eLng',   'editGeoHint');

document.getElementById('searchInput').addEventListener('input', function () {
    const q    = this.value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

window.addEventListener('load', function () {
    const hParam = new URLSearchParams(window.location.search).get('highlight');
    let target = null;
    if (hParam && /^\d+$/.test(hParam)) {
        target = document.getElementById('station-' + hParam);
    }
    if (!target) {
        target = document.querySelector('.highlight-row, .highlight-row-warning, .highlight-row-info');
    }
    if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        target.style.transition = 'all 0.3s';
        let pulse = 0;
        const it = setInterval(() => {
            target.classList.toggle('row-pulse');
            if (++pulse >= 6) clearInterval(it);
        }, 500);
    }
});

// Validasi & auto-submit form jump-to-page di Station
function jumpStationPage(e, lastPage) {
    var input = e.target.querySelector('input[name="page"]');
    var v = parseInt(input.value, 10);
    if (isNaN(v) || v < 1) { input.value = 1; return false; }
    if (v > lastPage)      { input.value = lastPage; }
    return true;
}
</script>
@endpush
