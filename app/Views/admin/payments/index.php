<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="mb-4">
    <h4 class="mb-1 fw-bold">Verifikasi Pembayaran</h4>
    <p class="text-muted mb-0">Tinjau bukti pembayaran dan setujui atau tolak</p>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body">
        <table id="paymentAdminTable" class="table table-hover table-bordered align-middle w-100">
            <thead class="table-dark">
                <tr>
                    <th>#</th><th>Penyewa</th><th>Kamar</th>
                    <th>Jumlah</th><th>Status</th><th>Tanggal</th><th>Aksi</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Modal: Lihat Bukti Bayar -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3">
            <div class="modal-header bg-info text-white border-0">
                <h5 class="modal-title"><i class="bi bi-image me-2"></i>Bukti Pembayaran</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-3">
                <div id="imgLoadingSpinner" class="py-5">
                    <div class="spinner-border text-info"></div>
                    <p class="text-muted mt-2 small">Memuat gambar...</p>
                </div>
                <img id="proofImage" src="#" alt="Bukti Bayar"
                     class="img-fluid rounded-3 d-none" style="max-height:500px;">
                <p class="text-muted small mt-2 mb-0" id="imgCaption"></p>
            </div>
            <div class="modal-footer border-0 justify-content-center gap-2">
                <button class="btn btn-success btn-verify-modal" data-action="verified">
                    <i class="bi bi-check-circle me-1"></i> Verifikasi
                </button>
                <button class="btn btn-danger btn-verify-modal" data-action="rejected">
                    <i class="bi bi-x-circle me-1"></i> Tolak
                </button>
                <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
let   table;
let   activePaymentId = null;
const imageModal = new bootstrap.Modal('#imageModal');

$(function () {
    table = $('#paymentAdminTable').DataTable({
        processing  : true,
        serverSide  : true,
        responsive  : true,
        deferRender : true,
        searchDelay : 400,
        order       : [[0, 'desc']],
        ajax: {
            url    : '/admin/payments/data',
            type   : 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            data   : d => { d[window.CSRF_NAME] = window.CSRF_HASH; },
            dataSrc: function (json) {
                if (json.csrf) window.CSRF_HASH = json.csrf;
                return json.data;
            }
        },
        columns: [
            { data: 'id', width: '50px' },
            { data: 'username' },
            { data: 'room_name' },
            { data: 'amount' },
            { data: 'status' },
            { data: 'created_at' },
            { data: 'action', orderable: false, searchable: false, width: '200px' },
        ],
        language: { processing: '<div class="spinner-border text-primary"></div>' },
    });
});

$(document).on('click', '.btn-view-proof', function () {
    activePaymentId = $(this).data('id');
    $('#proofImage').addClass('d-none');
    $('#imgLoadingSpinner').removeClass('d-none');
    $('#imgCaption').text($(this).data('user') + ' — ' + $(this).data('room'));
    imageModal.show();

    const img = new Image();
    img.onload = function () {
        $('#imgLoadingSpinner').addClass('d-none');
        $('#proofImage').attr('src', img.src).removeClass('d-none');
    };
    img.onerror = function () {
        $('#imgLoadingSpinner').html('<p class="text-danger">Gagal memuat gambar.</p>');
    };
    img.src = '/admin/payments/image/' + activePaymentId;
});

$(document).on('click', '.btn-verify', function () {
    doVerify($(this).data('id'), $(this).data('action'));
});

$(document).on('click', '.btn-verify-modal', function () {
    if (! activePaymentId) return;
    imageModal.hide();
    doVerify(activePaymentId, $(this).data('action'));
});

function doVerify(id, status) {
    if (! confirm('Pembayaran ini akan ' + (status === 'verified' ? 'diverifikasi' : 'ditolak') + '?')) return;
    $.ajax({
        url    : '/admin/payments/verify/' + id,
        method : 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        data   : { [window.CSRF_NAME]: window.CSRF_HASH, status },
        success: function (res) {
            if (res.csrf) window.CSRF_HASH = res.csrf;
            if (res.status === 'success') {
                table.ajax.reload(null, false);
                showToast(res.message, status === 'verified' ? 'success' : 'danger');
            } else {
                showToast(res.message ?? 'Gagal.', 'danger');
            }
        },
        error: () => showToast('Server error.', 'danger')
    });
}

function showToast(message, type = 'success') {
    const el = $(`<div class="toast align-items-center text-bg-${type} border-0 show position-fixed bottom-0 end-0 m-3 shadow" style="z-index:9999">
        <div class="d-flex"><div class="toast-body">${message}</div>
        <button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div></div>`).appendTo('body');
    setTimeout(() => el.fadeOut(300, () => el.remove()), 3500);
}
</script>
<?= $this->endSection() ?>