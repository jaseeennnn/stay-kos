<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
.room-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.room-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 28px rgba(0,0,0,0.13) !important;
}
.room-photo-wrap {
    position: relative;
    height: 200px;
    overflow: hidden;
    border-radius: 12px 12px 0 0;
    background: #f0f2f5;
    cursor: pointer;
}
.room-photo-wrap img {
    width: 100%; height: 100%;
    object-fit: cover;
    transition: transform 0.35s ease;
}
.room-card:hover .room-photo-wrap img {
    transform: scale(1.05);
}
.room-photo-placeholder {
    height: 200px;
    display: flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg, #f0f2f5, #e2e8f0);
    font-size: 3.5rem; color: #cbd5e0;
    border-radius: 12px 12px 0 0;
}
.room-photo-zoom {
    position: absolute; bottom: 8px; right: 8px;
    background: rgba(0,0,0,0.5); color: #fff;
    border: none; border-radius: 6px;
    padding: 4px 8px; font-size: 0.75rem;
    opacity: 0; transition: opacity 0.2s;
    backdrop-filter: blur(4px);
}
.room-card:hover .room-photo-zoom { opacity: 1; }
.facility-tag {
    display: inline-block;
    background: #e8f4fd; color: #0369a1;
    border-radius: 20px; padding: 2px 10px;
    font-size: 0.72rem; font-weight: 500;
    margin: 2px;
}

/* Lightbox */
#lightboxBackdrop {
    display: none;
    position: fixed; inset: 0; z-index: 9999;
    background: rgba(0,0,0,0.92);
    align-items: center; justify-content: center;
    animation: fadeInLb 0.2s ease;
}
#lightboxBackdrop.show { display: flex; }
#lightboxImg {
    max-width: 92vw; max-height: 88vh;
    object-fit: contain;
    border-radius: 8px;
    box-shadow: 0 0 60px rgba(0,0,0,0.6);
}
#lightboxClose {
    position: absolute; top: 16px; right: 20px;
    background: rgba(255,255,255,0.15); border: none;
    color: #fff; font-size: 1.6rem; width: 42px; height: 42px;
    border-radius: 50%; cursor: pointer; line-height: 1;
    transition: background 0.2s;
}
#lightboxClose:hover { background: rgba(255,255,255,0.3); }
#lightboxCaption {
    position: absolute; bottom: 16px;
    left: 50%; transform: translateX(-50%);
    background: rgba(0,0,0,0.5); color: #fff;
    padding: 6px 16px; border-radius: 20px;
    font-size: 0.82rem; backdrop-filter: blur(4px);
    white-space: nowrap;
}
@keyframes fadeInLb {
    from { opacity: 0; } to { opacity: 1; }
}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-1 fw-bold">Kamar Tersedia</h4>
        <p class="text-muted mb-0">Temukan kamar kos yang sesuai kebutuhanmu</p>
    </div>
    <span class="badge bg-success fs-6 px-3 py-2"><?= count($rooms) ?> kamar</span>
</div>

<?php if (empty($rooms)): ?>
    <div class="text-center py-5">
        <i class="bi bi-house-x fs-1 text-muted d-block mb-3"></i>
        <h5 class="text-muted">Belum ada kamar yang tersedia saat ini.</h5>
        <p class="text-muted small">Coba cek lagi nanti ya!</p>
    </div>

<?php else: ?>
    <div class="row g-4">
        <?php foreach ($rooms as $room): ?>
        <div class="col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-3 h-100 room-card">

                <!-- Foto Kamar -->
                <?php if (! empty($room['image'])): ?>
                <div class="room-photo-wrap"
                     data-src="<?= esc($uploadUrl . $room['image']) ?>"
                     data-caption="<?= esc($room['room_name']) ?>">
                    <img src="<?= esc($uploadUrl . $room['image']) ?>"
                         alt="<?= esc($room['room_name']) ?>"
                         loading="lazy">
                    <button class="room-photo-zoom">
                        <i class="bi bi-arrows-fullscreen me-1"></i>Perbesar
                    </button>
                </div>
                <?php else: ?>
                <div class="room-photo-placeholder">
                    <i class="bi bi-house-door"></i>
                </div>
                <?php endif; ?>

                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="card-title fw-bold mb-0"><?= esc($room['room_name']) ?></h5>
                        <span class="badge bg-success rounded-pill px-2">Tersedia</span>
                    </div>

                    <p class="text-primary fw-bold fs-5 mb-2">
                        Rp <?= number_format($room['price'], 0, ',', '.') ?>
                        <small class="text-muted fw-normal fs-6">/ bulan</small>
                    </p>

                    <?php if (! empty($room['facilities'])): ?>
                    <div class="mb-3">
                        <?php foreach (array_slice(array_map('trim', explode(',', $room['facilities'])), 0, 6) as $fac): ?>
                            <?php if ($fac): ?>
                            <span class="facility-tag">
                                <i class="bi bi-check-circle-fill text-success me-1" style="font-size:0.65rem;"></i>
                                <?= esc($fac) ?>
                            </span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-muted small mb-3">Fasilitas standar</p>
                    <?php endif; ?>

                    <button class="btn btn-primary w-100 mt-auto btn-book"
                            data-id="<?= $room['id'] ?>"
                            data-name="<?= esc($room['room_name']) ?>"
                            data-price="<?= $room['price'] ?>">
                        <i class="bi bi-calendar-plus me-1"></i> Pesan Kamar Ini
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- ── Lightbox ─────────────────────────────────────────────── -->
<div id="lightboxBackdrop">
    <button id="lightboxClose" title="Tutup">×</button>
    <img id="lightboxImg" src="" alt="">
    <div id="lightboxCaption"></div>
