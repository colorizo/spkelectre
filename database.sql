CREATE DATABASE IF NOT EXISTS db_spk_electre;
USE db_spk_electre;

-- Tabel untuk menyimpan sesi perhitungan
CREATE TABLE IF NOT EXISTS perhitungan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATETIME DEFAULT CURRENT_TIMESTAMP,
    user_id INT,
    lokasi VARCHAR(100) NOT NULL,
    keterangan TEXT
);

-- Tabel untuk menyimpan nilai matriks keputusan
CREATE TABLE IF NOT EXISTS matriks_keputusan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    perhitungan_id INT,
    alternatif VARCHAR(50),
    c1 FLOAT,
    c2 FLOAT,
    c3 FLOAT,
    c4 FLOAT,
    c5 FLOAT,
    FOREIGN KEY (perhitungan_id) REFERENCES perhitungan(id)
);

-- Tabel untuk menyimpan bobot kriteria
CREATE TABLE IF NOT EXISTS bobot_kriteria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    perhitungan_id INT,
    w1 FLOAT,
    w2 FLOAT,
    w3 FLOAT,
    w4 FLOAT,
    w5 FLOAT,
    FOREIGN KEY (perhitungan_id) REFERENCES perhitungan(id)
);

-- Tabel untuk menyimpan hasil perhitungan
CREATE TABLE IF NOT EXISTS hasil_perhitungan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    perhitungan_id INT,
    alternatif VARCHAR(50),
    nilai_akhir FLOAT,
    peringkat INT,
    FOREIGN KEY (perhitungan_id) REFERENCES perhitungan(id)
); 