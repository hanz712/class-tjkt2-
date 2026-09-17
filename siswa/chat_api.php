<?php
require_once "../config/auth.php";
require_once "../config/database.php";
wajib_login(['siswa']);
header('Content-Type: application/json; charset=utf-8');

$user_id=(int)($_SESSION['user_id']??0);
$nama=$_SESSION['nama']??'Siswa';
$method=$_SERVER['REQUEST_METHOD'];

if ($method==='POST') {
    $pesan=trim($_POST['pesan']??'');
    if ($pesan==='') { echo json_encode(['ok'=>false,'message'=>'Pesan tidak boleh kosong.']); exit; }
    if (mb_strlen($pesan)>500) { echo json_encode(['ok'=>false,'message'=>'Pesan maksimal 500 karakter.']); exit; }
    $pesan=preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u','',$pesan);
    $stmt=mysqli_prepare($conn,"INSERT INTO chat_kelas (user_id,nama_pengirim,pesan) VALUES (?,?,?)");
    mysqli_stmt_bind_param($stmt,'iss',$user_id,$nama,$pesan);
    $ok=mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
    echo json_encode(['ok'=>$ok,'message'=>$ok?'Pesan terkirim.':'Gagal mengirim pesan.']); exit;
}

$limit=min(50,max(1,(int)($_GET['limit']??20)));
$stmt=mysqli_prepare($conn,"SELECT id,user_id,nama_pengirim,pesan,DATE_FORMAT(created_at,'%H:%i') waktu,DATE_FORMAT(created_at,'%d/%m/%Y') tanggal FROM chat_kelas ORDER BY id DESC LIMIT ?");
mysqli_stmt_bind_param($stmt,'i',$limit); mysqli_stmt_execute($stmt); $res=mysqli_stmt_get_result($stmt); $rows=[];
while($r=mysqli_fetch_assoc($res)) $rows[]=$r;
mysqli_stmt_close($stmt); $rows=array_reverse($rows);
echo json_encode(['ok'=>true,'messages'=>$rows],JSON_UNESCAPED_UNICODE);
