@extends('layout.app')

@section('title', 'Akses Ditolak')
@section('page-title', 'Akses Ditolak')

@section('content')
<div style="display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:60vh; text-align:center;">
    <div style="font-size:80px; color:var(--red); margin-bottom:20px; opacity:0.6;">
        <i class="fas fa-lock"></i>
    </div>
    <h2 style="font-family:'Rajdhani',sans-serif; font-size:28px; font-weight:700; margin-bottom:10px;">
        403 — Akses Ditolak
    </h2>
    <p style="color:var(--text-muted); margin-bottom:24px;">
        Anda tidak memiliki izin untuk mengakses halaman ini.
    </p>
    <a href="{{ route('dashboard') }}" class="btn btn-primary">
        <i class="fas fa-home"></i> Kembali ke Dashboard
    </a>
</div>
@endsection
