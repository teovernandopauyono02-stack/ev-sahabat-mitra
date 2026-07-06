<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RiwayatPengisian;
use App\Models\StasiunPengisian;
use App\Models\AuditLog;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Format tanggal jadi "1 Jan 2026" (Bahasa Indonesia singkat).
     */
    private function formatTanggalID($date): string
    {
        $bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        $d = Carbon::parse($date);
        return $d->day . ' ' . $bulan[$d->month - 1] . ' ' . $d->year;
    }

    /**
     * Format teks periode laporan, contoh: "1 Jan 2026 s/d 31 Des 2026".
     * Kalau tanggal mulai = tanggal selesai, cukup tampilkan satu tanggal.
     */
    private function formatPeriode($startDate, $endDate): string
    {
        $start = Carbon::parse($startDate);
        $end   = Carbon::parse($endDate);

        if ($start->isSameDay($end)) {
            return $this->formatTanggalID($start);
        }
        return $this->formatTanggalID($start) . ' s/d ' . $this->formatTanggalID($end);
    }

    public function index(Request $request)
    {
        // Default: 1 Januari tahun ini s/d 31 Desember tahun ini (otomatis ikut tahun berjalan)
        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate   = $request->get('end_date',   Carbon::now()->endOfYear()->format('Y-m-d'));
        $stasiunId = $request->get('stasiun_id', null);

        $periodeText = $this->formatPeriode($startDate, $endDate);

        $query = RiwayatPengisian::with('stasiun')
            ->whereBetween('waktu_mulai', [
                $startDate . ' 00:00:00',
                $endDate   . ' 23:59:59'
            ]);

        if ($stasiunId) {
            $query->where('stasiun_pengisian_id', $stasiunId);
        }

        $report      = (clone $query)->latest('waktu_mulai')->paginate(20)->appends($request->all());
        $station     = StasiunPengisian::all();
        $totalEnergi = (clone $query)->sum('energi_kwh');
        $totalSesi   = (clone $query)->count();
        $avgEnergi   = $totalSesi > 0 ? round($totalEnergi / $totalSesi, 2) : 0;

        $trendRaw = (clone $query)
            ->selectRaw('DATE(waktu_mulai) as tgl, SUM(energi_kwh) as total')
            ->groupBy('tgl')
            ->orderBy('tgl')
            ->get();

        $trendData = $trendRaw->mapWithKeys(fn($t) =>
            [Carbon::parse($t->tgl)->format('d/m') => (float) $t->total]
        );

        $perStation = RiwayatPengisian::with('stasiun')
            ->whereBetween('waktu_mulai', [
                $startDate . ' 00:00:00',
                $endDate   . ' 23:59:59'
            ])
            ->when($stasiunId, fn($q) => $q->where('stasiun_pengisian_id', $stasiunId))
            ->selectRaw('stasiun_pengisian_id, SUM(energi_kwh) as total_energi, COUNT(*) as total_sesi')
            ->groupBy('stasiun_pengisian_id')
            ->get();

        return view('report', compact(
            'report', 'station', 'totalEnergi', 'totalSesi',
            'avgEnergi', 'perStation', 'startDate', 'endDate',
            'stasiunId', 'trendData', 'periodeText'
        ));
    }

    // =============================================
    // Export PDF
    // =============================================
    public function exportPdf(Request $request)
    {
        $now       = now()->setTimezone('Asia/Jakarta');
        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate   = $request->get('end_date',   Carbon::now()->endOfYear()->format('Y-m-d'));
        $stasiunId = $request->get('stasiun_id', null);

        $periodeText = $this->formatPeriode($startDate, $endDate);

        AuditLog::record(
            'Export Laporan PDF',
            'Report',
            "Export laporan periode {$periodeText}" . ($stasiunId ? " (stasiun ID {$stasiunId})" : ''),
            'info'
        );

        $log = RiwayatPengisian::with('stasiun')
            ->whereBetween('waktu_mulai', [
                $startDate . ' 00:00:00',
                $endDate   . ' 23:59:59'
            ])
            ->when($stasiunId, fn($q) => $q->where('stasiun_pengisian_id', $stasiunId))
            ->latest('waktu_mulai')
            ->get();

        $totalEnergi = $log->sum('energi_kwh');
        $totalSesi   = $log->count();
        $avgEnergi   = $totalSesi > 0 ? round($totalEnergi / $totalSesi, 2) : 0;
        $tanggal     = $now->translatedFormat('d F Y');
        $jam         = $now->format('H:i') . ' WIB';
        $tanggalJam  = $now->format('d/m/Y H:i:s') . ' WIB';

        $rows = '';
        foreach ($log as $i => $item) {
            $durasi = '—';
            if ($item->waktu_mulai && $item->waktu_selesai) {
                $mnt    = Carbon::parse($item->waktu_mulai)
                               ->diffInMinutes(Carbon::parse($item->waktu_selesai));
                $durasi = $mnt >= 60
                    ? floor($mnt / 60) . 'j ' . ($mnt % 60) . 'm'
                    : $mnt . ' mnt';
            }
            $namaStasiun = htmlspecialchars($item->stasiun->nama_stasiun ?? '—', ENT_QUOTES, 'UTF-8');
            $lokasi      = htmlspecialchars($item->stasiun->lokasi ?? '—', ENT_QUOTES, 'UTF-8');
            $mulai       = $item->waktu_mulai   ? Carbon::parse($item->waktu_mulai)->format('d/m/Y H:i')   : '—';
            $selesai     = $item->waktu_selesai ? Carbon::parse($item->waktu_selesai)->format('d/m/Y H:i') : '—';
            $kwh         = number_format((float) $item->energi_kwh, 1);
            $stripe      = $i % 2 !== 0 ? 'background:#f8fafc;' : '';

            $rows .= '<tr style="' . $stripe . '">
              <td>' . ($i + 1) . '</td>
              <td>' . $namaStasiun . '</td>
              <td>' . $lokasi . '</td>
              <td>' . $kwh . ' kWh</td>
              <td>' . $mulai . '</td>
              <td>' . $selesai . '</td>
              <td>' . $durasi . '</td>
            </tr>';
        }

        if ($rows === '') {
            $rows = '<tr><td colspan="7">Tidak ada data pada periode ini</td></tr>';
        }

        $html = '<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Energi — EV Smart Energy</title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family:Arial,sans-serif; font-size:11px; color:#1e293b; background:#f1f5f9; }

  .print-toolbar {
    background:#0d1b3e; padding:10px 30px;
    display:flex; align-items:center; justify-content:space-between;
    position:sticky; top:0; z-index:100;
    box-shadow:0 2px 8px rgba(0,0,0,0.3);
  }
  .print-toolbar .info { color:#94a3b8; font-size:11px; }
  .print-toolbar .info strong { color:#FFD700; }
  .btn-print {
    background:linear-gradient(135deg,#FFD700,#FFA500);
    color:#0d1b3e; border:none; padding:8px 24px;
    border-radius:6px; font-size:13px; font-weight:bold; cursor:pointer;
  }
  .btn-back {
    background:transparent; border:1px solid #334155;
    color:#94a3b8; padding:8px 16px; border-radius:6px;
    font-size:13px; cursor:pointer; margin-left:8px;
  }

  .wrap {
    max-width:1000px; margin:20px auto; background:#fff;
    border-radius:10px; overflow:hidden;
    box-shadow:0 4px 20px rgba(0,0,0,0.1);
  }

  .hdr {
    background:linear-gradient(135deg,#0d1b3e 0%,#1a4f9c 100%);
    color:white; padding:24px 30px 20px;
    border-bottom:3px solid #FFD700;
    display:flex; justify-content:space-between; align-items:flex-start;
  }
  .hdr h1 { font-size:20px; font-weight:bold; }
  .hdr h1 span { color:#FFD700; }
  .hdr .sub { font-size:11px; opacity:0.8; margin-top:4px; }
  .hdr .meta { text-align:right; font-size:10px; opacity:0.85; line-height:1.8; }
  .hdr .meta .tgl { font-size:13px; font-weight:bold; color:#FFD700; }

  .sumbar {
    background:#f8fafc; border-bottom:1px solid #e2e8f0;
    padding:10px 30px; display:flex; gap:24px;
    font-size:10px; color:#64748b;
  }
  .sumbar strong { color:#1e293b; }

  .cards { display:flex; border-bottom:2px solid #e2e8f0; }
  .card {
    flex:1; padding:18px 12px; text-align:center;
    border-right:1px solid #e2e8f0;
  }
  .card:last-child { border-right:none; }
  .card .val { font-size:30px; font-weight:bold; line-height:1; }
  .card .lbl { font-size:9px; color:#94a3b8; margin-top:5px; text-transform:uppercase; letter-spacing:0.5px; }

  .sec { padding:20px 30px 26px; }
  .sec-title {
    font-size:12px; font-weight:bold; color:#fff;
    background:linear-gradient(90deg,#1a4f9c,#0e9cdd);
    padding:6px 14px; border-radius:4px;
    margin-bottom:14px; display:inline-block;
  }

  table { width:100%; border-collapse:collapse; font-size:10px; }
  th {
    background:#0f172a; color:white;
    padding:10px 8px; font-size:10px; font-weight:bold;
    text-align:center; border:1px solid #1e3a6e;
  }
  td {
    padding:9px 8px; border-bottom:1px solid #f1f5f9;
    vertical-align:middle; text-align:center;
  }
  td:nth-child(2) { font-weight:bold; color:#1d4ed8; }
  td:nth-child(4) { font-weight:bold; color:#d97706; }
  td:nth-child(1) { color:#94a3b8; }
  td:nth-child(3) { color:#475569; }
  td:nth-child(5), td:nth-child(6) { color:#475569; }
  td:nth-child(7) { color:#64748b; }
  tr:hover td { background:#eff6ff !important; }

  .ftr {
    background:#f8fafc; border-top:2px solid #e2e8f0;
    padding:12px 30px; text-align:center;
    font-size:9px; color:#94a3b8;
  }
  .ftr strong { color:#1a4f9c; }

  @media print {
    body { background:#fff; }
    .print-toolbar { display:none !important; }
    .wrap { margin:0; box-shadow:none; border-radius:0; }
    tr:hover td { background:inherit !important; }
    @page { margin:10mm 8mm; size:A4 portrait; }
    -webkit-print-color-adjust:exact;
    print-color-adjust:exact;
  }
</style>
</head>
<body>

<div class="print-toolbar">
  <div class="info">
    <strong>⚡ EV Smart Energy</strong> &nbsp;—&nbsp; Laporan Konsumsi Energi &nbsp;|&nbsp;
    Dicetak: <strong>' . $tanggalJam . '</strong>
  </div>
  <div>
    <button class="btn-print" onclick="window.print()">🖨️ &nbsp;Print / Simpan PDF</button>
    <button class="btn-back" onclick="window.history.back()">✕ Tutup</button>
  </div>
</div>

<div class="wrap">

  <div class="hdr">
    <div>
      <h1>⚡ <span>EV Smart Energy</span> — Laporan Konsumsi Energi</h1>
      <div class="sub">EV Charging Station — Laporan Detail Pengisian Energi</div>
    </div>
    <div class="meta">
      <div class="tgl">' . $tanggal . '</div>
      <div>' . $jam . '</div>
      <div>EV Smart Energy Control Center</div>
    </div>
  </div>

  <div class="sumbar">
    <span>Periode: <strong>' . $periodeText . '</strong></span>
    <span>Total Sesi: <strong>' . $totalSesi . '</strong></span>
    <span>Total Energi: <strong style="color:#d97706">' . number_format($totalEnergi, 1) . ' kWh</strong></span>
    <span>Rata-rata: <strong style="color:#16a34a">' . $avgEnergi . ' kWh/sesi</strong></span>
  </div>

  <div class="cards">
    <div class="card">
      <div class="val" style="color:#d97706">' . number_format($totalEnergi, 1) . '</div>
      <div class="lbl">Total Energi (kWh)</div>
    </div>
    <div class="card">
      <div class="val" style="color:#2563eb">' . $totalSesi . '</div>
      <div class="lbl">Total Sesi</div>
    </div>
    <div class="card">
      <div class="val" style="color:#16a34a">' . $avgEnergi . '</div>
      <div class="lbl">Rata-rata kWh / Sesi</div>
    </div>
  </div>

  <div class="sec">
    <div class="sec-title">⚡ Detail Pengisian Energi</div>
    <table>
      <thead>
        <tr>
          <th style="width:35px">No</th>
          <th>Stasiun</th>
          <th>Lokasi</th>
          <th>Energi (kWh)</th>
          <th>Waktu Mulai</th>
          <th>Waktu Selesai</th>
          <th>Durasi</th>
        </tr>
      </thead>
      <tbody>' . $rows . '</tbody>
    </table>
  </div>

  <div class="ftr">
    Dokumen ini digenerate otomatis oleh <strong>EV Smart Energy Control Center</strong>
    &nbsp;|&nbsp; <strong>' . $tanggalJam . '</strong>
    &nbsp;|&nbsp; Total: ' . $totalSesi . ' sesi, ' . number_format($totalEnergi, 1) . ' kWh
  </div>

</div>

<script>
  window.addEventListener("load", function () {
    setTimeout(function () { window.print(); }, 600);
  });
</script>
</body>
</html>';

        return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    // =============================================
    // Export Excel
    // =============================================
    public function exportExcel(Request $request)
    {
        $now       = now()->setTimezone('Asia/Jakarta');
        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate   = $request->get('end_date',   Carbon::now()->endOfYear()->format('Y-m-d'));
        $stasiunId = $request->get('stasiun_id', null);

        $periodeText = $this->formatPeriode($startDate, $endDate);

        AuditLog::record(
            'Export Laporan Excel',
            'Report',
            "Export laporan Excel periode {$periodeText}" . ($stasiunId ? " (stasiun ID {$stasiunId})" : ''),
            'info'
        );

        $log = RiwayatPengisian::with('stasiun')
            ->whereBetween('waktu_mulai', [
                $startDate . ' 00:00:00',
                $endDate   . ' 23:59:59'
            ])
            ->when($stasiunId, fn($q) => $q->where('stasiun_pengisian_id', $stasiunId))
            ->latest('waktu_mulai')
            ->get();

        $totalEnergi  = $log->sum('energi_kwh');
        $totalSesi    = $log->count();
        $avgEnergi    = $totalSesi > 0 ? round($totalEnergi / $totalSesi, 2) : 0;
        $tanggalCetak = $now->format('d/m/Y H:i:s') . ' WIB';
        $fileName     = 'laporan_energi_' . $startDate . '_sd_' . $endDate . '.xls';
        $filePath     = storage_path('app/' . $fileName);

        $clean = function ($str) {
            $str = (string) $str;
            $str = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $str);
            return htmlspecialchars($str, ENT_XML1, 'UTF-8');
        };

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";

        $xml .= '<Styles>';
        $xml .= '<Style ss:ID="H"><Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="14"/><Interior ss:Color="#0d1b3e" ss:Pattern="Solid"/><Alignment ss:Horizontal="Left" ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#FFD700"/></Borders></Style>';
        $xml .= '<Style ss:ID="DATE"><Font ss:Bold="1" ss:Color="#1a4f9c" ss:Size="10"/></Style>';
        $xml .= '<Style ss:ID="SUB"><Font ss:Italic="1" ss:Color="#64748b" ss:Size="10"/></Style>';
        $xml .= '<Style ss:ID="SL_amber"><Font ss:Bold="1" ss:Size="9" ss:Color="#92400e"/><Interior ss:Color="#fffbeb" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center"/></Style>';
        $xml .= '<Style ss:ID="SV_amber"><Font ss:Bold="1" ss:Size="22" ss:Color="#d97706"/><Interior ss:Color="#fffbeb" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><NumberFormat ss:Format="0.0"/></Style>';
        $xml .= '<Style ss:ID="SL_blue"><Font ss:Bold="1" ss:Size="9" ss:Color="#1e40af"/><Interior ss:Color="#eff6ff" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center"/></Style>';
        $xml .= '<Style ss:ID="SV_blue"><Font ss:Bold="1" ss:Size="22" ss:Color="#2563eb"/><Interior ss:Color="#eff6ff" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        $xml .= '<Style ss:ID="SL_green"><Font ss:Bold="1" ss:Size="9" ss:Color="#14532d"/><Interior ss:Color="#f0fdf4" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center"/></Style>';
        $xml .= '<Style ss:ID="SV_green"><Font ss:Bold="1" ss:Size="22" ss:Color="#16a34a"/><Interior ss:Color="#f0fdf4" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><NumberFormat ss:Format="0.00"/></Style>';
        $xml .= '<Style ss:ID="SEC"><Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="11"/><Interior ss:Color="#1a4f9c" ss:Pattern="Solid"/></Style>';
        $xml .= '<Style ss:ID="TH"><Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="10"/><Interior ss:Color="#0f172a" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        $xml .= '<Style ss:ID="C"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:Size="10"/></Style>';
        $xml .= '<Style ss:ID="CB"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:Bold="1" ss:Size="10" ss:Color="#1d4ed8"/></Style>';
        $xml .= '<Style ss:ID="CK"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:Bold="1" ss:Size="10" ss:Color="#d97706"/><NumberFormat ss:Format="0.0"/></Style>';
        $xml .= '<Style ss:ID="SC"><Interior ss:Color="#f8fafc" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:Size="10"/></Style>';
        $xml .= '<Style ss:ID="SCB"><Interior ss:Color="#f8fafc" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:Bold="1" ss:Size="10" ss:Color="#1d4ed8"/></Style>';
        $xml .= '<Style ss:ID="SCK"><Interior ss:Color="#f8fafc" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:Bold="1" ss:Size="10" ss:Color="#d97706"/><NumberFormat ss:Format="0.0"/></Style>';
        $xml .= '<Style ss:ID="FTR"><Font ss:Italic="1" ss:Color="#94a3b8" ss:Size="9"/><Interior ss:Color="#f8fafc" ss:Pattern="Solid"/></Style>';
        $xml .= '</Styles>';

        $xml .= '<Worksheet ss:Name="Laporan Energi">';
        $xml .= '<Table ss:DefaultRowHeight="18">';
        foreach ([35, 130, 140, 100, 130, 130, 80] as $w) {
            $xml .= '<Column ss:Width="' . $w . '"/>';
        }

        $xml .= '<Row ss:Height="34"><Cell ss:MergeAcross="6" ss:StyleID="H"><Data ss:Type="String">⚡ EV SMART ENERGY CONTROL CENTER — Laporan Konsumsi Energi</Data></Cell></Row>';
        $xml .= '<Row ss:Height="18"><Cell ss:MergeAcross="6" ss:StyleID="DATE"><Data ss:Type="String">Dicetak: ' . $tanggalCetak . '</Data></Cell></Row>';
        $xml .= '<Row ss:Height="16"><Cell ss:MergeAcross="6" ss:StyleID="SUB"><Data ss:Type="String">   Periode: ' . $clean($periodeText) . ' | EV Charging Station Monitoring System</Data></Cell></Row>';
        $xml .= '<Row ss:Height="8"><Cell><Data ss:Type="String"></Data></Cell></Row>';

        $xml .= '<Row ss:Height="16">
          <Cell ss:StyleID="SL_amber"><Data ss:Type="String">TOTAL ENERGI (kWh)</Data></Cell>
          <Cell ss:StyleID="SL_blue"><Data ss:Type="String">TOTAL SESI</Data></Cell>
          <Cell ss:StyleID="SL_green"><Data ss:Type="String">RATA-RATA kWh/SESI</Data></Cell>
          <Cell><Data ss:Type="String"></Data></Cell>
        </Row>';
        $xml .= '<Row ss:Height="44">
          <Cell ss:StyleID="SV_amber"><Data ss:Type="Number">' . $totalEnergi . '</Data></Cell>
          <Cell ss:StyleID="SV_blue"><Data ss:Type="Number">' . $totalSesi . '</Data></Cell>
          <Cell ss:StyleID="SV_green"><Data ss:Type="Number">' . $avgEnergi . '</Data></Cell>
          <Cell><Data ss:Type="String"></Data></Cell>
        </Row>';

        $xml .= '<Row ss:Height="8"><Cell><Data ss:Type="String"></Data></Cell></Row>';
        $xml .= '<Row ss:Height="22"><Cell ss:MergeAcross="6" ss:StyleID="SEC"><Data ss:Type="String">   ⚡ DETAIL PENGISIAN ENERGI</Data></Cell></Row>';
        $xml .= '<Row ss:Height="22">
          <Cell ss:StyleID="TH"><Data ss:Type="String">No</Data></Cell>
          <Cell ss:StyleID="TH"><Data ss:Type="String">Stasiun</Data></Cell>
          <Cell ss:StyleID="TH"><Data ss:Type="String">Lokasi</Data></Cell>
          <Cell ss:StyleID="TH"><Data ss:Type="String">Energi (kWh)</Data></Cell>
          <Cell ss:StyleID="TH"><Data ss:Type="String">Waktu Mulai</Data></Cell>
          <Cell ss:StyleID="TH"><Data ss:Type="String">Waktu Selesai</Data></Cell>
          <Cell ss:StyleID="TH"><Data ss:Type="String">Durasi</Data></Cell>
        </Row>';

        foreach ($log as $i => $item) {
            $durasi = '-';
            if ($item->waktu_mulai && $item->waktu_selesai) {
                $mnt    = Carbon::parse($item->waktu_mulai)
                               ->diffInMinutes(Carbon::parse($item->waktu_selesai));
                $durasi = $mnt >= 60
                    ? floor($mnt / 60) . 'j ' . ($mnt % 60) . 'm'
                    : $mnt . ' mnt';
            }
            $s  = $i % 2 !== 0;
            $c  = $s ? 'SC'  : 'C';
            $cb = $s ? 'SCB' : 'CB';
            $ck = $s ? 'SCK' : 'CK';

            $nama    = $clean($item->stasiun->nama_stasiun ?? '-');
            $lokasi  = $clean($item->stasiun->lokasi ?? '-');
            $mulai   = $item->waktu_mulai   ? Carbon::parse($item->waktu_mulai)->format('d/m/Y H:i')   : '-';
            $selesai = $item->waktu_selesai ? Carbon::parse($item->waktu_selesai)->format('d/m/Y H:i') : '-';

            $xml .= '<Row ss:Height="19">
              <Cell ss:StyleID="' . $c  . '"><Data ss:Type="Number">' . ($i + 1) . '</Data></Cell>
              <Cell ss:StyleID="' . $cb . '"><Data ss:Type="String">' . $nama . '</Data></Cell>
              <Cell ss:StyleID="' . $c  . '"><Data ss:Type="String">' . $lokasi . '</Data></Cell>
              <Cell ss:StyleID="' . $ck . '"><Data ss:Type="Number">' . $item->energi_kwh . '</Data></Cell>
              <Cell ss:StyleID="' . $c  . '"><Data ss:Type="String">' . $clean($mulai) . '</Data></Cell>
              <Cell ss:StyleID="' . $c  . '"><Data ss:Type="String">' . $clean($selesai) . '</Data></Cell>
              <Cell ss:StyleID="' . $c  . '"><Data ss:Type="String">' . $clean($durasi) . '</Data></Cell>
            </Row>';
        }

        $xml .= '<Row ss:Height="8"><Cell><Data ss:Type="String"></Data></Cell></Row>';
        $xml .= '<Row ss:Height="22">
          <Cell ss:MergeAcross="2" ss:StyleID="H"><Data ss:Type="String">TOTAL ENERGI (kWh)</Data></Cell>
          <Cell ss:StyleID="SV_amber"><Data ss:Type="Number">' . round($totalEnergi, 1) . '</Data></Cell>
          <Cell ss:MergeAcross="2" ss:StyleID="H"><Data ss:Type="String">TOTAL SESI</Data></Cell>
          <Cell ss:StyleID="SV_blue"><Data ss:Type="Number">' . $totalSesi . '</Data></Cell>
        </Row>';
        $xml .= '<Row ss:Height="8"><Cell><Data ss:Type="String"></Data></Cell></Row>';
        $xml .= '<Row ss:Height="16"><Cell ss:MergeAcross="6" ss:StyleID="FTR"><Data ss:Type="String">   EV Smart Energy Control Center © 2026 | Dicetak: ' . $tanggalCetak . ' | Total: ' . $totalSesi . ' sesi, ' . round($totalEnergi, 1) . ' kWh</Data></Cell></Row>';

        $xml .= '</Table></Worksheet></Workbook>';

        file_put_contents($filePath, $xml);

        return response()->download($filePath, $fileName, [
            'Content-Type'        => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ])->deleteFileAfterSend(true);
    }
}