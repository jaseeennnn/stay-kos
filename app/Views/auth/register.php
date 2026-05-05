<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar – StayKos</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            display: flex; align-items: center; justify-content: center; padding: 2rem 1rem;
        }
        .auth-card { border-radius: 20px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.4); width: 100%; max-width: 460px; }
        .logo { font-size: 2.2rem; color: #ffc107; }
    </style>
</head>
<body>
<div class="auth-card card p-4 p-md-5">
    <div class="text-center mb-4">
        <div class="logo"><i class="bi bi-house-heart-fill"></i></div>
        <h3 class="fw-bold mt-2">Buat Akun</h3>
        <p class="text-muted small">Daftar untuk mulai memesan kamar kos</p>
    </div>

    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                <?php foreach (session()->getFlashdata('errors') as $err): ?>
                    <li><?= esc($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="/register">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label fw-semibold">Username</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" name="username" class="form-control"
                    value="<?= old('username') ?>" placeholder="Minimal 3 karakter" required>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">Email</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control"
                    value="<?= old('email') ?>" placeholder="you@example.com" required>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" id="regPwd" class="form-control"
                    placeholder="Minimal 6 karakter" required>
                <button class="btn btn-outline-secondary" type="button" id="toggleRegPwd">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label fw-semibold">Konfirmasi Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                <input type="password" name="confirm_password" class="form-control"
                    placeholder="Ulangi password" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
            <i class="bi bi-person-plus me-2"></i>Daftar Sekarang
        </button>
    </form>
    <p class="text-center mt-3 text-muted small">
        Sudah punya akun? <a href="/login" class="fw-semibold">Login di sini</a>
    </p>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('toggleRegPwd')?.addEventListener('click', function () {
        const pwd = document.getElementById('regPwd');
        const icon = this.querySelector('i');
        if (pwd.type === 'password') { pwd.type = 'text'; icon.className = 'bi bi-eye-slash'; }
        else { pwd.type = 'password'; icon.className = 'bi bi-eye'; }
    });
</script>
</body>
</html>