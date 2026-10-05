UPDATE onam_form_ayarlari
SET baslik = 'Oyun Grubu Katılımcı Bilgi ve Veli Onam Formu',
    aciklama = 'Çocuğunuza ve veliye ait bilgileri eksiksiz doldurunuz. Sağlık ve izin tercihlerinizi belirttikten sonra formu dijital olarak onaylayabilirsiniz.',
    onam_metni = 'PROGRAM KURALLARI
• Çocuk hasta olduğunda programa gönderilmemelidir.
• Düzenli katılım, çocuğun uyumu açısından önemlidir.
• Ders sırasında telefon kullanımı minimumda tutulmalı; fotoğraf/video çekimleri diğer çocukların mahremiyetini ve ders akışını etkilemeyecek şekilde yalnızca kendi çocuğunuzu kapsamalıdır.
• Öğretmenlerimizin ders sırasında çektiği çocuğunuza ait görüntüleri ders sonunda isteyebilirsiniz.

PROGRAM KATILIM ONAMI
“Çocuğumun, uzman eşliğinde yürütülen oyun grubu çalışmalarına katılmasını kabul ediyorum. Bu çalışmaların çocukların sosyal, duygusal ve gelişimsel becerilerini desteklemeye yönelik grup etkinlikleri olduğunu biliyorum.”

Devam ve telafi sürecine ilişkin olarak; haftada 1 gün (aylık 4 ders) katılım sağlayan öğrenciler için ayda 1 telafi hakkı, haftada 2 gün (aylık 8 ders) katılım sağlayan öğrenciler için ise ayda 2 telafi hakkı bulunmaktadır.

Telafi dersleri, mümkün olması durumunda aynı hafta içerisinde farklı bir grupta planlanır. Uygunluk sağlanamaması halinde telafi, bir sonraki hafta içerisinde gerçekleştirilir. Belirtilen telafi haklarının aşılması durumunda dersler programdan düşülerek ilerler.',
    form_surumu = 2
WHERE onam_metni IS NULL
   OR onam_metni = ''
   OR onam_metni = 'Formun ayrıntılı bilgilendirme ve onam maddeleri yönetici tarafından eklenecektir.';
