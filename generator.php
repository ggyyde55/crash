<?php
// generator.php
// ارفع هذا الملف مع api.php
?>
<!doctype html>
<html lang="ar">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Generator - العد والتصاعدي</title>
<style>
  html,body{height:100%;margin:0}
  body{display:flex;align-items:center;justify-content:center;background:#fff;font-family:arial,helvetica,sans-serif}
  .box{text-align:center}
  .num{font-size:80px;font-weight:700;color:#111}
  .small{font-size:14px;color:#666;margin-top:10px}
  button{margin-top:20px;padding:10px 18px;border-radius:8px;border:1px solid #ddd;cursor:pointer;background:#f5f5f5}
</style>
</head>
<body>
  <div class="box">
    <div id="display" class="num">--</div>
    <div id="sub" class="small">جاهز</div>
    <button id="force">ابدأ/إعادة التشغيل فورياً</button>
  </div>

<script>
const display = document.getElementById('display');
const sub = document.getElementById('sub');
const btn = document.getElementById('force');

let running = false;
let currentPrediction = null;

// helper: call API
function api(action){
  return fetch('api.php?action=' + action, {cache: 'no-store'}).then(r=>r.json());
}

// تولد دورة جديدة: تطلب من الخادم تعيين توقع جديد ثم تبدأ العد
async function startCycle(){
  if (running) return;
  running = true;
  sub.textContent = 'يطالب الخادم بتوليد توقع...';
  try {
    const res = await api('set');
    if (res.ok && res.prediction){
      currentPrediction = parseFloat(res.prediction);
      sub.textContent = 'التوقع: ' + res.prediction + ' — يبدأ العد الآن';
      // ابدأ العد التنازلي 7..0
      await countdown(7);
      // الآن نبدأ العد التصاعدي من 1.00 إلى 35.00 ونتوقف عندما نصل لـ prediction
      await ascendToPrediction(currentPrediction);
      // انتظر 3 ثواني
      sub.textContent = 'تم التوقف عند ' + formatNumber(currentPrediction) + ' — انتظار 3 ثواني';
      await sleep(3000);
      // انتهت الدورة، نبدأ دورة جديدة تلقائياً بعد قليل
      running = false;
      // نبدأ تلقائياً فوراً (لتستمر العملية بدون توقف)
      startCycle();
    } else {
      sub.textContent = 'خطأ في توليد التوقع من الخادم';
      running = false;
      setTimeout(startCycle, 2000);
    }
  } catch (e){
    console.error(e);
    sub.textContent = 'خطأ في الاتصال بالخادم';
    running = false;
    setTimeout(startCycle, 2000);
  }
}

function sleep(ms){ return new Promise(res=>setTimeout(res, ms)); }

async function countdown(from){
  for(let i=from;i>=0;i--){
    display.textContent = i;
    sub.textContent = 'العد التنازلي: ' + i;
    await sleep(1000);
  }
}

function formatNumber(n){
  return n.toFixed(2);
}

// ascend effect: نحرك بسرعة عبر أرقام عشرية من 1.00 ونوقف على prediction
async function ascendToPrediction(pred){
  // سنقوم بزيادة خطوة صغيرة مع تسريع بصري
  // نعرض أرقام عشرية من 1.00 إلى pred، لكن لكي يبدو سريعًا نستخدم تزايد متسارع
  const min = 1.00;
  const max = 35.00;
  // calculate animation frames
  // نريد مدة إجمالية تظهر مقبولة، مثلاً 1200-2200ms لكن لأن pred قد صغير جداً، نتحكم بمعدل التوقف.
  // سنقوم بمحاكاة "تجوال" من 1 حتى قيمة أعلى ثم نهبط إن لزمنا لنتوافق مع pred.
  let duration = 1200 + Math.random()*1400; // ms
  // إذا pred صغير جداً نخقص المدة لظهور توقف مبكر غالباً
  if (pred <= 12) duration = 800 + Math.random()*1000;
  const start = performance.now();
  const end = start + duration;
  // We'll animate value from 1 to maybe up-to 35, but ensure we finish exactly at pred
  // To make it look natural, we animate easing then snap to pred at end.
  return new Promise(resolve=>{
    function frame(now){
      let t = (now - start) / (end - start);
      if (t > 1) t = 1;
      // easeOutQuad
      let ease = 1 - (1 - t)*(1 - t);
      // progress map to value
      let val = min + (max - min) * ease;
      // but we don't want to exceed pred much before final; we'll blend towards pred near end
      if (t > 0.8){
        // within last 20% blend to exact pred
        let alpha = (t - 0.8) / 0.2;
        val = val * (1 - alpha) + pred * alpha;
      }
      // show two decimals
      display.textContent = Number(val).toFixed(2);
      if (t < 1){
        requestAnimationFrame(frame);
      } else {
        // final snap to pred
        display.textContent = Number(pred).toFixed(2);
        resolve();
      }
    }
    requestAnimationFrame(frame);
  });
}

btn.addEventListener('click', ()=> {
  // force cycle: call set and restart
  running = false;
  startCycle();
});

// start automatically on load
window.addEventListener('load', ()=> {
  setTimeout(startCycle, 500);
});
</script>
</body>
</html>