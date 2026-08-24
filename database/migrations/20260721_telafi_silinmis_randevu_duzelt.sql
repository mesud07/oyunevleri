UPDATE telafi_haklari th
SET th.durum = 'planlanmayi_bekliyor'
WHERE th.durum IN ('planlandi', 'kullanildi')
  AND NOT EXISTS (
    SELECT 1
    FROM randevular r
    WHERE r.telafi_hakki_id = th.id
  );
