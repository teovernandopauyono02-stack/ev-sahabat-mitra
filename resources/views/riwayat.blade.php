@extends('layout.app')
@section('title', 'Energy Log - EV Smart Energy')
@section('page-title', 'Energy Log')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/riwayat.css') }}">
@endpush

@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h2 style="display:flex;align-items:center;gap:12px;font-family:'Rajdhani',sans-serif;font-size:24px;font-weight:700;margin-bottom:4px">
            <span style="display:inline-flex;align-items:center;justify-content:center;width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#d97706,#f59e0b);box-shadow:0 0 18px rgba(245,158,11,0.4);flex-shrink:0;animation:pageIconPulse 3s ease-in-out infinite">
                <i class="fas fa-bolt" style="font-size:20px;color:#fff"></i>
            </span>
            <span style="background:linear-gradient(135deg,#f1f5f9,#fbbf24);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent">
                Energy Log
            </span>
        </h2>
        <p style="font-size:12px;color:#94a3b8">Riwayat seluruh aktivitas pengisian energi EV</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('addLogModal')">
        <i class="fas fa-plus"></i> Tambah Log
    </button>
</div>

@if(request('highlight') === 'high-energy')
<div style="background:rgba(59,130,246,0.1);border:1px solid #3b82f6;border-radius:8px;padding:10px 16px;margin-bottom:16px;color:#60a5fa;font-size:13px;display:flex;align-items:center;gap:8px;">
    <i class="fas fa-bolt"></i>
    <span>Log berikut memiliki <strong>Konsumsi Energi Tinggi</strong> — melebihi batas {{ env('THRESHOLD_ENERGI', 100) }} kWh!</span>
</div>
@endif

@if(request('highlight_station'))
<div style="background:rgba(168,85,247,0.1);border:1px solid #a855f7;border-radius:8px;padding:10px 16px;margin-bottom:16px;color:#c084fc;font-size:13px;display:flex;align-items:center;gap:8px;">
    <i class="fas fa-clock"></i>
    <span>Stasiun ini <strong>tidak ada aktivitas pengisian dalam 7 hari terakhir</strong> — tambahkan log pengisian baru!</span>
</div>
@endif

@if(request('highlight_anomaly'))
@php
    $sev = request('severity', 'warning');
    $thHigh = request('anomaly_threshold');
    $thLow  = request('anomaly_threshold_low');
    $stasiunNama = \App\Models\StasiunPengisian::find(request('stasiun_pengisian_id'))?->nama_stasiun ?? '-';
@endphp
<div style="background:{{ $sev==='critical'?'rgba(239,68,68,0.14)':'rgba(245,158,11,0.14)' }};border:1px solid {{ $sev==='critical'?'#ef4444':'#f59e0b' }};border-radius:8px;padding:12px 16px;margin-bottom:16px;color:{{ $sev==='critical'?'#fca5a5':'#fde68a' }};font-size:13px;display:flex;align-items:flex-start;gap:12px;">
    <i class="fas fa-{{ $sev==='critical'?'exclamation-triangle':'exclamation-circle' }}" style="font-size:18px;margin-top:1px;flex-shrink:0"></i>
    <div>
        <div style="font-weight:700;font-size:14px;margin-bottom:4px">
            ⚠️ Anomali {{ $sev==='critical'?'Critical':'Warning' }} — Stasiun {{ $stasiunNama }}
        </div>
        <div style="font-size:12px;line-height:1.6;color:{{ $sev==='critical'?'#fca5a5':'#fde68a' }}">
            Baris yang <strong>berkedip {{ $sev==='critical'?'merah':'kuning' }}</strong> memiliki konsumsi energi di luar batas normal.<br>
            Batas normal: <strong>{{ $thLow > 0 ? number_format($thLow,2).' kWh' : '0 kWh' }}</strong> s/d <strong>{{ number_format($thHigh,2) }} kWh</strong>
            &nbsp;·&nbsp; Klik <strong>Edit</strong> untuk koreksi atau <strong>Hapus</strong> jika data salah input.
        </div>
    </div>
</div>
@endif

