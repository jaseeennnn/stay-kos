<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$room     = $booking['room_name'] ?? 'Kamar';
$total    = $booking['total_price'] ?? 0;
$dur      = $booking['duration'] ?? 1;
$monthly  = $dur > 0 ? $total / $dur : 0;
$paid     = count(array_filter($schedule, fn($s) => $s['status'] === 'paid'));
$paidAmt  = $paid * $monthly;
$progress = $dur > 0 ? round(($paid / $dur) * 100) : 0;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Jadwal Cicilan — <?= esc($room) ?></h4>
        <p class="text-muted mb-0">
            Total: <strong>Rp <?= number_format($total, 0, ',', '.') ?></strong>
            selama <strong><?= $dur ?> bulan</strong>
            (Rp <?= number_format($monthly, 0, ',', '.') ?>/bulan)
        </p>
    </div>
    <a href="/user/bookings" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

<!-- Progress Bar -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between mb-2">
            <span class="fw-semibold">Progress Pelunasan</span>
            <span class="text-muted small"><?= $paid ?>/<?= $dur ?> bulan lunas</span>
        </div>
        <div class="progress rounded-pill" style="height:12px;">
            <div class="progress-bar bg-success progress-bar-striped progress-bar-animated"
                 style="width:<?= $progress ?>%"></div>
        </div>
        <div class="d-flex justify-content-between mt-2">
            <small class="text-success fw-semibold">Terbayar: Rp <?= number_format($paidAmt, 0, ',', '.') ?></small>
            <small class="text-muted"><?= $progress ?>% selesai</small>
            <small class="text-danger fw-semibold">Sisa: Rp <?= number_format($total - $paidAmt, 0, ',', '.') ?></small>
        </div>
    </div>
</div>

<!-- Schedule Cards -->
<div class="row g-3">
    <?php foreach ($schedule as $s):
        $isPaid     = $s['status'] === 'paid';
        $isOverdue  = $s['status'] === 'overdue';
        $hasPending = ($s['payment_status'] ?? '') === 'pending';
        $cardClass  = $isPaid ? 'border-success' : ($isOverdue ? 'border-danger' : ($hasPending ? 'border-warning' : 'border-0'));
        $headerBg   = $isPaid ? 'bg-success text-white' : ($isOverdue ? 'bg-danger text-white' : ($hasPending ? 'bg-warning text-dark' : 'bg-light'));
    ?>
    <div class="col-md-6 col-xl-4">
        <div class="card rounded-3 shadow-sm h-100 <?= $cardClass ?>">
            <div class="card-header <?= $headerBg ?> border-0 d-flex justify-content-between align-items-center">
                <span class="fw-bold">Bulan ke-<?= $s['month_number'] ?></span>
                <?php if ($isPaid): ?>
                    <span class="badge bg-white text-success"><i class="bi bi-check-circle-fill me-1"></i>Lunas</span>
                <?php elseif ($isOverdue): ?>
                    <span class="badge bg-white text-danger"><i class="bi bi-exclamation-circle-fill me-1"></i>Terlambat</span>
                <?php elseif ($hasPending): ?>
                    <span class="badge bg-white text-warning"><i class="bi bi-hourglass-split me-1"></i>Menunggu</span>
                <?php else: ?>
                    <span class="badge bg-secondary">Belum Bayar</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <p class="mb-1 text-muted small">
                    <i class="bi bi-calendar3 me-1"></i>
                    Jatuh tempo: <strong><?= date('d M Y', strtotime($s['due_date'])) ?></strong>
                </p>
                <p class="fs-5 fw-bold text-primary mb-3">Rp <?= number_format($s['amount'], 0, ',', '.') ?></p>

                <?php if ($isPaid): ?>
                    <p class="text-success small mb-0">
                        <i class="bi bi-check2-all me-1"></i>
                        Diverifikasi: <?= date('d M Y', strtotime($s['verified_at'] ?? 'now')) ?>
                    </p>
                <?php elseif ($hasPending): ?>
                    <p class="text-warning small mb-2"><i class="bi bi-clock me-1"></i> Menunggu verifikasi admin.</p>
                    <button class="btn btn-sm btn-outline-warning w-100 btn-pay"
                            data-id="<?= $s['id'] ?>" data-month="<?= $s['month_number'] ?>" data-amount="<?= $s['amount'] ?>">
                        <i class="bi bi-arrow-repeat me-1"></i> Upload Ulang
                    </button>
                <?php else: ?>
                    <button class="btn btn-sm <?= $isOverdue ? 'btn-danger' : 'btn-primary' ?> w-100 btn-pay"
                            data-id="<?= $s['id'] ?>" data-month="<?= $s['month_number'] ?>" data-amount="<?= $s['amount'] ?>">
                        <i class="bi bi-upload me-1"></i>
                        <?= $isOverdue ? 'Bayar Sekarang (Terlambat!)' : 'Upload Bukti Bayar' ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="payModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-3">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title">Upload Cicilan <span id="payMonthLabel"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info border-0 small mb-3">
                    Nominal: <strong id="payAmount" class="text-primary fs-6"></strong>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Bukti Transfer</label>
                    <input type="file" class="form-control" id="installFile" accept=".jpg,.jpeg,.png">
                    <div class="form-text">JPG / PNG, maks 2 MB</div>
                </div>
                <div id="installPreviewWrap" class="d-none text-center mt-2">
                    <img id="installPreview" src="#" class="img-fluid rounded-3 border" style="max-height:180px;">
                </div>
            </div>
            <div class="modal-footer border-0">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-primary" id="btnPayUpload">
                    <span class="spinner-border spinner-border-sm d-none me-1" id="paySpinner"></span>
                    <i class="bi bi-send me-1"></i> Kirim
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
const payModal = new bootstrap.Modal('#payModal');
let currentScheduleId = null;

