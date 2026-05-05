<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Booking Saya</h4>
        <p class="text-muted mb-0">Riwayat semua pemesanan kamar Anda</p>
    </div>
    <a href="/user/rooms" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Pesan Kamar Baru
    </a>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body">
        <table id="bookingTable" class="table table-hover table-bordered align-middle w-100">
            <thead class="table-dark">
                <tr>
                    <th>#</th><th>Kamar</th><th>Check-in</th>
                    <th>Durasi</th><th>Total Harga</th><th>Status</th><th>Aksi</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
let table;

$(function () {
    table = $('#bookingTable').DataTable({
        processing  : true,
        serverSide  : true,
        responsive  : true,
        deferRender : true,
        searchDelay : 400,
        order       : [[0, 'desc']],
        ajax: {
            url    : '/user/bookings/data',
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
            { data: 'room_name' },
            { data: 'checkin_date' },
            { data: 'duration', render: d => d + ' bln' },
            { data: 'total_price' },
            { data: 'status' },
            { data: 'action', orderable: false, searchable: false },
        ],
        language: {
            processing: '<div class="spinner-border text-primary"></div>',
            emptyTable: 'Belum ada booking.',
        },
    });
});

$(document).on('click', '.btn-cancel', function () {
    const id = $(this).data('id');
    if (! confirm('Yakin ingin membatalkan booking ini?')) return;
    $.ajax({
        url    : '/user/bookings/cancel/' + id,
        method : 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        data   : { [window.CSRF_NAME]: window.CSRF_HASH },
        success: function (res) {
            if (res.csrf) window.CSRF_HASH = res.csrf;
            if (res.status === 'success') { table.ajax.reload(null, false); showToast(res.message, 'success'); }
            else { showToast(res.message, 'danger'); }
        }
    });
});

function showToast(message, type = 'success') {
    const el = $(`<div class="toast align-items-center text-bg-${type} border-0 show position-fixed bottom-0 end-0 m-3 shadow" style="z-index:9999">
        <div class="d-flex"><div class="toast-body">${message}</div>
        <button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div></div>`).appendTo('body');
    setTimeout(() => el.fadeOut(300, () => el.remove()), 3500);
}
</script>
<?= $this->endSection() ?>