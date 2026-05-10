<?php
require __DIR__.'/../app/lib/bootstrap.php';
$room=$_GET['room']??'main';
if(!room_id_ok($room)) $room='main';
$rooms=rooms_cfg();
$base=base_path();
?>
<!doctype html><html lang="ja"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>TIME-PON 管理</title>
<link rel="stylesheet" href="assets/css/common.css"><link rel="stylesheet" href="assets/css/admin.css">
<body class="admin-body">
<div class="container">
  <h1>管理画面</h1>
  <div class="card">
    <div class="row roomtabs"><span>ルーム:</span><?php foreach($rooms as $r): ?><a class="<?= $r['id']===$room?'active':'' ?>" href="<?=$base?>/admin.php?room=<?=h($r['id'])?>"><?=h($r['name'])?></a><?php endforeach; ?></div>
    <div class="muted">同期状態: <span id="net">同期中...</span></div>
  </div>

  <div id="adminGrid">
    <div class="colL">
      <div class="card">
        <div>残り時間</div>
        <div id="time" class="big mono">--:--</div>
        <div class="row" style="margin-top:10px">
          <button data-cmd="start" class="btnxl">開始/再開</button>
          <button data-cmd="pause" class="secondary btnxl">一時停止</button>
          <button data-cmd="reset" class="danger btnxl">リセット</button>
        </div>
      </div>

      <div class="card">
        <h3>カンペ</h3>
        <div class="row" style="flex-wrap:nowrap;gap:8px;align-items:stretch">
          <input id="msg" placeholder="カンペ（演台に表示）" maxlength="500" style="flex:1;min-width:180px;">
          <button id="sendMsg" class="secondary">送信</button>
          <button id="clearMsg" class="secondary">消去</button>
        </div>
      </div>
    </div>

    <div class="colR">
      <div class="card">
        <h3>チャイム有効化</h3>
        <div class="row"><button id="enableAudio">クリックして有効化</button><span id="audioState" class="muted">未有効</span></div>
      </div>
      <div class="card">
        <h3>チャイム設定</h3>
        <div id="chimeEditor"></div>
        <button id="saveChimes" class="secondary">設定保存</button>
      </div>
      <div class="card">
        <h3>音源</h3>
        <div class="row"><input type="file" id="audio" accept=".mp3,.wav"><button id="up">アップロード</button></div>
        <ul id="files"></ul>
      </div>
    </div>
  </div>

  <div class="card">
    <h3>ルーム監視</h3>
    <table id="monitor"><thead><tr><th>ルーム</th><th>残り</th><th>状態</th><th>カンペ</th><th>接続</th></tr></thead><tbody></tbody></table>
  </div>
</div>
<script>window.TIMEPON_ROOM='<?=h($room)?>';</script><script src="assets/js/admin.js"></script></body></html>
