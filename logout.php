<?php
session_start();

// Oturum verilerini temizle
$_SESSION = array();

// Oturumu yok et
session_destroy();

// Giriş sayfasına yönlendir
header("Location: login.php");
exit;
?>