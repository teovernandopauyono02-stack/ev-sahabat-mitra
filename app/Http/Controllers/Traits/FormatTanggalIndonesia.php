<?php

namespace App\Http\Controllers\Traits;

use Carbon\Carbon;

/**
 * Trait FormatTanggalIndonesia — Format tanggal ke Bahasa Indonesia.
 *
 * Digunakan oleh:
 * - ApiintegrationController
 * - ReportController
 * (sebelumnya method ini di-copy-paste manual di kedua controller)
 */
trait FormatTanggalIndonesia
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
}
