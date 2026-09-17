CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(30) NOT NULL DEFAULT 'wali_kelas',
    profile_photo VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS siswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nis VARCHAR(30) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    jenis_kelamin ENUM('L','P') NOT NULL,
    qr_code VARCHAR(150) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS jadwal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hari VARCHAR(20) NOT NULL,
    jam_ke INT NOT NULL,
    mata_pelajaran VARCHAR(100) NOT NULL,
    guru VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS piket (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hari VARCHAR(20) NOT NULL,
    siswa_id INT NOT NULL,
    UNIQUE KEY unique_piket_harian (hari, siswa_id),
    FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS absensi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id INT NOT NULL,
    tanggal DATE NOT NULL,
    waktu TIME NOT NULL,
    status ENUM('Hadir','Sakit','Izin','Alpa') NOT NULL DEFAULT 'Hadir',
    metode VARCHAR(30) DEFAULT 'QR',
    UNIQUE KEY unique_absensi_harian (siswa_id, tanggal),
    FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS tugas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(150) NOT NULL,
    guru VARCHAR(100),
    deskripsi TEXT,
    deadline DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS galeri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(150),
    gambar VARCHAR(255),
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS pengaturan (id INT AUTO_INCREMENT PRIMARY KEY,nama_kelas VARCHAR(100) NOT NULL DEFAULT 'XI TJKT 2',tahun_pelajaran VARCHAR(30) NOT NULL DEFAULT '2026 / 2027',nama_sekolah VARCHAR(150) NOT NULL DEFAULT 'SMK PGRI Subang',logo VARCHAR(255) DEFAULT NULL,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP);
INSERT INTO pengaturan (id,nama_kelas,tahun_pelajaran,nama_sekolah) VALUES (1,'XI TJKT 2','2026 / 2027','SMK PGRI Subang') ON DUPLICATE KEY UPDATE id=id;

-- =========================================================
-- FITUR TAMBAHAN SISWA
-- =========================================================
CREATE TABLE IF NOT EXISTS announcement_reads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    announcement_id VARCHAR(100) NOT NULL,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_announcement_read (user_id, announcement_id),
    INDEX idx_announcement_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS chat_kelas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nama_pengirim VARCHAR(100) NOT NULL,
    pesan VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_chat_created (created_at),
    INDEX idx_chat_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
