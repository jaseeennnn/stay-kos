<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= esc($pageTitle ?? $title ?? 'Dashboard') ?> | StayKos</title>
    <meta name="description" content="<?= esc($metaDesc ?? 'Sistem booking kamar kos modern.') ?>">
    <meta name="robots"      content="<?= $allowIndex ?? 'noindex, nofollow' ?>">
    <link rel="canonical"    href="<?= current_url() ?>">

    <!-- Open Graph -->
    <meta property="og:title"       content="<?= esc($pageTitle ?? 'StayKos') ?>">
    <meta property="og:description" content="<?= esc($metaDesc ?? 'Sistem booking kos.') ?>">
    <meta property="og:url"         content="<?= current_url() ?>">
    <meta property="og:type"        content="website">

    <!-- Preconnect CDN -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://cdn.datatables.net" crossorigin>

    <!-- Bootstrap 5 + Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

    <meta name="theme-color" content="#1a1a2e">
    <link rel="manifest"     href="<?= base_url('manifest.json') ?>">

    <style>
        body { background: #f0f2f5; }
        .sidebar {
            min-height: 100vh;
            background: #1a1a2e;
            color: #fff;
            width: 250px;
            position: fixed;
            top: 0; left: 0;
            z-index: 1000;
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .sidebar .brand {
            padding: 1.5rem 1rem;
            font-size: 1.2rem;
            font-weight: 700;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.75);
            padding: 0.7rem 1rem;
            border-radius: 8px;
            margin: 2px 10px;
            transition: all 0.2s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,0.15);
        }
        .sidebar .nav-link i { margin-right: 8px; width: 18px; }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
            animation: fadeIn 0.2s ease;
        }
        .topbar {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: 0.6rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: -20px -20px 24px -20px;
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }
        .stat-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
        }
        .dropdown-menu { animation: dropIn 0.15s ease; }
        @keyframes dropIn {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(4px); }
            to   { opacity: 1; transform: none; }
        }
        .dataTables_wrapper { min-height: 200px; }
        .sidebar-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.4);
            z-index: 999;
        }
        .sidebar-overlay.show { display: block; }
        @media (max-width: 1199px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; }
        }

        /* ── Bell Notification dropdown ── */
        .notif-dropdown {
            min-width: 320px;
            max-height: 420px;
            overflow-y: auto;
            padding: 0;
        }
        .notif-dropdown .notif-header {
            padding: 0.75rem 1rem;
            font-weight: 600;
            font-size: 0.82rem;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            background: #fff;
            z-index: 1;
        }
        .notif-item {
            padding: 0.65rem 1rem;
            border-bottom: 1px solid #f5f5f5;
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
            font-size: 0.82rem;
            transition: background 0.15s;
            text-decoration: none;
            color: inherit;
        }
        .notif-item:hover { background: #f8f9fa; }
        .notif-item .notif-icon {
            width: 32px; height: 32px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            font-size: 0.9rem;
        }
        .notif-item .notif-text { line-height: 1.35; }
        .notif-item .notif-time { font-size: 0.7rem; color: #aaa; }
        .notif-footer {
            padding: 0.6rem 1rem;
            text-align: center;
            font-size: 0.78rem;
            border-top: 1px solid #f0f0f0;
            position: sticky;
            bottom: 0;
            background: #fff;
        }
        #bellBtn.has-notif { animation: bellShake 1s ease 0.5s; }
        @keyframes bellShake {
            0%,100% { transform: rotate(0); }
            20%      { transform: rotate(15deg); }
            40%      { transform: rotate(-12deg); }
            60%      { transform: rotate(8deg); }
            80%      { transform: rotate(-5deg); }
        }
    </style>

    <?= $this->renderSection('styles') ?>

    <!-- JSON-LD Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebApplication",
        "name": "StayKos",
        "description": "Sistem pemesanan kamar kos online",
        "url": "<?= base_url() ?>",
        "applicationCategory": "BusinessApplication"
    }
    </script>
</head>
<body>

<?= $this->include('layouts/sidebar') ?>

<div class="main-content">
    <!-- Topbar Premium -->
    <div class="topbar">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-light d-xl-none" id="sidebarToggle">
                <i class="bi bi-list fs-5"></i>
            </button>
            <div>
                <h6 class="mb-0 fw-semibold"><?= $title ?? 'Dashboard' ?></h6>
                <nav aria-label="breadcrumb" class="d-none d-sm-block">
                    <ol class="breadcrumb mb-0" style="font-size:0.72rem;">
                        <li class="breadcrumb-item">
                            <a href="<?= session()->get('role') === 'admin' ? '/admin/dashboard' : '/user/dashboard' ?>"
                               class="text-decoration-none text-muted">Home</a>
                        </li>
                        <li class="breadcrumb-item active text-primary"><?= $title ?? 'Dashboard' ?></li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">

            <!-- ── Notif Bell Dropdown (FIXED) ── -->
            <div class="dropdown">
                <button id="bellBtn"
                        class="btn btn-sm btn-light rounded-circle p-0 d-flex align-items-center justify-content-center position-relative <?= (($pendingCount ?? 0) + ($pendingInstallments ?? 0)) > 0 ? 'has-notif' : '' ?>"
                        style="width:38px;height:38px;"
                        data-bs-toggle="dropdown"
                        data-bs-auto-close="outside"
                        aria-expanded="false"
                        title="Notifikasi">
                    <i class="bi bi-bell<?= (($pendingCount ?? 0) + ($pendingInstallments ?? 0)) > 0 ? '-fill text-warning' : '' ?> fs-5"></i>
                    <?php
                        $totalNotif = ($pendingCount ?? 0) + ($pendingInstallments ?? 0);
                    ?>
                    <?php if ($totalNotif > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                          style="font-size:0.6rem;pointer-events:none;">
                        <?= $totalNotif > 99 ? '99+' : $totalNotif ?>
                    </span>
                    <?php endif; ?>
                </button>

                <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 mt-1 notif-dropdown p-0">
                    <!-- Header -->
                    <li class="notif-header">
                        <span><i class="bi bi-bell-fill text-warning me-1"></i> Notifikasi</span>
                        <?php if ($totalNotif > 0): ?>
                        <span class="badge bg-danger rounded-pill"><?= $totalNotif ?></span>
                        <?php else: ?>
                        <span class="text-muted" style="font-weight:400;">Tidak ada</span>
                        <?php endif; ?>
                    </li>

                    <?php if (session()->get('role') === 'admin'): ?>

                        <?php if (($pendingCount ?? 0) > 0): ?>
                        <li>
                            <a class="notif-item" href="/admin/bookings">
                                <div class="notif-icon bg-warning bg-opacity-15 text-warning">
                                    <i class="bi bi-calendar-check"></i>
                                </div>
                                <div class="notif-text">
                                    <div class="fw-semibold">Booking Menunggu Persetujuan</div>
                                    <div class="text-muted">
                                        <strong class="text-warning"><?= $pendingCount ?></strong> booking belum diproses
                                    </div>
                                    <div class="notif-time">Klik untuk lihat detail</div>
                                </div>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if (($pendingInstallments ?? 0) > 0): ?>
                        <li>
                            <a class="notif-item" href="/admin/installments">
                                <div class="notif-icon bg-info bg-opacity-15 text-info">
                                    <i class="bi bi-calendar2-week"></i>
                                </div>
                                <div class="notif-text">
                                    <div class="fw-semibold">Cicilan Menunggu Verifikasi</div>
                                    <div class="text-muted">
                                        <strong class="text-info"><?= $pendingInstallments ?></strong> bukti cicilan belum diverifikasi
                                    </div>
                                    <div class="notif-time">Klik untuk verifikasi</div>
                                </div>
                            </a>
                        </li>
                        <?php endif; ?>

                    <?php else: /* role = user */ ?>

                        <?php if (($pendingCount ?? 0) > 0): ?>
                        <li>
                            <a class="notif-item" href="/user/bookings">
                                <div class="notif-icon bg-warning bg-opacity-15 text-warning">
                                    <i class="bi bi-hourglass-split"></i>
                                </div>
                                <div class="notif-text">
                                    <div class="fw-semibold">Booking Menunggu Konfirmasi</div>
                                    <div class="text-muted">
                                        <strong class="text-warning"><?= $pendingCount ?></strong> booking kamu sedang diproses admin
                                    </div>
                                    <div class="notif-time">Klik untuk lihat status</div>
                                </div>
                            </a>
                        </li>
                        <?php endif; ?>

                    <?php endif; ?>

                    <?php if ($totalNotif === 0): ?>
                    <li>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-check-circle-fill text-success fs-4 d-block mb-2"></i>
                            <div class="small">Semua sudah beres!</div>
                        </div>
                    </li>
                    <?php endif; ?>

                    <!-- Footer -->
                    <li class="notif-footer">
                        <?php if (session()->get('role') === 'admin'): ?>
                            <a href="/admin/bookings" class="text-primary text-decoration-none small">
                                Lihat semua booking →
                            </a>
                        <?php else: ?>
                            <a href="/user/bookings" class="text-primary text-decoration-none small">
                                Lihat semua booking saya →
                            </a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
            <!-- ── End Bell ── -->

            <!-- User Dropdown -->
            <div class="dropdown">
                <button class="btn btn-sm p-0 d-flex align-items-center gap-2 rounded-pill px-2 py-1"
                        style="background:#f0f2f5;border:1px solid #e5e7eb;"
                        type="button" data-bs-toggle="dropdown">
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                         style="width:34px;height:34px;font-size:0.85rem;
                                background:<?= session()->get('role') === 'admin'
                                    ? 'linear-gradient(135deg,#dc3545,#c0392b)'
                                    : 'linear-gradient(135deg,#0f3460,#0d6efd)' ?>;">
                        <?= strtoupper(substr((string)session()->get('username'), 0, 1)) ?>
                    </div>
                    
                    <i class="bi bi-chevron-down text-muted" style="font-size:0.7rem;"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 mt-1" style="min-width:200px;">
                    <li class="px-3 py-2 border-bottom">
                        <div class="fw-semibold small"><?= esc(session()->get('username')) ?></div>
                        <div class="text-muted" style="font-size:0.72rem;"><?= session()->get('role') === 'admin' ? 'Administrator' : 'Penyewa' ?></div>
                    </li>
                    <!-- <li><hr class="dropdown-divider my-1"></li> -->
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2 text-danger" href="/logout">
                            <i class="bi bi-box-arrow-right"></i>
                            <span class="small fw-semibold">Keluar</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Flash messages -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Page Content -->
    <?= $this->renderSection('content') ?>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<!-- Global CSRF — digunakan oleh semua AJAX di setiap view -->
<script>
    window.CSRF_NAME = '<?= csrf_token() ?>';
    window.CSRF_HASH = '<?= csrf_hash() ?>';
</script>

<!-- Sidebar mobile toggle -->
<script>
    const sidebarEl = document.querySelector('.sidebar');
    const overlay   = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);

    document.getElementById('sidebarToggle')?.addEventListener('click', () => {
        sidebarEl.classList.toggle('open');
        overlay.classList.toggle('show');
    });
    overlay.addEventListener('click', () => {
        sidebarEl.classList.remove('open');
        overlay.classList.remove('show');
    });

    // Lazy-load images
    document.addEventListener('DOMContentLoaded', () => {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy-img');
                    observer.unobserve(img);
                }
            });
        }, { rootMargin: '100px' });
        document.querySelectorAll('.lazy-img').forEach(img => observer.observe(img));
    });
</script>

<?= $this->renderSection('scripts') ?>
</body>
</html>