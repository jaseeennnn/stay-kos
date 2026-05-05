<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="card border-0 rounded-3 mb-4 overflow-hidden"
     style="background:linear-gradient(135deg,#0f3460,#16213e);color:#fff;">
    <div class="card-body p-4 d-flex align-items-center gap-4">
        <div style="font-size:3rem;opacity:.9;">
            <i class="bi bi-house-heart-fill text-warning"></i>
        </div>
        <div>
            <h4 class="fw-bold mb-1">Selamat Datang, <?= esc(session()->get('username')) ?>! 👋</h4>
            <p class="mb-0 opacity-75">Kelola pemesanan kamar kos Anda dengan mudah.</p>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php
    $statuses = [
        ['label' => 'Total Booking',  'value' => $stats['total'],    'color' => 'primary', 'icon' => 'bi-calendar-check'],
        ['label' => 'Menunggu',       'value' => $stats['pending'],  'color' => 'warning',  'icon' => 'bi-hourglass-split'],
        ['label' => 'Disetujui',      'value' => $stats['approved'], 'color' => 'success',  'icon' => 'bi-check-circle'],
        ['label' => 'Ditolak',        'value' => $stats['rejected'], 'color' => 'danger',   'icon' => 'bi-x-circle'],
    ];
    foreach ($statuses as $s): ?>
    <div class="col-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 p-2 bg-<?= $s['color'] ?> bg-opacity-10 text-<?= $s['color'] ?>"
                     style="font-size:1.5rem;width:48px;height:48px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi <?= $s['icon'] ?>"></i>
                </div>
                <div>
                    <div class="text-muted small"><?= $s['label'] ?></div>
                    <div class="fs-4 fw-bold"><?= $s['value'] ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">Booking Terbaru</h6>
        <a href="/user/bookings" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Kamar</th><th>Check-in</th>
                        <th>Durasi</th><th>Total</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($myBookings)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            Belum ada booking. <a href="/user/rooms">Cari kamar sekarang!</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($myBookings as $b): ?>
                    <tr>
                        <td class="ps-3 fw-semibold"><?= esc($b['room_name']) ?></td>
                        <td><?= date('d M Y', strtotime($b['checkin_date'])) ?></td>
                        <td><?= $b['duration'] ?> bulan</td>
                        <td>Rp <?= number_format($b['total_price'], 0, ',', '.') ?></td>
                        <td>
                            <?php $badge = match ($b['status']) {
                                'pending'  => 'warning text-dark',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default    => 'secondary',
                            }; ?>
                            <span class="badge bg-<?= $badge ?>"><?= ucfirst($b['status']) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>