{{-- Filter Periode --}}
<div class="periode-filter-bar">
    <div class="pf-label">
        <i class="fas fa-calendar-alt"></i>
        <span>Periode</span>
    </div>
    @php $currentPeriode = request('periode', 'all'); @endphp
    <div class="pf-presets">
        <a href="{{ route('energy-log.index', ['periode' => 'all']) }}"    class="pf-btn {{ $currentPeriode === 'all'    ? 'active' : '' }}">Semua</a>
        <a href="{{ route('energy-log.index', ['periode' => 'today']) }}"  class="pf-btn {{ $currentPeriode === 'today'  ? 'active' : '' }}">Hari Ini</a>
        <a href="{{ route('energy-log.index', ['periode' => '7days']) }}"  class="pf-btn {{ $currentPeriode === '7days'  ? 'active' : '' }}">7 Hari</a>
        <a href="{{ route('energy-log.index', ['periode' => '30days']) }}" class="pf-btn {{ $currentPeriode === '30days' ? 'active' : '' }}">30 Hari</a>
        <a href="{{ route('energy-log.index', ['periode' => 'month']) }}"  class="pf-btn {{ $currentPeriode === 'month'  ? 'active' : '' }}">Bulan Ini</a>
    </div>
    <form method="GET" action="{{ route('energy-log.index') }}" class="pf-custom">
        <input type="hidden" name="periode" value="custom">
        <input type="date" name="start_date" class="pf-date" value="{{ request('start_date') }}">
        <span class="pf-sep">s/d</span>
        <input type="date" name="end_date" class="pf-date" value="{{ request('end_date') }}">
        <button type="submit" class="pf-btn pf-btn-apply">
            <i class="fas fa-search"></i> Filter
        </button>
    </form>
</div>


