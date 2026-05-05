<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="row g-4 mb-4">
    <?php
    $cards = [
        ['icon' => 'bi-door-open',       'color' => 'primary', 'label' => 'Total Kamar',     'value' => $totalRooms],
        ['icon' => 'bi-people',          'color' => 'info',    'label' => 'Total Pengguna',  'value' => $totalUsers],
        ['icon' => 'bi-calendar-check',  'color' => 'success', 'label' => 'Total Booking',   'value' => $totalBookings],
        ['icon' => 'bi-hourglass-split', 'color' => 'warning', 'label' => 'Pending Booking', 'value' => $pendingBookings],
    ];
    foreach ($cards as $card): ?>
    <div class="col-xl-3 col-sm-6">
        <div class="card stat-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 p-2 bg-<?= $card['color'] ?> bg-opacity-10 text-<?= $card['color'] ?>"
                     style="font-size:1.4rem;width:52px;height:52px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi <?= $card['icon'] ?>"></i>
                </div>
                <div>
                    <div class="text-muted small"><?= $card['label'] ?></div>
                    <div class="fs-3 fw-bold"><?= $card['value'] ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-3 p-4">
            <h6 class="fw-semibold mb-3">Status Booking</h6>
            <canvas id="bookingChart" height="200"></canvas>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-3 p-4">
            <h6 class="fw-semibold mb-3">Ringkasan</h6>
            <table class="table table-borderless mb-0">
                <tbody>
                    <tr><td>Booking Disetujui</td><td><span class="badge bg-success"><?= $approvedBookings ?></span></td></tr>
                    <tr><td>Booking Pending</td><td><span class="badge bg-warning text-dark"><?= $pendingBookings ?></span></td></tr>
                    <tr><td>Booking Ditolak</td><td><span class="badge bg-danger"><?= $rejectedBookings ?></span></td></tr>
                    <tr><td>Total Kamar</td><td><span class="badge bg-primary"><?= $totalRooms ?></span></td></tr>
                    <tr><td>Pengguna Terdaftar</td><td><span class="badge bg-info"><?= $totalUsers ?></span></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('bookingChart'), {
    type: 'doughnut',
    data: {
        labels: ['Disetujui', 'Pending', 'Ditolak'],
        datasets: [{
            data: [<?= $approvedBookings ?>, <?= $pendingBookings ?>, <?= $rejectedBookings ?>],
            backgroundColor: ['#198754', '#ffc107', '#dc3545'],
            borderWidth: 0,
        }]
    },
    options: {
        cutout: '70%',
        plugins: { legend: { position: 'bottom' } }
    }
});
</script>
<?= $this->endSection() ?>