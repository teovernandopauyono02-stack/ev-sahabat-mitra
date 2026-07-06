<div class="api-connection-banner connected">
    <div class="banner-icon">
        <i class="fas fa-plug"></i>
    </div>
    <div class="banner-content">
        <h3>Terhubung ke {{ $companyInfo['company_name'] }}</h3>
        <p>Dashboard menampilkan data real-time dari API eksternal</p>
        <div class="banner-meta">
            <span>
                <i class="fas fa-clock"></i>
                Last sync: {{ $companyInfo['last_sync']->diffForHumans() }}
            </span>
            <span>
                <i class="fas fa-link"></i>
                {{ $companyInfo['api_url'] }}
            </span>
        </div>
    </div>
    <div class="banner-action">
        <a href="{{ route('api-integration.index') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-cog"></i> Kelola API
        </a>
    </div>
</div>
@else
<div class="api-connection-banner local">
    <div class="banner-icon">
        <i class="fas fa-database"></i>
    </div>
    <div class="banner-content">
        <h3>Menggunakan Data Lokal</h3>
        <p>Tidak ada koneksi API aktif. Dashboard menampilkan data dari database lokal.</p>
    </div>
    <div class="banner-action">
        <a href="{{ route('api-integration.index') }}" class="btn btn-sm btn-primary">
            <i class="fas fa-search"></i> Cari API Perusahaan
        </a>
    </div>
</div>
@endif

<style>
.api-connection-banner {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.api-connection-banner.connected {
    background: linear-gradient(135deg, rgba(0, 210, 255, 0.1) 0%, rgba(0, 210, 255, 0.05) 100%);
    border-color: rgba(0, 210, 255, 0.3);
}

.api-connection-banner.local {
    background: rgba(255, 255, 255, 0.02);
    border-color: rgba(255, 255, 255, 0.1);
}

.banner-icon {
    width: 60px;
    height: 60px;
    background: rgba(0, 210, 255, 0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: var(--primary);
    flex-shrink: 0;
}

.local .banner-icon {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-muted);
}

.banner-content {
    flex: 1;
}

.banner-content h3 {
    margin: 0 0 0.5rem 0;
    color: var(--text);
    font-size: 1.2rem;
}

.banner-content p {
    margin: 0 0 0.75rem 0;
    color: var(--text-muted);
    font-size: 0.9rem;
}

.banner-meta {
    display: flex;
    gap: 2rem;
    font-size: 0.85rem;
    color: var(--text-muted);
}

.banner-meta span {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.banner-meta i {
    color: var(--primary);
}

.banner-action {
    flex-shrink: 0;
}

@media (max-width: 768px) {
    .api-connection-banner {
        flex-direction: column;
        text-align: center;
    }
    
    .banner-meta {
        flex-direction: column;
        gap: 0.5rem;
    }
}
</style>