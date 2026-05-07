<?php
// Veritabanı bağlantı ayarları
$host = 'localhost';
$dbname = 'oto_deger_db';
$username = 'root'; // XAMPP/WAMP varsayılan kullanıcı adı
$password = ''; // XAMPP/WAMP varsayılan şifre (boş)

try {
    // PDO ile bağlantı oluşturulması
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);

    // Hata modunu exception olarak ayarla
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
?>