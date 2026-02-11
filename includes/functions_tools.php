<?php

function yaz($data) {
    echo "<pre>";
    print_r($data);
    echo "</pre>";
}

function inputHiddenRef() {

	echo '<input type="hidden" name="ref" value="'.((empty($_SERVER['HTTP_REFERER'])) ? '/' : $_SERVER['HTTP_REFERER']).'">';

}

function headerLocation($url = '') {
	global $baseFolder;
	if (empty($url)) {
		header("Location: $baseFolder");
	} else {
		header("Location: $url");
	}
    exit;
}

function removeColons($data, $separator=false, $empty_access=false) {

	//receives something like :18:ABC:s and returns array
	$sep=($separator)?$separator:":";
	$x=explode($sep,$data);
	$r=array();

	foreach($x as $_x) {

		if($empty_access) {
			$r[]=$_x;	
		}

		elseif(strlen(trim($_x))>0) {
			$r[]=trim($_x);	
		}
        
	}
	return $r;	
    //close removeColons

}

function colonify($x) {

	if(!is_array($x) || empty($x)) {
		return false;
	}
    
	$r=":";
	foreach($x as $value) {
		$r.=$value.":";	
	}

	return $r;
    //close colonify

}

function curlPost($url,$fields) { 

    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL,$url); 
    curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/32.0.1700.107 Chrome/32.0.1700.107 Safari/537.36');
    curl_setopt($ch, CURLOPT_POST, count($fields));
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $output = curl_exec($ch); 
    curl_close($ch);
    
    return $output; 
    
}

################################# NOKTALI #################################
/*
 * KULLANIM ÖRNEĞİ
 *
 * noktaliSayi($sayi);
 * number_format (değişken, ondalık hanesi kaç sayıdan oluşacak, ondalık ayırma işareti, binlik ayırma işareti)
 * //return number_format($sayi, 2, '.', ','); bunda virgülden dolayı sorun çıkarttı db ye yazarken.
 *
 */

function noktaliSayi($sayi) {
    return number_format($sayi, 2, '.', '');
}

function ucNoktaliSayi($sayi) {
    return number_format($sayi, 3, '.', '');
}

function dortNoktaliSayi($sayi) {
    return number_format($sayi, 4, '.', '');
}

function ikiBasamakli($sayi) {
    return number_format($sayi, 2, ',', '');
}

function ucBasamakli($sayi) {
    return number_format($sayi, 3, ',', '');
}

function dortBasamakli($sayi) {
    return number_format($sayi, 4, ',', '');
}

function okunakliSayi($sayi) {
    return number_format($sayi, 2, '.', ',');
}
################################# NOKTALI END #################################


################################# TARIH YAZ #################################
/*
 * Kullanıcının ekranında göreceği tarih formatını standart hale getirebilmek için yaptım
 * Parametreleri kullanacağım;
 *
 * SD: Kısa Date Format (gg.aa)
 * FD: Full Date Format (gg.aa.YYYY)
 * ST: Kısa Time Format (hh:mm)
 * FT: Full Time Format (hh:mm:ss)
 * SDT : Kısa Date Format (gg.aa hh:mm)
 * FDT : Full Date Format (gg.aa.YYYY hh:mm)
 * FFF : Full Date Format (gg.aa.YYYY hh:mm:ss)
 *
 * toDB fonksiyonu için
 * Veritabanına kayıt formatları;
 * T: Sadece saat (hh:mm:ss)
 * D: Sadece tarih (YYYY-aa-gg)
 * DT: tarih ve saat (YYYY-aa-gg hh:mm:ss)
 *
 * tarih fonksiyonu için;
 * S: ekran için format (gg.aa.YYYY)
 * DB: DB için format (YYYY-aa-gg)
 *
 */

function tarihYaz($tarih,$format) {
    if (empty($tarih)) {
        return '';
    } else {
        switch($format){
            case 'J'  : $sonuc = date('j', strtotime($tarih)); break;
            case 'N'  : $sonuc = date('n', strtotime($tarih)); break;
            case 'D'  : $sonuc = date('d', strtotime($tarih)); break;
            case 'SD' : $sonuc = date('d.m', strtotime($tarih)); break;
            case 'FD' : $sonuc = date('d.m.Y', strtotime($tarih)); break;
            case 'ST' : $sonuc = date('H:i', strtotime($tarih)); break;
            case 'FT' : $sonuc = date('H:i:s', strtotime($tarih)); break;
            case 'SDT': $sonuc = date('d.m H:i', strtotime($tarih)); break;
            case 'FDT': $sonuc = date('d.m.Y - H:i', strtotime($tarih)); break;
            case 'FFF': $sonuc = date('d.m.Y H:i:s', strtotime($tarih)); break;
        }
        return $sonuc;
    }

}

