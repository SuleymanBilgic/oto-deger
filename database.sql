-- PHPMyAdmin üzerinden içe aktarabilir (import) veya SQL sekmesinde çalıştırabilirsiniz.

CREATE DATABASE IF NOT EXISTS oto_deger_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE oto_deger_db;

-- 1. User (Sistemi kullanan kişi)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Vehicle (Kullanıcının fiyatını öğrenmek istediği araç)
CREATE TABLE IF NOT EXISTS vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    brand VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    production_year INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 3. VehicleSpecification (Aracın teknik özellikleri)
CREATE TABLE IF NOT EXISTS vehicle_specifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    fuel_type VARCHAR(30), -- yakıt tipi (Benzin, Dizel, LPG vb.)
    transmission VARCHAR(30), -- vites (Manuel, Otomatik vb.)
    body_type VARCHAR(30), -- kasa tipi (Sedan, Hatchback vb.)
    engine_capacity VARCHAR(20), -- motor hacmi (1.6, 1.4 vb.)
    traction_type VARCHAR(30), -- çekiş tipi (Önden, Arkadan, 4x4)
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
);

-- 4. VehicleCondition (Aracın genel durumu)
CREATE TABLE IF NOT EXISTS vehicle_conditions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    mileage INT NOT NULL, -- kilometre
    damage_record DECIMAL(10,2) DEFAULT 0, -- tramer/hasar kaydı (TL cinsinden)
    replaced_parts_count INT DEFAULT 0, -- değişen parça sayısı
    painted_parts_count INT DEFAULT 0, -- boyalı parça sayısı
    expertise_status TEXT, -- ekspertiz durumu (Özet metin)
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
);

-- 5. PartStatus (Tek tek parçaların durumu)
CREATE TABLE IF NOT EXISTS part_statuses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    part_name VARCHAR(50) NOT NULL, -- kaput, sol çamurluk, tavan vb.
    status VARCHAR(50) NOT NULL, -- orijinal, boyalı, değişen vb.
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
);

-- 6. MarketListing (İnternetten veya veri setinden alınan benzer araç ilanları)
CREATE TABLE IF NOT EXISTS market_listings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT, -- Hangi araç için emsal teşkil ediyorsa o araca referans
    listing_price DECIMAL(12,2) NOT NULL, -- ilan fiyatı
    city VARCHAR(50), -- şehir
    listing_date DATE, -- ilan tarihi
    source_site VARCHAR(100), -- kaynak site (sahibinden, arabam vb.)
    seller_type VARCHAR(50), -- satıcı tipi (Galeriden, Sahibinden)
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL
);

-- 7. PricePrediction (Sistemin ürettiği fiyat tahmini)
CREATE TABLE IF NOT EXISTS price_predictions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    average_price DECIMAL(12,2) NOT NULL, -- ortalama fiyat
    min_price DECIMAL(12,2) NOT NULL, -- minimum fiyat
    max_price DECIMAL(12,2) NOT NULL, -- maksimum fiyat
    used_listing_count INT, -- kullanılan ilan sayısı
    prediction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- tahmin tarihi
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
);
