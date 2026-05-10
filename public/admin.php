<?php
require __DIR__.'/../app/lib/bootstrap.php';
$room=$_GET['room']??'main';
if(!room_id_ok($room)) $room='main';
$rooms=rooms_cfg();
$base=base_path();
?>
<!doctype html><html lang="ja"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>TIME-PON 管理</title>
<link rel="stylesheet" href="assets/css/common.css"><link rel="stylesheet" href="assets/css/admin.css">
<body class="admin-body"><div class="wrap">
<header class="topbar"><h1>TIME-PON 管理</h1><div id="net" class="pill">同期中...</div></header>
<nav class="roomtabs"><?php foreach($rooms as $r): ?><a class="<?= $r['id']===$room?'active':'' ?>" href="<?=$base?>/admin.php?room=<?=h($r['id'])?>"><?=h($r['name'])?></a><?php endforeach; ?></nav>
<section class="grid">
<div class="card timer-card"><div id="time">--:--</div><div class="row"><button data-cmd="start">開始/再開</button><button data-cmd="pause">一時停止</button><button data-cmd="reset">リセット</button></div></div>
<div class="card"><h3>カンペ</h3><div class="row"><input id="msg" placeholder="カンペを入力"><button id="sendMsg">送信</button><button id="clearMsg">消去</button></div></div>
<div class="card"><h3>チャイム有効化</h3><button id="enableAudio">クリックして有効化</button><small id="audioState">未有効</small></div>
<div class="card"><h3>チャイム設定</h3><div id="chimeEditor"></div><button id="saveChimes">設定保存</button></div>
<div class="card"><h3>音源</h3><div class="row"><input type="file" id="audio" accept=".mp3,.wav"><button id="up">アップロード</button></div><ul id="files"></ul></div>
</section>
<section class="card"><h3>ルーム監視</h3><table id="monitor"><thead><tr><th>ルーム</th><th>残り</th><th>状態</th><th>カンペ</th><th>接続</th></tr></thead><tbody></tbody></table></section>
</div>
<script>window.TIMEPON_ROOM='<?=h($room)?>';</script><script src="assets/js/admin.js"></script></body></html>
