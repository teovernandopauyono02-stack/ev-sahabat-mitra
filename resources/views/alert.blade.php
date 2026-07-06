@extends('layout.app')
@section('title', 'Alert - EV Smart Energy')
@section('page-title', 'Alert')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/alert.css') }}">
@endpush

@section('content')

{{-- Page Header --}}
<div class="alert-header">
    <div>
        <h2 style="display:flex;align-items:center;gap:12px;font-family:'Rajdhani',sans-serif;font-size:24px;font-weight:700;margin-bottom:4px">
            <span style="display:inline-flex;align-items:center;justify-content:center;width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#dc2626,#ef4444);box-shadow:0 0 18px rgba(239,68,68,0.4);flex-shrink:0;animation:pageIconPulse 3s ease-in-out infinite">
                <i class="fas fa-bell" style="font-size:20px;color:#fff"></i>
            </span>
            <span style="background:linear-gradient(135deg,#f1f5f9,#f87171);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent">
                Sistem Alert
            </span>
        </h2>
        <p style="font-size:12px;color:#94a3b8">Notifikasi dan peringatan kondisi stasiun pengisian EV</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <a href="{{ route('alert.export-pdf') }}" target="_blank" class="btn-refresh" style="background:rgba(239,68,68,0.1);color:#ef4444;border-color:rgba(239,68,68,0.3);text-decoration:none">
            <i class="fas fa-file-pdf"></i> Export PDF
        </a>
        @if(($totalResolved ?? 0) > 0)
            <form method="POST" action="{{ route('alert.reset-resolved') }}" style="display:inline">
                @csrf
                <button type="submit" class="btn-refresh" style="background:rgba(245,158,11,0.1);color:#f59e0b;border-color:rgba(245,158,11,0.3)" title="Pulihkan {{ $totalResolved }} alert yang sudah diselesaikan">
                    <i class="fas fa-undo"></i> Pulihkan ({{ $totalResolved }})
                </button>
            </form>
        @endif
        <button class="btn-refresh" onclick="window.location.reload()">
            <i class="fas fa-sync-alt"></i> Refresh
        </button>
    </div>
</div>

{{-- Summary Stats --}}
<div class="alert-stat-grid">
    <div class="alert-stat-card a-cyan">
        <i class="fas fa-bell alert-stat-icon"></i>
        <div>
            <div class="alert-stat-val">{{ $totalAlert }}</div>
            <div class="alert-stat-label">Total Alert</div>
        </div>
    </div>
    <div class="alert-stat-card a-red">
        <i class="fas fa-exclamation-circle alert-stat-icon"></i>
        <div>
            <div class="alert-stat-val">{{ $totalDanger }}</div>
            <div class="alert-stat-label">Critical</div>
        </div>
    </div>
    <div class="alert-stat-card a-yellow">
        <i class="fas fa-exclamation-triangle alert-stat-icon"></i>
        <div>
            <div class="alert-stat-val">{{ $totalWarning }}</div>
            <div class="alert-stat-label">Warning</div>
        </div>
    </div>
    <div class="alert-stat-card a-blue">
        <i class="fas fa-info-circle alert-stat-icon"></i>
        <div>
            <div class="alert-stat-val">{{ $totalInfo }}</div>
            <div class="alert-stat-label">Info</div>
        </div>
    </div>
    @if(($totalPurple ?? 0) > 0)
    <div class="alert-stat-card a-purple">
        <i class="fas fa-clock alert-stat-icon"></i>
        <div>
            <div class="alert-stat-val">{{ $totalPurple ?? 0 }}</div>
            <div class="alert-stat-label">Tidak Aktif</div>
        </div>
    </div>
    @endif
    @if(($totalSecurity ?? 0) > 0)
    <div class="alert-stat-card a-security">
        <i class="fas fa-user-shield alert-stat-icon"></i>
        <div>
            <div class="alert-stat-val">{{ $totalSecurity ?? 0 }}</div>
            <div class="alert-stat-label">Keamanan</div>
        </div>
    </div>
    @endif
</div>

