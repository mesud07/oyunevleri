<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Response;
final class YoklamaController { public function liste(): void { Response::json(['basari'=>true,'mesaj'=>'Yoklama listesi hazir.','veri'=>[]]); } }
