<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Cek apakah user sudah login
 */
function sudah_login()
{
    return isset($_SESSION['user_id']);
}

/**
 * Wajib login
 *
 * Contoh:
 * wajib_login();
 *
 * Khusus wali:
 * wajib_login(['wali_kelas']);
 *
 * Khusus siswa:
 * wajib_login(['siswa']);
 */
function wajib_login($roles = [])
{
    if (!sudah_login()) {
        header("Location: ../login.php");
        exit;
    }

    if (!empty($roles)) {
        $user_role = $_SESSION['role'] ?? '';

        if (!in_array($user_role, $roles, true)) {
            header("Location: ../index.php");
            exit;
        }
    }
}

/**
 * Redirect berdasarkan role
 */
function redirect_role()
{
    if (!isset($_SESSION['role'])) {
        header("Location: login.php");
        exit;
    }

    if ($_SESSION['role'] === 'siswa') {
        header("Location: siswa/index.php");
        exit;
    }

    header("Location: admin/index.php");
    exit;
}

/**
 * Cek role
 */
function is_role($role)
{
    return isset($_SESSION['role'])
        && $_SESSION['role'] === $role;
}