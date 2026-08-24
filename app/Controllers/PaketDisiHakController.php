<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Response;
final class PaketDisiHakController { public function liste(): void { Response::json(['basari'=>true,'mesaj'=>'Paket disi hak listesi hazir.','veri'=>[]]); } }
