<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar - Griya Sprei</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/login.css">
</head>

<body class="page-login page-register">
    <main class="login-card login-card--register">
        <header class="brand">
            <img
                class="brand__logo"
                src="/assets/images/logo.png"
                alt="Logo Griya Sprei">
            <div>
                <h1 class="brand__name">Griya Sprei</h1>
                <p class="brand__tagline">
                    Homemade Pillow, Bolster, Sprei, Bedcover, Etc.
                </p>
            </div>
        </header><!-- ===== JUDUL ===== -->
        <div style="margin-bottom: 24px;">
            <h2 style="margin-bottom: 5px;">Buat Akun</h2>
            <p style="font-size: 14px; color: #777;">
                Daftar untuk mulai berbelanja di Griya Sprei
            </p>
        </div><!-- ===== FORM REGISTER ===== -->
        <form id="registerForm" novalidate><!-- Nama -->
            <div class="field"><label class="field__label" for="nama">
                    Nama Lengkap
                    <span class="required">*</span>
                </label>
                <div class="input"><svg
                        class="input__icon"
                        viewBox="0 0 24 24"
                        width="18"
                        height="18"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8">
                        <circle cx="12" cy="8" r="4" />
                        <path d="M4 21c0-4 3.5-7 8-7s8 3 8 7" />
                    </svg><input
                        type="text"
                        id="nama"
                        name="nama"
                        placeholder="Masukkan nama lengkap"
                        autocomplete="name"><button type="button" class="input__action" id="clearNama" aria-label="Hapus nama" hidden>
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8">
                            <circle cx="12" cy="12" r="9" />
                            <path d="m9 9 6 6m0-6-6 6" />
                        </svg>
                    </button></div>
            </div><!-- Email -->
            <div class="field"><label class="field__label" for="email">
                    Email
                    <span class="required">*</span>
                </label>
                <div class="input"><svg
                        class="input__icon"
                        viewBox="0 0 24 24"
                        width="18"
                        height="18"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8">
                        <rect x="3" y="5" width="18" height="14" rx="2" />
                        <path d="m3 7 9 6 9-6" />
                    </svg><input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Masukkan email"
                        autocomplete="email"><button type="button" class="input__action" id="clearEmail" aria-label="Hapus email" hidden>
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8">
                            <circle cx="12" cy="12" r="9" />
                            <path d="m9 9 6 6m0-6-6 6" />
                        </svg>
                    </button></div>
            </div><!-- Nomor HP -->
            <div class="field"><label class="field__label" for="no_hp">
                    Nomor HP
                    <span class="required">*</span>
                </label>
                <div class="input"><svg
                        class="input__icon"
                        viewBox="0 0 24 24"
                        width="18"
                        height="18"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8">
                        <rect x="6" y="2" width="12" height="20" rx="2" />
                        <path d="M10 18h4" />
                    </svg><input
                        type="tel"
                        id="no_hp"
                        name="no_hp"
                        placeholder="Contoh: 081234567890"
                        autocomplete="tel"></div>
            </div><!-- Password -->
            <div class="field"><label class="field__label" for="password">
                    Kata Sandi
                    <span class="required">*</span>
                </label>
                <div class="input"><svg
                        class="input__icon"
                        viewBox="0 0 24 24"
                        width="18"
                        height="18"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8">
                        <rect x="5" y="11" width="14" height="10" rx="2" />
                        <path d="M8 11V8a4 4 0 0 1 8 0v3" />
                        <circle cx="12" cy="16" r=".8" fill="currentColor" />
                    </svg><input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Minimal 8 karakter"
                        autocomplete="off"><button type="button" class="input__action password-toggle" data-target="password" aria-label="Tampilkan kata sandi">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                    </button></div>
            </div><!-- Konfirmasi Password -->
            <div class="field"><label class="field__label" for="konfirmasi_password">
                    Konfirmasi Kata Sandi
                    <span class="required">*</span>
                </label>
                <div class="input"><svg
                        class="input__icon"
                        viewBox="0 0 24 24"
                        width="18"
                        height="18"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8">
                        <rect x="5" y="11" width="14" height="10" rx="2" />
                        <path d="M8 11V8a4 4 0 0 1 8 0v3" />
                        <circle cx="12" cy="16" r=".8" fill="currentColor" />
                    </svg><input
                        type="password"
                        id="konfirmasi_password"
                        name="konfirmasi_password"
                        placeholder="Ulangi kata sandi"
                        autocomplete="off"><button type="button" class="input__action password-toggle" data-target="konfirmasi_password" aria-label="Tampilkan kata sandi">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                    </button></div>
            </div><!-- Tombol Daftar -->
            <button type="submit" class="btn-submit"><svg
                    viewBox="0 0 24 24"
                    width="18"
                    height="18"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8">
                    <path d="M12 5v14M5 12h14" />
                </svg>Daftar Akun</button>
        </form><!-- Kembali Login -->
        <p class="register">
            Sudah memiliki akun?
            <a href="/?page=customer-login">Masuk Sekarang</a>
        </p>
    </main>
    <script src="/assets/js/customer/register.js"></script>
</body>

</html>