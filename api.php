<?php
// api.php
// بسيط للتخزين المؤقت للتوقعات والحالة - يستخدم ملف JSON محلي.
// Actions: ?action=set  => يولد توقع جديد و يرجع JSON
//          ?action=get  => يرجع الحالة والتوقع الحالي
//          ?action=clear => يحذف التوقع (اختياري)

header('Content-Type: application/json');

$stateFile = __DIR__ . '/state.json';
$action = isset($_GET['action']) ? $_GET['action'] : 'get';

// load state
$state = ['prediction' => null, 'timestamp' => 0, 'running' => false];
if (file_exists($stateFile)) {
    $raw = @file_get_contents($stateFile);
    $s = @json_decode($raw, true);
    if (is_array($s)) $state = array_merge($state, $s);
}

if ($action === 'set') {
    // توليد رقم عشوائي مائل إلى أقل من 12 في الغالب
    // سنعطي 75% احتمال لاختيار بين 1 و 12، و25% لباقي 13..35
    $r = mt_rand(1,100);
    if ($r <= 75) {
        $num = mt_rand(1,12);
    } else {
        $num = mt_rand(13,35);
    }
    // نضيف جزء عشري .00 كما طلبت
    $prediction = number_format($num, 2, '.', '');
    $state['prediction'] = $prediction;
    $state['timestamp'] = time();
    $state['running'] = true;
    // save
    @file_put_contents($stateFile, json_encode($state));
    echo json_encode(['ok' => true, 'prediction' => $prediction, 'timestamp' => $state['timestamp']]);
    exit;
}

if ($action === 'clear') {
    $state = ['prediction' => null, 'timestamp' => 0, 'running' => false];
    @file_put_contents($stateFile, json_encode($state));
    echo json_encode(['ok' => true]);
    exit;
}

if ($action === 'get') {
    echo json_encode(['ok' => true, 'prediction' => $state['prediction'], 'timestamp' => $state['timestamp'], 'running' => $state['running']]);
    exit;
}

// default
echo json_encode(['ok' => false, 'msg' => 'unknown action']);