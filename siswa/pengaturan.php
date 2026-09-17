<?php
require_once "../config/auth.php";
require_once "../config/database.php";
wajib_login(['siswa']);

date_default_timezone_set('Asia/Jakarta');
$user_id = (int)($_SESSION['user_id'] ?? 0);
$pesan = '';
$error = '';

$stmt = mysqli_prepare($conn, "SELECT nama, username, password, profile_photo FROM users WHERE id = ? AND role = 'siswa' LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$user) { die('Akun siswa tidak ditemukan.'); }

if (empty($_SESSION['csrf_siswa_settings'])) {
    $_SESSION['csrf_siswa_settings'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_siswa_settings'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
        $error = 'Permintaan tidak valid. Silakan coba lagi.';
    } else {
        $aksi = $_POST['aksi'] ?? '';

        if ($aksi === 'password') {
            $lama = $_POST['password_lama'] ?? '';
            $baru = $_POST['password_baru'] ?? '';
            $konfirmasi = $_POST['password_konfirmasi'] ?? '';

            if (!password_verify($lama, $user['password'])) {
                $error = 'Password lama tidak sesuai.';
            } elseif (strlen($baru) < 6) {
                $error = 'Password baru minimal 6 karakter.';
            } elseif ($baru !== $konfirmasi) {
                $error = 'Konfirmasi password tidak sama.';
            } else {
                $hash = password_hash($baru, PASSWORD_DEFAULT);
                $up = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ? AND role = 'siswa'");
                mysqli_stmt_bind_param($up, "si", $hash, $user_id);
                if (mysqli_stmt_execute($up)) $pesan = 'Password berhasil diganti.';
                else $error = 'Gagal mengganti password.';
                mysqli_stmt_close($up);
            }
        }

        if ($aksi === 'foto' && empty($error)) {
            if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Pilih foto terlebih dahulu.';
            } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
                $error = 'Ukuran foto maksimal 2 MB.';
            } else {
                $tmp = $_FILES['foto']['tmp_name'];
                $mime = function_exists('mime_content_type') ? mime_content_type($tmp) : '';
                $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
                if (!isset($allowed[$mime])) {
                    $error = 'Format foto harus JPG, PNG, atau WEBP.';
                } else {
                    $dir = __DIR__ . '/../uploads/profile';
                    if (!is_dir($dir)) @mkdir($dir, 0755, true);
                    $filename = 'siswa_' . $user_id . '_' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
                    $target = $dir . '/' . $filename;
                    if (move_uploaded_file($tmp, $target)) {
                        $up = mysqli_prepare($conn, "UPDATE users SET profile_photo = ? WHERE id = ? AND role = 'siswa'");
                        mysqli_stmt_bind_param($up, "si", $filename, $user_id);
                        if (mysqli_stmt_execute($up)) {
                            if (!empty($user['profile_photo'])) @unlink($dir . '/' . basename($user['profile_photo']));
                            $user['profile_photo'] = $filename;
                            $pesan = 'Foto profil berhasil diperbarui.';
                        } else {
                            @unlink($target); $error = 'Gagal menyimpan foto profil.';
                        }
                        mysqli_stmt_close($up);
                    } else $error = 'Foto gagal diunggah.';
                }
            }
        }
    }
}

$avatar = !empty($user['profile_photo']) ? '../uploads/profile/' . rawurlencode($user['profile_photo']) : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pengaturan Akun | XI TJKT 2</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
*{box-sizing:border-box}body{margin:0;background:#f4f7fb;color:#172033;font-family:Inter,"Segoe UI",Arial,sans-serif}.topbar{height:76px;background:#fff;border-bottom:1px solid #e8edf5;display:flex;align-items:center;padding:0 34px}.brand{display:flex;align-items:center;gap:13px}.brand-logo{width:45px;height:45px;border-radius:13px;object-fit:cover;background:#eef2f7}.brand-title{font-size:17px;font-weight:800;margin:0}.brand-subtitle{font-size:12px;color:#8791a3}.page{max-width:900px;margin:auto;padding:30px 34px 50px}.back{display:inline-block;color:#687386;text-decoration:none;font-size:13px;margin-bottom:18px}.hero{background:#172033;color:#fff;border-radius:24px;padding:27px 30px;margin-bottom:20px}.hero h1{font-size:25px;font-weight:800;margin:0 0 5px}.hero p{margin:0;color:#bbc4d3;font-size:13px}.panel{background:#fff;border:1px solid #e8edf5;border-radius:20px;padding:22px;margin-bottom:18px}.panel h2{font-size:16px;font-weight:800;margin:0 0 17px}.profile{display:flex;align-items:center;gap:15px;margin-bottom:20px}.avatar{width:64px;height:64px;border-radius:18px;background:#172033;color:#fff;display:grid;place-items:center;font-weight:800;font-size:22px;overflow:hidden}.avatar img{width:100%;height:100%;object-fit:cover}.muted{font-size:12px;color:#8791a3}.form-label{font-size:12px;font-weight:700}.form-control{border-radius:10px;padding:11px;border-color:#dfe4ec}.btn-main{background:#172033;color:#fff;border:0;border-radius:10px;padding:10px 15px;font-weight:700;font-size:13px}.alert{font-size:13px;border-radius:12px}@media(max-width:600px){.topbar{padding:0 20px}.page{padding:20px}.hero{padding:23px}.brand-subtitle{display:none}}
</style></head><body>
<header class="topbar"><div class="brand"><img src="../assets/images/logo.png" class="brand-logo" alt="Logo"><div><p class="brand-title">XI TJKT 2</p><div class="brand-subtitle">Portal Siswa</div></div></div></header>
<main class="page"><a class="back" href="index.php"><i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard</a>
<section class="hero"><h1>Pengaturan Akun</h1><p>Kelola password dan foto profil akun kamu.</p></section>
<?php if($pesan): ?><div class="alert alert-success"><?=htmlspecialchars($pesan)?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
<div class="panel"><h2><i class="fa-solid fa-user"></i> Profil Akun</h2><div class="profile"><div class="avatar"><?php if($avatar): ?><img src="<?=htmlspecialchars($avatar)?>" alt="Foto profil"><?php else: ?><?=strtoupper(substr($user['nama'],0,1))?><?php endif; ?></div><div><strong><?=htmlspecialchars($user['nama'])?></strong><div class="muted">NIS / Username: <?=htmlspecialchars($user['username'])?></div></div></div>
<form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="aksi" value="foto"><label class="form-label">Foto Profil</label><input class="form-control mb-3" type="file" name="foto" accept="image/jpeg,image/png,image/webp" required><div class="muted mb-3">JPG, PNG, WEBP · maksimal 2 MB</div><button class="btn-main" type="submit"><i class="fa-solid fa-camera"></i> Simpan Foto</button></form></div>
<div class="panel"><h2><i class="fa-solid fa-lock"></i> Ganti Password</h2><form method="post"><input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="aksi" value="password"><div class="mb-3"><label class="form-label">Password Lama</label><input class="form-control" type="password" name="password_lama" required></div><div class="mb-3"><label class="form-label">Password Baru</label><input class="form-control" type="password" name="password_baru" minlength="6" required></div><div class="mb-3"><label class="form-label">Konfirmasi Password Baru</label><input class="form-control" type="password" name="password_konfirmasi" minlength="6" required></div><button class="btn-main" type="submit"><i class="fa-solid fa-key"></i> Ganti Password</button></form></div>
</main></body></html>
