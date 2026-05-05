<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h4 class="mb-0">Manajemen Kamar</h4>
    <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-primary" id="btnAdd"><i class="bi bi-plus-lg me-1"></i> Tambah Kamar</button>
        <a href="/admin/export/rooms-excel" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
        </a>
        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="bi bi-upload me-1"></i> Import Excel
        </button>
        <a href="/admin/export/import-template" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-download me-1"></i> Template
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body">
        <table id="roomsTable" class="table table-hover table-bordered align-middle w-100">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th style="width:70px;">Foto</th>
                    <th>Nama Kamar</th>
                    <th>Harga/Bulan</th>
                    <th>Fasilitas</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Modal: Add / Edit -->
<div class="modal fade" id="roomModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-3">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitle">Tambah Kamar</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="roomForm" enctype="multipart/form-data" novalidate>
                    <input type="hidden" id="roomId">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nama Kamar</label>
                            <input type="text" class="form-control" id="room_name" placeholder="cth. Kamar A1">
                            <div class="invalid-feedback" id="err_room_name"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Harga / Bulan (Rp)</label>
                            <input type="number" class="form-control" id="price" placeholder="cth. 1500000">
                            <div class="invalid-feedback" id="err_price"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Fasilitas</label>
                            <textarea class="form-control" id="facilities" rows="2"
                                      placeholder="AC, WiFi, Kamar Mandi Dalam..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select class="form-select" id="status">
                                <option value="available">Tersedia</option>
                                <option value="booked">Disewa</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>

                        <!-- ── Upload Foto ── -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Foto Kamar</label>
                            <input type="file" class="form-control" id="imageFile"
                                   accept="image/jpeg,image/png,image/webp">
                            <div class="form-text">JPG/PNG/WebP, maks 3 MB</div>
                            <div class="invalid-feedback" id="err_image"></div>
                        </div>

                        <!-- Preview foto -->
                        <div class="col-12" id="previewWrapper" style="display:none;">
                            <div class="d-flex align-items-start gap-3 p-3 rounded-3 border bg-light">
                                <img id="imagePreview" src="" alt="preview"
                                     class="rounded-2 shadow-sm"
                                     style="width:120px;height:90px;object-fit:cover;">
                                <div class="flex-grow-1">
                                    <div class="small fw-semibold text-muted mb-2">Preview Foto</div>
                                    <div id="existingImageInfo" class="small text-muted mb-2 d-none">
                                        Foto saat ini. Upload baru untuk mengganti.
                                    </div>
                                    <div class="form-check" id="removeImageCheck" style="display:none;">
                                        <input class="form-check-input" type="checkbox" id="remove_image" value="1">
                                        <label class="form-check-label small text-danger" for="remove_image">
                                            Hapus foto ini
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-primary" id="btnSave">
                    <span class="spinner-border spinner-border-sm d-none me-1" id="saveSpinner"></span>
                    Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Lihat Foto Besar -->
<div class="modal fade" id="photoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-body p-0 text-center position-relative">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-2"
                        data-bs-dismiss="modal" style="z-index:10;"></button>
                <img id="photoFull" src="" alt="foto kamar"
                     class="img-fluid rounded-3 shadow-lg"
                     style="max-height:80vh;object-fit:contain;">
            </div>
        </div>
    </div>
</div>

<!-- Modal: Import Excel -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-3">
            <div class="modal-header bg-warning text-dark border-0">
                <h5 class="modal-title"><i class="bi bi-file-earmark-excel me-2"></i>Import Kamar dari Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="/admin/export/import-rooms" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="alert alert-info border-0 small">
                        Gunakan <a href="/admin/export/import-template">template Excel</a> yang disediakan.
                        Data mulai dari baris ke-3.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pilih File Excel (.xlsx)</label>
                        <input type="file" name="import_file" class="form-control" accept=".xlsx,.xls" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-cloud-upload me-1"></i> Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
const BASE      = '/admin/rooms';
const roomModal = new bootstrap.Modal('#roomModal');
const photoModal = new bootstrap.Modal('#photoModal');
let   table;

// ── DataTable ──────────────────────────────────────────────────
$(function () {
    table = $('#roomsTable').DataTable({
        processing  : true,
        serverSide  : true,
        responsive  : true,
        deferRender : true,
        stateSave   : true,
        searchDelay : 400,
        ajax: {
            url    : BASE + '/data',
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
            { data: 'image',      orderable: false, searchable: false, width: '70px' },
            { data: 'room_name' },
            { data: 'price' },
            { data: 'facilities', orderable: false },
            { data: 'status' },
            { data: 'action',     orderable: false, searchable: false, width: '100px' },
        ],
        language: {
            processing : '<div class="spinner-border text-primary"></div>',
            emptyTable : 'Belum ada data kamar.',
        },
    });
});

// ── Klik thumbnail → lihat foto besar ─────────────────────────
$(document).on('click', '.room-thumb-admin', function () {
    $('#photoFull').attr('src', $(this).data('src'));
    photoModal.show();
});

// ── Tambah Kamar ───────────────────────────────────────────────
$('#btnAdd').on('click', function () {
    $('#modalTitle').text('Tambah Kamar');
    $('#roomForm')[0].reset();
    $('#roomId').val('');
    clearErrors();
    resetImagePreview();
    roomModal.show();
});

