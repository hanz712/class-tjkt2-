<?php
require_once "../config/auth.php";
require_once "../config/database.php";
wajib_login(['siswa']);

$user_id = (int)($_SESSION['user_id'] ?? 0);
$data_dir  = __DIR__ . "/../data";
$data_file = $data_dir . "/pengumuman.json";
$data = [];
if (file_exists($data_file)) {
    $data = json_decode(file_get_contents($data_file), true);
    if (!is_array($data)) $data = [];
}

// Tandai satu pengumuman sebagai sudah dibaca.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['read_id'])) {
    $read_id = trim($_POST['read_id']);
    if ($read_id !== '') {
        $stmt = mysqli_prepare($conn, "INSERT IGNORE INTO announcement_reads (user_id, announcement_id) VALUES (?, ?)");
        if ($stmt) { mysqli_stmt_bind_param($stmt, "is", $user_id, $read_id); mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt); }
    }
    header('Location: pengumuman.php'); exit;
}

$read_ids = [];
$stmt = mysqli_prepare($conn, "SELECT announcement_id FROM announcement_reads WHERE user_id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id); mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt); while ($r = mysqli_fetch_assoc($res)) $read_ids[(string)$r['announcement_id']] = true;
    mysqli_stmt_close($stmt);
}
$data = array_reverse($data);
$unread = 0; foreach ($data as $item) if (!isset($read_ids[(string)($item['id'] ?? '')])) $unread++;
?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Inbox Pengumuman | XI TJKT 2</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"><style>
*{box-sizing:border-box}body{margin:0;background:#f4f7fb;color:#172033;font-family:Inter,"Segoe UI",Arial,sans-serif}.topbar{height:76px;background:#fff;border-bottom:1px solid #e8edf5;display:flex;align-items:center;padding:0 34px}.brand{display:flex;align-items:center;gap:13px}.brand-logo{width:45px;height:45px;border-radius:13px;object-fit:cover}.brand-title{font-size:17px;font-weight:800;margin:0}.brand-subtitle{font-size:12px;color:#8791a3}.page{max-width:900px;margin:auto;padding:30px 34px 50px}.back{color:#687386;text-decoration:none;font-size:13px;display:inline-block;margin-bottom:18px}.hero{background:#172033;color:#fff;border-radius:24px;padding:27px 30px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;gap:15px}.hero h1{font-size:25px;font-weight:800;margin:0 0 5px}.hero p{margin:0;color:#bbc4d3;font-size:13px}.badge-count{background:#fff;color:#172033;padding:8px 12px;border-radius:12px;font-size:12px;font-weight:800}.announcement{background:#fff;border:1px solid #e8edf5;border-radius:18px;padding:20px;margin-bottom:14px}.announcement.unread{border-left:4px solid #172033}.title-row{display:flex;justify-content:space-between;gap:15px}.announcement h4{font-size:16px;font-weight:800;margin:0 0 6px}.date{color:#8791a3;font-size:11px}.isi{white-space:pre-line;line-height:1.7;margin-top:14px;color:#374151;font-size:13px}.empty{background:#fff;border:1px solid #e8edf5;border-radius:18px;padding:55px 20px;text-align:center;color:#8791a3}.read-badge{font-size:10px;padding:5px 8px;border-radius:8px;background:#edf0f4;color:#6b7280}.new-badge{font-size:10px;padding:5px 8px;border-radius:8px;background:#172033;color:#fff}.read-btn{margin-top:12px;border:1px solid #dce2ea;background:#fff;border-radius:9px;padding:8px 12px;font-size:11px;font-weight:700;color:#334155}@media(max-width:600px){.topbar{padding:0 20px}.page{padding:20px}.hero{padding:23px}.title-row{display:block}.title-row .badge{display:inline-block;margin-top:8px}}
</style></head><body><header class="topbar"><div class="brand"><img src="../assets/images/logo.png" class="brand-logo" alt="Logo"><div><p class="brand-title">XI TJKT 2</p><div class="brand-subtitle">Inbox Pengumuman</div></div></div></header><main class="page"><a class="back" href="index.php"><i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard</a><section class="hero"><div><h1><i class="fa-solid fa-bell"></i> Pengumuman</h1><p>Informasi terbaru dari Wali Kelas.</p></div><div class="badge-count"><?=$unread?> belum dibaca</div></section><?php if(empty($data)): ?><div class="empty"><div style="font-size:45px">📭</div><h5 class="mt-3">Belum ada pengumuman</h5><p class="mb-0">Pengumuman dari wali kelas akan muncul di sini.</p></div><?php else: foreach($data as $item): $id=(string)($item['id']??''); $is_read=isset($read_ids[$id]); ?><article class="announcement <?=$is_read?'':'unread'?>"><div class="title-row"><div><h4><?=htmlspecialchars($item['judul']??'Tanpa judul')?></h4><div class="date"><i class="fa-regular fa-clock"></i> <?=htmlspecialchars($item['tanggal']??'')?></div></div><div><?= $is_read ? '<span class="read-badge">Sudah dibaca</span>' : '<span class="new-badge">Baru</span>' ?></div></div><div class="isi"><?=htmlspecialchars($item['isi']??'')?></div><?php if(!$is_read): ?><form method="post"><input type="hidden" name="read_id" value="<?=htmlspecialchars($id)?>"><button class="read-btn" type="submit"><i class="fa-solid fa-check"></i> Tandai sudah dibaca</button></form><?php endif; ?></article><?php endforeach; endif; ?></main></body></html>
