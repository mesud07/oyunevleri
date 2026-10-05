<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Services\GozlemRaporuPdfServisi;
use App\Services\GozlemRaporuServisi;

$bolumler = [
    'genel_gozlem' => "Hasan Alp oyun grubumuza neşeli, sevgi dolu ve meraklı bir şekilde katılım sağlamaktadır. Grup ortamına ve öğretmenlerine uyumunun güzel ilerlediğini gözlemliyoruz.\n\nÖzellikle duyusal oyunlar, kitaplar, dans ve hareket etkinlikleri Hasan Alp'in ilgisini çekiyor. Köpük, su ve farklı materyallerle yapılan çalışmalarda keyifli vakit geçiriyor ve keşfetmeye istekli oluyor.",
    'etkinliklere_katilim' => "İlk derslerimizde boyama ve sulu boya gibi masa başı çalışmalarında ilgisinin daha kısa sürdüğünü gözlemlemiştik. İlerleyen derslerde ise boyama etkinliğinde daha uzun süre kalabilmesi ve çalışmaya devam etmesi bizi mutlu eden güzel bir gelişme oldu.\n\nÇember zamanı, top aktarma ve ince motor çalışmalarına da güzel katılım sağlıyor. Kitaplara olan ilgisi ise oldukça belirgin.",
    'yonerge_ve_grup_uyumu' => "Hasan Alp genel olarak öğretmeninin yönlendirmelerini dinleyebiliyor ve etkinliklere uyum sağlayabiliyor. Denver gelişim taraması sırasında da verilen yönergeleri dikkatle dinleyerek çalışmalara güzel katılım gösterdi.\n\nÇok keyif aldığı ve heyecanlandığı bazı etkinliklerde oyunun heyecanıyla sınırları hatırlamak için öğretmen desteğine ihtiyaç duyabiliyor. Bu anları, duygularını düzenleme ve grup kurallarını öğrenme sürecinin doğal bir parçası olarak görüyor ve sakin yönlendirmelerle destekliyoruz.",
    'cocuk_gelisimci_gorusu' => "Hasan Alp'in hareket ederek, dokunarak ve deneyimleyerek öğrenmekten keyif aldığı görülüyor. İlgi duyduğu etkinliklerde dikkatini daha uzun süre sürdürebiliyor.\n\nMasa başı çalışmalarındaki süresinin zaman içerisinde artması da dikkat ve etkinliği sürdürebilme becerisi açısından güzel bir ilerleme. Bu alanı zorlamadan, sevdiği etkinliklerle birleştirerek desteklemeye devam edeceğiz.",
    'psikolojik_danisman_gorusu' => "Hasan Alp'in sosyal iletişime açık, sevgi dolu ve grup içerisinde olmaktan keyif alan bir çocuk olduğunu gözlemliyoruz.\n\nHeyecanının yükseldiği anlarda kısa ve anlaşılır hatırlatmalar yapılması, sınırları öğrenmesini kolaylaştıracaktır. Evde de benzer durumlarda uzun açıklamalar yerine kısa ve olumlu ifadeler kullanılabilir.",
    'genel_degerlendirme' => "Hasan Alp'in oyun grubuna uyumu olumlu şekilde ilerliyor. Özellikle duyusal çalışmalar, hareket etkinlikleri ve kitaplarla oldukça keyifli zaman geçiriyor.\n\nÖnümüzdeki süreçte dikkat süresi, yönerge takibi ve heyecanlandığı anlarda kendini düzenleme becerilerini oyun içerisinde desteklemeye devam edeceğiz. Hasan Alp'in gelişimini ve grup içerisindeki güzel ilerlemelerini takip etmekten mutluluk duyuyoruz.",
];

$pdf = (new GozlemRaporuPdfServisi())->olustur([
    'ogrenci_adi' => 'Hasan Alp',
    'yas_grubu' => '25-36 Ay',
    'baslangic' => '2026-08-11',
    'bitis' => '2026-09-03',
    'not_sayisi' => 12,
    'bolumler' => $bolumler,
]);

if (!str_starts_with($pdf, '%PDF-') || strlen($pdf) < 10000) {
    fwrite(STDERR, "Gozlem raporu PDF uretimi basarisiz.\n");
    exit(1);
}

$yasServisi = new GozlemRaporuServisi();
if ($yasServisi->yasGrubu('2024-03-01', '2026-03-01') !== '13-24 Ay') {
    fwrite(STDERR, "Yas grubu hesaplamasi hatali.\n");
    exit(1);
}

if (in_array('--write-sample', $argv, true)) {
    $hedef = dirname(__DIR__) . '/output/pdf/hasan-alp-gozlem-raporu-ornek.pdf';
    file_put_contents($hedef, $pdf);
    echo $hedef . "\n";
}

echo "Gozlem raporu PDF smoke testi basarili.\n";