<div class="card">
    <div class="card-header">
        <span class="card-title">
            Daftar Log
            <span style="color:var(--yellow)">({{ number_format($log->total(), 0, ',', '.') }} total)</span>
            @if($log->total() > 0)
                <span style="font-size:11px;color:var(--text-muted);font-weight:500;margin-left:6px">
                    · Menampilkan {{ $log->firstItem() }}–{{ $log->lastItem() }}
                </span>
            @endif
        </span>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Cari stasiun atau lokasi..." class="form-control" style="width:240px" id="searchInput">
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Stasiun</th>
                    <th>Lokasi</th>
                    <th>Energi (kWh)</th>
                    <th>Waktu Mulai</th>
                    <th>Waktu Selesai</th>
                    <th>Durasi</th>
                    <th style="text-align:right">Aksi</th>
                </tr>
            </thead>
            <tbody id="logTableBody">
                @php $thresholdEnergi = (float) env('THRESHOLD_ENERGI', 100); @endphp
                @forelse($log as $item)
                @php
                    $isHighEnergy = request('highlight') === 'high-energy' && $item->energi_kwh > $thresholdEnergi;
                    $isStation    = request('highlight_station') == $item->stasiun_pengisian_id;

                    // Anomali dari Big Data
                    $anomalyId      = request('highlight_anomaly');
                    $anomThreshHigh = (float) request('anomaly_threshold', 999999);
                    $anomThreshLow  = (float) request('anomaly_threshold_low', 0);
                    $sev            = request('severity', 'warning');

                    // Highlight SEMUA baris yang energinya anomali (di luar threshold)
                    // Karena sudah difilter per stasiun, semua baris di sini dari stasiun yang sama
                    $isAnomalyRow = $anomalyId && (
                        ($anomThreshHigh < 999999 && $item->energi_kwh > $anomThreshHigh) ||
                        ($anomThreshLow > 0 && $item->energi_kwh < $anomThreshLow)
                    );

                    if ($isAnomalyRow && $sev === 'critical') {
                        $trClass = 'highlight-red';
                        $trStyle = 'border-left:3px solid #ef4444;';
                    } elseif ($isAnomalyRow) {
                        $trClass = 'highlight-yellow';
                        $trStyle = 'border-left:3px solid #f59e0b;';
                    } elseif ($isHighEnergy) {
                        $trClass = 'highlight-blue';
                        $trStyle = 'background:rgba(59,130,246,0.08);border-left:3px solid #3b82f6;';
                    } elseif ($isStation) {
                        $trClass = 'highlight-purple';
                        $trStyle = 'background:rgba(168,85,247,0.08);border-left:3px solid #a855f7;';
                    } elseif ($item->energi_kwh > $thresholdEnergi) {
                        $trClass = '';
                        $trStyle = 'background:rgba(239,68,68,0.06);border-left:3px solid #ef4444;';
                    } else {
                        $trClass = '';
                        $trStyle = '';
                    }
                @endphp
                <tr class="{{ $trClass }}" style="{{ $trStyle }}"
                    @if($isAnomalyRow && $item->id == $anomalyId) id="anomaly-row-{{ $item->id }}" @endif>
                    <td style="color:#e2e8f0">{{ ($log->firstItem() ?? 0) + $loop->iteration - 1 }}</td>
                    <td>
                        <span style="font-family:'Rajdhani',sans-serif;font-size:15px;font-weight:700;color:var(--cyan)">
                            {{ $item->stasiun->nama_stasiun ?? '-' }}
                        </span>
                    </td>
                    <td style="color:#e2e8f0">{{ $item->stasiun->lokasi ?? '-' }}</td>
                    <td>
                        @php
                            $anomThH    = request('anomaly_threshold') ? (float)request('anomaly_threshold') : $thresholdEnergi;
                            $isHigh     = $item->energi_kwh > $anomThH;
                            $isVeryHigh = $item->energi_kwh > ($anomThH * 1.5);
                            // Warna angka: normal=emas, tinggi=emas terang, sangat tinggi=merah
                            $kwhColor   = $isVeryHigh ? '#f87171' : ($isHigh ? '#FFD700' : '#FFD700');
                        @endphp
                        <span style="font-family:'Rajdhani',sans-serif;font-size:16px;font-weight:700;color:{{ $kwhColor }}">
                            {{ number_format($item->energi_kwh, 1, ',', '.') }}
                        </span>
                        <span style="font-size:11px;color:{{ $isVeryHigh ? '#f87171' : ($isHigh ? '#fbbf24' : 'var(--text-muted)') }}"> kWh</span>
                        @if($isVeryHigh)
                            <span class="badge-anomaly-critical">⚠ SANGAT TINGGI</span>
                        @elseif($isHigh)
                            <span class="badge-anomaly-warning">⚠ TINGGI</span>
                        @endif
                    </td>
                    <td style="font-size:13px">
                        {{ $item->waktu_mulai ? \Carbon\Carbon::parse($item->waktu_mulai)->format('d/m/Y H:i') : '-' }}
                    </td>
                    <td style="font-size:13px">
                        {{ $item->waktu_selesai ? \Carbon\Carbon::parse($item->waktu_selesai)->format('d/m/Y H:i') : '-' }}
                    </td>
                    <td style="font-size:12px;color:var(--text-muted)">
                        @if($item->waktu_mulai && $item->waktu_selesai)
                            {{ \Carbon\Carbon::parse($item->waktu_mulai)->diffForHumans(\Carbon\Carbon::parse($item->waktu_selesai), true) }}
                        @else —
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;justify-content:flex-end">
                            <button class="btn btn-secondary btn-sm"
                                onclick="openEditLog(
                                    {{ $item->id }},
                                    {{ $item->stasiun_pengisian_id }},
                                    {{ $item->energi_kwh }},
                                    '{{ $item->waktu_mulai }}',
                                    '{{ $item->waktu_selesai }}'
                                )">
                                <i class="fas fa-pencil-alt"></i> Edit
                            </button>
                            <form method="POST" action="{{ route('energy-log.destroy', $item->id) }}" style="margin:0">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Hapus log ini?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center;padding:48px;color:var(--text-muted)">
                        <i class="fas fa-bolt" style="font-size:36px;display:block;margin-bottom:12px;opacity:0.2"></i>
                        Belum ada log energi.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($log->hasPages())
    @php
        $current  = $log->currentPage();
        $last     = $log->lastPage();
        $window   = 2; // berapa nomor halaman tampil di kiri-kanan halaman aktif
        $pages    = [];
        // Selalu masukkan halaman pertama
        $pages[] = 1;
        // Range di sekitar current
        for ($i = max(2, $current - $window); $i <= min($last - 1, $current + $window); $i++) {
            $pages[] = $i;
        }
        // Halaman terakhir (kalau >1)
        if ($last > 1) $pages[] = $last;
        $pages = array_values(array_unique($pages));
    @endphp
    <div class="pagination-wrap">
        <span class="page-info">
            Halaman <strong style="color:var(--yellow)">{{ $current }}</strong> dari <strong>{{ $last }}</strong>
            @if($log->firstItem()) · No. {{ $log->firstItem() }}–{{ $log->lastItem() }} dari {{ number_format($log->total(), 0, ',', '.') }} @endif
        </span>
        <div class="pagination">
            {{-- First --}}
            @if($current > 2)
                <a class="page-link" href="{{ $log->url(1) }}" title="Halaman pertama">«</a>
            @else
                <span class="page-link" style="opacity:0.4">«</span>
            @endif

            {{-- Prev --}}
            @if($log->onFirstPage())
                <span class="page-link" style="opacity:0.4">‹ Prev</span>
            @else
                <a class="page-link" href="{{ $log->previousPageUrl() }}">‹ Prev</a>
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
                    <a class="page-link" href="{{ $log->url($p) }}">{{ $p }}</a>
                @endif
                @php $prev = $p; @endphp
            @endforeach

            {{-- Next --}}
            @if($log->hasMorePages())
                <a class="page-link" href="{{ $log->nextPageUrl() }}">Next ›</a>
            @else
                <span class="page-link" style="opacity:0.4">Next ›</span>
            @endif

            {{-- Last --}}
            @if($current < $last - 1)
                <a class="page-link" href="{{ $log->url($last) }}" title="Halaman terakhir">»</a>
            @else
                <span class="page-link" style="opacity:0.4">»</span>
            @endif
        </div>

        {{-- Jump to page (kalau halaman > 7) --}}
        @if($last > 7)
        <form method="GET" action="" id="jumpPageForm" style="display:inline-flex;align-items:center;gap:6px;margin-left:8px"
              onsubmit="return jumpToPage(event, {{ $last }})">
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
                   title="Ketik nomor halaman lalu Enter / klik di luar">
        </form>
        @endif
    </div>
    @endif
