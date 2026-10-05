<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FaturaArsivi;

final class FaturaArsivServisi
{
    private string $root;

    public function __construct()
    {
        $this->root=BASE_PATH.'/storage';
    }

    public function oku(array $invoice,string $format): ?array
    {
        $format=$this->format($format);
        $record=FaturaArsivi::formatIcin((int)$invoice['id'],$format);
        if(!$record)return null;
        $path=$this->guvenliTamYol((string)$record['goreli_yol']);
        if(!is_file($path)||!is_readable($path))return null;
        $content=file_get_contents($path);
        if($content===false||hash('sha256',$content)!==(string)$record['sha256'])return null;
        return $this->cevap($invoice,$format,$content,'local');
    }

    public function kaydet(array $invoice,string $format,string $content): array
    {
        $format=$this->format($format);
        $this->dogrula($format,$content);
        $existing=$this->oku($invoice,$format);
        if($existing)return $existing;

        $year=preg_match('/^(20\d{2})-/',(string)($invoice['fatura_tarihi']??''),$match)?$match[1]:date('Y');
        $uuid=preg_replace('/[^a-f0-9-]/i','',(string)($invoice['ettn']??''))?:'fatura-'.(int)$invoice['id'];
        $relative='faturalar/'.(int)$invoice['kurum_id'].'/'.$year.'/'.$uuid.'.'.$format;
        $path=$this->guvenliTamYol($relative);
        $directory=dirname($path);
        if(!is_dir($directory)&&!mkdir($directory,02770,true)&&!is_dir($directory))throw new \RuntimeException('Fatura arşiv klasörü oluşturulamadı.');
        $this->izinleriDuzelt($directory);
        $temp=tempnam($directory,'.fatura-');
        if($temp===false)throw new \RuntimeException('Fatura arşivi için geçici dosya oluşturulamadı.');
        try {
            $written=file_put_contents($temp,$content,LOCK_EX);
            if($written!==strlen($content))throw new \RuntimeException('Fatura arşiv dosyası eksik yazıldı.');
            @chmod($temp,0660);
            if(!rename($temp,$path))throw new \RuntimeException('Fatura arşiv dosyası kalıcı konuma taşınamadı.');
        } finally {
            if(is_file($temp))@unlink($temp);
        }
        @chown($path,'www-data');
        @chgrp($path,'www-data');
        @chmod($path,0660);
        FaturaArsivi::kaydet((int)$invoice['id'],$format,$relative,hash('sha256',$content),strlen($content));
        return $this->cevap($invoice,$format,$content,'local');
    }

    private function format(string $format): string
    {
        $format=strtolower(trim($format));
        if(!in_array($format,['pdf','xml'],true))throw new \RuntimeException('Geçersiz fatura arşiv formatı.');
        return $format;
    }

    private function dogrula(string $format,string $content): void
    {
        if($content==='')throw new \RuntimeException('Boş fatura belgesi arşivlenemez.');
        if($format==='pdf'&&!str_starts_with($content,'%PDF-'))throw new \RuntimeException('Arşivlenecek PDF içeriği geçerli değil.');
        if($format==='xml'&&!preg_match('/^\s*(?:<\?xml[^>]*>\s*)?<Invoice\b/i',$content))throw new \RuntimeException('Arşivlenecek XML içeriği geçerli değil.');
    }

    private function guvenliTamYol(string $relative): string
    {
        $relative=ltrim(str_replace('\\','/',$relative),'/');
        if(!str_starts_with($relative,'faturalar/')||str_contains($relative,'../'))throw new \RuntimeException('Geçersiz fatura arşiv yolu.');
        return $this->root.'/'.$relative;
    }

    private function izinleriDuzelt(string $directory): void
    {
        $archiveRoot=$this->root.'/faturalar';
        $current=$directory;
        while(str_starts_with($current,$archiveRoot)){
            @chown($current,'www-data');
            @chgrp($current,'www-data');
            @chmod($current,02770);
            if($current===$archiveRoot)break;
            $parent=dirname($current);
            if($parent===$current)break;
            $current=$parent;
        }
    }

    private function cevap(array $invoice,string $format,string $content,string $source): array
    {
        $base=trim((string)($invoice['fatura_no']??''))?:((string)($invoice['ettn']??'fatura'));
        $base=preg_replace('/[^A-Za-z0-9_-]/','-',$base)?:'fatura';
        return ['content'=>$content,'mime'=>$format==='pdf'?'application/pdf':'application/xml','name'=>$base.'.'.$format,'source'=>$source];
    }
}
