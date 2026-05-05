<div class="sidebar">
    <div class="brand">
        <i class="bi bi-house-heart-fill text-warning me-2"></i>StayKos
    </div>
    <nav class="nav flex-column mt-2 flex-grow-1">
        <?php if (session()->get('role') === 'admin'): ?>
            <a class="nav-link <?= uri_string() === 'admin/dashboard' ? 'active' : '' ?>" href="/admin/dashboard">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a class="nav-link <?= strpos(uri_string(), 'admin/rooms') !== false ? 'active' : '' ?>" href="/admin/rooms">
                <i class="bi bi-door-open"></i> Kamar
            </a>
            <a class="nav-link <?= strpos(uri_string(), 'admin/bookings') !== false ? 'active' : '' ?>" href="/admin/bookings">
                <i class="bi bi-calendar-check"></i> Booking
            </a>
            <a class="nav-link <?= strpos(uri_string(), 'admin/payments') !== false ? 'active' : '' ?>" href="/admin/payments">
                <i class="bi bi-credit-card"></i> Pembayaran
            </a>
            <a class="nav-link <?= strpos(uri_string(), 'admin/installments') !== false ? 'active' : '' ?>" href="/admin/installments">
                <i class="bi bi-calendar2-week"></i> Cicilan
            </a>
        <?php else: ?>
            <a class="nav-link <?= uri_string() === 'user/dashboard' ? 'active' : '' ?>" href="/user/dashboard">
                <i class="bi bi-house"></i> Beranda
            </a>
            <a class="nav-link <?= strpos(uri_string(), 'user/rooms') !== false ? 'active' : '' ?>" href="/user/rooms">
                <i class="bi bi-door-open"></i> Cari Kamar
            </a>
            <a class="nav-link <?= strpos(uri_string(), 'user/bookings') !== false ? 'active' : '' ?>" href="/user/bookings">
                <i class="bi bi-calendar2-check"></i> Booking Saya
            </a>
            <a class="nav-link <?= strpos(uri_string(), 'user/payments') !== false ? 'active' : '' ?>" href="/user/payments">
                <i class="bi bi-receipt"></i> Pembayaran
            </a>
        <?php endif; ?>
    </nav>
    <div class="p-3 text-muted small text-center" style="border-top:1px solid rgba(255,255,255,0.1);">
        &copy; <?= date('Y') ?> StayKos
    </div>
</div>