</div>

{{-- Modal Tambah --}}
<div class="modal-overlay" id="addLogModal">
    <div class="modal">
        <button class="btn-close-modal" onclick="closeModal('addLogModal')"><i class="fas fa-times"></i></button>
        <div class="modal-title">Tambah Energy Log</div>
        <form method="POST" action="{{ route('energy-log.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Stasiun</label>
                <select name="stasiun_pengisian_id" class="form-control" required>
                    <option value="">-- Pilih Stasiun --</option>
                    @foreach($station as $st)
                        <option value="{{ $st->id }}">{{ $st->nama_stasiun }} - {{ $st->lokasi }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Energi (kWh)</label>
                <input type="number" step="0.01" name="energi_kwh" class="form-control" required min="0" placeholder="contoh: 36.5">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Waktu Mulai</label>
                    <input type="datetime-local" name="waktu_mulai" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Waktu Selesai</label>
                    <input type="datetime-local" name="waktu_selesai" class="form-control" required>
                </div>
            </div>
            <div class="modal-action">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addLogModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Edit --}}
<div class="modal-overlay" id="editLogModal">
    <div class="modal">
        <button class="btn-close-modal" onclick="closeModal('editLogModal')"><i class="fas fa-times"></i></button>
        <div class="modal-title">Edit Energy Log</div>
        <form method="POST" id="editLogForm">
            @csrf @method('PUT')
            <div class="form-group">
                <label class="form-label">Stasiun</label>
                <select name="stasiun_pengisian_id" id="eStasiun" class="form-control" required>
                    @foreach($station as $st)
                        <option value="{{ $st->id }}">{{ $st->nama_stasiun }} - {{ $st->lokasi }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Energi (kWh)</label>
                <input type="number" step="0.01" name="energi_kwh" id="eEnergi" class="form-control" required min="0">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Waktu Mulai</label>
                    <input type="datetime-local" name="waktu_mulai" id="eMulai" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Waktu Selesai</label>
                    <input type="datetime-local" name="waktu_selesai" id="eSelesai" class="form-control" required>
                </div>
            </div>
            <div class="modal-action">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editLogModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Auto-scroll ke baris anomali pertama jika ada
document.addEventListener('DOMContentLoaded', function () {
    const firstAnomaly = document.querySelector('[id^="anomaly-row-"]');
    if (firstAnomaly) {
        setTimeout(() => {
            firstAnomaly.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 400);
        return;
    }
    // Auto-scroll ke baris pertama yang sedang di-highlight (info/purple/red/yellow)
    const firstHighlight = document.querySelector(
        'tr.highlight-blue, tr.highlight-purple, tr.highlight-red, tr.highlight-yellow'
    );
    if (firstHighlight) {
        setTimeout(() => {
            firstHighlight.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 400);
    }
});

document.getElementById('searchInput').addEventListener('input', function () {
    const q    = this.value.toLowerCase();
    const rows = document.querySelectorAll('#logTableBody tr');
    rows.forEach(function (row) {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

function openEditLog(id, stasiunId, energi, mulai, selesai) {
    document.getElementById('editLogForm').action = `/energy-log/${id}`;
    document.getElementById('eStasiun').value = stasiunId;
    document.getElementById('eEnergi').value  = energi;
    document.getElementById('eMulai').value   = mulai ? mulai.replace(' ','T').substring(0,16) : '';
    document.getElementById('eSelesai').value = selesai ? selesai.replace(' ','T').substring(0,16) : '';
    openModal('editLogModal');
}

// Validasi jump-to-page (cegah keluar batas)
function jumpToPage(e, lastPage) {
    const input = e.target.querySelector('input[name="page"]');
    const v = parseInt(input.value, 10);
    if (isNaN(v) || v < 1) { input.value = 1; return false; }
    if (v > lastPage)      { input.value = lastPage; }
    return true;
}
</script>
@endpush