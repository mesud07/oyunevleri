<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\SecretBox;
use App\Exceptions\MysoftApiException;
use App\Exceptions\MysoftAuthenticationException;

final class MysoftClient
{
    private array $config;

    public function __construct(private readonly array $integration)
    {
        $this->config = require BASE_PATH . '/config/mysoft.php';
    }

    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, $query, null);
    }

    public function post(string $path, array $body): array
    {
        return $this->request('POST', $path, [], $body);
    }

    private function request(string $method, string $path, array $query, ?array $body, bool $retry401 = true): array
    {
        $url = rtrim((string)$this->integration['base_url'], '/') . '/' . ltrim($path, '/');
        $query = array_filter($query, static fn($value): bool => $value !== null && $value !== '');
        if ($query) $url .= '?' . http_build_query($query);
        $headers = ['Accept: application/json', 'Authorization: Bearer ' . $this->token()];
        $ch = curl_init($url);
        $options = [CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>$headers,CURLOPT_CONNECTTIMEOUT=>(int)$this->config['connect_timeout'],CURLOPT_TIMEOUT=>(int)$this->config['timeout']];
        if ($method === 'POST') {
            $headers[]='Content-Type: application/json';
            $options[CURLOPT_POST]=true;
            $options[CURLOPT_HTTPHEADER]=$headers;
            $options[CURLOPT_POSTFIELDS]=json_encode($body,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        }
        curl_setopt_array($ch,$options);
        $raw=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$error=curl_error($ch);
        if(PHP_VERSION_ID<80500)curl_close($ch);
        if($raw===false||$error!=='')throw new MysoftApiException('Mysoft servisine baglanilamadi: '.$error,null,$status);
        $decoded=json_decode((string)$raw,true);
        if($status===401&&$retry401){$this->tokenCacheSil();return $this->request($method,$path,$query,$body,false);}
        if(!is_array($decoded))throw new MysoftApiException('Mysoft servisi gecersiz JSON cevabi dondu.',null,$status);
        if($status<200||$status>=300||array_key_exists('succeed',$decoded)&&$decoded['succeed']!==true){
            $message=trim((string)($decoded['message']??''))?:'Mysoft islemi basarisiz oldu.';
            throw new MysoftApiException($message,(string)($decoded['errorCode']??'')?:null,$status,$decoded);
        }
        return $decoded;
    }

    private function token(): string
    {
        $cache=$this->tokenCacheOku();
        if($cache&&((int)$cache['expires_at']-(int)$this->config['token_refresh_margin'])>time())return SecretBox::decrypt((string)$cache['token']);
        $clientId=trim((string)($this->integration['client_id_resolved']??''));
        $secret=trim((string)($this->integration['client_secret_resolved']??''));
        if($clientId===''||$secret==='')throw new MysoftAuthenticationException('Mysoft client_id veya client_secret tanimli degil.');
        $url=rtrim((string)$this->integration['base_url'],'/').'/oauth/token';
        $ch=curl_init($url);
        curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>['Content-Type: application/x-www-form-urlencoded','Accept: application/json'],CURLOPT_POSTFIELDS=>http_build_query(['client_id'=>$clientId,'client_secret'=>$secret,'grant_type'=>'client_credentials']),CURLOPT_CONNECTTIMEOUT=>(int)$this->config['connect_timeout'],CURLOPT_TIMEOUT=>(int)$this->config['timeout']]);
        $raw=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$error=curl_error($ch);if(PHP_VERSION_ID<80500)curl_close($ch);
        if($raw===false||$error!=='')throw new MysoftAuthenticationException('Mysoft token servisine baglanilamadi.',null,$status);
        $data=json_decode((string)$raw,true);
        if($status<200||$status>=300||!is_array($data)||empty($data['access_token']))throw new MysoftAuthenticationException('Mysoft kimlik dogrulamasi basarisiz.',null,$status,is_array($data)?$data:null);
        $expires=max(60,(int)($data['expires_in']??300));
        $this->tokenCacheYaz(['token'=>SecretBox::encrypt((string)$data['access_token']),'expires_at'=>time()+$expires]);
        return (string)$data['access_token'];
    }

    private function tokenCachePath(): string
    {
        $dir=BASE_PATH.'/storage/cache';if(!is_dir($dir))@mkdir($dir,0700,true);
        return $dir.'/mysoft-token-'.(int)$this->integration['id'].'.json';
    }
    private function tokenCacheOku(): ?array
    {
        $path=$this->tokenCachePath();if(!is_file($path))return null;
        $data=json_decode((string)file_get_contents($path),true);return is_array($data)?$data:null;
    }
    private function tokenCacheYaz(array $data): void
    {
        $path=$this->tokenCachePath();$tmp=$path.'.'.bin2hex(random_bytes(4)).'.tmp';file_put_contents($tmp,json_encode($data,JSON_THROW_ON_ERROR),LOCK_EX);chmod($tmp,0600);rename($tmp,$path);
    }
    private function tokenCacheSil(): void { $path=$this->tokenCachePath();if(is_file($path))@unlink($path); }
}
