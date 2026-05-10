<?php
require __DIR__.'/../app/lib/bootstrap.php';
require __DIR__.'/../app/lib/Storage.php';
require __DIR__.'/../app/lib/TimerState.php';
require __DIR__.'/../app/lib/Upload.php';

const CHIME_TYPES = ['start','elapsed','remaining','end','overtime'];

function room_ids(): array { return array_map(fn($r)=>(string)$r['id'], rooms_cfg()); }
function sanitize_sound_name(string $name): string { return basename($name); }
function normalize_chime_row(array $row, array $availableSounds): ?array {
  $type = (string)($row['type'] ?? '');
  if (!in_array($type, CHIME_TYPES, true)) return null;
  $sec = max(0, min(43200, (int)($row['sec'] ?? 0)));
  $count = (int)($row['count'] ?? 1);
  if (!in_array($count, [1,2], true)) $count = 1;
  $volume = (float)($row['volume'] ?? 0.5);
  $volume = max(0.0, min(1.0, $volume));
  $enabled = (bool)($row['enabled'] ?? false);
  $sound = sanitize_sound_name((string)($row['sound'] ?? ''));
  if (!preg_match('/^[A-Za-z0-9._-]+\.(mp3|wav)$/i', $sound)) $sound = '';
  if (!in_array($sound, $availableSounds, true)) {
    $sound = $availableSounds[0] ?? 'bell1.mp3';
    $enabled = false;
  }
  return ['type'=>$type,'sec'=>$sec,'sound'=>$sound,'count'=>$count,'volume'=>$volume,'enabled'=>$enabled];
}

$action=$_GET['act']??$_POST['act']??'';
$room=$_GET['room']??$_POST['room']??'';

if($action==='rooms'){ send_json(['ok'=>true,'rooms'=>rooms_cfg()]); }
if($action==='fixed_rooms'){ send_json(['ok'=>true,'rooms'=>array_map(fn($r)=>['id'=>$r['id'],'name'=>$r['name']],rooms_cfg())]); }
if($action==='cli_snapshot'){
  $rooms=[]; $now=(int)(microtime(true)*1000); $ids=room_ids();
  foreach(rooms_cfg() as $r){
    $st=TimerState::load($r['id']);
    $base=($st['state']==='paused'?(int)$st['pausedAtMs']:$now)-(int)$st['startedAtMs']-(int)$st['pausedAccumMs'];
    $remain=(int)$st['durationSec']*1000-$base;
    $rooms[]=[
      'id'=>$r['id'],'name'=>$r['name'],'state'=>$st['state'],'remainSec'=>(int)floor($remain/1000),
      'message'=>(string)$st['message'],'warn1Min'=>(int)$st['warn1Min'],'warn2Min'=>(int)$st['warn2Min'],
      'durationSec'=>(int)$st['durationSec'],'stageLastSeen'=>(int)($st['stage']['lastSeen']??0),'updatedAt'=>(int)$st['updatedAt'],
      'urls'=>['admin'=>base_path().'/admin.php?room='.$r['id'],'stage'=>base_path().'/stage.php?room='.$r['id']],
      'chimes'=>$st['chimes'],
    ];
  }
  send_json(['ok'=>true,'serverNowMs'=>$now,'rooms'=>$rooms,'roomIds'=>$ids,'sounds'=>Upload::list()]);
}