{{-- Filter Tabs --}}
<div class="alert-tab-wrap">
    <button class="alert-tab active" onclick="filterAlert('all', this)">
        Semua <span class="tab-count">{{ $totalAlert }}</span>
    </button>
    <button class="alert-tab" onclick="filterAlert('danger', this)">
        Critical <span class="tab-count danger">{{ $totalDanger }}</span>
    </button>
    <button class="alert-tab" onclick="filterAlert('warning', this)">
        Warning <span class="tab-count warning">{{ $totalWarning }}</span>
    </button>
    <button class="alert-tab" onclick="filterAlert('info', this)">
        Info <span class="tab-count info">{{ $totalInfo }}</span>
    </button>
    @if(($totalPurple ?? 0) > 0)
    <button class="alert-tab" onclick="filterAlert('purple', this)">
        Tidak Aktif <span class="tab-count purple">{{ $totalPurple ?? 0 }}</span>
    </button>
    @endif
    @if(($totalSecurity ?? 0) > 0)
    <button class="alert-tab" onclick="filterAlert('security', this)">
        Keamanan <span class="tab-count security">{{ $totalSecurity ?? 0 }}</span>
    </button>
    @endif
</div>

{{-- Alert List --}}
<div class="alert-list" id="alertList">
    @forelse($alert as $a)
    <div class="alert-item alert-{{ $a['type'] }}" data-type="{{ $a['type'] }}" data-key="{{ $a['key'] ?? '' }}" id="alert-{{ $a['key'] ?? $loop->index }}">
        @php
            // Bangun URL berdasarkan tipe alert
            $detailUrl = $a['link'] ?? '#';
            if ($a['type'] === 'info') {
                // Energi Tinggi → Energy Log dengan highlight biru
                $detailUrl = route('energy-log.index', ['highlight' => 'high-energy']);
            } elseif ($a['type'] === 'purple') {
                // Tidak Ada Aktivitas → Energy Log dengan highlight stasiun ungu
                $detailUrl = route('energy-log.index') . '?highlight_station=' . ($a['station_id'] ?? '');
            } elseif (!empty($a['station_id'])) {
                // Danger/Warning → Station dengan highlight ID
                $detailUrl = route('station.index') . '?highlight=' . $a['station_id'];
            }
        @endphp
        <a href="{{ $detailUrl }}" style="display:contents;text-decoration:none;color:inherit">
            <div class="alert-item-icon">
                <i class="{{ $a['icon'] }}"></i>
            </div>
            <div class="alert-item-body">
                <div class="alert-item-title">{{ $a['title'] }}</div>
                <div class="alert-item-message">{{ $a['message'] }}</div>
                <div class="alert-item-meta">
                    <span><i class="fas fa-charging-station"></i> {{ $a['station'] }}</span>
                    <span><i class="fas fa-clock"></i> {{ $a['time'] }}</span>
                </div>
            </div>
            <div class="alert-item-badge">
                @if($a['type'] === 'danger')
                    <span class="alert-badge danger">Critical</span>
                @elseif($a['type'] === 'warning')
                    <span class="alert-badge warning">Warning</span>
                @elseif($a['type'] === 'purple')
                    <span class="alert-badge purple">Tidak Aktif</span>
                @elseif($a['type'] === 'security')
                    <span class="alert-badge security">Keamanan</span>
                @else
                    <span class="alert-badge info">Info</span>
                @endif
            </div>
        </a>
        <button type="button" class="alert-resolve-btn" onclick="resolveAlert(event, '{{ $a['key'] ?? '' }}')" title="Tandai alert ini sudah ditangani">
            <i class="fas fa-check"></i>
            <span class="resolve-label">Done</span>
        </button>
    </div>
    @empty
    <div class="no-alert">
        <i class="fas fa-check-circle"></i>
        <div class="no-alert-title">Semua Baik!</div>
        <div class="no-alert-sub">Tidak ada peringatan atau notifikasi saat ini.</div>
    </div>
    @endforelse
</div>

@endsection

@push('scripts')
<script>
function filterAlert(type, btn) {
    document.querySelectorAll('.alert-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.alert-item').forEach(item => {
        if (type === 'all' || item.dataset.type === type) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

async function resolveAlert(event, key) {
    event.stopPropagation();
    event.preventDefault();
    if (!key) return;

    const item = document.getElementById('alert-' + key);
    if (!item) return;

    try {
        const res = await fetch('{{ route("alert.resolve") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept':       'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ key: key })
        });
        const j = await res.json();
        if (j.success) {
            item.classList.add('fading-out');
            setTimeout(() => {
                item.remove();
                // Cek kalau gak ada alert tersisa, refresh untuk update counter
                if (!document.querySelector('.alert-item:not(.fading-out)')) {
                    window.location.reload();
                }
            }, 300);
        } else {
            alert('Gagal menandai alert: ' + (j.message ?? 'Unknown error'));
        }
    } catch (e) {
        alert('Error: ' + e.message);
    }
}
</script>
@endpush