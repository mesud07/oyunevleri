<?php
// Hataları görelim
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- AYARLAR ---
// Sunucu IP adresi:
$host       = '89.252.183.194'; 
$port       = 3306;
$db_name    = 'oyunev_master';
$username   = 'oyunev_mesud';
// Şifreni buraya yaz (özel karakterler olduğu için tek tırnak içinde kalsın)
$password   = 'Balkanlar07.'; 

echo "<h1>Detaylı Veritabanı Bağlantı Testi</h1>";
echo "<b>Hedef Sunucu:</b> $host <br>";
echo "<b>Hedef Port:</b> $port <br>";
echo "<b>Kullanıcı:</b> $username <br><hr>";

try {
    // ADIM 1: PORT KONTROLÜ (Firewall Testi)
    echo "<h3>Adım 1: Ağ/Port Kontrolü Yapılıyor...</h3>";
    $connection = @fsockopen($host, $port, $errno, $errstr, 5);

    if (is_resource($connection)) {
        echo "<span style='color:green; font-weight:bold;'>✅ BAŞARILI:</span> Sunucunun 3306 portu açık. Firewall engeli yok.<br>";
        fclose($connection);
    } else {
        throw new Exception("AĞ HATASI: Sunucu portuna erişilemedi. (Hata: $errstr - Kod: $errno). Hosting Firewall engeli var.");
    }

    echo "<hr>";

    // ADIM 2: PDO İLE GİRİŞ (Şifre/Yetki Testi)
    echo "<h3>Adım 2: Kullanıcı Girişi Deneniyor...</h3>";
    
    $dsn = "mysql:host=$host;port=$port;dbname=$db_name;charset=utf8";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h2 style='color:green;'>🎉 TEBRİKLER! BAĞLANTI TAMAMEN BAŞARILI!</h2>";
    echo "Şu an veritabanının içindeyiz. Tablo sorgusu bile yapabilirsin.";

} catch (PDOException $e) {
    // PDO (Veritabanı) Hataları
    echo "<h2 style='color:red;'>❌ BAĞLANTI REDDEDİLDİ</h2>";
    echo "<b>Hata Mesajı:</b> " . $e->getMessage() . "<br><br>";
    
    // Hata Analizi
    if (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "<b>TANI:</b> Port açık ama <u>Kullanıcı Adı</u> veya <u>Şifre</u> yanlış.";
    } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
        echo "<b>TANI:</b> Giriş yapıldı ama <u>Veritabanı Adı</u> ($db_name) bulunamadı.";
    } elseif (strpos($e->getMessage(), '2002') !== false) {
        echo "<b>TANI:</b> Sunucu bağlantıyı reddetti. IP adresi yanlış olabilir veya veritabanı bu IP'den (Shared IP) dinlemiyor.";
    }

} catch (Exception $e) {
    // Genel Hatalar (Port hatası buraya düşer)
    echo "<h2 style='color:red;'>❌ ERİŞİM ENGELİ</h2>";
    echo "<b>Hata:</b> " . $e->getMessage() . "<br><br>";
    echo "<b>ÇÖZÜM:</b> Bu aşamada hata alıyorsanız, Hosting firmasına (Güzel Hosting) ticket açıp IP adresinizi bildirmeniz şarttır.";
}
?>

<?php
// Container'ın dış dünyaya hangi IP ile çıktığını öğrenelim
$ip = file_get_contents('http://ipecho.net/plain');
echo "<h1>Container Dış IP Adresi:</h1>";
echo "<h2 style='color:red;'>" . $ip . "</h2>";

echo "<br><strong>Kontrol:</strong> Bu IP adresi, cPanel 'Remote MySQL' listesindeki IP ile AYNI MI?";
?>
