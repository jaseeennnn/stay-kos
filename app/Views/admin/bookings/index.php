<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-0 fw-bold">Manajemen Booking</h4>
        <p class="text-muted small mb-0">Setujui / tolak booking, dan download invoice untuk booking yang sudah disetujui</p>
    </div>
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <!-- Filter -->
        <div class="btn-group btn-group-sm" role="group">
            <button class="btn btn-outline-secondary active" id="filterAll">Semua</button>
            <button class="btn btn-outline-warning"          id="filterPending">Pending</button>
            <button class="btn btn-outline-success"          id="filterApproved">Disetujui</button>
            <button class="btn btn-outline-danger"           id="filterRejected">Ditolak</button>
        </div>
        <!-- Export -->
        <a href="/admin/export/bookings-excel" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel me-1"></i>Excel
        </a>
        <a href="/admin/export/bookings-pdf" class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i>Laporan PDF
        </a>
    </div>
</div>

<!-- Legend -->
<div class="d-flex flex-wrap gap-2 mb-3 small text-muted">
    <span><i class="bi bi-check-circle-fill text-success me-1"></i>Setujui = kamar berubah "Disewa" + jadwal cicilan dibuat otomatis</span>
    <span class="ms-3"><i class="bi bi-file-earmark-pdf-fill text-primary me-1"></i>Booking disetujui → tombol <strong>Invoice PDF</strong> muncul di kolom Aksi</span>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-3">
        <table id="bookingAdminTable" class="table table-hover table-bordered align-middle w-100">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Penyewa</th>
                    <th>Kamar</th>
                    <th>Check-in</th>
                    <th>Durasi</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Modal: Konfirmasi Setujui / Tolak -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold d-flex align-items-center gap-2">
                    <span id="confirmIcon" class="fs-4"></span>
                    <span id="confirmTitle">Konfirmasi</span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <p id="confirmText" class="mb-0 text-muted small"></p>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-end gap-2">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-sm" id="btnConfirmAction">Ya, Lanjutkan</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
let   table;
let   pendingAction = null;
const confirmModal  = new bootstrap.Modal('#confirmModal');

$(function () {
    table = $('#bookingAdminTable').DataTable({
        processing  : true,
        serverSide  : true,
        responsive  : true,
        deferRender : true,
        stateSave   : true,
        searchDelay : 400,
        order       : [[0, 'desc']],
        ajax: {
            url    : '/admin/bookings/data',
            type   : 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            data   : d => { d[window.CSRF_NAME] = window.CSRF_HASH; },
            dataSrc: function (json) {
                if (json.csrf) window.CSRF_HASH = json.csrf;
                return json.data;
            },
            error: () => showToast('Gagal memuat data booking.', 'danger'),
        },
        columns: [
            { data: 'id',           width: '50px' },
            { data: 'username' },
            { data: 'room_name' },
            { data: 'checkin_date' },
            { data: 'duration',     render: d => d + ' bln',  width: '70px' },
            { data: 'total_price' },
            { data: 'status' },
            { data: 'action',       orderable: false, searchable: false, width: '190px' },
        ],
        language: {
            processing : '<div class="spinner-border text-primary"></div>',
            emptyTable : 'Tidak ada data booking.',
            zeroRecords: 'Tidak ada hasil yang cocok.',
        },
    });

    // ── Filter pills ───────────────────────────────────────────
    $('#filterAll').on('click',      () => { setFilter('#filterAll');      table.search('').draw(); });
    $('#filterPending').on('click',  () => { setFilter('#filterPending');  table.search('pending').draw(); });
    $('#filterApproved').on('click', () => { setFilter('#filterApproved'); table.search('approved').draw(); });
    $('#filterRejected').on('click', () => { setFilter('#filterRejected'); table.search('rejected').draw(); });
});

function setFilter(selector) {
    $('#filterAll, #filterPending, #filterApproved, #filterRejected').removeClass('active');
    $(selector).addClass('active');
}

// ── Klik Setujui / Tolak ───────────────────────────────────────
$(document).on('click', '.btn-approve, .btn-reject', function () {
    const id     = $(this).data('id');
    const status = $(this).hasClass('btn-approve') ? 'approved' : 'rejected';
    pendingAction = { id, status };

    if (status === 'approved') {
        $('#confirmIcon').html('<i class="bi bi-check-circle-fill text-success"></i>');
        $('#confirmTitle').text('Setujui Booking');
        $('#confirmText').text('Booking ini akan disetujui. Kamar otomatis berubah menjadi "Disewa" dan jadwal cicilan dibuat.');
        $('#btnConfirmAction').removeClass('btn-danger').addClass('btn-success').text('Setujui');
    } else {
        $('#confirmIcon').html('<i class="bi bi-x-circle-fill text-danger"></i>');
        $('#confirmTitle').text('Tolak Booking');
        $('#confirmText').text('Booking ini akan ditolak. Penyewa bisa melakukan booking ulang.');
        $('#btnConfirmAction').removeClass('btn-success').addClass('btn-danger').text('Tolak');
    }

    confirmModal.show();
});

// ── Eksekusi setelah konfirmasi ────────────────────────────────
$('#btnConfirmAction').on('click', function () {
    if (! pendingAction) return;

    const { id, status } = pendingAction;
    $(this).prop('disabled', true)
           .html('<span class="spinner-border spinner-border-sm me-1"></span>Memproses...');

    $.ajax({
        url    : '/admin/bookings/status/' + id,
        method : 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        data   : { [window.CSRF_NAME]: window.CSRF_HASH, status },
        success: function (res) {
            if (res.csrf) window.CSRF_HASH = res.csrf;
            confirmModal.hide();
            pendingAction = null;
            resetConfirmBtn();

            if (res.status === 'success') {
                table.ajax.reload(null, false);
                showToast(res.message, status === 'approved' ? 'success' : 'warning');
            } else {
                showToast(res.message ?? 'Gagal memproses booking.', 'danger');
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

// Reset tombol konfirmasi setelah selesai
function resetConfirmBtn() {
    $('#btnConfirmAction').prop('disabled', false).text('Ya, Lanjutkan');
}

// Reset saat modal ditutup (klik X / Batal)
$('#confirmModal').on('hidden.bs.modal', () => {
    pendingAction = null;
    resetConfirmBtn();
});

function showToast(message, type = 'success') {
    const el = $(`<div class="toast align-items-center text-bg-${type} border-0 show
                      position-fixed bottom-0 end-0 m-3 shadow" style="z-index:9999">
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div></div>`).appendTo('body');
    setTimeout(() => el.fadeOut(300, () => el.remove()), 4000);
}
</script>
<?= $this->endSection() ?>