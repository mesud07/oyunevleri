<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Response;
final class AyarlarController { public function liste(): void { Response::json(['basari'=>true,'mesaj'=>'Ayarlar hazir.','veri'=>[]]); } }
