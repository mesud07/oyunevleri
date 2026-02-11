<?php
require_once("includes/config.php");
require_once("includes/functions.php");
$log_personel_id = 0;
$log_personel_ad = 'Bilinmeyen';

if (!empty($_SESSION['user'])) {
    $log_personel_id = (int) ($_SESSION['user']['personel_id'] ?? 0);
    $log_personel_ad = $_SESSION['user']['personel_adsoyad'] ?? $log_personel_ad;
} elseif (!empty($_SESSION['kullanici'])) {
    $log_personel_id = (int) ($_SESSION['kullanici']['id'] ?? 0);
    $log_personel_ad = $_SESSION['kullanici']['ad_soyad']
        ?? $_SESSION['kullanici']['kullanici_adi']
        ?? $log_personel_ad;
    $_SESSION['user'] = [
        'personel_id' => $log_personel_id,
        'personel_adsoyad' => $log_personel_ad,
    ];
}

if ($log_personel_id > 0 || $log_personel_ad !== 'Bilinmeyen') {
    logTut("ÇIKIŞ YAPTI", "({$log_personel_id}) {$log_personel_ad}", '5', '#');
}
session_destroy();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<title>ÇIKIŞ</title>
<meta http-equiv="refresh" content="2;URL=login">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
<style>
body{
    padding:0;
    margin:0;
	background: white;
    overflow: hidden;
}

div#topDiv{
    width:100%;
    height:0%;
    opacity:0.9;
    background:black;
    position:absolute;
    top: 0%;
}
div#bottomDiv{
    width:100%;
    height:0%;
    opacity:0.9;
    background:black;
    position:absolute;
    bottom: 0%;
}
div#centerDiv{
    position:absolute;
    height: 1px;
    top: 50%;
    width:100%;
    background: white;
    display:none;
    z-index:1;
}
</style>

<script>
	$(document).ready(function(){
		$('div#topDiv').animate({
			//51% for chrome
			height: "50%"
			,opacity: 1
		}, 400);
		$('div#bottomDiv').animate({
			//51% for chrome
			height: "50%"
			,opacity: 1
		}, 400, function(){
				$('div#centerDiv').css({display: "block"}).animate({
						width: "0%",
						left: "50%"
					 }, 300);
				}
		);
	});
</script>

</head>
<body>
<div id="topDiv"></div>
<div id="centerDiv"></div>
<div id="bottomDiv"></div>
</body>
</html>
