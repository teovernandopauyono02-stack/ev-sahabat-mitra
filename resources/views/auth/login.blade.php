<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login — EV Smart Energy Control Center</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <style>
        :root {
            --bg:        #070d1a;
            --bg2:       #0b1425;
            --panel:     #0f1c30;
            --border:    rgba(255,255,255,0.07);
            --border2:   rgba(6,182,212,0.18);
            --primary:   #1d4ed8;
            --primary-l: #3b82f6;
            --accent:    #06b6d4;
            --accent-l:  #22d3ee;
            --green:     #10b981;
            --green-l:   #34d399;
            --amber:     #f59e0b;
            --red:       #ef4444;
            --text:      #f1f5f9;
            --text-2:    #94a3b8;
            --text-3:    #475569;
            --card-bg:   rgba(15,28,48,0.85);
        }
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        html, body { height:100%; }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: var(--bg);
            color: var(--text);
            overflow: hidden;
            position: relative;
            max-width: 100vw;
        }

        /* ── CANVAS BACKGROUND ── */
        #bg-canvas {
            position: fixed;
            inset: 0;
            z-index: 0;
        }

        /* ── GRID OVERLAY ── */
        .grid-overlay {
            display: none;
        }

        /* ── CHARGE BAR ── */
        .charge-bar-wrap {
            position: fixed; top:0; left:0; right:0;
            height: 2px; z-index: 200; overflow: hidden;
        }
        .charge-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--accent), var(--primary-l), var(--green));
            background-size: 200% 100%;
            animation: chargeAnim 3.5s ease-in-out infinite, shimmer 2s linear infinite;
            box-shadow: 0 0 10px rgba(6,182,212,0.9);
        }
        @keyframes chargeAnim {
            0%   { width: 0% }
            65%  { width: 80% }
            85%  { width: 92% }
            100% { width: 100% }
        }
        @keyframes shimmer {
            0%   { background-position: 200% 0 }
            100% { background-position: -200% 0 }
        }

        /* ── MAIN LAYOUT ── */
        .layout {
            position: relative;
            z-index: 10;
            height: 100vh;
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 420px;
            align-items: stretch;
            overflow: hidden;
        }

        /* ══════════════════════════════════════
           LEFT PANEL — Dashboard Preview
        ══════════════════════════════════════ */
        .left-panel {
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 0;
            padding: 28px 36px 28px 44px;
            background: transparent;
            overflow: hidden;
            height: 100vh;
            position: relative;
            z-index: 5;
            min-width: 0;
        }
        .left-panel::-webkit-scrollbar { width: 4px; }
        .left-panel::-webkit-scrollbar-thumb { background: rgba(6,182,212,0.2); border-radius: 2px; }
        /* HAPUS pemisah — tidak ada border/fade antara left dan right */
        .left-panel::after { display: none; }

        /* Ambient glow left */
        .left-panel .glow-tl {
            position: absolute;
            top: -120px; left: -80px;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(6,182,212,0.06) 0%, transparent 70%);
            pointer-events: none;
        }

        /* Brand */
        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 10px;
            animation: fadeUp 0.6s ease both;
        }
        .brand-icon {
            width: 46px; height: 46px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--accent), var(--primary));
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; color: #fff;
            box-shadow: 0 0 24px rgba(6,182,212,0.3);
            flex-shrink: 0;
        }
        .brand-text .name {
            font-size: 16px; font-weight: 700; color: #fff; line-height: 1.2;
        }
        .brand-text .name span { color: var(--accent); }
        .brand-text .sub {
            font-size: 10px; color: var(--text-2);
            text-transform: uppercase; letter-spacing: 1.8px; margin-top: 2px;
        }

        /* Hero text */
        .hero {
            flex: 0 0 auto;
            display: flex;
            flex-direction: column;
            padding: 6px 0;
            animation: fadeUp 0.7s ease 0.1s both;
        }
        .hero h1 {
            font-size: 24px;
            font-weight: 800;
            line-height: 1.2;
            color: #fff;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        .hero-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            font-weight: 600;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 18px;
        }
        .hero-label::before {
            content: '';
            width: 24px; height: 1.5px;
            background: var(--accent);
            border-radius: 2px;
        }
        .hero h1 {
            font-size: 38px;
            font-weight: 800;
            line-height: 1.2;
            color: #fff;
            margin-bottom: 18px;
            letter-spacing: -0.5px;
        }
        .hero h1 .highlight {
            background: linear-gradient(90deg, var(--accent-l), var(--primary-l));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .hero p {
            font-size: 12px;
            color: var(--text-2);
            line-height: 1.5;
            max-width: 480px;
            margin-bottom: 10px;
        }

        /* Feature pills */
        .feature-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 10px;
        }
        .pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 7px 14px;
            border-radius: 100px;
            border: 1px solid var(--border);
            background: rgba(255,255,255,0.03);
            font-size: 12px;
            color: var(--text-2);
            backdrop-filter: blur(6px);
            transition: all 0.2s;
        }
        .pill i { font-size: 11px; }
        .pill.cyan  { border-color: rgba(6,182,212,0.25);  color: var(--accent-l);  background: rgba(6,182,212,0.06); }
        .pill.blue  { border-color: rgba(59,130,246,0.25); color: var(--primary-l); background: rgba(59,130,246,0.06); }
        .pill.green { border-color: rgba(16,185,129,0.25); color: var(--green-l);   background: rgba(16,185,129,0.06); }
        .pill.amber { border-color: rgba(245,158,11,0.25); color: var(--amber);     background: rgba(245,158,11,0.06); }

        /* ── SPKLU ILLUSTRATION ── */
        .spklu-wrap {
            width: 100%;
            max-width: 100%;
            margin-bottom: 0;
            animation: fadeUp 0.7s ease 0.15s both;
            position: relative;
            overflow: hidden;
        }
        /* Canvas particle overlay di atas ilustrasi */
        #spklu-particles {
            position: absolute;
            inset: 0;
            z-index: 3;
            pointer-events: none;
            border-radius: 12px;
        }
        .spklu-wrap svg { width: 100%; height: auto; display: block; }

        /* Electric flow animation along cable */
        .cable-flow {
            stroke-dasharray: 10 8;
            animation: cableFlow 1.2s linear infinite;
        }
        .cable-flow-2 {
            stroke-dasharray: 6 10;
            animation: cableFlow 1.8s linear infinite reverse;
        }
        @keyframes cableFlow { to { stroke-dashoffset: -36; } }

        /* Bolt icon pulse */
        .bolt-pulse { animation: boltPulse 1.4s ease-in-out infinite; }
        @keyframes boltPulse {
            0%,100% { opacity: 0.5; transform: scale(1); }
            50%     { opacity: 1;   transform: scale(1.15); }
        }

        /* Screen flicker */
        .screen-glow { animation: screenGlow 2.5s ease-in-out infinite; }
        @keyframes screenGlow {
            0%,100% { opacity: 0.85; }
            50%     { opacity: 1; filter: brightness(1.2); }
        }

        /* Energy particles floating up */
        .e-particle { animation: eFloat linear infinite; }
        @keyframes eFloat {
            0%   { transform: translateY(0) scale(1);   opacity: 0.8; }
            100% { transform: translateY(-28px) scale(0.4); opacity: 0; }
        }

        /* Car charge indicator bar */
        .charge-fill { animation: chargeFill 3s ease-in-out infinite alternate; }
        @keyframes chargeFill {
            /* Bar layar charger: x=73, max width=44 (batas kanan=117) */
            /* 72% dari 44px = ~31.7px → max 32px agar tidak melewati batas */
            0%   { width: 20px; }
            100% { width: 32px; }
        }
        /* Animasi bar baterai mobil — terpisah agar bisa beda batas */
        .car-charge-fill { animation: carChargeFill 3s ease-in-out infinite alternate; }
        @keyframes carChargeFill {
            /* Bar mobil: x=343, batas kanan=411 (terminal di x=412) */
            /* Max width = 68px agar tidak melewati terminal */
            0%   { width: 38px; }
            100% { width: 65px; }
        }

        /* Halo ring around charger */
        .halo-ring { animation: haloExpand 2s ease-out infinite; }
        @keyframes haloExpand {
            0%   { r: 18; opacity: 0.5; }
            100% { r: 32; opacity: 0; }
        }

        /* ── DASHBOARD MINI CARDS ── */
        .dash-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-bottom: 32px;
            animation: fadeUp 0.7s ease 0.2s both;
        }
        .dash-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px 18px;
            position: relative;
            overflow: hidden;
            transition: border-color 0.3s, transform 0.3s;
        }
        .dash-card:hover {
            border-color: var(--border2);
            transform: translateY(-2px);
        }
        .dash-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            border-radius: 14px 14px 0 0;
        }
        .dash-card.c1::before { background: linear-gradient(90deg, var(--accent), transparent); }
        .dash-card.c2::before { background: linear-gradient(90deg, var(--green), transparent); }
        .dash-card.c3::before { background: linear-gradient(90deg, var(--amber), transparent); }
        .dash-card .dc-label {
            font-size: 10px; font-weight: 600;
            color: var(--text-3);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        .dash-card .dc-value {
            font-size: 26px; font-weight: 800;
            line-height: 1;
            margin-bottom: 6px;
        }
        .dash-card.c1 .dc-value { color: var(--accent-l); }
        .dash-card.c2 .dc-value { color: var(--green-l); }
        .dash-card.c3 .dc-value { color: var(--amber); }
        .dash-card .dc-unit { font-size: 11px; font-weight: 400; color: var(--text-3); margin-left: 3px; }
        .dash-card .dc-trend {
            display: flex; align-items: center; gap: 5px;
            font-size: 11px; color: var(--green-l);
        }
        .dash-card .dc-trend.down { color: var(--red); }
        .dash-card .dc-icon {
            position: absolute;
            top: 14px; right: 14px;
            font-size: 18px;
            opacity: 0.15;
        }
        .dash-card.c1 .dc-icon { color: var(--accent); }
        .dash-card.c2 .dc-icon { color: var(--green); }
        .dash-card.c3 .dc-icon { color: var(--amber); }

        /* ── CHART AREA ── */
        .chart-wrap {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 18px 20px;
            animation: fadeUp 0.7s ease 0.3s both;
        }
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
        }
        .chart-title {
            font-size: 12px; font-weight: 600;
            color: var(--text-2);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .chart-badge {
            display: flex; align-items: center; gap: 5px;
            font-size: 11px; color: var(--green-l);
            background: rgba(16,185,129,0.1);
            border: 1px solid rgba(16,185,129,0.2);
            padding: 3px 9px; border-radius: 100px;
        }
        .chart-badge::before {
            content: '';
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--green);
            animation: pulse 1.5s ease-in-out infinite;
        }
        @keyframes pulse {
            0%,100% { opacity:1; transform:scale(1); }
            50%     { opacity:0.5; transform:scale(0.8); }
        }
        #energyChart { width:100%; height:80px; display:block; }

        /* ── LIVE METRICS ROW ── */
        .live-metrics-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 12px;
            animation: fadeUp 0.7s ease 0.35s both;
        }
        .lm-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px 12px;
            display: flex;
            align-items: center;
            gap: 9px;
            position: relative;
            overflow: hidden;
            transition: border-color 0.2s;
        }
        .lm-card:hover { border-color: rgba(6,182,212,0.3); }
        .lm-icon {
            width: 32px; height: 32px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; flex-shrink: 0;
        }
        .lm-body { flex: 1; min-width: 0; }
        .lm-label {
            font-size: 9px; font-weight: 600;
            color: var(--text-3);
            text-transform: uppercase; letter-spacing: 0.8px;
            margin-bottom: 2px;
        }
        .lm-val {
            font-family: 'Rajdhani', sans-serif;
            font-size: 15px; font-weight: 700;
            color: var(--accent-l); line-height: 1;
        }
        .lm-val span { font-size: 10px; font-weight: 500; color: var(--text-3); margin-left: 2px; }
        .lm-pulse {
            position: absolute; top: 8px; right: 8px;
            width: 6px; height: 6px;
            border-radius: 50%;
            background: #06b6d4;
            animation: pulse 2s ease-in-out infinite;
        }

        /* ── SCROLLING TICKER ── */
        .ev-ticker {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 7px 12px;
            overflow: hidden;
            margin-bottom: 14px;
            animation: fadeUp 0.7s ease 0.4s both;
        }
        .ticker-label {
            font-size: 9px; font-weight: 700;
            color: var(--accent);
            text-transform: uppercase; letter-spacing: 1px;
            white-space: nowrap;
            display: flex; align-items: center; gap: 5px;
            flex-shrink: 0;
            padding-right: 10px;
            border-right: 1px solid var(--border);
        }
        .ticker-track {
            flex: 1; overflow: hidden;
        }
        .ticker-inner {
            display: flex; gap: 40px;
            white-space: nowrap;
            animation: tickerScroll 30s linear infinite;
            font-size: 11px; color: var(--text-2);
        }
        .ticker-inner span { flex-shrink: 0; }
        @keyframes tickerScroll {
            0%   { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        .ev-ticker:hover .ticker-inner { animation-play-state: paused; }

        /* Light mode overrides */
        body.login-light .lm-card {
            background: rgba(255,255,255,0.7);
            border-color: rgba(29,78,216,0.1);
        }
        body.login-light .lm-label { color: #64748b; }
        body.login-light .ev-ticker {
            background: rgba(255,255,255,0.7);
            border-color: rgba(29,78,216,0.1);
        }
        body.login-light .ticker-label { color: #1d4ed8; border-color: rgba(29,78,216,0.1); }
        body.login-light .ticker-inner { color: #475569; }

        /* ── BOTTOM STATUS BAR ── */
        .status-bar {
            display: flex;
            align-items: center;
            gap: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            animation: fadeUp 0.7s ease 0.4s both;
        }
        .status-item {
            display: flex; align-items: center; gap: 7px;
            font-size: 11px; color: var(--text-3);
        }
        .status-dot {
            width: 7px; height: 7px;
            border-radius: 50%;
        }
        .status-dot.online  { background: var(--green); box-shadow: 0 0 6px var(--green); animation: pulse 2s infinite; }
        .status-dot.warning { background: var(--amber); box-shadow: 0 0 6px var(--amber); }
        .status-dot.offline { background: var(--red); }
        .status-item strong { color: var(--text-2); font-weight: 600; }
        .status-divider { width: 1px; height: 16px; background: var(--border); }
        .status-time { margin-left: auto; font-size: 11px; color: var(--text-3); font-variant-numeric: tabular-nums; }

        /* Canvas particle khusus left panel */
        #lp-canvas {
            position: absolute;
            inset: 0;
            z-index: 0;
            pointer-events: none;
        }
        /* Glow blob kanan bawah left panel — cyan */
        .lp-glow-br {
            position: absolute;
            bottom: -100px; right: -80px;
            width: 450px; height: 450px;
            background: radial-gradient(circle, rgba(6,182,212,0.14) 0%, transparent 65%);
            border-radius: 50%;
            pointer-events: none;
            animation: blobFloat2 11s ease-in-out infinite;
        }
        /* Glow blob tengah left panel — biru */
        .lp-glow-mid {
            position: absolute;
            top: 45%; left: 55%;
            transform: translate(-50%, -50%);
            width: 350px; height: 350px;
            background: radial-gradient(circle, rgba(29,78,216,0.1) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            animation: blobFloat1 14s ease-in-out infinite reverse;
        }
        /* Semua konten left panel harus di atas canvas */
        .left-panel > *:not(#lp-canvas):not(.lp-glow-br):not(.lp-glow-mid):not(.glow-tl) {
            position: relative;
            z-index: 2;
        }

        /* ══════════════════════════════════════
           RIGHT PANEL — Login Form
        ══════════════════════════════════════ */
        .right-panel {
            position: relative;
            z-index: 20;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 0 44px;
            background: transparent;
            overflow: hidden;
            min-width: 0;
        }
        /* Hapus pseudo-element lama — glow sudah dari canvas global */
        .right-panel::before { display: none; }
        .right-panel::after  { display: none; }
        @keyframes blobFloat1 {
            0%,100% { transform: translate(0,0) scale(1); }
            33%     { transform: translate(-20px, 30px) scale(1.05); }
            66%     { transform: translate(15px, -20px) scale(0.95); }
        }
        @keyframes blobFloat2 {
            0%,100% { transform: translate(0,0) scale(1); }
            40%     { transform: translate(25px, -35px) scale(1.08); }
            70%     { transform: translate(-15px, 20px) scale(0.92); }
        }
        /* Canvas particle khusus right panel */
        #rp-canvas {
            position: absolute;
            inset: 0;
            z-index: 0;
            pointer-events: none;
        }
        /* Glow hijau tengah */
        .rp-glow-mid {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(16,185,129,0.07) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            animation: blobFloat1 12s ease-in-out infinite reverse;
        }

        .login-box {
            width: 100%;
            max-width: 360px;
            position: relative;
            z-index: 2;
            animation: cardIn 0.8s cubic-bezier(0.34,1.4,0.64,1) 0.2s both;
        }
        @keyframes cardIn {
            from { opacity:0; transform:translateY(30px) scale(0.97); }
            to   { opacity:1; transform:translateY(0) scale(1); }
        }

        /* Login header */
        .login-logo-wrap {
            display: flex;
            justify-content: center;
            margin-bottom: 28px;
        }
        .login-logo {
            width: 60px; height: 60px;
            border-radius: 18px;
            background: linear-gradient(135deg, var(--accent), var(--primary));
            display: flex; align-items: center; justify-content: center;
            font-size: 26px; color: #fff;
            box-shadow: 0 0 0 1px rgba(6,182,212,0.2), 0 16px 40px rgba(6,182,212,0.2);
            position: relative;
            animation: logoPulse 3s ease-in-out infinite;
        }
        @keyframes logoPulse {
            0%,100% { box-shadow: 0 0 0 1px rgba(6,182,212,0.2), 0 16px 40px rgba(6,182,212,0.2); }
            50%     { box-shadow: 0 0 0 1px rgba(6,182,212,0.4), 0 16px 50px rgba(6,182,212,0.35); }
        }
        .login-logo::after {
            content: '';
            position: absolute;
            inset: -6px;
            border-radius: 24px;
            border: 1px solid rgba(6,182,212,0.2);
            animation: ringExpand 3s ease-in-out infinite;
        }
        @keyframes ringExpand {
            0%,100% { transform:scale(1); opacity:0.6; }
            50%     { transform:scale(1.08); opacity:0; }
        }

        .login-heading {
            text-align: center;
            margin-bottom: 32px;
        }
        .login-heading h2 {
            font-size: 22px; font-weight: 800;
            color: #fff; margin-bottom: 6px;
            letter-spacing: -0.3px;
        }
        .login-heading p {
            font-size: 13px; color: var(--text-2);
            line-height: 1.6;
        }

        /* Subtle separator — no lines */
        .form-separator {
            margin-bottom: 22px;
        }
        .form-separator span {
            display: block;
            font-size: 11px; color: var(--text-3);
            text-transform: uppercase; letter-spacing: 2px;
            text-align: center;
        }

        /* Form */
        .form-group { margin-bottom: 16px; }
        .form-label {
            display: block;
            font-size: 11px; font-weight: 600;
            color: var(--text-2);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 7px;
        }
        .input-wrap { position: relative; }
        .input-icon {
            position: absolute;
            left: 14px; top: 50%;
            transform: translateY(-50%);
            color: var(--text-3);
            font-size: 13px;
            transition: color 0.2s;
            pointer-events: none;
        }
        .form-input {
            width: 100%;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px;
            padding: 13px 14px 13px 42px;
            color: #fff;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: all 0.25s;
        }
        .form-input::placeholder { color: var(--text-3); }
        .form-input:focus {
            outline: none;
            background: rgba(255,255,255,0.06);
            border-color: rgba(6,182,212,0.4);
            box-shadow: 0 0 0 3px rgba(6,182,212,0.08);
        }
        .input-wrap:focus-within .input-icon { color: var(--accent); }
        .form-input.is-valid   { border-color: rgba(16,185,129,0.4); background: rgba(16,185,129,0.04); }
        .form-input.is-invalid { border-color: rgba(239,68,68,0.4);  background: rgba(239,68,68,0.04); }
        .form-input.is-valid:focus   { box-shadow: 0 0 0 3px rgba(16,185,129,0.08); }
        .form-input.is-invalid:focus { box-shadow: 0 0 0 3px rgba(239,68,68,0.08); }

        .input-status {
            position: absolute; right: 40px; top: 50%;
            transform: translateY(-50%);
            font-size: 13px; opacity: 0; transition: opacity 0.2s;
        }
        .input-status.show { opacity: 1; }
        .input-status.valid   { color: var(--green-l); }
        .input-status.invalid { color: var(--red); }

        .toggle-pass {
            position: absolute; right: 13px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            color: var(--text-3); cursor: pointer;
            padding: 4px; font-size: 13px;
            transition: color 0.2s;
        }
        .toggle-pass:hover { color: var(--accent); }

        .validation-msg {
            font-size: 11px; margin-top: 5px;
            display: flex; align-items: center; gap: 5px;
            min-height: 15px;
        }
        .validation-msg.valid   { color: var(--green-l); }
        .validation-msg.invalid { color: var(--red); }

        /* Remember row */
        .row-actions {
            display: flex; justify-content: space-between;
            align-items: center; margin: 18px 0 22px;
        }
        .row-actions label {
            display: flex; align-items: center; gap: 8px;
            font-size: 13px; color: var(--text-2); cursor: pointer;
        }
        .row-actions input[type=checkbox] { accent-color: var(--accent); width:14px; height:14px; }

        /* Submit button */
        .btn-login {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, var(--accent), var(--primary));
            border: none; border-radius: 10px;
            color: #fff; font-size: 14px; font-weight: 700;
            cursor: pointer; font-family: 'Inter', sans-serif;
            transition: all 0.25s;
            box-shadow: 0 8px 24px rgba(6,182,212,0.25);
            letter-spacing: 0.4px;
            position: relative; overflow: hidden;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-login:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 14px 32px rgba(6,182,212,0.38);
        }
        .btn-login:active:not(:disabled) { transform: translateY(0); }
        .btn-login:disabled { opacity: 0.65; cursor: not-allowed; }
        .ripple {
            position: absolute; border-radius: 50%;
            background: rgba(255,255,255,0.25);
            transform: scale(0);
            animation: rippleAnim 0.6s linear;
            pointer-events: none;
        }
        @keyframes rippleAnim { to { transform:scale(4); opacity:0; } }
        .btn-spinner {
            width: 15px; height: 15px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            display: none;
        }
        .btn-login.loading .btn-spinner { display: block; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Error */
        .error-msg {
            background: rgba(239,68,68,0.08);
            border: 1px solid rgba(239,68,68,0.25);
            border-radius: 10px; padding: 11px 14px;
            color: #fca5a5; font-size: 13px;
            margin-bottom: 16px;
            display: flex; align-items: center; gap: 9px;
            animation: shakeX 0.4s ease;
        }
        @keyframes shakeX {
            0%,100%{transform:translateX(0)} 20%{transform:translateX(-5px)}
            40%{transform:translateX(5px)} 60%{transform:translateX(-3px)} 80%{transform:translateX(3px)}
        }

        /* === Countdown Lock Notification === */
        .lock-notice {
            background: linear-gradient(135deg, rgba(239,68,68,0.12), rgba(239,68,68,0.06));
            border: 1px solid rgba(239,68,68,0.35);
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 16px;
            position: relative;
            overflow: hidden;
            transition: all 0.5s ease;
            animation: shakeX 0.4s ease;
        }
        .lock-notice.unlocked {
            background: linear-gradient(135deg, rgba(16,185,129,0.14), rgba(16,185,129,0.06));
            border-color: rgba(16,185,129,0.45);
            box-shadow: 0 0 18px rgba(16,185,129,0.18);
        }
        .lock-notice-progress {
            position: absolute;
            bottom: 0; left: 0;
            height: 3px;
            background: linear-gradient(90deg, #ef4444, #f87171);
            transition: width 1s linear, background 0.4s ease;
        }
        .lock-notice.unlocked .lock-notice-progress {
            background: linear-gradient(90deg, #10b981, #34d399);
            width: 100% !important;
        }
        .lock-notice-row {
            display: flex; align-items: center; gap: 12px;
        }
        .lock-icon-wrap {
            width: 42px; height: 42px;
            border-radius: 10px;
            background: rgba(239,68,68,0.18);
            color: #f87171;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
            transition: all 0.4s ease;
            flex-shrink: 0;
        }
        .lock-notice.unlocked .lock-icon-wrap {
            background: rgba(16,185,129,0.2);
            color: #34d399;
            transform: scale(1.05);
        }
        .lock-notice-body {
            flex: 1;
            min-width: 0;
        }
        .lock-notice-title {
            font-size: 13px;
            font-weight: 700;
            color: #fca5a5;
            margin-bottom: 2px;
            transition: color 0.4s ease;
        }
        .lock-notice.unlocked .lock-notice-title {
            color: #34d399;
        }
        .lock-notice-msg {
            font-size: 12px;
            color: #cbd5e1;
            line-height: 1.4;
        }
        .lock-notice-counter {
            flex-shrink: 0;
            text-align: center;
            min-width: 64px;
            padding: 6px 10px;
            background: rgba(0,0,0,0.25);
            border: 1px solid rgba(239,68,68,0.3);
            border-radius: 10px;
            transition: all 0.4s ease;
        }
        .lock-notice.unlocked .lock-notice-counter {
            border-color: rgba(16,185,129,0.4);
            background: rgba(16,185,129,0.08);
        }
        .lock-counter-num {
            font-family: 'Inter', sans-serif;
            font-size: 22px;
            font-weight: 800;
            color: #f87171;
            line-height: 1;
            font-variant-numeric: tabular-nums;
            transition: color 0.4s ease;
        }
        .lock-notice.unlocked .lock-counter-num {
            color: #34d399;
        }
        .lock-counter-label {
            font-size: 9px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 2px;
        }
        .lock-notice.unlocked .lock-counter-label {
            color: #34d399;
        }

        /* Footer */
        .login-footer {
            margin-top: 24px;
            text-align: center;
        }
        .login-footer p {
            font-size: 11px; color: var(--text-3); line-height: 1.7;
        }
        .login-footer strong { color: var(--text-2); font-weight: 600; }

        /* Theme toggle */
        .theme-btn {
            position: fixed; top: 20px; right: 20px; z-index: 100;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            color: var(--text-2); width: 34px; height: 34px;
            border-radius: 8px; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; transition: all 0.2s;
        }
        .theme-btn:hover { color: var(--accent); border-color: var(--border2); }

        /* Animations */
        @keyframes fadeUp {
            from { opacity:0; transform:translateY(20px); }
            to   { opacity:1; transform:translateY(0); }
        }

        /* ── LOGIN LIGHT MODE ── */
        body.login-light {
            background: linear-gradient(150deg, #dbeafe 0%, #eff6ff 100%);
        }
        body.login-light .left-panel {
            background: linear-gradient(160deg, rgba(219,234,254,0.97) 0%, rgba(239,246,255,0.99) 100%);
        }
        body.login-light .left-panel::after {
            background: linear-gradient(to right, transparent, rgba(239,246,255,0.6));
        }
        body.login-light .login-card {
            background: rgba(255,255,255,0.98);
            box-shadow: 0 28px 65px -14px rgba(29,78,216,0.18), 0 0 0 1px rgba(29,78,216,0.08);
        }
        body.login-light .login-card::before {
            background: linear-gradient(90deg, transparent, #1d4ed8, #3b82f6, #10b981, transparent);
        }
        body.login-light .login-heading h2 { color: #0f172a; }
        body.login-light .login-heading p  { color: #64748b; }
        body.login-light .form-label       { color: #334155; }
        body.login-light .form-input {
            background: #f8faff;
            border-color: rgba(29,78,216,0.2);
            color: #0f172a;
        }
        body.login-light .form-input:focus {
            border-color: #1d4ed8;
            box-shadow: 0 0 0 4px rgba(29,78,216,0.09);
            background: #fff;
        }
        body.login-light .form-input::placeholder { color: #94a3b8; }
        body.login-light .input-icon { color: #94a3b8; }
        body.login-light .input-wrap:focus-within .input-icon { color: #1d4ed8; }
        body.login-light .toggle-pass { color: #94a3b8; }
        body.login-light .toggle-pass:hover { color: #1d4ed8; }
        body.login-light .row-actions label { color: #334155; }
        body.login-light .btn-login {
            background: linear-gradient(135deg, #1d4ed8, #1e3a8a);
            box-shadow: 0 6px 20px rgba(29,78,216,0.3);
        }
        body.login-light .btn-login:hover:not(:disabled) {
            box-shadow: 0 12px 30px rgba(29,78,216,0.45);
        }
        body.login-light .login-footer p { color: #64748b; }
        body.login-light .login-footer strong { color: #334155; }
        body.login-light .tab-item { color: #1d4ed8; }
        body.login-light .tab-item::after { background: linear-gradient(90deg, #1d4ed8, #3b82f6); }
        body.login-light .hero h1 { color: #0f172a; }
        body.login-light .hero p  { color: #475569; }
        body.login-light .hero-label { color: #1d4ed8; }
        body.login-light .hero-label::before { background: #1d4ed8; }
        body.login-light .welcome-title { color: #0f172a; }
        body.login-light .welcome-desc  { color: #475569; }
        body.login-light .stat-card {
            background: rgba(255,255,255,0.85);
            border-color: rgba(29,78,216,0.15);
        }
        body.login-light .stat-value { color: #0f172a; }
        body.login-light .stat-label { color: #64748b; }
        body.login-light .stat-icon.blue  { background: rgba(29,78,216,0.12); }
        body.login-light .stat-icon.cyan  { background: rgba(6,182,212,0.12); }
        body.login-light .stat-icon.green { background: rgba(16,185,129,0.12); }
        body.login-light .brand-name { color: #0f172a; }
        body.login-light .brand-sub  { color: #64748b; }
        body.login-light .theme-btn {
            background: rgba(29,78,216,0.06);
            border-color: rgba(29,78,216,0.2);
            color: #334155;
        }
        body.login-light .theme-btn:hover { color: #1d4ed8; border-color: rgba(29,78,216,0.4); }
        body.login-light .charge-bar {
            background: linear-gradient(90deg, #1d4ed8, #3b82f6, #10b981);
        }
        body.login-light .glow-orb { opacity: 0.06; }
        body.login-light .grid-overlay {
            background-image:
                linear-gradient(rgba(29,78,216,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(29,78,216,0.04) 1px, transparent 1px);
        }
        body.login-light .dash-card {
            background: rgba(255,255,255,0.8);
            border-color: rgba(29,78,216,0.12);
        }
        body.login-light .dc-label { color: #64748b; }
        body.login-light .status-bar { border-top-color: rgba(29,78,216,0.1); }
        body.login-light .status-item { color: #64748b; }
        body.login-light .status-item strong { color: #334155; }
        body.login-light .status-divider { background: rgba(29,78,216,0.1); }
        body.login-light .status-time { color: #64748b; }
        body.login-light .chart-wrap {
            background: rgba(255,255,255,0.7);
            border-color: rgba(29,78,216,0.1);
        }
        body.login-light .chart-title { color: #334155; }
        body.login-light .pill {
            background: rgba(255,255,255,0.7);
            border-color: rgba(29,78,216,0.15);
            color: #334155;
        }
        body.login-light .pill.cyan  { background: rgba(6,182,212,0.08);  color: #0369a1; }
        body.login-light .pill.blue  { background: rgba(29,78,216,0.08);  color: #1d4ed8; }
        body.login-light .pill.green { background: rgba(16,185,129,0.08); color: #059669; }
        body.login-light .pill.amber { background: rgba(245,158,11,0.08); color: #d97706; }
        body.login-light .bottom-info { color: #64748b; }
        body.login-light .bottom-info strong { color: #334155; }

        /* Responsive */
        @media (max-width: 1024px) {
            .layout { grid-template-columns: 1fr; align-items: stretch; }
            .left-panel { display: none; }
            .right-panel {
                position: relative; top: auto;
                height: auto; min-height: 100vh;
                padding: 40px 28px;
            }
        }
        @media (max-width: 480px) {
            .right-panel { padding: 32px 20px; }
            .login-box { max-width: 100%; }
        }
    </style>
</head>
<body>

<canvas id="bg-canvas"></canvas>
<div class="grid-overlay"></div>
<div class="charge-bar-wrap"><div class="charge-bar"></div></div>

{{-- Glow blobs — satu set global menutupi seluruh halaman --}}
<div style="position:fixed;z-index:1;pointer-events:none;border-radius:50%;filter:blur(110px);opacity:0.45;width:650px;height:650px;top:-200px;left:-150px;background:radial-gradient(circle,rgba(29,78,216,0.55),transparent 70%)"></div>
<div style="position:fixed;z-index:1;pointer-events:none;border-radius:50%;filter:blur(110px);opacity:0.35;width:550px;height:550px;bottom:-180px;right:-120px;background:radial-gradient(circle,rgba(6,182,212,0.5),transparent 70%)"></div>
<div style="position:fixed;z-index:1;pointer-events:none;border-radius:50%;filter:blur(90px);opacity:0.22;width:400px;height:400px;top:35%;left:40%;background:radial-gradient(circle,rgba(16,185,129,0.45),transparent 70%)"></div>
<div style="position:fixed;z-index:1;pointer-events:none;border-radius:50%;filter:blur(100px);opacity:0.28;width:500px;height:500px;top:10%;right:25%;background:radial-gradient(circle,rgba(29,78,216,0.4),transparent 70%)"></div>

<button class="theme-btn" id="themeBtn" title="Toggle tema">
    <i class="fas fa-moon" id="themeIcon"></i>
</button>

<div class="layout">

    <!-- ══ LEFT PANEL ══ -->
    <div class="left-panel">
        <div class="glow-tl"></div>

        <!-- Brand -->
        <div class="brand">
            <div class="brand-icon"><i class="fas fa-charging-station"></i></div>
            <div class="brand-text">
                <div class="name">EV <span>Smart Energy</span></div>
                <div class="sub">PT. Sahabat Mitra Intrabuana</div>
            </div>
        </div>

        <!-- Hero -->
        <div class="hero">
            <div class="hero-label">Sistem Monitoring EV Charging Station Berbasis Web PT. Sahabat Mitra Intrabuana</div>
            <h1>Monitor & Kelola<br><span class="highlight">Infrastruktur Pengisian</span><br>Secara Real-Time</h1>
            <p>Platform terpadu untuk memantau performa stasiun pengisian kendaraan listrik, analitik konsumsi energi, dan integrasi multi-vendor dalam satu dashboard.</p>

            <div class="feature-pills">
                <span class="pill cyan"><i class="fas fa-bolt"></i> Real-time Monitoring</span>
                <span class="pill blue"><i class="fas fa-chart-line"></i> Energy Analytics</span>
                <span class="pill green"><i class="fas fa-plug"></i> Multi-Charger Support</span>
                <span class="pill amber"><i class="fas fa-shield-halved"></i> Secure Access</span>
            </div>

            <!-- SPKLU Illustration — Professional EV Scene -->
            <div class="spklu-wrap">
              <canvas id="spklu-particles"></canvas>
              <svg viewBox="0 0 640 210" xmlns="http://www.w3.org/2000/svg">
                <defs>
                  <linearGradient id="carPaint" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#1e293b"/>
                    <stop offset="55%" stop-color="#0f172a"/>
                    <stop offset="100%" stop-color="#0a1120"/>
                  </linearGradient>
                  <linearGradient id="carRoof" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#334155"/>
                    <stop offset="100%" stop-color="#1e293b"/>
                  </linearGradient>
                  <linearGradient id="glassGrad" x1="0" y1="0" x2="0.3" y2="1">
                    <stop offset="0%" stop-color="#7dd3fc" stop-opacity="0.55"/>
                    <stop offset="100%" stop-color="#0369a1" stop-opacity="0.35"/>
                  </linearGradient>
                  <linearGradient id="chargerUnit" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#1e3a5f"/>
                    <stop offset="100%" stop-color="#0c1e35"/>
                  </linearGradient>
                  <linearGradient id="screenBg" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#082f49"/>
                    <stop offset="100%" stop-color="#041520"/>
                  </linearGradient>
                  <linearGradient id="barFill" x1="0" y1="0" x2="1" y2="0">
                    <stop offset="0%" stop-color="#10b981"/>
                    <stop offset="100%" stop-color="#34d399"/>
                  </linearGradient>
                  <linearGradient id="roadSurface" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#1a2332"/>
                    <stop offset="100%" stop-color="#0d1520"/>
                  </linearGradient>
                  <radialGradient id="eGlow" cx="50%" cy="50%" r="50%">
                    <stop offset="0%" stop-color="#06b6d4" stop-opacity="0.4"/>
                    <stop offset="100%" stop-color="#06b6d4" stop-opacity="0"/>
                  </radialGradient>
                  <radialGradient id="wheelR" cx="38%" cy="32%" r="65%">
                    <stop offset="0%" stop-color="#64748b"/>
                    <stop offset="100%" stop-color="#0f172a"/>
                  </radialGradient>
                  <filter id="softGlow" x="-30%" y="-30%" width="160%" height="160%">
                    <feGaussianBlur stdDeviation="3" result="b"/>
                    <feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge>
                  </filter>
                  <filter id="tinyGlow" x="-50%" y="-50%" width="200%" height="200%">
                    <feGaussianBlur stdDeviation="1.5" result="b"/>
                    <feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge>
                  </filter>
                </defs>
                <rect x="0" y="0" width="640" height="210" fill="#070d1a"/>
                <!-- Road -->
                <rect x="0" y="165" width="640" height="45" fill="url(#roadSurface)"/>
                <rect x="0" y="163" width="640" height="4" fill="rgba(255,255,255,0.04)"/>
                <rect x="50"  y="181" width="36" height="3" rx="1.5" fill="rgba(255,255,255,0.07)"/>
                <rect x="130" y="181" width="36" height="3" rx="1.5" fill="rgba(255,255,255,0.07)"/>
                <rect x="210" y="181" width="36" height="3" rx="1.5" fill="rgba(255,255,255,0.07)"/>
                <rect x="290" y="181" width="36" height="3" rx="1.5" fill="rgba(255,255,255,0.07)"/>
                <rect x="370" y="181" width="36" height="3" rx="1.5" fill="rgba(255,255,255,0.07)"/>
                <rect x="450" y="181" width="36" height="3" rx="1.5" fill="rgba(255,255,255,0.07)"/>
                <rect x="530" y="181" width="36" height="3" rx="1.5" fill="rgba(255,255,255,0.07)"/>
                <!-- SPKLU Charger -->
                <rect x="91" y="100" width="8" height="65" rx="3" fill="#1e293b"/>
                <rect x="60" y="38" width="70" height="90" rx="10" fill="url(#chargerUnit)" stroke="rgba(6,182,212,0.18)" stroke-width="1"/>
                <rect x="60" y="38" width="70" height="3" rx="2" fill="#06b6d4" opacity="0.8" class="screen-glow"/>
                <rect x="60" y="44" width="3" height="78" rx="1.5" fill="rgba(6,182,212,0.25)"/>
                <rect x="127" y="44" width="3" height="78" rx="1.5" fill="rgba(6,182,212,0.1)"/>
                <rect x="68" y="48" width="54" height="42" rx="5" fill="#041520" stroke="rgba(6,182,212,0.12)" stroke-width="0.8"/>
                <rect x="70" y="50" width="50" height="38" rx="4" fill="url(#screenBg)" class="screen-glow"/>
                <rect x="70" y="50" width="50" height="8" rx="4" fill="rgba(6,182,212,0.15)"/>
                <text x="95" y="56.5" text-anchor="middle" fill="#22d3ee" font-size="5" font-weight="700" font-family="Inter,monospace" letter-spacing="0.5">SPKLU · AC 22kW</text>
                <text x="95" y="67" text-anchor="middle" fill="#34d399" font-size="6.5" font-weight="700" font-family="Inter,monospace">CHARGING</text>
                <rect x="73" y="71" width="44" height="6" rx="3" fill="rgba(0,0,0,0.5)"/>
                <rect x="73" y="71" class="charge-fill" height="6" rx="3" fill="url(#barFill)"/>
                <text x="95" y="77" text-anchor="middle" fill="rgba(255,255,255,0.7)" font-size="5" font-family="Inter,monospace">72%</text>
                <text x="95" y="85" text-anchor="middle" fill="rgba(148,163,184,0.6)" font-size="5" font-family="Inter,monospace">22.1 kW  ·  18.4 A</text>
                <circle cx="80" cy="98" r="3" fill="#10b981" class="bolt-pulse"/>
                <circle cx="91" cy="98" r="3" fill="#10b981" class="bolt-pulse" style="animation-delay:0.4s"/>
                <circle cx="102" cy="98" r="3" fill="#f59e0b" class="bolt-pulse" style="animation-delay:0.8s"/>
                <circle cx="113" cy="98" r="3" fill="rgba(100,116,139,0.4)"/>
                <rect x="72" y="106" width="46" height="14" rx="5" fill="#0a1628" stroke="rgba(6,182,212,0.3)" stroke-width="1"/>
                <circle cx="88" cy="113" r="2" fill="rgba(6,182,212,0.5)"/>
                <circle cx="95" cy="113" r="2" fill="rgba(6,182,212,0.5)"/>
                <circle cx="102" cy="113" r="2" fill="rgba(6,182,212,0.5)"/>
                <circle cx="95" cy="75" class="halo-ring" fill="none" stroke="rgba(6,182,212,0.25)" stroke-width="1"/>
                <rect x="58" y="22" width="74" height="13" rx="4" fill="rgba(6,182,212,0.07)" stroke="rgba(6,182,212,0.18)" stroke-width="0.8"/>
                <!-- Tulisan EVOPOWER di atas charger seperti di foto asli -->
                <text x="95" y="31.5" text-anchor="middle" fill="#ffffff" font-size="7" font-weight="900" font-family="Arial,sans-serif" letter-spacing="1.5">EV<tspan fill="#06b6d4">O</tspan>POWER</text>
                <!-- Cable -->
                <path d="M 130 113 C 152 113, 162 160, 185 160 C 215 160, 228 152, 248 148" stroke="#0f172a" stroke-width="7" fill="none" stroke-linecap="round"/>
                <path d="M 130 113 C 152 113, 162 160, 185 160 C 215 160, 228 152, 248 148" stroke="#1e293b" stroke-width="5" fill="none" stroke-linecap="round"/>
                <path d="M 130 113 C 152 113, 162 160, 185 160 C 215 160, 228 152, 248 148" stroke="#06b6d4" stroke-width="2.5" fill="none" stroke-linecap="round" class="cable-flow" filter="url(#softGlow)"/>
                <path d="M 130 113 C 152 113, 162 160, 185 160 C 215 160, 228 152, 248 148" stroke="#22d3ee" stroke-width="1.2" fill="none" stroke-linecap="round" class="cable-flow-2" opacity="0.5"/>
                <rect x="244" y="144" width="12" height="9" rx="3" fill="#1e293b" stroke="rgba(6,182,212,0.6)" stroke-width="1"/>
                <rect x="246" y="146" width="8" height="5" rx="1.5" fill="rgba(6,182,212,0.35)"/>
                <!-- Energy particles -->
                <circle cx="162" cy="155" r="2.2" fill="#06b6d4" class="e-particle" style="animation-duration:1.0s;animation-delay:0s" filter="url(#tinyGlow)"/>
                <circle cx="178" cy="158" r="1.8" fill="#22d3ee" class="e-particle" style="animation-duration:1.3s;animation-delay:0.25s" filter="url(#tinyGlow)"/>
                <circle cx="195" cy="158" r="2.0" fill="#34d399" class="e-particle" style="animation-duration:1.5s;animation-delay:0.5s" filter="url(#tinyGlow)"/>
                <circle cx="212" cy="155" r="1.6" fill="#06b6d4" class="e-particle" style="animation-duration:1.1s;animation-delay:0.75s" filter="url(#tinyGlow)"/>
                <circle cx="228" cy="151" r="1.9" fill="#22d3ee" class="e-particle" style="animation-duration:1.4s;animation-delay:1.0s" filter="url(#tinyGlow)"/>
                <ellipse cx="190" cy="155" rx="40" ry="14" fill="url(#eGlow)" opacity="0.7"/>
                <!-- EV Car -->
                <ellipse cx="385" cy="167" rx="125" ry="7" fill="rgba(0,0,0,0.45)"/>
                <path d="M 255 148 L 255 162 Q 255 167, 260 167 L 510 167 Q 515 167, 515 162 L 515 148 Z" fill="#0a1120"/>
                <rect x="260" y="158" width="248" height="4" rx="2" fill="rgba(6,182,212,0.12)"/>
                <path d="M 258 148 Q 258 132, 272 126 L 300 118 Q 332 110, 375 110 L 420 110 Q 458 110, 476 120 L 492 132 Q 498 137, 498 146 L 498 162 L 258 162 Z" fill="url(#carPaint)"/>
                <path d="M 305 126 L 326 104 Q 348 94, 385 93 Q 420 93, 442 103 L 462 120 L 462 126 Z" fill="url(#carRoof)"/>
                <path d="M 440 103 L 462 120 L 462 126 L 428 126 L 428 99 Q 435 99, 440 103 Z" fill="url(#glassGrad)"/>
                <path d="M 432 101 L 440 108 L 436 110 L 428 103 Z" fill="rgba(255,255,255,0.08)"/>
                <path d="M 305 126 L 326 104 Q 334 98, 346 97 L 346 126 Z" fill="url(#glassGrad)"/>
                <path d="M 348 97 L 426 97 L 426 126 L 348 126 Z" fill="url(#glassGrad)"/>
                <line x1="386" y1="97" x2="386" y2="126" stroke="rgba(125,211,252,0.2)" stroke-width="1"/>
                <path d="M 352 99 Q 370 97, 388 99 L 386 104 Q 368 102, 352 104 Z" fill="rgba(255,255,255,0.07)"/>
                <line x1="348" y1="126" x2="348" y2="160" stroke="rgba(255,255,255,0.06)" stroke-width="1"/>
                <line x1="424" y1="126" x2="424" y2="160" stroke="rgba(255,255,255,0.06)" stroke-width="1"/>
                <rect x="356" y="140" width="18" height="3" rx="1.5" fill="rgba(148,163,184,0.3)"/>
                <rect x="432" y="140" width="18" height="3" rx="1.5" fill="rgba(148,163,184,0.3)"/>
                <path d="M 492 132 Q 510 138, 514 148 L 514 158 Q 510 162, 505 162 L 498 162 L 498 132 Z" fill="#0f172a"/>
                <path d="M 494 133 Q 508 138, 510 146" stroke="#fef9c3" stroke-width="2.5" fill="none" stroke-linecap="round" opacity="0.9"/>
                <path d="M 494 133 Q 508 138, 510 146" stroke="#fef3c7" stroke-width="5" fill="none" stroke-linecap="round" opacity="0.15"/>
                <rect x="258" y="130" width="4" height="28" rx="2" fill="#dc2626" opacity="0.7"/>
                <rect x="252" y="136" width="10" height="14" rx="2.5" fill="#0a1120" stroke="rgba(6,182,212,0.55)" stroke-width="1"/>
                <rect x="254" y="138" width="6" height="10" rx="1.5" fill="rgba(6,182,212,0.25)"/>
                <ellipse cx="257" cy="143" rx="10" ry="8" fill="rgba(6,182,212,0.1)"/>
                <rect x="476" y="130" width="20" height="11" rx="3" fill="rgba(6,182,212,0.12)" stroke="rgba(6,182,212,0.35)" stroke-width="0.8"/>
                <text x="486" y="138.5" text-anchor="middle" fill="#22d3ee" font-size="6" font-weight="800" font-family="Inter,sans-serif">EV</text>
                <!-- Wheels rear -->
                <circle cx="308" cy="167" r="22" fill="#0a1120" stroke="#1a2535" stroke-width="1.5"/>
                <circle cx="308" cy="167" r="16" fill="url(#wheelR)"/>
                <circle cx="308" cy="167" r="9" fill="#0a1120"/>
                <g stroke="#475569" stroke-width="1.8" stroke-linecap="round">
                  <line x1="308" y1="151" x2="308" y2="158"/><line x1="308" y1="176" x2="308" y2="183"/>
                  <line x1="292" y1="167" x2="299" y2="167"/><line x1="317" y1="167" x2="324" y2="167"/>
                  <line x1="297" y1="156" x2="302" y2="161"/><line x1="314" y1="173" x2="319" y2="178"/>
                  <line x1="319" y1="156" x2="314" y2="161"/><line x1="302" y1="173" x2="297" y2="178"/>
                </g>
                <circle cx="308" cy="167" r="4" fill="#64748b"/><circle cx="308" cy="167" r="2" fill="#94a3b8"/>
                <!-- Wheels front -->
                <circle cx="462" cy="167" r="22" fill="#0a1120" stroke="#1a2535" stroke-width="1.5"/>
                <circle cx="462" cy="167" r="16" fill="url(#wheelR)"/>
                <circle cx="462" cy="167" r="9" fill="#0a1120"/>
                <g stroke="#475569" stroke-width="1.8" stroke-linecap="round">
                  <line x1="462" y1="151" x2="462" y2="158"/><line x1="462" y1="176" x2="462" y2="183"/>
                  <line x1="446" y1="167" x2="453" y2="167"/><line x1="471" y1="167" x2="478" y2="167"/>
                  <line x1="451" y1="156" x2="456" y2="161"/><line x1="468" y1="173" x2="473" y2="178"/>
                  <line x1="473" y1="156" x2="468" y2="161"/><line x1="456" y1="173" x2="451" y2="178"/>
                </g>
                <circle cx="462" cy="167" r="4" fill="#64748b"/><circle cx="462" cy="167" r="2" fill="#94a3b8"/>
                <!-- Battery HUD -->
                <rect x="340" y="88" width="72" height="18" rx="4" fill="rgba(10,18,32,0.85)" stroke="rgba(6,182,212,0.2)" stroke-width="0.8"/>
                <rect x="412" y="93" width="4" height="8" rx="1" fill="rgba(6,182,212,0.3)"/>
                <!-- Bar baterai mobil: x=343, batas kanan=411 (sebelum terminal), max width=68 -->
                <rect x="343" y="91" class="car-charge-fill" height="12" rx="2.5" fill="url(#barFill)" opacity="0.9"/>
                <text x="376" y="100.5" text-anchor="middle" fill="rgba(255,255,255,0.8)" font-size="7" font-weight="700" font-family="Inter,monospace">72%  ·  Charging</text>
                <!-- Technician -->
                <ellipse cx="162" cy="168" rx="16" ry="4.5" fill="rgba(0,0,0,0.35)"/>
                <!-- Kaki -->
                <path d="M 153 158 Q 148 158, 146 162 L 146 167 L 158 167 L 158 162 Q 157 158, 153 158 Z" fill="#1e293b"/>
                <path d="M 170 158 Q 165 158, 163 162 L 163 167 L 175 167 L 175 162 Q 174 158, 170 158 Z" fill="#1e293b"/>
                <rect x="146" y="165" width="12" height="2" rx="1" fill="rgba(255,255,255,0.08)"/>
                <rect x="163" y="165" width="12" height="2" rx="1" fill="rgba(255,255,255,0.08)"/>
                <!-- Badan baju biru -->
                <path d="M 150 128 L 148 158 L 158 158 L 160 140 L 162 140 L 164 158 L 174 158 L 172 128 Z" fill="#1e3a8a"/>
                <path d="M 146 96 L 142 128 L 180 128 L 176 96 Q 170 90, 161 90 Q 152 90, 146 96 Z" fill="#1d4ed8"/>
                <!-- Kerah baju -->
                <path d="M 155 96 L 161 104 L 167 96 L 161 90 Z" fill="#1e3a8a"/>
                <path d="M 155 96 L 158 100 L 161 96 Z" fill="#2563eb"/>
                <path d="M 167 96 L 164 100 L 161 96 Z" fill="#2563eb"/>
                <!-- Kancing tengah -->
                <circle cx="161" cy="100" r="0.7" fill="#1e3a8a"/>
                <circle cx="161" cy="105" r="0.7" fill="#1e3a8a"/>
                <circle cx="161" cy="110" r="0.7" fill="#1e3a8a"/>
                <circle cx="161" cy="115" r="0.7" fill="#1e3a8a"/>
                <circle cx="161" cy="120" r="0.7" fill="#1e3a8a"/>
                <!-- Garis jahit tengah -->
                <line x1="161" y1="96" x2="161" y2="128" stroke="rgba(0,0,0,0.12)" stroke-width="0.4"/>
                <!-- Kantong dada kiri -->
                <rect x="148" y="109" width="10" height="8" rx="1" fill="#1a3a7a" stroke="rgba(255,255,255,0.1)" stroke-width="0.4"/>
                <rect x="148" y="109" width="10" height="2" rx="1" fill="#1e3a8a"/>
                <!-- Kantong dada kanan -->
                <rect x="164" y="109" width="10" height="8" rx="1" fill="#1a3a7a" stroke="rgba(255,255,255,0.1)" stroke-width="0.4"/>
                <rect x="164" y="109" width="10" height="2" rx="1" fill="#1e3a8a"/>
                <!-- Strip reflektif abu (bukan kuning) -->
                <rect x="142" y="116" width="38" height="3" rx="0" fill="#94a3b8" opacity="0.8"/>
                <rect x="142" y="119" width="38" height="1.5" rx="0" fill="#cbd5e1" opacity="0.5"/>
                <!-- Epaulet bahu kiri -->
                <rect x="146" y="93" width="8" height="3" rx="1" fill="#1e3a8a" stroke="rgba(255,255,255,0.15)" stroke-width="0.4"/>
                <!-- Epaulet bahu kanan -->
                <rect x="168" y="93" width="8" height="3" rx="1" fill="#1e3a8a" stroke="rgba(255,255,255,0.15)" stroke-width="0.4"/>

                <!-- Tulisan EVOPOWER di dada kanan — pas di area kantong kanan -->
                <text x="169" y="107.5" text-anchor="middle" fill="#ffffff" font-size="2.2" font-weight="900" font-family="Arial,sans-serif" letter-spacing="0.2">EV<tspan fill="#22d3ee">O</tspan>POWER</text>

                <!-- ===== LENGAN KIRI ===== -->
                <path d="M 142 98 Q 134 110, 133 124 Q 133 128, 136 130" stroke="#1d4ed8" stroke-width="9" fill="none" stroke-linecap="round"/>
                <path d="M 142 98 Q 134 110, 133 124 Q 133 128, 136 130" stroke="#1e3a8a" stroke-width="7" fill="none" stroke-linecap="round"/>
                <!-- Strip reflektif lengan kiri -->
                <path d="M 134 118 Q 137 117, 140 117" stroke="#94a3b8" stroke-width="2" fill="none" stroke-linecap="round" opacity="0.8"/>
                <!-- BENDERA MERAH PUTIH di lengan kiri -->
                <rect x="133.5" y="104.5" width="9" height="7" rx="1" fill="rgba(0,0,0,0.3)"/>
                <rect x="133" y="104" width="9" height="7" rx="1" fill="#ffffff" stroke="rgba(0,0,0,0.2)" stroke-width="0.4"/>
                <rect x="133" y="104" width="9" height="3.5" rx="1" fill="#dc2626"/>
                <rect x="133" y="107.5" width="9" height="3.5" rx="0" fill="#f8f8f8"/>

                <!-- ===== LENGAN KANAN ===== -->
                <path d="M 180 98 Q 188 108, 186 120 Q 185 126, 182 130" stroke="#1d4ed8" stroke-width="9" fill="none" stroke-linecap="round"/>
                <path d="M 180 98 Q 188 108, 186 120 Q 185 126, 182 130" stroke="#1e3a8a" stroke-width="7" fill="none" stroke-linecap="round"/>
                <!-- Strip reflektif lengan kanan -->
                <path d="M 182 118 Q 185 117, 188 117" stroke="#94a3b8" stroke-width="2" fill="none" stroke-linecap="round" opacity="0.8"/>
                <!-- LOGO SAFETY FIRST (P3K) di lengan kanan -->
                <rect x="181.5" y="103.5" width="9" height="9" rx="1.5" fill="rgba(0,0,0,0.3)"/>
                <rect x="181" y="103" width="9" height="9" rx="1.5" fill="#166534" stroke="#15803d" stroke-width="0.5"/>
                <circle cx="185.5" cy="107.5" r="3" fill="none" stroke="#ffffff" stroke-width="0.8"/>
                <!-- Palang P3K -->
                <rect x="184.8" y="105.5" width="1.4" height="4" rx="0.3" fill="#ffffff"/>
                <rect x="183.5" y="106.8" width="4" height="1.4" rx="0.3" fill="#ffffff"/>
                <text x="185.5" y="113.5" text-anchor="middle" fill="#86efac" font-size="1.6" font-weight="700" font-family="Arial,sans-serif">SAFETY FIRST</text>

                <!-- Tangan kiri bawah -->
                <path d="M 133 128 Q 130 132, 131 136 Q 132 140, 136 140 Q 140 140, 141 136 Q 142 132, 139 130 Z" fill="#0f172a"/>
                <!-- Tangan kanan bawah -->
                <path d="M 182 128 Q 185 132, 184 136 Q 183 140, 179 140 Q 175 140, 174 136 Q 173 132, 176 130 Z" fill="#0f172a"/>

                <!-- ===== HELM PUTIH ===== -->
                <!-- Leher -->
                <rect x="157" y="82" width="8" height="10" rx="4" fill="#d97706"/>
                <!-- Kepala / muka (kulit) -->
                <ellipse cx="161" cy="72" rx="14" ry="15" fill="#f5c07a"/>
                <!-- Dagu / rahang bawah -->
                <path d="M 149 76 Q 149 84, 161 86 Q 173 84, 173 76" fill="#e8a85a"/>
                <!-- Rambut / kepala bagian atas gelap -->
                <path d="M 147 68 Q 147 55, 161 53 Q 175 55, 175 68 Q 172 60, 161 59 Q 150 60, 147 68 Z" fill="#3d2b1f"/>
                <!-- Alis -->
                <path d="M 153 64 Q 157 62, 160 64" stroke="#3d2b1f" stroke-width="1.5" fill="none" stroke-linecap="round"/>
                <path d="M 162 64 Q 165 62, 169 64" stroke="#3d2b1f" stroke-width="1.5" fill="none" stroke-linecap="round"/>
                <!-- Mata -->
                <ellipse cx="157" cy="68" rx="2.5" ry="2.8" fill="#fff"/>
                <ellipse cx="165" cy="68" rx="2.5" ry="2.8" fill="#fff"/>
                <circle cx="157" cy="68.5" r="1.6" fill="#3d2b1f"/>
                <circle cx="165" cy="68.5" r="1.6" fill="#3d2b1f"/>
                <circle cx="157.6" cy="67.8" r="0.6" fill="rgba(255,255,255,0.9)"/>
                <circle cx="165.6" cy="67.8" r="0.6" fill="rgba(255,255,255,0.9)"/>
                <!-- Hidung -->
                <path d="M 161 70 Q 159 73, 161 74 Q 163 73, 161 70" fill="rgba(0,0,0,0.15)"/>
                <!-- Mulut senyum -->
                <path d="M 157 79 Q 161 82, 165 79" stroke="#c0704a" stroke-width="1.2" fill="none" stroke-linecap="round"/>
                <!-- Telinga -->
                <ellipse cx="147" cy="72" rx="2.5" ry="3.5" fill="#f5c07a"/>
                <ellipse cx="175" cy="72" rx="2.5" ry="3.5" fill="#f5c07a"/>

                <!-- ===== HELM PUTIH (di atas kepala) ===== -->
                <!-- Badan helm putih -->
                <path d="M 146 66 Q 146 50, 161 48 Q 176 50, 176 66 L 178 68 L 144 68 Z" fill="#f0f0f0"/>
                <!-- Tepi bawah helm -->
                <rect x="144" y="67" width="34" height="5" rx="2.5" fill="#d0d0d0"/>
                <!-- Visor / kaca helm (transparan biru) -->
                <rect x="148" y="58" width="26" height="10" rx="3" fill="rgba(6,182,212,0.25)" stroke="rgba(6,182,212,0.5)" stroke-width="0.8"/>
                <!-- Garis detail helm -->
                <path d="M 146 62 Q 161 58, 176 62" stroke="rgba(0,0,0,0.1)" stroke-width="0.8" fill="none"/>
                <!-- Highlight helm -->
                <path d="M 150 52 Q 161 49, 172 52" stroke="rgba(255,255,255,0.6)" stroke-width="1.5" fill="none" stroke-linecap="round"/>

                <!-- ===== LOGO GO POWER DI HELM ===== -->
                <circle cx="161" cy="54" r="7" fill="white" opacity="0.97"/>
                <!-- G biru -->
                <text x="154.5" y="56.5" fill="#1d3a8a" font-size="5.5" font-weight="900" font-family="Arial Black,Arial,sans-serif">G</text>
                <!-- O kecil dengan swirl merah -->
                <circle cx="161.5" cy="53.5" r="2.2" fill="none" stroke="#1d3a8a" stroke-width="1.3"/>
                <path d="M 161.5 51.3 Q 163.7 51.5, 163.7 53.5 Q 163.7 55.5, 161.5 55.7" stroke="#dc2626" stroke-width="1.3" fill="none" stroke-linecap="round"/>
                <!-- POWER kecil di bawah GO -->
                <text x="161" y="59.5" text-anchor="middle" fill="#1d3a8a" font-size="3.2" font-weight="900" font-family="Arial Black,Arial,sans-serif" letter-spacing="0.5">POWER</text>

                <!-- Tali helm -->
                <line x1="161" y1="90" x2="158" y2="100" stroke="rgba(255,255,255,0.2)" stroke-width="0.8"/>
                <!-- Hapus kotak ID card — hanya tulisan EvoPower di baju -->
                <!-- Status overlay -->
                <rect x="490" y="14" width="132" height="34" rx="7" fill="rgba(10,18,32,0.88)" stroke="rgba(6,182,212,0.15)" stroke-width="1"/>
                <circle cx="504" cy="31" r="4.5" fill="#10b981" class="bolt-pulse"/>
                <circle cx="504" cy="31" r="7" fill="rgba(16,185,129,0.12)" class="bolt-pulse"/>
                <text x="514" y="26" fill="rgba(148,163,184,0.7)" font-size="6.5" font-weight="600" font-family="Inter,sans-serif" letter-spacing="0.5">SESSION ACTIVE</text>
                <text x="514" y="37" fill="#34d399" font-size="8.5" font-weight="700" font-family="Inter,sans-serif">Charging · 22 kW</text>
                <!-- Power label -->
                <rect x="155" y="120" width="62" height="14" rx="4" fill="rgba(6,182,212,0.07)" stroke="rgba(6,182,212,0.18)" stroke-width="0.8"/>
                <text x="186" y="130" text-anchor="middle" fill="rgba(6,182,212,0.75)" font-size="6.5" font-weight="600" font-family="Inter,sans-serif">&#x26A1; 22.1 kW · AC Type 2</text>
              </svg>
            </div>

        </div>
    </div>

    <!-- ══ RIGHT PANEL ══ -->
    <div class="right-panel">
        <div class="rp-glow-mid"></div>
        <div class="login-box">

            <div class="login-logo-wrap">
                <div class="login-logo"><i class="fas fa-bolt"></i></div>
            </div>

            <div class="login-heading">
                <h2>Selamat Datang</h2>
                <p>EV Smart Energy PT. Sahabat Mitra Intrabuana</p>
            </div>

            <div class="form-separator"><span>Autentikasi Akun</span></div>

            @if($errors->any())
                @php
                    $errMsg = $errors->first();
                    // Deteksi pesan akun terkunci → ambil angka detiknya
                    $isLocked = false;
                    $lockSeconds = 30;
                    if (preg_match('/(\d+)\s*detik/i', $errMsg, $m)) {
                        $isLocked = true;
                        $lockSeconds = (int) $m[1];
                    }
                @endphp

                @if($isLocked)
                    <div class="lock-notice" id="lockNotice" data-seconds="{{ $lockSeconds }}">
                        <div class="lock-notice-row">
                            <div class="lock-icon-wrap" id="lockIcon">
                                <i class="fas fa-lock" id="lockIconSymbol"></i>
                            </div>
                            <div class="lock-notice-body">
                                <div class="lock-notice-title" id="lockTitle">Akun Sementara Dikunci</div>
                                <div class="lock-notice-msg" id="lockMessage">{{ $errMsg }}</div>
                            </div>
                            <div class="lock-notice-counter">
                                <div class="lock-counter-num" id="lockCounter">{{ $lockSeconds }}</div>
                                <div class="lock-counter-label" id="lockCounterLabel">Detik</div>
                            </div>
                        </div>
                        <div class="lock-notice-progress" id="lockProgress" style="width:100%"></div>
                    </div>
                @else
                    <div class="error-msg">
                        <i class="fas fa-circle-exclamation"></i>
                        {{ $errMsg }}
                    </div>
                @endif
            @endif

            <form method="POST" action="{{ route('login') }}" id="loginForm" novalidate>
                @csrf

                <div class="form-group">
                    <label class="form-label" for="emailInput">Alamat Email</label>
                    <div class="input-wrap">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" name="email" id="emailInput"
                               class="form-input" required
                               placeholder="nama@perusahaan.com"
                               value="{{ old('email') }}" autofocus autocomplete="email">
                        <span class="input-status" id="emailStatus"></span>
                    </div>
                    <div class="validation-msg" id="emailMsg"></div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="passwordInput">Password</label>
                    <div class="input-wrap">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" id="passwordInput"
                               class="form-input" required
                               placeholder="••••••••"
                               autocomplete="current-password">
                        <span class="input-status" id="passStatus"></span>
                        <button type="button" class="toggle-pass" id="togglePassBtn">
                            <i id="eyeIcon" class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="validation-msg" id="passMsg"></div>
                </div>

                <div class="row-actions">
                    <label>
                        <input type="checkbox" name="remember" id="remember">
                        <span>Ingat saya</span>
                    </label>
                    <a href="{{ route('password.forgot') }}" style="font-size:13px;color:var(--accent-l);text-decoration:none;font-weight:600;transition:color 0.2s;" onmouseover="this.style.color='#FFD700'" onmouseout="this.style.color='var(--accent-l)'">
                        <i class="fas fa-key" style="font-size:11px;margin-right:4px"></i>Lupa Password?
                    </a>
                </div>

                <button type="submit" class="btn-login" id="loginBtn">
                    <div class="btn-spinner" id="btnSpinner"></div>
                    <span id="btnText"><i class="fas fa-right-to-bracket" style="margin-right:6px"></i>Masuk ke Dashboard</span>
                </button>
            </form>

            <div class="login-footer">
                <p>Akun default: <strong>admin@ev-sahabat.com</strong></p>
                <p style="margin-top:4px">© {{ date('Y') }} PT. Sahabat Mitra Intrabuana</p>
            </div>
        </div>
    </div>

</div>

<script>
/* ── 1. ANIMATED BACKGROUND CANVAS ── */
(function () {
    const canvas = document.getElementById('bg-canvas');
    const ctx = canvas.getContext('2d');
    let W, H, nodes = [];

    function resize() {
        W = canvas.width  = window.innerWidth;
        H = canvas.height = window.innerHeight;
    }
    resize();
    window.addEventListener('resize', resize);

    function rand(a, b) { return Math.random() * (b - a) + a; }

    class Node {
        constructor() { this.reset(); }
        reset() {
            this.x  = rand(0, W);
            this.y  = rand(0, H);
            this.r  = rand(1, 2.5);
            this.vx = rand(-0.28, 0.28);
            this.vy = rand(-0.28, 0.28);
            this.a  = rand(0.45, 0.75);
            this.col = Math.random() > 0.6 ? '6,182,212' : Math.random() > 0.5 ? '59,130,246' : '16,185,129';
        }        update() {
            this.x += this.vx;
            this.y += this.vy;
            if (this.x < 0 || this.x > W) this.vx *= -1;
            if (this.y < 0 || this.y > H) this.vy *= -1;
        }
        draw() {
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.r, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(${this.col},${this.a})`;
            ctx.fill();
        }
    }

    for (let i = 0; i < 150; i++) nodes.push(new Node());

    function drawLinks() {
        for (let i = 0; i < nodes.length; i++) {
            for (let j = i + 1; j < nodes.length; j++) {
                const dx = nodes[i].x - nodes[j].x;
                const dy = nodes[i].y - nodes[j].y;
                const d  = Math.sqrt(dx * dx + dy * dy);
                if (d < 160) {
                    ctx.beginPath();
                    ctx.moveTo(nodes[i].x, nodes[i].y);
                    ctx.lineTo(nodes[j].x, nodes[j].y);
                    ctx.strokeStyle = `rgba(6,182,212,${0.28 * (1 - d / 160)})`;
                    ctx.lineWidth = 0.9;
                    ctx.stroke();
                }
            }        }
    }

    function loop() {
        ctx.clearRect(0, 0, W, H);
        drawLinks();
        nodes.forEach(n => { n.update(); n.draw(); });
        requestAnimationFrame(loop);
    }
    loop();
})();

/* ── 2. ENERGY CHART (Canvas) ── */
(function () {
    const canvas = document.getElementById('energyChart');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const dpr = window.devicePixelRatio || 1;

    function draw() {
        const W = canvas.offsetWidth;
        const H = 80;
        canvas.width  = W * dpr;
        canvas.height = H * dpr;
        canvas.style.width  = W + 'px';
        canvas.style.height = H + 'px';
        ctx.scale(dpr, dpr);

        const data = [210, 285, 260, 340, 295, 380, 342];
        const labels = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
        const max = Math.max(...data) * 1.15;
        const pad = { l: 8, r: 8, t: 8, b: 20 };
        const cW = W - pad.l - pad.r;
        const cH = H - pad.t - pad.b;
        const step = cW / (data.length - 1);

        // Points
        const pts = data.map((v, i) => ({
            x: pad.l + i * step,
            y: pad.t + cH - (v / max) * cH
        }));

        // Area fill
        const grad = ctx.createLinearGradient(0, pad.t, 0, pad.t + cH);
        grad.addColorStop(0, 'rgba(6,182,212,0.22)');
        grad.addColorStop(1, 'rgba(6,182,212,0)');
        ctx.beginPath();
        ctx.moveTo(pts[0].x, pts[0].y);
        for (let i = 1; i < pts.length; i++) {
            const cx = (pts[i - 1].x + pts[i].x) / 2;
            ctx.bezierCurveTo(cx, pts[i - 1].y, cx, pts[i].y, pts[i].x, pts[i].y);
        }
        ctx.lineTo(pts[pts.length - 1].x, pad.t + cH);
        ctx.lineTo(pts[0].x, pad.t + cH);
        ctx.closePath();
        ctx.fillStyle = grad;
        ctx.fill();

        // Line
        ctx.beginPath();
        ctx.moveTo(pts[0].x, pts[0].y);
        for (let i = 1; i < pts.length; i++) {
            const cx = (pts[i - 1].x + pts[i].x) / 2;
            ctx.bezierCurveTo(cx, pts[i - 1].y, cx, pts[i].y, pts[i].x, pts[i].y);
        }
        ctx.strokeStyle = '#06b6d4';
        ctx.lineWidth = 2;
        ctx.stroke();

        // Dots
        pts.forEach((p, i) => {
            ctx.beginPath();
            ctx.arc(p.x, p.y, i === pts.length - 1 ? 4 : 2.5, 0, Math.PI * 2);
            ctx.fillStyle = i === pts.length - 1 ? '#22d3ee' : 'rgba(6,182,212,0.6)';
            ctx.fill();
            if (i === pts.length - 1) {
                ctx.beginPath();
                ctx.arc(p.x, p.y, 7, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(6,182,212,0.15)';
                ctx.fill();
            }
        });

        // Labels
        ctx.fillStyle = 'rgba(148,163,184,0.7)';
        ctx.font = `${9 * dpr / dpr}px Inter, sans-serif`;
        ctx.textAlign = 'center';
        labels.forEach((l, i) => {
            ctx.fillText(l, pts[i].x, H - 4);
        });
    }

    draw();
    window.addEventListener('resize', draw);
})();

/* ── 3. COUNT-UP ── */
document.querySelectorAll('.count-up').forEach(el => {
    const target = parseInt(el.dataset.target);
    let current = 0;
    const step = Math.ceil(target / 55);
    const timer = setInterval(() => {
        current = Math.min(current + step, target);
        el.textContent = current;
        if (current >= target) clearInterval(timer);
    }, 20);
});

/* ── 4. LIVE CLOCK ── */
function updateClock() {
    const el = document.getElementById('liveClock');
    if (!el) return;
    const now = new Date();
    el.textContent = now.toLocaleTimeString('id-ID', { hour12: false });
}
updateClock();
setInterval(updateClock, 1000);

/* ── 1b. LEFT PANEL PARTICLE CANVAS ── */
(function () {
    const canvas = document.getElementById('lp-canvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const panel = canvas.parentElement;
    let W, H, nodes = [];

    function resize() {
        W = canvas.width  = panel.offsetWidth;
        H = canvas.height = panel.offsetHeight;
    }
    resize();
    window.addEventListener('resize', resize);

    // 55 partikel — lebih banyak karena panel lebih lebar
    for (let i = 0; i < 55; i++) {
        nodes.push({
            x:  Math.random() * W,
            y:  Math.random() * H,
            vx: (Math.random() - 0.5) * 0.22,
            vy: (Math.random() - 0.5) * 0.22,
            r:  Math.random() * 1.8 + 0.5,
            a:  Math.random() * 0.35 + 0.15,
            col: Math.random() > 0.6 ? '6,182,212'
               : Math.random() > 0.5 ? '59,130,246'
               : '16,185,129',
        });
    }

    function tick() {
        ctx.clearRect(0, 0, W, H);
        nodes.forEach(n => {
            n.x += n.vx; n.y += n.vy;
            if (n.x < 0 || n.x > W) n.vx *= -1;
            if (n.y < 0 || n.y > H) n.vy *= -1;
            ctx.beginPath();
            ctx.arc(n.x, n.y, n.r, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(${n.col},${n.a})`;
            ctx.fill();
        });
        for (let i = 0; i < nodes.length; i++) {
            for (let j = i + 1; j < nodes.length; j++) {
                const dx = nodes[i].x - nodes[j].x;
                const dy = nodes[i].y - nodes[j].y;
                const d  = Math.sqrt(dx * dx + dy * dy);
                if (d < 130) {
                    ctx.beginPath();
                    ctx.moveTo(nodes[i].x, nodes[i].y);
                    ctx.lineTo(nodes[j].x, nodes[j].y);
                    ctx.strokeStyle = `rgba(6,182,212,${0.13 * (1 - d / 130)})`;
                    ctx.lineWidth = 0.7;
                    ctx.stroke();
                }
            }
        }
        requestAnimationFrame(tick);
    }
    tick();
})();

/* ── 1c. RIGHT PANEL PARTICLE CANVAS ── */
(function () {
    const canvas = document.getElementById('rp-canvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const panel = canvas.parentElement;
    let W, H, nodes = [];

    function resize() {
        W = canvas.width  = panel.offsetWidth;
        H = canvas.height = panel.offsetHeight;
    }
    resize();
    window.addEventListener('resize', resize);

    for (let i = 0; i < 40; i++) {
        nodes.push({
            x:  Math.random() * W,
            y:  Math.random() * H,
            vx: (Math.random() - 0.5) * 0.25,
            vy: (Math.random() - 0.5) * 0.25,
            r:  Math.random() * 1.6 + 0.4,
            a:  Math.random() * 0.4 + 0.2,
            col: Math.random() > 0.5 ? '6,182,212' : '59,130,246',
        });
    }

    function tick() {
        ctx.clearRect(0, 0, W, H);
        nodes.forEach(n => {
            n.x += n.vx; n.y += n.vy;
            if (n.x < 0 || n.x > W) n.vx *= -1;
            if (n.y < 0 || n.y > H) n.vy *= -1;
            ctx.beginPath();
            ctx.arc(n.x, n.y, n.r, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(${n.col},${n.a})`;
            ctx.fill();
        });
        for (let i = 0; i < nodes.length; i++) {
            for (let j = i + 1; j < nodes.length; j++) {
                const dx = nodes[i].x - nodes[j].x;
                const dy = nodes[i].y - nodes[j].y;
                const d  = Math.sqrt(dx * dx + dy * dy);
                if (d < 120) {
                    ctx.beginPath();
                    ctx.moveTo(nodes[i].x, nodes[i].y);
                    ctx.lineTo(nodes[j].x, nodes[j].y);
                    ctx.strokeStyle = `rgba(6,182,212,${0.14 * (1 - d / 120)})`;
                    ctx.lineWidth = 0.7;
                    ctx.stroke();
                }
            }
        }
        requestAnimationFrame(tick);
    }
    tick();
})();

/* ── 1d. SPKLU ILLUSTRATION PARTICLE OVERLAY ── */
(function () {
    const canvas = document.getElementById('spklu-particles');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const wrap = canvas.parentElement;

    function resize() {
        canvas.width  = wrap.offsetWidth;
        canvas.height = wrap.offsetHeight;
    }
    resize();
    window.addEventListener('resize', resize);

    const W = () => canvas.width;
    const H = () => canvas.height;

    // Partikel kecil melayang di atas ilustrasi
    const particles = [];
    for (let i = 0; i < 60; i++) {
        particles.push({
            x:    Math.random() * 640,
            y:    Math.random() * 210,
            vx:   (Math.random() - 0.5) * 0.6,
            vy:   -(Math.random() * 0.4 + 0.1), // naik ke atas
            r:    Math.random() * 2 + 0.5,
            a:    Math.random() * 0.6 + 0.3,
            life: Math.random(),
            col:  Math.random() > 0.5 ? '6,182,212'
                : Math.random() > 0.5 ? '34,211,238'
                : '16,185,129',
        });
    }

    // Partikel energi di sekitar kabel (area tengah)
    const energyParticles = [];
    for (let i = 0; i < 20; i++) {
        energyParticles.push({
            // Posisi di sekitar kabel (x: 130-250, y: 140-165)
            x:    130 + Math.random() * 120,
            y:    140 + Math.random() * 25,
            vx:   (Math.random() - 0.5) * 0.8,
            vy:   -(Math.random() * 0.6 + 0.2),
            r:    Math.random() * 1.5 + 0.5,
            a:    Math.random() * 0.8 + 0.4,
            col:  '6,182,212',
        });
    }

    function scaleX(x) { return (x / 640) * W(); }
    function scaleY(y) { return (y / 210) * H(); }

    function tick() {
        ctx.clearRect(0, 0, W(), H());

        // Partikel umum
        particles.forEach(p => {
            p.x += p.vx;
            p.y += p.vy;
            p.life -= 0.003;

            // Reset saat keluar atau habis life
            if (p.y < -5 || p.life <= 0 || p.x < 0 || p.x > 640) {
                p.x    = Math.random() * 640;
                p.y    = 210;
                p.life = 1;
                p.vy   = -(Math.random() * 0.4 + 0.1);
                p.vx   = (Math.random() - 0.5) * 0.6;
            }

            const alpha = p.a * p.life;
            ctx.beginPath();
            ctx.arc(scaleX(p.x), scaleY(p.y), p.r, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(${p.col},${alpha})`;
            ctx.fill();
        });

        // Partikel energi di kabel — lebih terang dan lebih cepat
        energyParticles.forEach(p => {
            p.x += p.vx;
            p.y += p.vy;

            if (p.y < 100 || p.x < 100 || p.x > 280) {
                p.x  = 130 + Math.random() * 120;
                p.y  = 140 + Math.random() * 25;
                p.vy = -(Math.random() * 0.6 + 0.2);
                p.vx = (Math.random() - 0.5) * 0.8;
            }

            ctx.beginPath();
            ctx.arc(scaleX(p.x), scaleY(p.y), p.r, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(${p.col},${p.a})`;
            ctx.fill();

            // Glow effect
            ctx.beginPath();
            ctx.arc(scaleX(p.x), scaleY(p.y), p.r * 2.5, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(6,182,212,0.06)`;
            ctx.fill();
        });

        requestAnimationFrame(tick);
    }
    tick();
})();

/* ── 4b. LIVE METRICS ANIMATION ── */
(function () {
    // Simulasi data real-time yang berubah tiap beberapa detik
    const powerEl  = document.getElementById('lmPower');
    const co2El    = document.getElementById('lmCo2');
    const uptimeEl = document.getElementById('lmUptime');
    if (!powerEl) return;

    function randBetween(min, max, dec) {
        return (Math.random() * (max - min) + min).toFixed(dec);
    }

    function updateMetrics() {
        // Daya berfluktuasi 18-26 kW
        const power = randBetween(18.0, 26.5, 1);
        powerEl.innerHTML = power + ' <span>kW</span>';

        // CO2 perlahan naik
        const co2 = randBetween(14.2, 16.8, 1);
        co2El.innerHTML = co2 + ' <span>kg</span>';

        // Uptime stabil 99.x%
        const uptime = randBetween(99.6, 99.9, 1);
        uptimeEl.innerHTML = uptime + ' <span>%</span>';
    }

    // Update tiap 3 detik
    setInterval(updateMetrics, 3000);
})();

/* ── 5. VALIDATION ── */
const eI = document.getElementById('emailInput');
const pI = document.getElementById('passwordInput');
const eM = document.getElementById('emailMsg');
const pM = document.getElementById('passMsg');
const eS = document.getElementById('emailStatus');
const pS = document.getElementById('passStatus');

function setOk(i, s, m, msg) {
    i.classList.remove('is-invalid'); i.classList.add('is-valid');
    s.className = 'input-status show valid';
    s.innerHTML = '<i class="fas fa-check-circle"></i>';
    m.className = 'validation-msg valid';
    m.innerHTML = '<i class="fas fa-check" style="font-size:10px"></i> ' + msg;
}
function setErr(i, s, m, msg) {
    i.classList.remove('is-valid'); i.classList.add('is-invalid');
    s.className = 'input-status show invalid';
    s.innerHTML = '<i class="fas fa-times-circle"></i>';
    m.className = 'validation-msg invalid';
    m.innerHTML = '<i class="fas fa-exclamation-circle" style="font-size:10px"></i> ' + msg;
}
function clr(i, s, m) {
    i.classList.remove('is-valid', 'is-invalid');
    s.className = 'input-status'; s.innerHTML = '';
    m.className = 'validation-msg'; m.innerHTML = '';
}

eI.addEventListener('input', function () {
    const v = this.value.trim();
    if (!v) { clr(this, eS, eM); return; }
    /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)
        ? setOk(this, eS, eM, 'Format email valid')
        : setErr(this, eS, eM, 'Format email tidak valid');
});
pI.addEventListener('input', function () {
    const v = this.value;
    if (!v) { clr(this, pS, pM); return; }
    v.length >= 6
        ? setOk(this, pS, pM, 'Password terisi')
        : setErr(this, pS, pM, 'Minimal 6 karakter');
});

/* ── 6. TOGGLE PASSWORD ── */
document.getElementById('togglePassBtn').addEventListener('click', function () {
    const i = document.getElementById('passwordInput');
    const ic = document.getElementById('eyeIcon');
    if (i.type === 'password') { i.type = 'text'; ic.className = 'fas fa-eye-slash'; }
    else { i.type = 'password'; ic.className = 'fas fa-eye'; }
});

/* ── 7. RIPPLE + LOADING ── */
const loginBtn  = document.getElementById('loginBtn');
const loginForm = document.getElementById('loginForm');

loginBtn.addEventListener('click', function (e) {
    const r  = this.getBoundingClientRect();
    const rp = document.createElement('span');
    const sz = Math.max(r.width, r.height);
    rp.className = 'ripple';
    rp.style.cssText = `width:${sz}px;height:${sz}px;left:${e.clientX - r.left - sz / 2}px;top:${e.clientY - r.top - sz / 2}px;`;
    this.appendChild(rp);
    setTimeout(() => rp.remove(), 700);
});

loginForm.addEventListener('submit', function (e) {
    const em = eI.value.trim(), pw = pI.value;
    let valid = true;
    if (!em || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) { setErr(eI, eS, eM, 'Masukkan email yang valid'); valid = false; }
    if (!pw || pw.length < 6) { setErr(pI, pS, pM, 'Password minimal 6 karakter'); valid = false; }
    if (!valid) { e.preventDefault(); return; }
    loginBtn.disabled = true;
    loginBtn.classList.add('loading');
    document.getElementById('btnSpinner').style.display = 'block';
    document.getElementById('btnText').innerHTML = 'Memproses...';
});

/* ── COUNTDOWN TIMER untuk akun terkunci ── */
(function () {
    const notice = document.getElementById('lockNotice');
    if (!notice) return;

    const totalSec   = parseInt(notice.dataset.seconds, 10) || 30;
    const counterEl  = document.getElementById('lockCounter');
    const labelEl    = document.getElementById('lockCounterLabel');
    const titleEl    = document.getElementById('lockTitle');
    const msgEl      = document.getElementById('lockMessage');
    const progressEl = document.getElementById('lockProgress');
    const iconEl     = document.getElementById('lockIconSymbol');
    const loginBtn   = document.getElementById('loginBtn');
    const emailInput = document.getElementById('emailInput');
    const passInput  = document.getElementById('passwordInput');

    // Disable form selama lock
    loginBtn.disabled = true;
    loginBtn.style.opacity = '0.5';
    loginBtn.style.cursor  = 'not-allowed';
    if (emailInput) emailInput.disabled = true;
    if (passInput)  passInput.disabled  = true;

    let remaining = totalSec;

    // Set progress bar awal
    progressEl.style.width = '100%';

    const tick = setInterval(function () {
        remaining--;

        // Update angka
        counterEl.textContent = remaining;

        // Update progress bar (mengecil dari 100% ke 0%)
        const pct = (remaining / totalSec) * 100;
        progressEl.style.width = pct + '%';

        // Warna berubah saat mendekati selesai
        if (remaining <= 10) {
            progressEl.style.background = 'linear-gradient(90deg, #f59e0b, #fbbf24)';
            counterEl.style.color = '#fbbf24';
        }

        if (remaining <= 0) {
            clearInterval(tick);

            // === BERUBAH HIJAU — akun sudah bisa dipakai ===
            notice.classList.add('unlocked');
            progressEl.style.width = '100%';
            progressEl.style.background = 'linear-gradient(90deg, #10b981, #34d399)';

            counterEl.textContent = '✓';
            counterEl.style.color = '#34d399';
            counterEl.style.fontSize = '20px';
            labelEl.textContent = 'Siap!';
            labelEl.style.color = '#34d399';

            iconEl.className = 'fas fa-lock-open';

            titleEl.textContent = '✅ Akun Sudah Bisa Digunakan';
            titleEl.style.color = '#34d399';
            msgEl.textContent   = 'Silakan coba login kembali sekarang.';

            // Enable form kembali
            loginBtn.disabled = false;
            loginBtn.style.opacity = '1';
            loginBtn.style.cursor  = 'pointer';
            if (emailInput) emailInput.disabled = false;
            if (passInput)  passInput.disabled  = false;

            // Animasi glow hijau pada tombol
            loginBtn.style.boxShadow = '0 0 20px rgba(16,185,129,0.5)';
            loginBtn.style.background = 'linear-gradient(135deg, #10b981, #34d399)';

            // Auto-focus ke password
            if (passInput) passInput.focus();

            // Hilangkan notifikasi setelah 5 detik
            setTimeout(function () {
                notice.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
                notice.style.opacity = '0';
                notice.style.transform = 'translateY(-8px)';
                setTimeout(() => notice.remove(), 800);

                // Kembalikan warna tombol normal
                loginBtn.style.boxShadow = '';
                loginBtn.style.background = '';
            }, 5000);
        }
    }, 1000);
})();

/* ── 8. THEME TOGGLE (login page only) ── */
const tBtn = document.getElementById('themeBtn');
const tIco = document.getElementById('themeIcon');

function applyLoginTheme(theme) {
    if (theme === 'light') {
        document.body.classList.add('login-light');
        document.body.classList.remove('login-dark');
        tIco.className = 'fas fa-sun';
    } else {
        document.body.classList.add('login-dark');
        document.body.classList.remove('login-light');
        tIco.className = 'fas fa-moon';
    }
    localStorage.setItem('login-theme', theme);
}

// Terapkan tema tersimpan saat halaman load
const savedLoginTheme = localStorage.getItem('login-theme') || 'dark';
applyLoginTheme(savedLoginTheme);

tBtn.addEventListener('click', function () {
    const current = localStorage.getItem('login-theme') || 'dark';
    applyLoginTheme(current === 'dark' ? 'light' : 'dark');
});
</script>
</body>
</html>