$(document).on('click', '.btn-pay', function () {
    currentScheduleId = $(this).data('id');
    $('#payMonthLabel').text('Bulan ke-' + $(this).data('month'));
    $('#payAmount').text('Rp ' + parseFloat($(this).data('amount')).toLocaleString('id-ID'));
    $('#installFile').val('');
    $('#installPreviewWrap').addClass('d-none');
    payModal.show();
});

$('#installFile').on('change', function () {
    const f = this.files[0];
    if (f) {
        const r = new FileReader();
        r.onload = e => { $('#installPreview').attr('src', e.target.result); $('#installPreviewWrap').removeClass('d-none'); };
        r.readAsDataURL(f);
    }
});

$('#btnPayUpload').on('click', function () {
    const file = $('#installFile')[0].files[0];
    if (! file) { showToast('Pilih file dulu.', 'warning'); return; }
    if (file.size > 2 * 1024 * 1024) { showToast('Maks 2 MB.', 'danger'); return; }

    const fd = new FormData();
    fd.append('payment_proof', file);
    fd.append(window.CSRF_NAME, window.CSRF_HASH);

    $('#paySpinner').removeClass('d-none'); $('#btnPayUpload').prop('disabled', true);

    $.ajax({
        url: '/user/installments/upload/' + currentScheduleId,
        method: 'POST', data: fd, contentType: false, processData: false,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        success: function (res) {
            if (res.csrf) window.CSRF_HASH = res.csrf;
            $('#paySpinner').addClass('d-none'); $('#btnPayUpload').prop('disabled', false);
            if (res.status === 'success') {
                payModal.hide();
                showToast(res.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(res.message, 'danger');
            }
        },
        error: () => { $('#paySpinner').addClass('d-none'); showToast('Server error.', 'danger'); }
    });
});

function showToast(msg, type = 'success') {
    $(`<div class="toast align-items-center text-bg-${type} border-0 show position-fixed bottom-0 end-0 m-3 shadow" style="z-index:9999">
        <div class="d-flex"><div class="toast-body">${msg}</div>
        <button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div></div>`).appendTo('body').delay(3500).fadeOut(300, function(){ $(this).remove(); });
}
</script>
<?= $this->endSection() ?>