if(!room_id_ok($room)) send_json(['ok'=>false,'error'=>'invalid_room'],400);
if($action==='get'){ $st=TimerState::load($room); send_json(['ok'=>true,'state'=>$st,'serverNowMs'=>(int)(microtime(true)*1000)]); }
if($action==='status'){ $out=[]; $now=(int)(microtime(true)*1000); foreach(rooms_cfg() as $r){ $st=TimerState::load($r['id']); $base=($st['state']==='paused'?(int)$st['pausedAtMs']:$now)-(int)$st['startedAtMs']-(int)$st['pausedAccumMs']; $remain=(int)$st['durationSec']*1000-$base; $out[]=['id'=>$r['id'],'name'=>$r['name'],'state'=>$st['state'],'remainSec'=>(int)floor($remain/1000),'message'=>$st['message'],'stageLastSeen'=>(int)($st['stage']['lastSeen']??0)]; } send_json(['ok'=>true,'rooms'=>$out,'serverNowMs'=>$now]); }
if($action==='room_meta'){
  $b=base_path();
  $urls=['admin'=>$b.'/admin.php?room='.$room,'stage'=>$b.'/stage.php?room='.$room];
  foreach(rooms_cfg() as $r){
    $rid=$r['id']; $uc=ucfirst($rid);
    $urls['admin'.$uc]=$b.'/admin/'.$rid;
    $urls['stage'.$uc]=$b.'/stage/'.$rid;
  }
  send_json(['ok'=>true,'room'=>$room,'urls'=>$urls,'roomIds'=>room_ids()]);
}
if($action==='set'){ require_post(); $st=TimerState::load($room); $cmd=$_POST['cmd']??''; $now=(int)(microtime(true)*1000); if($cmd==='start'){ if($st['state']==='idle'){ $st['startedAtMs']=$now; $st['pausedAccumMs']=0; $st['pausedAtMs']=0;} elseif($st['state']==='paused'){ $st['pausedAccumMs'] += max(0,$now-(int)$st['pausedAtMs']); $st['pausedAtMs']=0;} $st['state']='running'; }
elseif($cmd==='pause'&&$st['state']==='running'){ $st['state']='paused'; $st['pausedAtMs']=$now; }
elseif($cmd==='reset'){ $st['state']='idle'; $st['startedAtMs']=0; $st['pausedAccumMs']=0; $st['pausedAtMs']=0; }
elseif($cmd==='message'){ $st['message']=mb_substr((string)($_POST['text']??''),0,500); }
elseif($cmd==='heartbeat'){ $st['stage']['lastSeen']=$now; }
TimerState::save($room,$st); send_json(['ok'=>true]); }
if($action==='settings'){
  require_post();
  $st=TimerState::load($room);
  $st['durationSec']=max(60,min(43200,(int)($_POST['durationSec'] ?? $st['durationSec'])));
  $st['warn1Min']=max(0,min(120,(int)($_POST['warn1Min'] ?? $st['warn1Min'])));
  $st['warn2Min']=max(0,min(120,(int)($_POST['warn2Min'] ?? $st['warn2Min'])));
  if($st['warn1Min'] < $st['warn2Min']){ [$st['warn1Min'],$st['warn2Min']] = [$st['warn2Min'],$st['warn1Min']]; }
  if(isset($_POST['chimes'])){
    $c=json_decode((string)$_POST['chimes'],true);
    if(!is_array($c)) send_json(['ok'=>false,'error'=>'invalid_chimes_json'],400);
    $sounds=Upload::list();
    $normalized=[];
    foreach($c as $row){
      if(!is_array($row)) continue;
      $n=normalize_chime_row($row,$sounds);
      if($n!==null) $normalized[]=$n;
    }
    if(!$normalized) send_json(['ok'=>false,'error'=>'no_valid_chimes'],400);
    $st['chimes']=$normalized;
  }
  TimerState::save($room,$st);
  send_json(['ok'=>true,'state'=>$st]);
}
if($action==='chime_list'){ send_json(['ok'=>true,'files'=>Upload::list()]); }
if($action==='chime_config'){ $st=TimerState::load($room); send_json(['ok'=>true,'chimes'=>$st['chimes']]); }
if($action==='chime_upload'){ require_post(); [$ok,$msg]=Upload::save($_FILES['audio']??[]); send_json(['ok'=>$ok,'msg'=>$msg],$ok?200:400); }
if($action==='chime_delete'){ require_post(); $f=basename((string)($_POST['file']??'')); if(!preg_match('/^[A-Za-z0-9._-]+\.(mp3|wav)$/i',$f)) send_json(['ok'=>false],400); @unlink(Upload::dir().'/'.$f); send_json(['ok'=>true]); }
send_json(['ok'=>false,'error'=>'unknown_action'],404);
