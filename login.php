<?php

require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/config/auth.php";

/*
|--------------------------------------------------------------------------
| SECURITY HEADERS
|--------------------------------------------------------------------------
*/

header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
header("Cross-Origin-Opener-Policy: same-origin");

/*
|--------------------------------------------------------------------------
| CONTENT SECURITY POLICY
|--------------------------------------------------------------------------
| Tidak menggunakan CDN eksternal pada halaman login.
*/

$nonce = base64_encode(random_bytes(16));

header(
    "Content-Security-Policy: " .
    "default-src 'self'; " .
    "img-src 'self' data:; " .
    "style-src 'self' 'unsafe-inline'; " .
    "script-src 'self' 'nonce-" . $nonce . "'; " .
    "font-src 'self'; " .
    "connect-src 'self'; " .
    "form-action 'self'; " .
    "frame-ancestors 'none'; " .
    "base-uri 'self'; " .
    "object-src 'none';"
);


/*
|--------------------------------------------------------------------------
| JIKA SUDAH LOGIN
|--------------------------------------------------------------------------
*/

if (sudah_login()) {
    redirect_role();
}


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION["csrf_login"])) {
    $_SESSION["csrf_login"] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION["csrf_login"];


/*
|--------------------------------------------------------------------------
| VARIABLE
|--------------------------------------------------------------------------
*/

$error = "";
$success = "";


/*
|--------------------------------------------------------------------------
| PROSES LOGIN
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /*
    |--------------------------------------------------------------------------
    | VALIDASI CSRF
    |--------------------------------------------------------------------------
    */

    $csrf_post = $_POST["csrf_token"] ?? "";

    if (
        empty($csrf_post) ||
        empty($_SESSION["csrf_login"]) ||
        !hash_equals($_SESSION["csrf_login"], $csrf_post)
    ) {

        $error = "Permintaan login tidak valid. Silakan muat ulang halaman.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | AMBIL INPUT
        |--------------------------------------------------------------------------
        */

        $username = trim($_POST["username"] ?? "");
        $password = $_POST["password"] ?? "";
        $role     = trim($_POST["role"] ?? "");


        /*
        |--------------------------------------------------------------------------
        | VALIDASI INPUT
        |--------------------------------------------------------------------------
        */

        $role_valid = in_array(
            $role,
            ["siswa", "wali_kelas"],
            true
        );

        if (
            $username === "" ||
            $password === "" ||
            !$role_valid
        ) {

            $error = "Username, password, dan pilihan akun wajib diisi.";

        } elseif (strlen($username) > 50) {

            $error = "Username tidak valid.";

        } elseif (strlen($password) > 255) {

            $error = "Password tidak valid.";

        } else {

            /*
            |--------------------------------------------------------------------------
            | QUERY DATABASE
            |--------------------------------------------------------------------------
            */

            $stmt = mysqli_prepare(
                $conn,
                "SELECT id, nama, username, password, role
                 FROM users
                 WHERE username = ?
                 AND role = ?
                 LIMIT 1"
            );


            if (!$stmt) {

                $error = "Terjadi kesalahan pada sistem login.";

            } else {

                mysqli_stmt_bind_param(
                    $stmt,
                    "ss",
                    $username,
                    $role
                );

                mysqli_stmt_execute($stmt);

                /*
                |--------------------------------------------------------------------------
                | AMBIL DATA
                |--------------------------------------------------------------------------
                */

                mysqli_stmt_bind_result(
                    $stmt,
                    $user_id,
                    $user_nama,
                    $user_username,
                    $user_password,
                    $user_role
                );

                $user_found = mysqli_stmt_fetch($stmt);

                mysqli_stmt_close($stmt);


                /*
                |--------------------------------------------------------------------------
                | VALIDASI AKUN
                |--------------------------------------------------------------------------
                */

                if (
                    !$user_found ||
                    !password_verify($password, $user_password)
                ) {

                    /*
                    | Pesan dibuat sama untuk username/password
                    | agar tidak membocorkan apakah username terdaftar.
                    */

                    $error = "Username, password, atau jenis akun salah.";

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | REGENERATE SESSION
                    |--------------------------------------------------------------------------
                    */

                    session_regenerate_id(true);


                    /*
                    |--------------------------------------------------------------------------
                    | SIMPAN SESSION
                    |--------------------------------------------------------------------------
                    */

                    $_SESSION["user_id"]  = $user_id;
                    $_SESSION["nama"]     = $user_nama;
                    $_SESSION["username"] = $user_username;
                    $_SESSION["role"]     = $user_role;


                    /*
                    |--------------------------------------------------------------------------
                    | REGENERATE CSRF TOKEN
                    |--------------------------------------------------------------------------
                    */

                    $_SESSION["csrf_login"] = bin2hex(
                        random_bytes(32)
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | REDIRECT BERDASARKAN ROLE
                    |--------------------------------------------------------------------------
                    */

                    if ($user_role === "siswa") {

                        header("Location: siswa/index.php");
                        exit;

                    }

                    if ($user_role === "wali_kelas") {

                        header("Location: admin/index.php");
                        exit;

                    }

                    /*
                    | Seharusnya tidak pernah sampai sini
                    */

                    $_SESSION = [];

                    session_destroy();

                    $error = "Role akun tidak dikenali.";
                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="robots"
        content="noindex, nofollow"
    >

    <title>Login - XI TJKT 2</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {

            min-height: 100vh;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background:
                radial-gradient(
                    circle at top left,
                    #243b55 0%,
                    #141e30 45%,
                    #080b12 100%
                );

            color: #ffffff;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 20px;
        }


        .login-container {

            width: 100%;
            max-width: 430px;
        }


        .login-card {

            width: 100%;

            padding: 35px 30px;

            border-radius: 24px;

            background:
                rgba(255, 255, 255, 0.08);

            border:
                1px solid rgba(255, 255, 255, 0.14);

            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);

            box-shadow:
                0 25px 70px rgba(0, 0, 0, 0.45);
        }


        /* =========================
           LOGO
        ========================= */

        .brand-logo {

            width: 90px;
            height: 90px;

            margin: 0 auto 18px;

            border-radius: 22px;

            overflow: hidden;

            display: flex;
            align-items: center;
            justify-content: center;

            background:
                rgba(255, 255, 255, 0.08);

            border:
                1px solid rgba(255, 255, 255, 0.18);

            box-shadow:
                0 12px 35px rgba(0, 0, 0, 0.35);
        }


        .brand-logo img {

            width: 100%;
            height: 100%;

            object-fit: cover;

            display: block;
        }


        /* =========================
           HEADER
        ========================= */

        .title {

            text-align: center;

            font-size: 27px;

            font-weight: 700;

            margin-bottom: 7px;
        }


        .subtitle {

            text-align: center;

            font-size: 14px;

            color:
                rgba(255, 255, 255, 0.62);

            margin-bottom: 28px;
        }


        /* =========================
           ALERT
        ========================= */

        .alert {

            padding: 13px 15px;

            border-radius: 12px;

            margin-bottom: 18px;

            font-size: 14px;

            line-height: 1.5;
        }


        .alert-error {

            background:
                rgba(239, 68, 68, 0.14);

            border:
                1px solid rgba(239, 68, 68, 0.35);

            color: #ffb4b4;
        }


        .alert-success {

            background:
                rgba(34, 197, 94, 0.14);

            border:
                1px solid rgba(34, 197, 94, 0.35);

            color: #a7f3c0;
        }


        /* =========================
           ROLE
        ========================= */

        .role-title {

            font-size: 14px;

            font-weight: 600;

            margin-bottom: 10px;

            color:
                rgba(255, 255, 255, 0.82);
        }


        .role-container {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 10px;

            margin-bottom: 20px;
        }


        .role-option {

            position: relative;
        }


        .role-option input {

            position: absolute;

            opacity: 0;

            pointer-events: none;
        }


        .role-label {

            min-height: 62px;

            padding: 12px;

            border-radius: 14px;

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 9px;

            cursor: pointer;

            background:
                rgba(255, 255, 255, 0.055);

            border:
                1px solid rgba(255, 255, 255, 0.12);

            color:
                rgba(255, 255, 255, 0.72);

            transition:
                0.2s ease;

            font-size: 14px;

            font-weight: 600;
        }


        .role-label:hover {

            background:
                rgba(255, 255, 255, 0.09);

            border-color:
                rgba(255, 255, 255, 0.22);
        }


        .role-option input:checked + .role-label {

            background:
                rgba(59, 130, 246, 0.20);

            border-color:
                rgba(96, 165, 250, 0.75);

            color: #ffffff;

            box-shadow:
                0 0 0 2px
                rgba(59, 130, 246, 0.12);
        }


        /* =========================
           ROLE ICON
        ========================= */

        .role-icon {

            font-size: 19px;

            width: 22px;

            text-align: center;
        }


        /* =========================
           FORM
        ========================= */

        .form-group {

            margin-bottom: 18px;
        }


        .form-label {

            display: block;

            font-size: 14px;

            font-weight: 600;

            margin-bottom: 8px;

            color:
                rgba(255, 255, 255, 0.82);
        }


        .input-wrapper {

            position: relative;
        }


        .input-icon {

            position: absolute;

            left: 15px;

            top: 50%;

            transform: translateY(-50%);

            color:
                rgba(255, 255, 255, 0.45);

            font-size: 15px;

            pointer-events: none;
        }


        .form-control {

            width: 100%;

            height: 50px;

            padding:
                0 48px 0 45px;

            border-radius: 13px;

            border:
                1px solid rgba(255, 255, 255, 0.13);

            outline: none;

            background:
                rgba(0, 0, 0, 0.18);

            color: #ffffff;

            font-size: 14px;

            transition:
                0.2s ease;
        }


        .form-control::placeholder {

            color:
                rgba(255, 255, 255, 0.35);
        }


        .form-control:focus {

            border-color:
                rgba(96, 165, 250, 0.8);

            background:
                rgba(0, 0, 0, 0.25);

            box-shadow:
                0 0 0 3px
                rgba(59, 130, 246, 0.12);
        }


        /* =========================
           PASSWORD
        ========================= */

        .password-toggle {

            position: absolute;

            right: 14px;

            top: 50%;

            transform: translateY(-50%);

            border: none;

            background: transparent;

            color:
                rgba(255, 255, 255, 0.45);

            cursor: pointer;

            font-size: 18px;

            padding: 5px;
        }


        .password-toggle:hover {

            color: #ffffff;
        }


        /* =========================
           BUTTON
        ========================= */

        .login-button {

            width: 100%;

            height: 52px;

            border: none;

            border-radius: 14px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #3b82f6
                );

            color: #ffffff;

            font-size: 15px;

            font-weight: 700;

            cursor: pointer;

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 9px;

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;

            box-shadow:
                0 10px 25px
                rgba(37, 99, 235, 0.28);
        }


        .login-button:hover {

            transform: translateY(-1px);

            box-shadow:
                0 14px 30px
                rgba(37, 99, 235, 0.38);
        }


        .login-button:active {

            transform: translateY(0);
        }


        /* =========================
           FOOTER
        ========================= */

        .footer {

            text-align: center;

            margin-top: 25px;

            font-size: 12px;

            color:
                rgba(255, 255, 255, 0.42);
        }


        .footer strong {

            color:
                rgba(255, 255, 255, 0.7);
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 480px) {

            body {

                padding: 14px;
            }


            .login-card {

                padding:
                    28px 20px;

                border-radius: 20px;
            }


            .brand-logo {

                width: 78px;
                height: 78px;

                border-radius: 18px;
            }


            .title {

                font-size: 24px;
            }


            .subtitle {

                font-size: 13px;
            }
        }

    </style>

</head>


<body>

<div class="login-container">

    <div class="login-card">


        <!-- LOGO -->

        <div class="brand-logo">

            <img
                src="assets/images/logo.png"
                alt="Logo XI TJKT 2"
            >

        </div>


        <!-- HEADER -->

        <div class="title">
            XI TJKT 2
        </div>


        <div class="subtitle">
            Sistem Informasi Kelas
        </div>


        <!-- ERROR -->

        <?php if ($error !== ""): ?>

            <div
                class="alert alert-error"
                role="alert"
            >

                ⚠️

                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>

            </div>

        <?php endif; ?>


        <!-- SUCCESS -->

        <?php if ($success !== ""): ?>

            <div
                class="alert alert-success"
                role="status"
            >

                ✓

                <?= htmlspecialchars(
                    $success,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>

            </div>

        <?php endif; ?>


        <!-- LOGIN FORM -->

        <form
            method="POST"
            action="login.php"
            autocomplete="on"
        >

            <!-- CSRF -->

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $csrf_token,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
            >


            <!-- ROLE -->

            <div class="role-title">
                Masuk sebagai
            </div>


            <div class="role-container">


                <!-- WALI KELAS -->

                <div class="role-option">

                    <input
                        type="radio"
                        id="wali_kelas"
                        name="role"
                        value="wali_kelas"
                        checked
                    >

                    <label
                        for="wali_kelas"
                        class="role-label"
                    >

                        <span class="role-icon">
                            👨‍🏫
                        </span>

                        <span>
                            Guru / Wali
                        </span>

                    </label>

                </div>


                <!-- SISWA -->

                <div class="role-option">

                    <input
                        type="radio"
                        id="siswa"
                        name="role"
                        value="siswa"
                    >

                    <label
                        for="siswa"
                        class="role-label"
                    >

                        <span class="role-icon">
                            🎓
                        </span>

                        <span>
                            Siswa
                        </span>

                    </label>

                </div>

            </div>


            <!-- USERNAME -->

            <div class="form-group">

                <label
                    for="username"
                    class="form-label"
                >
                    Username
                </label>


                <div class="input-wrapper">

                    <span
                        class="input-icon"
                        aria-hidden="true"
                    >
                        👤
                    </span>


                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control"
                        placeholder="Masukkan username"
                        required
                        maxlength="50"
                        autocomplete="username"
                    >

                </div>

            </div>


            <!-- PASSWORD -->

            <div class="form-group">

                <label
                    for="password"
                    class="form-label"
                >
                    Password
                </label>


                <div class="input-wrapper">

                    <span
                        class="input-icon"
                        aria-hidden="true"
                    >
                        🔒
                    </span>


                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Masukkan password"
                        required
                        maxlength="255"
                        autocomplete="current-password"
                    >


                    <button
                        type="button"
                        class="password-toggle"
                        id="togglePassword"
                        aria-label="Tampilkan password"
                        aria-pressed="false"
                    >
                        👁
                    </button>

                </div>

            </div>


            <!-- LOGIN BUTTON -->

            <button
                type="submit"
                class="login-button"
            >

                <span>
                    → 
                </span>

                <span>
                    Masuk
                </span>

            </button>

        </form>


        <!-- FOOTER -->

        <div class="footer">

            <strong>
                XI TJKT 2
            </strong>

            <br>

            Tahun Pelajaran 2026 / 2027

        </div>

    </div>

</div>


<script nonce="<?= htmlspecialchars(
    $nonce,
    ENT_QUOTES,
    "UTF-8"
) ?>">

    const passwordInput =
        document.getElementById("password");

    const togglePassword =
        document.getElementById("togglePassword");


    togglePassword.addEventListener(
        "click",
        function () {

            const isPassword =
                passwordInput.type === "password";


            if (isPassword) {

                passwordInput.type = "text";

                togglePassword.textContent = "🙈";

                togglePassword.setAttribute(
                    "aria-label",
                    "Sembunyikan password"
                );

                togglePassword.setAttribute(
                    "aria-pressed",
                    "true"
                );

            } else {

                passwordInput.type = "password";

                togglePassword.textContent = "👁";

                togglePassword.setAttribute(
                    "aria-label",
                    "Tampilkan password"
                );

                togglePassword.setAttribute(
                    "aria-pressed",
                    "false"
                );

            }

        }
    );

</script>


</body>

</html>