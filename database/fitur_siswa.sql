-- =========================================================
-- FITUR TAMBAHAN SISWA XI TJKT 2
-- Jalankan file ini SEKALI di database yang sudah dipakai project.
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
