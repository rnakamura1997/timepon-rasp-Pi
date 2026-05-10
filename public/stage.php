<?php require __DIR__.'/../app/lib/bootstrap.php'; $room=$_GET['room']??'main'; if(!room_id_ok($room)) $room='main'; ?>
<!doctype html><html lang="ja"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="assets/css/common.css"><link rel="stylesheet" href="assets/css/stage.css"><body>
<div class="stage"><div class="room">ROOM: <?=h($room)?></div><div id="time">--:--</div><div id="msg"></div></div>
<script>window.TIMEPON_ROOM='<?=h($room)?>';</script><script src="assets/js/stage.js"></script></body></html>
