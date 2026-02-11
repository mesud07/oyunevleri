<?php
session_start();

$db = new PDO("mysql:host=89.252.183.194;port=3306;dbname=oyunev_master;charset=utf8", "oyunev_mesud", "Balkanlar07.");
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
// $db->query("SET CHARACTER SET utf8"); aşağıdakiler olmazda db de ĞÜŞİÖÇ soru işareti olarak geliyor
$db->exec("SET NAMES 'utf8'");
$db->exec("SET CHARACTER SET utf8");
$db->exec("SET CHARACTER_SET_CONNECTION=utf8");
$db->exec("SET SQL_MODE = ''");
/*try {
	
} catch ( PDOException $e ){
     print $e->getMessage();
}
*/

// sırası farkediyor...
setlocale(LC_ALL, 'tr_TR.UTF-8');
setlocale(LC_TIME, 'turkish');

$baseFolder = './';
$ajax = 'ajax';
$ajax_uedts = 'ajax_uedts';

// Mail (PHPMailer SMTP ayarlari)
$mail_host = $_ENV['MAIL_HOST'] ?? 'mail.oyunevleri.com';
$mail_port = (int)($_ENV['MAIL_PORT'] ?? 587);
$mail_user = $_ENV['MAIL_USER'] ?? 'info@oyunevleri.com';
$mail_pass = $_ENV['MAIL_PASS'] ?? 'Balkanlar07.';
$mail_secure = $_ENV['MAIL_SECURE'] ?? 'tls';
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
                    <path
                         d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2">
                    </path>
               </svg>';
$close_svg ='<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" class="feather feather-x">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
               </svg>';
#################################### END - SVG ICONS ####################################
?>