</div>

<!-- ── Modal: Pesan Kamar ───────────────────────────────────── -->
<div class="modal fade" id="bookModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-3">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Pesan: <span id="modalRoomName"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-info-circle-fill"></i>
                    <div>
                        Harga: <strong id="previewPrice">-</strong> ×
                        <strong id="previewDuration">-</strong> bulan =
                        <strong id="previewTotal" class="text-primary">-</strong>
                    </div>
                </div>
                <form id="bookForm" novalidate>
                    <input type="hidden" id="bookRoomId">
                    <input type="hidden" id="bookRoomPrice">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tanggal Check-in</label>
                        <input type="date" class="form-control" id="checkin_date"
                            min="<?= date('Y-m-d') ?>" required>
                        <div class="invalid-feedback" id="err_checkin"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Durasi Sewa (bulan)</label>
                        <input type="number" class="form-control" id="duration" min="1" max="24" required>
                        <div class="invalid-feedback" id="err_duration"></div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold">Catatan (opsional)</label>
                        <textarea class="form-control" id="bookNotes" rows="2"
                                  placeholder="Permintaan khusus..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-primary" id="btnSubmitBook">
                    <span class="spinner-border spinner-border-sm d-none me-1" id="bookSpinner"></span>
                    <i class="bi bi-send me-1"></i> Kirim Pemesanan
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
const bookModal = new bootstrap.Modal('#bookModal');

// ── Lightbox ───────────────────────────────────────────────────
$(document).on('click', '.room-photo-wrap', function () {
    const src     = $(this).data('src');
    const caption = $(this).data('caption');
    $('#lightboxImg').attr('src', src);
    $('#lightboxCaption').text(caption);
    $('#lightboxBackdrop').addClass('show');
    $('body').css('overflow', 'hidden');
});

$('#lightboxClose, #lightboxBackdrop').on('click', function (e) {
    if (e.target === this) {
        $('#lightboxBackdrop').removeClass('show');
        $('body').css('overflow', '');
    }
});

$(document).on('keydown', function (e) {
    if (e.key === 'Escape') {
        $('#lightboxBackdrop').removeClass('show');
        $('body').css('overflow', '');
    }
});

// ── Buka modal booking ─────────────────────────────────────────
$(document).on('click', '.btn-book', function () {
    const id    = $(this).data('id');
    const name  = $(this).data('name');
    const price = parseFloat($(this).data('price'));
    $('#bookRoomId').val(id);
    $('#bookRoomPrice').val(price);
    $('#modalRoomName').text(name);
    $('#previewPrice').text(formatRp(price));
    $('#previewDuration, #previewTotal').text('-');
    $('#checkin_date, #duration, #bookNotes').val('');
    $('.is-invalid').removeClass('is-invalid');
    bookModal.show();
});

$('#duration').on('input', function () {
    const dur   = parseInt($(this).val()) || 0;
    const price = parseFloat($('#bookRoomPrice').val()) || 0;
    $('#previewDuration').text(dur || '-');
    $('#previewTotal').text(dur > 0 ? formatRp(price * dur) : '-');
});

$('#btnSubmitBook').on('click', function () {
    const checkin = $('#checkin_date').val();
    const dur     = $('#duration').val();
    if (! checkin || ! dur) { showToast('Lengkapi semua field wajib.', 'warning'); return; }

    setBookSpinner(true);
    $.ajax({
        url    : '/user/bookings/store',
        method : 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        data   : {
            [window.CSRF_NAME]: window.CSRF_HASH,
            room_id      : $('#bookRoomId').val(),
            checkin_date : checkin,
            duration     : dur,
            notes        : $('#bookNotes').val(),
        },
        success: function (res) {
            setBookSpinner(false);
            if (res.csrf) window.CSRF_HASH = res.csrf;
            if (res.status === 'success') {
                bookModal.hide();
                showToast(res.message, 'success');
            } else {
                if (res.errors) {
                    if (res.errors.checkin_date) { $('#checkin_date').addClass('is-invalid'); $('#err_checkin').text(res.errors.checkin_date); }
                    if (res.errors.duration)     { $('#duration').addClass('is-invalid'); $('#err_duration').text(res.errors.duration); }
                }
                showToast(res.message ?? 'Terjadi kesalahan.', 'danger');
            }
        },
        error: () => { setBookSpinner(false); showToast('Server error. Coba lagi.', 'danger'); }
    });
});

function formatRp(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); }
function setBookSpinner(s) { $('#bookSpinner').toggleClass('d-none', !s); $('#btnSubmitBook').prop('disabled', s); }
function showToast(message, type = 'success') {
    const el = $(`<div class="toast align-items-center text-bg-${type} border-0 show
                      position-fixed bottom-0 end-0 m-3 shadow" style="z-index:9000">
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div></div>`).appendTo('body');
    setTimeout(() => el.fadeOut(300, () => el.remove()), 3500);
}
</script>
<?= $this->endSection() ?>