<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Masuk — Hadirin</title>

    <link
        rel="icon"
        type="image/svg+xml"
        href="{{ asset('favicon.svg') }}?v=2"
    >

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
        }

        body {
            min-height: 100vh;
            color: #ffffff;
            font-family: Arial, Helvetica, sans-serif;
            background:
                radial-gradient(
                    circle at 15% 20%,
                    rgba(37, 99, 235, 0.34),
                    transparent 30%
                ),
                radial-gradient(
                    circle at 88% 78%,
                    rgba(16, 185, 129, 0.18),
                    transparent 30%
                ),
                #020617;
        }

        .login-page {
            width: min(1280px, 100%);
            min-height: 100vh;
            margin: 0 auto;
            padding: 40px;
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(380px, 0.95fr);
            align-items: center;
            gap: 72px;
        }

        .identity {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo {
            width: 68px;
            height: 68px;
            flex: 0 0 68px;
            display: grid;
            place-items: center;
            border-radius: 22px;
            color: #ffffff;
            font-size: 30px;
            font-weight: 900;
            background: #3b82f6;
            box-shadow: 0 18px 40px rgba(59, 130, 246, 0.3);
        }

        .brand-name {
            margin: 0;
            font-size: 38px;
            font-weight: 900;
            line-height: 1;
            letter-spacing: -0.04em;
        }

        .brand-description {
            margin: 12px 0 0;
            color: #cbd5e1;
            font-size: 16px;
        }

        .badge {
            display: inline-flex;
            margin-top: 42px;
            padding: 11px 22px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 999px;
            color: #dbeafe;
            background: rgba(255, 255, 255, 0.08);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        .hero-title {
            max-width: 600px;
            margin: 34px 0 0;
            color: #ffffff;
            font-size: clamp(42px, 5vw, 62px);
            font-weight: 900;
            line-height: 1.08;
            letter-spacing: -0.045em;
        }

        .hero-description {
            max-width: 560px;
            margin: 26px 0 0;
            color: #cbd5e1;
            font-size: 18px;
            line-height: 1.8;
        }

        .features {
            max-width: 590px;
            margin-top: 38px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .feature {
            padding: 22px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.05);
        }

        .feature-title {
            margin: 0;
            color: #ffffff;
            font-size: 19px;
            font-weight: 800;
        }

        .feature-description {
            margin: 8px 0 0;
            color: #94a3b8;
            font-size: 13px;
        }

        .form-column {
            width: 100%;
            display: flex;
            justify-content: flex-end;
        }

        .login-card {
            width: 100%;
            max-width: 470px;
            padding: 38px;
            border-radius: 36px;
            color: #0f172a;
            background: rgba(255, 255, 255, 0.96);
            box-shadow: 0 35px 90px rgba(0, 0, 0, 0.32);
        }

        .card-badge {
            display: inline-flex;
            padding: 9px 15px;
            border-radius: 999px;
            color: #1d4ed8;
            background: #eff6ff;
            font-size: 12px;
            font-weight: 800;
        }

        .welcome-text {
            margin: 32px 0 0;
            color: #2563eb;
            font-size: 14px;
            font-weight: 800;
        }

        .login-title {
            margin: 8px 0 0;
            color: #0f172a;
            font-size: 38px;
            font-weight: 900;
            line-height: 1.15;
            letter-spacing: -0.04em;
        }

        .login-description {
            margin: 13px 0 0;
            color: #64748b;
            font-size: 14px;
            line-height: 1.7;
        }

        .status-message,
        .error-message {
            margin-top: 22px;
            padding: 14px 16px;
            border-radius: 16px;
            font-size: 14px;
            font-weight: 700;
        }

        .status-message {
            border: 1px solid #a7f3d0;
            color: #047857;
            background: #ecfdf5;
        }

        .error-message {
            border: 1px solid #fecaca;
            color: #b91c1c;
            background: #fef2f2;
        }

        .login-form {
            margin-top: 30px;
        }

        .form-group + .form-group {
            margin-top: 21px;
        }

        .form-label {
            display: block;
            margin-bottom: 9px;
            color: #334155;
            font-size: 14px;
            font-weight: 800;
        }

        .form-input {
            width: 100%;
            height: 58px;
            padding: 0 18px;
            border: 1px solid #dbeafe;
            border-radius: 17px;
            outline: none;
            color: #0f172a;
            background: #eff6ff;
            font-size: 16px;
            transition: 0.2s;
        }

        .form-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
        }

        .remember {
            width: fit-content;
            margin-top: 23px;
            display: flex;
            align-items: center;
            gap: 11px;
            color: #64748b;
            font-size: 14px;
            cursor: pointer;
        }

        .remember input {
            width: 18px;
            height: 18px;
        }

        .login-button {
            width: 100%;
            height: 58px;
            margin-top: 26px;
            border: 0;
            border-radius: 17px;
            color: #ffffff;
            background: #2563eb;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 14px 28px rgba(37, 99, 235, 0.24);
            transition: 0.2s;
        }

        .login-button:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .mobile-brand {
            display: none;
        }

        .footer-text {
            margin: 18px 0 0;
            color: #64748b;
            font-size: 12px;
            text-align: center;
        }

        @media (max-width: 900px) {
            .login-page {
                width: 100%;
                grid-template-columns: 1fr;
                padding: 32px 20px;
            }

            .information-column {
                display: none;
            }

            .form-column {
                justify-content: center;
            }

            .mobile-brand {
                max-width: 470px;
                margin: 0 auto 24px;
                display: flex;
                align-items: center;
                gap: 14px;
            }

            .mobile-brand .logo {
                width: 56px;
                height: 56px;
                flex-basis: 56px;
                border-radius: 18px;
                font-size: 24px;
            }

            .mobile-brand .brand-name {
                font-size: 26px;
            }

            .mobile-brand .brand-description {
                margin-top: 6px;
                font-size: 13px;
            }
        }

        @media (max-width: 520px) {
            .login-page {
                padding: 24px 14px;
                align-items: start;
            }

            .login-card {
                padding: 26px 20px;
                border-radius: 28px;
            }

            .login-title {
                font-size: 31px;
            }

            .form-input,
            .login-button {
                height: 54px;
            }
        }
    </style>
</head>

<body>
    <main class="login-page">
        {{-- Informasi kiri --}}
        <section class="information-column">
            <div class="identity">
                <div class="logo">H</div>

                <div>
                    <h1 class="brand-name">Hadirin</h1>

                    <p class="brand-description">
                        Sistem Absensi · SMKN 4 Pariaman
                    </p>
                </div>
            </div>

            <div class="badge">
                Sistem Absensi Digital
            </div>

            <h2 class="hero-title">
                Kehadiran lebih mudah, cepat, dan akurat.
            </h2>

            <p class="hero-description">
                Masuk untuk mencatat kehadiran, melihat riwayat absensi,
                dan mengakses layanan sesuai hak akses Anda.
            </p>

            <div class="features">
                <article class="feature">
                    <h3 class="feature-title">Selfie</h3>
                    <p class="feature-description">Kamera depan</p>
                </article>

                <article class="feature">
                    <h3 class="feature-title">GPS</h3>
                    <p class="feature-description">Radius 100 meter</p>
                </article>

                <article class="feature">
                    <h3 class="feature-title">Akses</h3>
                    <p class="feature-description">Otomatis</p>
                </article>
            </div>
        </section>

        {{-- Form kanan --}}
        <section>
            <div class="mobile-brand">
                <div class="logo">H</div>

                <div>
                    <h1 class="brand-name">Hadirin</h1>
                    <p class="brand-description">SMKN 4 Pariaman</p>
                </div>
            </div>

            <div class="form-column">
                <div class="login-card">
                    <span class="card-badge">
                        Sistem Kehadiran
                    </span>

                    <p class="welcome-text">
                        Selamat datang kembali
                    </p>

                    <h2 class="login-title">
                        Masuk ke Hadirin
                    </h2>

                    <p class="login-description">
                        Gunakan akun Anda untuk mengakses layanan
                        absensi dan informasi kehadiran.
                    </p>

                    @if (session('status'))
                        <div class="status-message" role="status">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if (is_object($errors) && $errors->any())
                        <div class="error-message" role="alert">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form
                        class="login-form"
                        method="POST"
                        action="{{ route('login.store') }}"
                    >
                        @csrf

                        <div class="form-group">
                            <label class="form-label" for="email">
                                Alamat email
                            </label>

                            <input
                                class="form-input"
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                placeholder="nama@hadirin.test"
                                required
                                autofocus
                                autocomplete="username"
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password">
                                Password
                            </label>

                            <input
                                class="form-input"
                                id="password"
                                name="password"
                                type="password"
                                placeholder="••••••••"
                                required
                                autocomplete="current-password"
                            >
                        </div>

                        <label class="remember" for="remember">
                            <input id="remember" type="checkbox" name="remember">
                            <span>Ingat saya</span>
                        </label>

                        <button class="login-button" type="submit">
                            Masuk&nbsp;&nbsp;→
                        </button>
                    </form>
                </div>
            </div>

            <p class="footer-text">
                Sistem Absensi SMKN 4 Pariaman
            </p>
        </section>
    </main>
</body>
</html>
