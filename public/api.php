<?php
require __DIR__.'/../app/lib/bootstrap.php'; require __DIR__.'/../app/lib/Storage.php'; require __DIR__.'/../app/lib/TimerState.php'; require __DIR__.'/../app/lib/Upload.php';
$action=$_GET['act']??$_POST['act']??'';
$room=$_GET['room']??$_POST['room']??'';
if($action==='rooms'){ send_json(['ok'=>true,'rooms'=>rooms_cfg()]); }
if(!room_id_ok($room)) send_json(['ok'=>false,'error'=>'invalid_room'],400);
if($action==='get'){ $st=TimerState::load($room); send_json(['ok'=>true,'state'=>$st,'serverNowMs'=>(int)(microtime(true)*1000)]); }
if($action==='set'){ require_post(); $st=TimerState::load($room); $cmd=$_POST['cmd']??''; $now=(int)(microtime(true)*1000); if($cmd==='start'){ if($st['state']==='idle'){ $st['startedAtMs']=$now; $st['pausedAccumMs']=0; $st['pausedAtMs']=0;} elseif($st['state']==='paused'){ $st['pausedAccumMs'] += max(0,$now-(int)$st['pausedAtMs']); $st['pausedAtMs']=0;} $st['state']='running'; }
elseif($cmd==='pause'&&$st['state']==='running'){ $st['state']='paused'; $st['pausedAtMs']=$now; }
elseif($cmd==='reset'){ $st['state']='idle'; $st['startedAtMs']=0; $st['pausedAccumMs']=0; $st['pausedAtMs']=0; }
elseif($cmd==='message'){ $st['message']=mb_substr((string)($_POST['text']??''),0,500); }
TimerState::save($room,$st); send_json(['ok'=>true]); }
if($action==='settings'){ require_post(); $st=TimerState::load($room); $st['durationSec']=max(60,min(43200,(int)$_POST['durationSec'])); $st['warn1Min']=max(0,min(120,(int)$_POST['warn1Min'])); $st['warn2Min']=max(0,min(120,(int)$_POST['warn2Min'])); if(isset($_POST['chimes'])){ $c=json_decode((string)$_POST['chimes'],true); if(is_array($c)) $st['chimes']=$c; } TimerState::save($room,$st); send_json(['ok'=>true]); }
if($action==='chime_list'){ send_json(['ok'=>true,'files'=>Upload::list()]); }
if($action==='chime_upload'){ require_post(); [$ok,$msg]=Upload::save($_FILES['audio']??[]); send_json(['ok'=>$ok,'msg'=>$msg],$ok?200:400); }
if($action==='chime_delete'){ require_post(); $f=basename((string)($_POST['file']??'')); if(!preg_match('/^[A-Za-z0-9._-]+\.(mp3|wav)$/i',$f)) send_json(['ok'=>false],400); @unlink(cfg()['storage_dir'].'/chimes/'.$f); send_json(['ok'=>true]); }
send_json(['ok'=>false,'error'=>'unknown_action'],404);
