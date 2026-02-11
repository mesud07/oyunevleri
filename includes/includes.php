<?php

    ############################################## GÜVENLİK ##############################################

    ## Güvenlik 1
    /*
    if ((!isset($_SESSION['login'])) OR ($_SESSION['login'] != 'envar')) {
		header("Location: login");
        exit;
    }
    */
	## Güvenlik 2
	/*
	https://ustaderslik.com/konu/PHP_Session_Hijacking_G%C3%BCvenli%C4%9Fi
	if($veritabanindaki_ip != $_SERVER['REMOTE_ADDR']){

		echo "oturum çalınmış...";

		session_destroy();

	}*/
	
	/*
    ## Firma DB Bağlantısı
    if (!isset($_SESSION['firma_db'])) header("Location: $baseURL/login.php");

    mysql_select_db($_SESSION['firma_db'], $baglan) or header("Location: $baseURL/login.php");
    mysql_query("SET NAMES UTF8");

    ## Personellerin adı ve soyadı için her seferinde db ye ulaşmasın diye yaptım
    if (!isset($_SESSION['personeller'])) {
        unset($_SESSION['personeller']);
        $_SESSION['personeller'] = array();
        $strSQL		  = "SELECT * FROM personeller ORDER BY personel_adsoyad";
        $resultSQL	  = mysql_query($strSQL);
        while($rowSQL = mysql_fetch_array($resultSQL)) {
            $id = $rowSQL['personel_id'];
            $_SESSION['personeller'][$id] = $rowSQL['personel_adsoyad'];
        }
    }

    ## HTTP_REFERER boş ise
    if (!isset($_SERVER['HTTP_REFERER'])) $_SERVER['HTTP_REFERER'] = $baseURL;*/
    ############################################## END OF GÜVENLİK ##############################################
