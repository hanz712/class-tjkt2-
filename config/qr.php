<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('QR_SECRET', 'TJKT2-ABSENSI-SECRET-2026-GANTI-JIKA-MAU');
define('QR_EXPIRE', 600);

/*
|--------------------------------------------------------------------------
| Membuat token QR
|--------------------------------------------------------------------------
*/
function buat_token_qr()
{
    $timestamp = time();

    $signature = hash_hmac(
        'sha256',
        (string) $timestamp,
        QR_SECRET
    );

    return $timestamp . '.' . $signature;
}

/*
|--------------------------------------------------------------------------
| Validasi token QR
|--------------------------------------------------------------------------
*/
function validasi_token_qr($token)
{
    if (empty($token)) {
        return false;
    }

    $parts = explode('.', $token, 2);

    if (count($parts) !== 2) {
        return false;
    }

    $timestamp = $parts[0];
    $signature = $parts[1];

    if (!ctype_digit($timestamp)) {
        return false;
    }

    $timestamp = (int) $timestamp;

    /*
     * QR dianggap tidak berlaku jika
     * sudah lebih dari 10 menit.
     */
    $umur = time() - $timestamp;

    if ($umur < 0) {
        return false;
    }

    if ($umur > QR_EXPIRE) {
        return false;
    }

    /*
     * Buat signature yang seharusnya.
     */
    $expected = hash_hmac(
        'sha256',
        (string) $timestamp,
        QR_SECRET
    );

    /*
     * Cocokkan signature.
     */
    return hash_equals(
        $expected,
        $signature
    );
}