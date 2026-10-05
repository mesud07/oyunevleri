<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NesApiException;

final class NesClient
{
    private array $config;

    public function __construct(private readonly array $integration)
    {
        $this->config=require BASE_PATH.'/config/nes.php';
        NesEndpointGuvenligi::dogrula((string)($integration['base_url']??''),(string)($integration['ortam']??''));
    }

    public function get(string $path): array { return $this->request('GET',$path); }
    public function post(string $path,array $body): array { return $this->request('POST',$path,$body,false,false,true); }

    public function upload(string $path,string $xml,array $fields): array
    {
        $tmp=tempnam(sys_get_temp_dir(),'talya-nes-');
        if($tmp===false)throw new NesApiException('NES icin gecici belge olusturulamadi.');
        @chmod($tmp,0600);
        $written=file_put_contents($tmp,$xml,LOCK_EX);
        if($written!==strlen($xml)){@unlink($tmp);throw new NesApiException('NES için geçici belge güvenli biçimde yazılamadı.');}
        try {
            $fields['File']=new \CURLFile($tmp,'application/xml','invoice.xml');
            return $this->request('POST',$path,$fields,true);
        } finally { @unlink($tmp); }
    }

    public function binary(string $path): array
    {
        return $this->request('GET',$path,null,false,true);
    }

    private function request(string $method,string $path,?array $body=null,bool $multipart=false,bool $binary=false,bool $json=false): array
    {
        $base=NesEndpointGuvenligi::dogrula((string)$this->integration['base_url'],(string)$this->integration['ortam']);
        $url=$base.'/'.ltrim($path,'/');
        $key=trim((string)($this->integration['client_secret_resolved']??''));
        if($key==='')throw new NesApiException('NES API anahtari tanimli degil.',null,401);
        $headers=['Authorization: Bearer '.$key,'Accept: '.($binary?'application/octet-stream':'application/json')];
        $ch=curl_init($url);
        $options=[
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_HTTPHEADER=>$headers,
            CURLOPT_CONNECTTIMEOUT=>(int)$this->config['connect_timeout'],
            CURLOPT_TIMEOUT=>(int)$this->config['timeout'],
            CURLOPT_HEADER=>$binary,
            CURLOPT_FOLLOWLOCATION=>false,
            CURLOPT_SSL_VERIFYPEER=>true,
            CURLOPT_SSL_VERIFYHOST=>2,
            CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,
            CURLOPT_USERAGENT=>'TalyaKids/1.0 NES Integration',
        ];
        if($method==='POST'){$options[CURLOPT_POST]=true;if($json){$headers[]='Content-Type: application/json';$options[CURLOPT_HTTPHEADER]=$headers;$options[CURLOPT_POSTFIELDS]=json_encode($body,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);}else{$options[CURLOPT_POSTFIELDS]=$body;}}
        curl_setopt_array($ch,$options);
        $raw=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$error=curl_error($ch);$headerSize=(int)curl_getinfo($ch,CURLINFO_HEADER_SIZE);$contentType=(string)curl_getinfo($ch,CURLINFO_CONTENT_TYPE);
        if(PHP_VERSION_ID<80500)curl_close($ch);
        if($raw===false||$error!==''){error_log('NES bağlantı hatası: '.preg_replace('/[\r\n]+/',' ',(string)$error));throw new NesApiException('NES servisine güvenli bağlantı kurulamadı.',null,$status,null,$method==='POST');}
        $maxBytes=max(1048576,(int)($this->config['max_response_bytes']??20971520));
        if(strlen((string)$raw)>$maxBytes)throw new NesApiException('NES servis cevabı izin verilen boyutu aştı.',null,502,null,$method==='POST');
        if($status<200||$status>=300){
            $decoded=json_decode((string)$raw,true);$message=is_array($decoded)?trim((string)($decoded['message']??$decoded['title']??'')):'';$errors=is_array($decoded)?($decoded['errors']??$decoded['invalidFields']??[]):[];$first=is_array($errors)&&isset($errors[0])&&is_array($errors[0])?$errors[0]:[];$code=(string)($first['code']??$first['field']??'');$detail=trim((string)($first['detail']??$first['description']??''));
            $known=['422-21'=>'NES canlı hesabında e-Arşiv tasarımı için imza/kaşe görseli tanımlı değil. NES portalındaki belge tasarımı ayarlarından imza/kaşe eklenmelidir.','422-20'=>'NES hesabında kullanılabilir e-Arşiv belge tasarımı bulunamadı.'];
            throw new NesApiException($known[$code]??($detail?:($message?:'NES işlemi başarısız oldu.')),$code?:null,$status,is_array($decoded)?$decoded:null,false);
        }
        if($binary)return ['content'=>substr((string)$raw,$headerSize),'content_type'=>$contentType,'status'=>$status];
        if(trim((string)$raw)==='')return [];
        $decoded=json_decode((string)$raw,true);
        if(!is_array($decoded))throw new NesApiException('NES servisi gecersiz JSON cevabi dondu.',null,$status,null,$method==='POST');
        return $decoded;
    }
}
