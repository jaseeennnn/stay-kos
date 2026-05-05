<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-1 fw-bold">Manajemen Cicilan</h4>
        <p class="text-muted mb-0">Tinjau bukti cicilan bulanan penyewa dan verifikasi pembayaran</p>
    </div>
</div>

<!-- Filter Pills -->
<div class="mb-3 d-flex gap-2 flex-wrap align-items-center">
    <span class="text-muted small me-1">Filter:</span>
    <button id="filterAll"      class="btn btn-sm btn-outline-secondary active">Semua</button>
    <button id="filterPending"  class="btn btn-sm btn-outline-warning">Pending</button>
    <button id="filterVerified" class="btn btn-sm btn-outline-success">Terverifikasi</button>
    <button id="filterRejected" class="btn btn-sm btn-outline-danger">Ditolak</button>
</div>

<!-- Legend -->
<div class="mb-3 d-flex flex-wrap gap-3 small text-muted">
    <span><i class="bi bi-eye-fill text-info me-1"></i><strong>Lihat Bukti</strong> — tersedia di semua baris</span>
    <span><i class="bi bi-check-circle-fill text-success me-1"></i><strong>Verifikasi / Tolak</strong> — hanya untuk yang Pending</span>
    <span><i class="bi bi-arrow-counterclockwise text-success me-1"></i><strong>Verifikasi Ulang</strong> — untuk yang salah ditolak</span>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-3">
        <table id="installmentAdminTable" class="table table-hover table-bordered align-middle w-100">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Penyewa</th>
                    <th>Kamar</th>
                    <th>Bulan</th>
                    <th>Jatuh Tempo</th>
                    <th>Jumlah</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- ── Modal: Lihat Bukti Cicilan ────────────────────────────── -->
<div class="modal fade" id="proofModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-3 border-0">
            <div class="modal-header bg-info text-white border-0">
                <h5 class="modal-title">
                    <i class="bi bi-image me-2"></i>Bukti Cicilan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-3">
                <p id="proofLabel" class="text-muted small mb-3 fw-semibold"></p>
                <div id="proofSpinner" class="py-4">
                    <div class="spinner-border text-info"></div>
                    <p class="text-muted mt-2 small">Memuat gambar...</p>
                </div>
                <img id="proofImg" src="" alt="Bukti cicilan"
                     class="img-fluid rounded-3 shadow d-none"
                     style="max-height: 520px; object-fit: contain;">
            </div>
            <div class="modal-footer border-0 justify-content-center gap-2" id="proofActions">
                <!-- Tombol verifikasi/tolak langsung dari modal, diisi dinamis oleh JS -->
            </div>
        </div>
    </div>
</div>

<!-- ── Modal: Konfirmasi Verifikasi / Tolak ──────────────────── -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <span id="confirmIcon" class="fs-4"></span>
                    <span id="confirmTitle">Konfirmasi</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <p id="confirmText" class="mb-0 text-muted small"></p>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-end gap-2">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button id="btnConfirmAction" class="btn btn-sm btn-success">Lanjutkan</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
let   table;
let   pendingAction  = null;
const confirmModal   = new bootstrap.Modal('#confirmModal');
const proofModal     = new bootstrap.Modal('#proofModal');

// ── DataTable ──────────────────────────────────────────────────
$(function () {
    table = $('#installmentAdminTable').DataTable({
        processing  : true,
        serverSide  : true,
        responsive  : true,
        deferRender : true,
        searchDelay : 400,
        order       : [[0, 'desc']],
        ajax: {
            url    : '/admin/installments/data',
            type   : 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            data   : d => { d[window.CSRF_NAME] = window.CSRF_HASH; },
            dataSrc: function (json) {
                if (json.csrf) window.CSRF_HASH = json.csrf;
                return json.data;
            },
            error: () => showToast('Gagal memuat data cicilan.', 'danger'),
        },
        columns: [
            { data: 'id',            width: '50px' },
            { data: 'username' },
            { data: 'room_name' },
            { data: 'month_display' },
            { data: 'due_date' },
            { data: 'amount_paid' },
            { data: 'status' },
            { data: 'action',        orderable: false, searchable: false, width: '200px' },
        ],
        language: {
            processing : '<div class="spinner-border text-primary"></div>',
            emptyTable : 'Belum ada data cicilan.',
            zeroRecords: 'Tidak ada cicilan yang cocok.',
        },
    });

    // Filter pills
    $('#filterAll').on('click',      () => { setFilter('#filterAll');      table.search('').draw(); });
    $('#filterPending').on('click',  () => { setFilter('#filterPending');  table.search('pending').draw(); });
    $('#filterVerified').on('click', () => { setFilter('#filterVerified'); table.search('verified').draw(); });
    $('#filterRejected').on('click', () => { setFilter('#filterRejected'); table.search('rejected').draw(); });
});

