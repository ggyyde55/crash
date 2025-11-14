<?php
// predictor.php
?>
<!doctype html>
<html lang="ar">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Predictor - عرض التوقع</title>
<style>
  html,body{height:100%;margin:0}
  body{display:flex;align-items:center;justify-content:center;background:#fff;font-family:arial,helvetica,sans-serif}
  .box{text-align:center}
  .num{font-size:72px;font-weight:700;color:#111}
  .small{font-size:14px;color:#666;margin-top:10px}
  .status{font-size:12px;color:#999;margin-top:8px}
</style>
</head>
<body>
  <div class="box">
    <div id="prediction" class="num">--</div>
    <div id="info" class="small">لا يوجد توقع حتى الآن</div>
    <div id="stat" class="status"></div>
  </div>

<script>
const predEl = document.getElementById('prediction');
const info = document.getElementById('info');
const stat = document.getElementById('stat');

let lastTimestamp = 0;

// polling الخادم للحصول على التوقع الحالي
async function fetchPrediction(){
  try {
    const res = await fetch('api.php?action=get', {cache: 'no-store'});
    const j = await res.json();
    if (j.ok){
      if (j.prediction){
        // لو التوقيت الجديد مختلف عن القديم نحدث العرض
        if (j.timestamp !== lastTimestamp){
          lastTimestamp = j.timestamp;
          predEl.textContent = parseFloat(j.prediction).toFixed(2);
          info.textContent = 'تحديث جديد — التوقع الحالي';
          // وميض بسيط
          flash();
        } else {
          // حتى لو نفسه، فقط نبقي العرض
          // (ممكن نغير النص لتوضيح أن التوقع ثابت)
          info.textContent = 'التوقع ثابت حالياً';
        }
      } else {
        predEl.textContent = '--';
        info.textContent = 'لا يوجد توقع (انتظر أن يبدأ الموقع الثاني)';
      }
      stat.textContent = 'آخر تحديث: ' + (j.timestamp ? new Date(j.timestamp * 1000).toLocaleString() : 'لا يوجد');
    } else {
      info.textContent = 'خطأ في استدعاء الخادم';
    }
  } catch (e){
    console.error(e);
    info.textContent = 'خطأ في الاتصال بالخادم';
  }
}

function flash(){
  predEl.style.transition = 'transform .15s';
  predEl.style.transform = 'scale(1.05)';
  setTimeout(()=>{ predEl.style.transform = 'scale(1)'; }, 180);
}

// polling loop
setInterval(fetchPrediction, 700);
// initial
fetchPrediction();
</script>
</body>
</html>