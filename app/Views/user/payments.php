<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-1 fw-bold">Pembayaran Saya</h4>
        <p class="text-muted mb-0">Upload bukti transfer untuk melunasi booking yang disetujui</p>
    </div>
</div>

<!-- Alert: Booking approved tapi belum upload bukti bayar -->
<?php if (! empty($unpaid)): ?>
<div class="alert alert-warning border-0 rounded-3 shadow-sm mb-4">
    <div class="d-flex align-items-start gap-2">
        <i class="bi bi-exclamation-triangle-fill fs-5 mt-1 flex-shrink-0"></i>
        <div>
            <h6 class="fw-bold mb-1">Pembayaran Diperlukan</h6>
            <p class="mb-2 small">Booking berikut sudah disetujui admin. Segera upload bukti pembayaran lunas:</p>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($unpaid as $u): ?>
                <button class="btn btn-warning btn-sm btn-upload"
                        data-id="<?= $u['booking_id'] ?>"
                        data-room="<?= esc($u['room_name']) ?>"
                        data-amount="<?= $u['total_price'] ?>">
                    <i class="bi bi-upload me-1"></i>
                    <?= esc($u['room_name']) ?> — Rp <?= number_format($u['total_price'], 0, ',', '.') ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Tabel Riwayat Pembayaran -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white border-0 pt-3 pb-0 px-3">
        <h6 class="fw-semibold mb-0">Riwayat Pembayaran</h6>
        <p class="text-muted small mb-2">
            Setelah terverifikasi, semua jadwal cicilan otomatis ditandai <strong class="text-success">lunas</strong>
            dan invoice tersedia untuk diunduh.
        </p>
    </div>
    <div class="card-body p-3">
        <table id="paymentTable" class="table table-hover table-bordered align-middle w-100">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Kamar</th>
                    <th>Jumlah</th>
                    <th>Status</th>
                    <th>Tanggal Upload</th>
                    <th>Aksi</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Legend -->
<div class="mt-3 d-flex flex-wrap gap-3 small text-muted">
    <span><span class="badge bg-warning text-dark">Menunggu Verifikasi</span> — Admin sedang mereview bukti bayar</span>
    <span><span class="badge bg-success">Lunas ✓</span> — Semua cicilan otomatis terbayar, invoice bisa diunduh</span>
    <span><span class="badge bg-danger">Ditolak</span> — Bukti ditolak, silakan upload ulang</span>
</div>

<!-- Modal: Upload Bukti Bayar -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title">
                    <i class="bi bi-cloud-upload me-2"></i>Upload Bukti Pembayaran
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info border-0 rounded-2 small mb-3">
                    <i class="bi bi-info-circle-fill me-1"></i>
                    Kamar: <strong id="uploadRoomName"></strong><br>
                    Total Pembayaran: <strong id="uploadAmount" class="text-primary fs-6"></strong>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Pilih File Bukti Transfer</label>
                    <input type="file" class="form-control" id="paymentFile" accept=".jpg,.jpeg,.png">
                    <div class="form-text text-muted">
                        <i class="bi bi-image me-1"></i>Format JPG / PNG. Maksimum 2 MB.
                    </div>
                </div>
                <div id="imgPreviewWrapper" class="d-none text-center">
                    <img id="imgPreview" src="#" alt="Preview"
                         class="img-fluid rounded-3 border shadow-sm" style="max-height:220px;">
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-success" id="btnUpload">
                    <span class="spinner-border spinner-border-sm d-none me-1" id="uploadSpinner"></span>
                    <i class="bi bi-send me-1"></i> Kirim Bukti
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
const uploadModal  = new bootstrap.Modal('#uploadModal');
let   currentBookingId = null;
let   table;

$(function () {
    table = $('#paymentTable').DataTable({
        processing  : true,
        serverSide  : true,
        responsive  : true,
        searchDelay : 400,
        order       : [[0, 'desc']],
        ajax: {
            url    : '/user/payments/data',
            type   : 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            data   : d => { d[window.CSRF_NAME] = window.CSRF_HASH; },
            dataSrc: function (json) {
                if (json.csrf) window.CSRF_HASH = json.csrf;
                return json.data;
            }
        },
        columns: [
            { data: 'id',         width: '50px' },
            { data: 'room_name' },
            { data: 'amount' },
            { data: 'status' },
            { data: 'created_at' },
            { data: 'action',     orderable: false, searchable: false, width: '160px' },
        ],
        language: {
            processing : '<div class="spinner-border text-primary"></div>',
            emptyTable : 'Belum ada riwayat pembayaran.',
        },
    });
});

// ── Buka modal upload ──────────────────────────────────────────
$(document).on('click', '.btn-upload', function () {
    currentBookingId = $(this).data('id');
    const amount     = parseFloat(String($(this).data('amount')).replace(/[^0-9.]/g, '')) || 0;
    $('#uploadRoomName').text($(this).data('room') || '-');
    $('#uploadAmount').text(amount > 0 ? 'Rp ' + amount.toLocaleString('id-ID') : '-');
    $('#paymentFile').val('');
    $('#imgPreviewWrapper').addClass('d-none');
    uploadModal.show();
});

// ── Preview gambar ─────────────────────────────────────────────
$('#paymentFile').on('change', function () {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = e => {
            $('#imgPreview').attr('src', e.target.result);
            $('#imgPreviewWrapper').removeClass('d-none');
        };
        reader.readAsDataURL(file);
    }
});

// ── Submit upload ──────────────────────────────────────────────
$('#btnUpload').on('click', function () {
    const file = $('#paymentFile')[0].files[0];
    if (! file) { showToast('Pilih file terlebih dahulu.', 'warning'); return; }
    if (! ['image/jpeg', 'image/jpg', 'image/png'].includes(file.type)) {
        showToast('Hanya JPG dan PNG yang diperbolehkan.', 'danger'); return;
    }
    if (file.size > 2 * 1024 * 1024) { showToast('Maksimum 2 MB.', 'danger'); return; }

    const fd = new FormData();
    fd.append('payment_proof', file);
    fd.append(window.CSRF_NAME, window.CSRF_HASH);

    $('#uploadSpinner').removeClass('d-none');
    $('#btnUpload').prop('disabled', true);

    $.ajax({
        url         : '/user/payments/upload/' + currentBookingId,
        method      : 'POST',
        data        : fd,
        contentType : false,
        processData : false,
        headers     : { 'X-Requested-With': 'XMLHttpRequest' },
        success: function (res) {
            $('#uploadSpinner').addClass('d-none');
            $('#btnUpload').prop('disabled', false);
            if (res.csrf) window.CSRF_HASH = res.csrf;
            if (res.status === 'success') {
                uploadModal.hide();
                table.ajax.reload(null, false);
                showToast(res.message, 'success');
            } else {
                showToast(res.message ?? 'Gagal mengupload.', 'danger');
            }
        },
        error: () => {
            $('#uploadSpinner').addClass('d-none');
            $('#btnUpload').prop('disabled', false);
            showToast('Server error. Coba lagi.', 'danger');
        }
    });
});

function showToast(message, type = 'success') {
    const el = $(`<div class="toast align-items-center text-bg-${type} border-0 show
                      position-fixed bottom-0 end-0 m-3 shadow" style="z-index:9999">
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div></div>`).appendTo('body');
    setTimeout(() => el.fadeOut(300, () => el.remove()), 4500);
}
</script>
<?= $this->endSection() ?>