// ── Edit Kamar ─────────────────────────────────────────────────
$(document).on('click', '.btn-edit', function () {
    const id = $(this).data('id');
    $.ajax({
        url    : BASE + '/edit/' + id,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        success: function (res) {
            if (res.csrf) window.CSRF_HASH = res.csrf;
            if (res.status === 'success') {
                const d = res.data;
                $('#modalTitle').text('Edit Kamar');
                $('#roomId').val(d.id);
                $('#room_name').val(d.room_name);
                $('#price').val(d.price);
                $('#facilities').val(d.facilities);
                $('#status').val(d.status);
                clearErrors();
                resetImagePreview();

                if (d.image_url) {
                    $('#imagePreview').attr('src', d.image_url);
                    $('#previewWrapper').show();
                    $('#existingImageInfo').removeClass('d-none');
                    $('#removeImageCheck').show();
                    $('#remove_image').prop('checked', false);
                }
                roomModal.show();
            }
        }
    });
});

// ── Preview foto saat dipilih ──────────────────────────────────
$('#imageFile').on('change', function () {
    const file = this.files[0];
    if (! file) return;

    const maxMB = 3;
    if (file.size > maxMB * 1024 * 1024) {
        showToast('Ukuran foto maksimal ' + maxMB + ' MB.', 'warning');
        this.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = e => {
        $('#imagePreview').attr('src', e.target.result);
        $('#previewWrapper').show();
        $('#existingImageInfo').addClass('d-none');
        $('#removeImageCheck').hide();
        $('#remove_image').prop('checked', false);
    };
    reader.readAsDataURL(file);
});

// ── Hapus foto → sembunyikan preview ──────────────────────────
$('#remove_image').on('change', function () {
    if (this.checked) {
        $('#imagePreview').attr('src', '');
        $('#previewWrapper').hide();
    }
});

// ── Simpan ─────────────────────────────────────────────────────
$('#btnSave').on('click', function () {
    const id  = $('#roomId').val();
    const url = id ? BASE + '/update/' + id : BASE + '/store';

    clearErrors();
    showSpinner(true);

    // Gunakan FormData agar bisa kirim file
    const fd = new FormData();
    fd.append(window.CSRF_NAME, window.CSRF_HASH);
    fd.append('room_name',    $('#room_name').val());
    fd.append('price',        $('#price').val());
    fd.append('facilities',   $('#facilities').val());
    fd.append('status',       $('#status').val());
    fd.append('remove_image', $('#remove_image').is(':checked') ? '1' : '0');

    const imgFile = $('#imageFile')[0].files[0];
    if (imgFile) fd.append('image', imgFile);

    $.ajax({
        url         : url,
        method      : 'POST',
        headers     : { 'X-Requested-With': 'XMLHttpRequest' },
        data        : fd,
        processData : false,   // jangan encode FormData
        contentType : false,   // biarkan browser set multipart boundary
        success: function (res) {
            showSpinner(false);
            if (res.csrf) window.CSRF_HASH = res.csrf;
            if (res.status === 'success') {
                roomModal.hide();
                table.ajax.reload(null, false);
                showToast(res.message, 'success');
            } else {
                showFieldErrors(res.errors);
                showToast(res.message ?? 'Validasi gagal.', 'danger');
            }
        },
        error: () => { showSpinner(false); showToast('Server error.', 'danger'); }
    });
});

// ── Hapus Kamar ────────────────────────────────────────────────
$(document).on('click', '.btn-delete', function () {
    const id = $(this).data('id');
    if (! confirm('Yakin hapus kamar ini? Foto juga akan dihapus.')) return;
    $.ajax({
        url    : BASE + '/delete/' + id,
        method : 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        data   : { [window.CSRF_NAME]: window.CSRF_HASH },
        success: function (res) {
            if (res.csrf) window.CSRF_HASH = res.csrf;
            if (res.status === 'success') {
                table.ajax.reload(null, false);
                showToast(res.message, 'success');
            }
        }
    });
});

// ── Helpers ────────────────────────────────────────────────────
function resetImagePreview() {
    $('#imageFile').val('');
    $('#imagePreview').attr('src', '');
    $('#previewWrapper').hide();
    $('#existingImageInfo').addClass('d-none');
    $('#removeImageCheck').hide();
    $('#remove_image').prop('checked', false);
}
function showFieldErrors(errors) {
    for (const [field, msg] of Object.entries(errors || {})) {
        $('#' + field).addClass('is-invalid');
        $('#err_' + field).text(msg);
    }
}
function clearErrors() {
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').text('');
}
function showSpinner(state) {
    $('#saveSpinner').toggleClass('d-none', !state);
    $('#btnSave').prop('disabled', state);
}
function showToast(message, type = 'success') {
    const el = $(`<div class="toast align-items-center text-bg-${type} border-0 show
                      position-fixed bottom-0 end-0 m-3 shadow" style="z-index:9999">
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div></div>`).appendTo('body');
    setTimeout(() => el.fadeOut(300, () => el.remove()), 3500);
}
</script>
<?= $this->endSection() ?>