function setFilter(sel) {
    $('#filterAll, #filterPending, #filterVerified, #filterRejected').removeClass('active');
    $(sel).addClass('active');
}

// ── Lihat Bukti Cicilan ────────────────────────────────────────
$(document).on('click', '.btn-view-proof', function () {
    const src   = $(this).data('src');
    const label = $(this).data('label');

    // Reset state modal
    $('#proofLabel').text(label);
    $('#proofImg').addClass('d-none').attr('src', '');
    $('#proofSpinner').show();
    $('#proofActions').empty();

    proofModal.show();

    // Muat gambar
    const img = new Image();
    img.onload = () => {
        $('#proofSpinner').hide();
        $('#proofImg').attr('src', src).removeClass('d-none');
    };
    img.onerror = () => {
        $('#proofSpinner').hide();
        $('#proofImg')
            .attr('src', '')
            .removeClass('d-none')
            .replaceWith('<p class="text-danger py-3"><i class="bi bi-exclamation-triangle me-1"></i>Gagal memuat gambar bukti.</p>');
    };
    img.src = src;
});

// ── Klik Verifikasi / Tolak / Verifikasi Ulang ─────────────────
$(document).on('click', '.btn-inst-verify', function () {
    const id     = $(this).data('id');
    const action = $(this).data('action');
    pendingAction = { id, action };

    const cfg = {
        verified: {
            icon : '<i class="bi bi-check-circle-fill text-success"></i>',
            title: 'Verifikasi Cicilan',
            text : 'Pembayaran cicilan ini akan ditandai lunas.',
            btnCls: 'btn-success',
            btnTxt: 'Verifikasi',
        },
        rejected: {
            icon : '<i class="bi bi-x-circle-fill text-danger"></i>',
            title: 'Tolak Cicilan',
            text : 'Bukti ini akan ditolak. Penyewa perlu upload ulang bukti pembayaran.',
            btnCls: 'btn-danger',
            btnTxt: 'Tolak',
        },
    }[action];

    $('#confirmIcon').html(cfg.icon);
    $('#confirmTitle').text(cfg.title);
    $('#confirmText').text(cfg.text);
    $('#btnConfirmAction')
        .removeClass('btn-success btn-danger')
        .addClass(cfg.btnCls)
        .text(cfg.btnTxt);

    // Tutup proof modal jika terbuka, lalu buka confirm
    proofModal.hide();
    setTimeout(() => confirmModal.show(), 250);
});

// ── Eksekusi setelah konfirmasi ────────────────────────────────
$('#btnConfirmAction').on('click', function () {
    if (! pendingAction) return;

    const { id, action } = pendingAction;
    $(this).prop('disabled', true)
           .html('<span class="spinner-border spinner-border-sm me-1"></span>Memproses...');

    $.ajax({
        url    : '/admin/installments/verify/' + id,
        method : 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        data   : { [window.CSRF_NAME]: window.CSRF_HASH, status: action },
        success: function (res) {
            if (res.csrf) window.CSRF_HASH = res.csrf;
            confirmModal.hide();
            pendingAction = null;
            resetConfirmBtn();

            if (res.status === 'success') {
                table.ajax.reload(null, false);
                showToast(res.message, action === 'verified' ? 'success' : 'warning');
            } else {
                showToast(res.message ?? 'Gagal memproses.', 'danger');
            }
        },
        error: () => {
            confirmModal.hide();
            pendingAction = null;
            resetConfirmBtn();
            showToast('Server error. Coba lagi.', 'danger');
        }
    });
});

$('#confirmModal').on('hidden.bs.modal', () => {
    pendingAction = null;
    resetConfirmBtn();
});

function resetConfirmBtn() {
    $('#btnConfirmAction').prop('disabled', false).text('Lanjutkan');
}

function showToast(msg, type = 'success') {
    const el = $(`<div class="toast align-items-center text-bg-${type} border-0 show
                      position-fixed bottom-0 end-0 m-3 shadow" style="z-index:9999">
        <div class="d-flex">
            <div class="toast-body">${msg}</div>
            <button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div></div>`).appendTo('body');
    setTimeout(() => el.fadeOut(300, () => el.remove()), 3500);
}
</script>
<?= $this->endSection() ?>