function dateToDB($tarih,$format) {
    switch($format){
        case 'T' : $sonuc = date('H:i:s', strtotime($tarih)); break;
        case 'D' : $sonuc = date('Y-m-d', strtotime($tarih)); break;
        case 'DT': $sonuc = date('Y-m-d H:i:s', strtotime($tarih)); break;
        case 'DTMIN': $sonuc = date('Y-m-d 00:00:00', strtotime($tarih)); break;
        case 'DTMAX': $sonuc = date('Y-m-d 23:59:59', strtotime($tarih)); break;
    }
    return $sonuc;
}

function tarih($tarih,$format=0) {
    switch($format){
        case 'SD' : $sonuc = date('d.m', strtotime($tarih)); break;
        case 'FD' : $sonuc = date('d.m.Y', strtotime($tarih)); break;
        case 'ST' : $sonuc = date('H:i', strtotime($tarih)); break;
        case 'FT' : $sonuc = date('H:i:s', strtotime($tarih)); break;
        case 'SDT': $sonuc = date('d.m H:i', strtotime($tarih)); break;
        case 'FDT': $sonuc = date('d.m.Y - H:i', strtotime($tarih)); break;
        case 'FFF': $sonuc = date('d.m.Y H:i:s', strtotime($tarih)); break;
    }
    return $sonuc;
}

################################# TARIH YAZ END #################################


/* 
    Bu fonksiyon, Türkçede bir kelimenin sonuna dilbilgisel ekler,
    (yönelme, birlikte, aitlik vb.) ekleyen bir kullanıcı tanımlı fonksiyondur. 
    İşlev, kelimenin son sesli harfine ve son harfine göre ekin nasıl olacağını belirler. 
*/
function ekEkle($metin, $tip) {
    $kalinSesli = ['a', 'o', 'ı', 'u'];
    $inceSesli = ['e', 'i', 'ü', 'ö'];
    $sesliHarfler = array_merge($kalinSesli, $inceSesli);
    $bharfler = ['ç', 'f', 'h', 'k', 's', 'ş', 't', 'p'];

    $sonHarf = mb_substr(rtrim($metin), -1);
    $sonSesli = '';

    // Son sesli harfi bul
    for ($i = mb_strlen($metin) - 1; $i >= 0; $i--) {
        $harf = mb_substr($metin, $i, 1);
        if (in_array($harf, $sesliHarfler)) {
            $sonSesli = $harf;
            break;
        }
    }

    if ($sonSesli == '') {
        return $metin; // Eğer sesli harf yoksa metin olduğu gibi döner
    }

    $outp = '';

    if (in_array($tip, ['birlikte', 'yonelme'])) {
        $harf = in_array($sonSesli, $kalinSesli) ? 'a' : 'e';
        $kharf = in_array($sonHarf, $sesliHarfler) ? 'y' : '';
        $outp = $kharf . ($tip == 'birlikte' ? 'l' : '') . $harf;

    } elseif (in_array($tip, ['aitlik', 'belirtme'])) {
        $kharf = in_array($sonHarf, $sesliHarfler) ? ($tip == 'belirtme' ? 'y' : 'n') : '';
        $harfMap = ['i' => 'i', 'e' => 'i', 'a' => 'ı', 'ı' => 'ı', 'ü' => 'ü', 'ö' => 'ü', 'u' => 'u', 'o' => 'u'];
        $harf = $harfMap[$sonSesli] ?? 'i';
        $outp = $kharf . $harf . ($tip == 'belirtme' ? '' : 'n');

    } elseif (in_array($tip, ['bulunma', 'ayrilma'])) {
        $bharf = in_array($sonHarf, $bharfler) ? 't' : 'd';
        $harf = in_array($sonSesli, $kalinSesli) ? 'a' : 'e';
        $outp = $bharf . $harf . ($tip == 'ayrilma' ? 'n' : '');

    } else {
        // return 'bilinmeyen ek';
        return false;
    }

    // return $metin . "'" . $outp;
    return $outp;
}

?>