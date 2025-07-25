-- DATABASE: db_kost
CREATE DATABASE IF NOT EXISTS db_kost;
USE db_kost;

-- Tabel Penghuni Kost
drop table if exists penghuni;
CREATE TABLE penghuni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    no_ktp VARCHAR(30) NOT NULL,
    no_hp VARCHAR(20) NOT NULL,
    tgl_masuk DATE NOT NULL,
    tgl_keluar DATE DEFAULT NULL
);

-- Tabel Kamar Kost
drop table if exists kamar;
CREATE TABLE kamar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nomor VARCHAR(10) NOT NULL,
    harga INT NOT NULL,
    gambar VARCHAR(255) DEFAULT NULL
);

-- Tabel Barang
drop table if exists barang;
CREATE TABLE barang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    harga INT NOT NULL
);

-- Tabel Kamar Penghuni
drop table if exists kmr_penghuni;
CREATE TABLE kmr_penghuni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_kamar INT NOT NULL,
    id_penghuni INT NOT NULL,
    tgl_masuk DATE NOT NULL,
    tgl_keluar DATE DEFAULT NULL,
    FOREIGN KEY (id_kamar) REFERENCES kamar(id),
    FOREIGN KEY (id_penghuni) REFERENCES penghuni(id)
);

-- Tabel Barang Bawaan Penghuni
drop table if exists brng_bawaan;
CREATE TABLE brng_bawaan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_penghuni INT NOT NULL,
    id_barang INT NOT NULL,
    FOREIGN KEY (id_penghuni) REFERENCES penghuni(id),
    FOREIGN KEY (id_barang) REFERENCES barang(id)
);

-- Tabel Tagihan
drop table if exists tagihan;
CREATE TABLE tagihan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bulan DATE NOT NULL,
    id_kmr_penghuni INT NOT NULL,
    jml_tagihan INT NOT NULL,
    FOREIGN KEY (id_kmr_penghuni) REFERENCES kmr_penghuni(id)
);

-- Tabel Pembayaran
drop table if exists bayar;
CREATE TABLE bayar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_tagihan INT NOT NULL,
    jml_bayar INT NOT NULL,
    status ENUM('lunas','cicil') NOT NULL,
    FOREIGN KEY (id_tagihan) REFERENCES tagihan(id)
);

-- Tabel User
drop table if exists user;
CREATE TABLE user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    id_kamar INT DEFAULT NULL,
    role ENUM('admin','user') NOT NULL DEFAULT 'user'
);

-- Data Kamar
INSERT INTO kamar (nomor, harga, gambar) VALUES
('A1', 700000, NULL),
('A2', 750000, NULL),
('B1', 800000, NULL),
('B2', 850000, NULL);

-- Data Barang
INSERT INTO barang (nama, harga) VALUES
('Kipas Angin', 25000),
('Dispenser', 30000),
('Lemari', 40000),
('Meja Belajar', 20000);

-- Data Penghuni
INSERT INTO penghuni (nama, no_ktp, no_hp, tgl_masuk, tgl_keluar) VALUES
('Andi Saputra', '3201012345678901', '081234567890', '2024-07-01', NULL),
('Budi Santoso', '3201012345678902', '081234567891', '2024-07-05', NULL),
('Citra Dewi', '3201012345678903', '081234567892', '2024-07-10', NULL);

-- Data Kamar Penghuni
INSERT INTO kmr_penghuni (id_kamar, id_penghuni, tgl_masuk, tgl_keluar) VALUES
(1, 1, '2024-07-01', NULL),
(2, 2, '2024-07-05', NULL),
(3, 3, '2024-07-10', NULL);

-- Data Barang Bawaan
INSERT INTO brng_bawaan (id_penghuni, id_barang) VALUES
(1, 1),
(1, 2),
(2, 3),
(3, 4);

-- Data Tagihan
INSERT INTO tagihan (bulan, id_kmr_penghuni, jml_tagihan) VALUES
('2024-07-01', 1, 700000+25000+30000),
('2024-07-01', 2, 750000+40000),
('2024-07-01', 3, 800000+20000);

-- Data Pembayaran
INSERT INTO bayar (id_tagihan, jml_bayar, status) VALUES
(1, 755000, 'lunas'),
(2, 40000, 'cicil'),
(3, 820000, 'lunas');

-- Data User (hash password ganti sesuai hasil password_hash di servermu)
INSERT INTO user (username, password, nama, role) VALUES
('admin', '$2y$10$eImiTXuWVxfM37uY4JANjQwQeQ5Q1rQ1rQ1rQ1rQ1', 'Administrator', 'admin');
