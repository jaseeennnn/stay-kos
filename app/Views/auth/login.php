<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – StayKos</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            display: flex; align-items: center; justify-content: center;
        }
        .auth-card {
            border-radius: 20px; border: none;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
            width: 100%; max-width: 420px;
        }
        .logo { font-size: 2.5rem; color: #ffc107; }
    </style>
</head>
<body>
<div class="auth-card card p-4 p-md-5">
    <div class="text-center mb-4">
        <div class="logo"><i class="bi bi-house-heart-fill"></i></div>
        <h3 class="fw-bold mt-2">StayKos</h3>
        <p class="text-muted small">Masuk ke akun Anda</p>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>

    <form method="POST" action="/login">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label fw-semibold">Username</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" name="username" class="form-control"
                    value="<?= old('username') ?>" placeholder="Masukkan username" required>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" id="loginPwd" class="form-control"
                    placeholder="Masukkan password" required>
                <button class="btn btn-outline-secondary" type="button" id="toggleLoginPwd">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
            <i class="bi bi-box-arrow-in-right me-2"></i>Login
        </button>
    </form>
    <p class="text-center mt-3 text-muted small">
        Belum punya akun? <a href="/register" class="fw-semibold">Daftar di sini</a>
    </p>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('toggleLoginPwd')?.addEventListener('click', function () {
        const pwd = document.getElementById('loginPwd');
        const icon = this.querySelector('i');
        if (pwd.type === 'password') { pwd.type = 'text'; icon.className = 'bi bi-eye-slash'; }
        else { pwd.type = 'password'; icon.className = 'bi bi-eye'; }
    });
</script>
</body>
</html>