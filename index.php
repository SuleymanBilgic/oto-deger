<?php
session_start();
require_once 'db.php';

// Kullanıcı giriş yapmamışsa login sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$hesaplanan_fiyat = 0;
$hata = "";

// Form gönderilmişse fiyatı hesapla
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $marka = $_POST['marka'] ?? '';
    $model = $_POST['model'] ?? '';
    $yil = intval($_POST['yil'] ?? 0);
    $kilometre = intval($_POST['kilometre'] ?? 0);
    $degisen = intval($_POST['degisen'] ?? 0);
    $boyali = intval($_POST['boyali'] ?? 0);
    $yakit_tipi = $_POST['yakit_tipi'] ?? '';
    $vites = $_POST['vites'] ?? '';
    $kasa_tipi = $_POST['kasa_tipi'] ?? '';
    $motor_hacmi = $_POST['motor_hacmi'] ?? '';

    if (empty($marka) || empty($model) || empty($yakit_tipi) || empty($vites) || empty($kasa_tipi) || empty($motor_hacmi) || $yil < 1950 || $yil > date("Y")) {
        $hata = "Lütfen geçerli araç bilgileri giriniz.";
    } else {
        // 1. Veritabanından (SQL) emsal araç fiyatı çekme işlemi
        $stmt = $db->prepare("
            SELECT p.average_price, v.production_year 
            FROM vehicles v 
            JOIN price_predictions p ON v.id = p.vehicle_id 
            WHERE LOWER(v.brand) = LOWER(?) AND LOWER(v.model) = LOWER(?)
            ORDER BY ABS(v.production_year - ?) ASC 
            LIMIT 1
        ");
        $stmt->execute([trim($marka), trim($model), $yil]);
        $emsal = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($emsal) {
            // Emsal bulundu! (SQL'deki sample data)
            $taban_fiyat = floatval($emsal['average_price']);
            
            // Eğer emsal aracın yılı farklıysa, aradaki her yıl için %5 değer farkı uygula
            $yil_farki = $yil - $emsal['production_year'];
            $taban_fiyat = $taban_fiyat + ($taban_fiyat * ($yil_farki * 0.05));
        } else {
            // SQL'de emsal yoksa, piyasa gerçeklerine daha uygun yüksek bir taban fiyat (örn: 1.200.000 TL)
            $taban_fiyat = 1200000;
            $yas = date("Y") - $yil;
            $taban_fiyat -= ($yas * 40000); // Her yaş için 40.000 TL düş
        }

        // 2. Kilometre indirimi: Her 10.000 km için %1 değer kaybı
        $km_indirimi_orani = floor($kilometre / 10000) * 0.01;
        $km_indirimi = $taban_fiyat * $km_indirimi_orani;

        // 3. Hasar/Boya indirimi
        $degisen_indirimi = $degisen * ($taban_fiyat * 0.04); // Her değişen parça %4 değer düşürür
        $boya_indirimi = $boyali * ($taban_fiyat * 0.015);    // Her boyalı parça %1.5 değer düşürür

        // 4. Ekstra özellik fiyatlandırması
        $vites_farki = ($vites === 'Otomatik' || $vites === 'Yarı Otomatik') ? ($taban_fiyat * 0.05) : 0;
        $kasa_farki = ($kasa_tipi === 'SUV') ? ($taban_fiyat * 0.05) : 0;
        $yakit_farki = ($yakit_tipi === 'Elektrik') ? ($taban_fiyat * 0.10) : 0; // Elektrikli araçlar +%10

        // Toplam hesaplama
        $hesaplanan_fiyat = $taban_fiyat - $km_indirimi - $degisen_indirimi - $boya_indirimi + $vites_farki + $kasa_farki + $yakit_farki;

        // Fiyat çok düşerse alt sınır
        if ($hesaplanan_fiyat < 200000) {
            $hesaplanan_fiyat = 200000;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oto Değer - Araç Değerleme Formu</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container" style="max-width: 600px;">
        <div style="text-align: right; margin-bottom: 10px;">
            <span>Hoş geldin, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!</span>
            <a href="logout.php" class="btn btn-danger"
                style="display: inline-block; width: auto; padding: 5px 10px; margin-left: 10px;">Çıkış Yap</a>
        </div>

        <h1>Oto Değer Hesaplama</h1>
        <p style="text-align: center; margin-bottom: 20px;">Aracınızın tahmini değerini öğrenmek için formu doldurun.
        </p>

        <?php if ($hata): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($hata); ?></div>
        <?php endif; ?>

        <form method="POST" action="index.php" id="valuationForm">
            <div class="form-group">
                <label for="marka">Araç Markası (örn: Renault, Ford, Fiat)</label>
                <input type="text" id="marka" name="marka" required>
            </div>

            <div class="form-group">
                <label for="model">Araç Modeli (örn: Clio, Focus, Egea)</label>
                <input type="text" id="model" name="model" required>
            </div>

            <div class="form-group">
                <label for="yakit_tipi">Yakıt Tipi</label>
                <select id="yakit_tipi" name="yakit_tipi" required>
                    <option value="">Seçiniz</option>
                    <option value="Benzin">Benzin</option>
                    <option value="Dizel">Dizel</option>
                    <option value="LPG">LPG / Benzin</option>
                    <option value="Elektrik">Elektrik</option>
                    <option value="Hibrit">Hibrit</option>
                </select>
            </div>

            <div class="form-group">
                <label for="vites">Vites Tipi</label>
                <select id="vites" name="vites" required>
                    <option value="">Seçiniz</option>
                    <option value="Manuel">Manuel</option>
                    <option value="Otomatik">Otomatik</option>
                    <option value="Yarı Otomatik">Yarı Otomatik</option>
                </select>
            </div>

            <div class="form-group">
                <label for="kasa_tipi">Kasa Tipi</label>
                <select id="kasa_tipi" name="kasa_tipi" required>
                    <option value="">Seçiniz</option>
                    <option value="Sedan">Sedan</option>
                    <option value="Hatchback">Hatchback</option>
                    <option value="SUV">SUV</option>
                    <option value="Station Wagon">Station Wagon</option>
                </select>
            </div>

            <div class="form-group">
                <label for="motor_hacmi">Motor Hacmi (Örn: 1.4, 1.6)</label>
                <input type="text" id="motor_hacmi" name="motor_hacmi" required>
            </div>

            <div class="form-group">
                <label for="yil">Üretim Yılı</label>
                <input type="number" id="yil" name="yil" min="1950" max="<?php echo date('Y'); ?>" required>
            </div>

            <div class="form-group">
                <label for="kilometre">Kilometresi (örn: 120000)</label>
                <input type="number" id="kilometre" name="kilometre" min="0" required>
            </div>

            <div class="form-group">
                <label for="degisen">Değişen Parça Sayısı</label>
                <select id="degisen" name="degisen">
                    <option value="0">Yok</option>
                    <option value="1">1 Parça</option>
                    <option value="2">2 Parça</option>
                    <option value="3">3 Parça</option>
                    <option value="4">4+ Parça</option>
                </select>
            </div>

            <div class="form-group">
                <label for="boyali">Boyalı Parça Sayısı</label>
                <select id="boyali" name="boyali">
                    <option value="0">Yok</option>
                    <option value="1">1 Parça</option>
                    <option value="2">2 Parça</option>
                    <option value="3">3 Parça</option>
                    <option value="4">4+ Parça</option>
                    <option value="5">Komple Boyalı</option>
                </select>
            </div>

            <button type="submit" class="btn">Değerini Hesapla</button>
        </form>

        <?php if ($hesaplanan_fiyat > 0): ?>
            <div class="result-box">
                <h3>Tahmini Araç Değeri</h3>
                <p><?php echo htmlspecialchars($marka . ' ' . $model . ' (' . $yil . ')'); ?> için tahmini fiyat:</p>
                <div class="price"><?php echo number_format($hesaplanan_fiyat, 0, ',', '.'); ?> TL</div>
            </div>
        <?php endif; ?>
    </div>

    <script src="script.js"></script>
</body>

</html>