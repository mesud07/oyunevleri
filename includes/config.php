<?php
session_start();

// sirasi farkediyor...
date_default_timezone_set("Europe/Istanbul");
setlocale(LC_ALL, 'tr_TR.UTF-8');
setlocale(LC_TIME, 'turkish');

// master ve kurum db ayarlari
$_SESSION['ana_db'] = 'oyunev_master';
if (empty($_SESSION['kurum_db'])) {
    $_SESSION['kurum_db'] = 'oyunev_kurum';
}
$db_kullanici = 'oyunev_mesud';
$db_sifre = 'Balkanlar07.';
$db_port = '3306';
$db_connect_timeout = 30;
$pdo_options = [
    PDO::ATTR_TIMEOUT => $db_connect_timeout,
];
$remote_db_host = '89.252.183.194';
$local_db_host = '127.0.0.1';
$db_host = $remote_db_host;

// baglanti degiskenleri (varsayilan null)
$db_master = null;
$db = null;

try {
    $db_master = new PDO(
            "mysql:host={$db_host};port={$db_port};dbname={$_SESSION['ana_db']};charset=utf8;connect_timeout={$db_connect_timeout}",
            $db_kullanici,
            $db_sifre,
            $pdo_options
        );
    $db_master->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // asagidakiler olmazsa db de GUSIO C soru isareti olarak geliyor
    $db_master->exec("SET NAMES 'utf8'");
    $db_master->exec("SET CHARACTER SET utf8");
    $db_master->exec("SET CHARACTER_SET_CONNECTION=utf8");
    $db_master->exec("SET NAMES utf8mb4 COLLATE utf8mb4_turkish_ci");
    $db_master->exec("SET SQL_MODE = ''");

    // kurum db baglantisi (login sonrasi session dolu olur)
    if (!empty($_SESSION['kurum_db'])) {
        $db = new PDO(
            "mysql:host={$db_host};port={$db_port};dbname={$_SESSION['kurum_db']};charset=utf8;connect_timeout={$db_connect_timeout}",
            $db_kullanici,
            $db_sifre,
            $pdo_options
        );
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        // asagidakiler olmazsa db de GUSIO C soru isareti olarak geliyor
        $db->exec("SET NAMES 'utf8'");
        $db->exec("SET CHARACTER SET utf8");
        $db->exec("SET CHARACTER_SET_CONNECTION=utf8");
        $db->exec("SET NAMES utf8mb4 COLLATE utf8mb4_turkish_ci");
        $db->exec("SET SQL_MODE = ''");
    }
} catch (PDOException $e) {
    $_SESSION['db_hata'] = $e->getMessage();
    error_log('DB Hata: ' . $e->getMessage());
}

$baseFolder = './';
$ajax = 'ajax';

// Google OAuth (Veli Gmail girisi icin)
$google_client_id = $_ENV['GOOGLE_CLIENT_ID'] ?? 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com';

// Mail (PHPMailer SMTP ayarlari)
$mail_host = $_ENV['MAIL_HOST'] ?? 'mail.oyunevleri.com';
$mail_port = (int)($_ENV['MAIL_PORT'] ?? 587);
$mail_user = $_ENV['MAIL_USER'] ?? 'info@oyunevleri.com';
$mail_pass = $_ENV['MAIL_PASS'] ?? 'Balkanlar07.';
$mail_secure = $_ENV['MAIL_SECURE'] ?? 'tls'; // tls veya ssl
$mail_from = $_ENV['MAIL_FROM'] ?? 'info@oyunevleri.com';
$mail_from_name = $_ENV['MAIL_FROM_NAME'] ?? 'Oyunevleri';
$mail_allow_insecure = filter_var($_ENV['MAIL_ALLOW_INSECURE'] ?? '1', FILTER_VALIDATE_BOOLEAN);

// Upload ayarlari (galeri vb.)
$doc_root = $_SERVER['DOCUMENT_ROOT'] ?? '';
$project_root = dirname(__DIR__);
$home_dir = $_SERVER['HOME'] ?? getenv('HOME') ?? '';
$home_public_upload = $home_dir !== '' ? ($home_dir . '/public_html/uploads') : '';
$public_html_upload = $project_root . '/public_html/uploads';
if ($home_public_upload !== '' && is_dir($home_public_upload)) {
    $default_upload_dir = $home_public_upload;
} elseif (is_dir($public_html_upload)) {
    $default_upload_dir = $public_html_upload;
} elseif ($doc_root !== '') {
    $default_upload_dir = $doc_root . '/uploads';
} else {
    $default_upload_dir = $project_root . '/uploads';
}
$upload_base_dir = rtrim($_ENV['UPLOAD_BASE_DIR'] ?? $default_upload_dir, '/');
if ($upload_base_dir !== '' && $upload_base_dir[0] !== '/') {
    $upload_base_dir = $project_root . '/' . ltrim($upload_base_dir, '/');
}
$upload_base_url = rtrim($_ENV['UPLOAD_BASE_URL'] ?? '/uploads', '/');

// Galeri upload yolu (kurum galerisi icin ozel)
$doc_root_clean = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$galeri_upload_dir = $doc_root_clean !== '' ? ($doc_root_clean . '/uploads/kurum_galeri') : ($upload_base_dir . '/kurum_galeri');
$galeri_upload_url = '/uploads/kurum_galeri';

#################################### START - SVG ICONS ####################################
$list_svg = '<svg xmlns="http://www.w3.org/2000/svg" 
                    width="24" height="24" viewBox="0 0 24 24" fill="none" 
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" 
                    stroke-linejoin="round" class="feather feather-list">
                    <line x1="8" y1="6" x2="21" y2="6"></line>
                    <line x1="8" y1="12" x2="21" y2="12"></line>
                    <line x1="8" y1="18" x2="21" y2="18"></line>
                    <line x1="3" y1="6" x2="3" y2="6"></line>
                    <line x1="3" y1="12" x2="3" y2="12"></line>
                    <line x1="3" y1="18" x2="3" y2="18"></line>
               </svg>';
$edit_svg = '<svg xmlns="http://www.w3.org/2000/svg"
                    width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" class="feather feather-edit-3">
                    <path d="M12 20h9"></path>
                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z">
                    </path>
               </svg>';
$delete_svg = '<svg xmlns="http://www.w3.org/2000/svg"
                    width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" class="feather feather-trash">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2">
                    </path>
               </svg>';
$close_svg ='<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" class="feather feather-x">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
               </svg>';
$back_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" 
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                    class="feather feather-corner-up-left"><polyline points="9 14 4 9 9 4"></polyline>
                    <path d="M20 20v-7a4 4 0 0 0-4-4H4"></path>
               </svg>';
$doc_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" 
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                    class="feather feather-file-text"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
               </svg>';
$open_new_tab = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" 
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                    class="feather feather-external-link"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                    <polyline points="15 3 21 3 21 9"></polyline>
                    <line x1="10" y1="14" x2="21" y2="3"></line>
               </svg>';
#################################### END - SVG ICONS ####################################
?>
