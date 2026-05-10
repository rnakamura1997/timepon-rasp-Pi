<?php
class TimerState {
  public static function defaults(string $id): array {
    $c=cfg();
    return [
      'id'=>$id,'state'=>'idle','durationSec'=>$c['default_duration_sec'],'warn1Min'=>$c['default_warn1_min'],'warn2Min'=>$c['default_warn2_min'],
      'startedAtMs'=>0,'pausedAccumMs'=>0,'pausedAtMs'=>0,'message'=>'','stage'=>['lastSeen'=>0],'updatedAt'=>time(),
      'chimes'=>[
        ['type'=>'start','sec'=>0,'sound'=>'bell1','count'=>1,'volume'=>0.4,'enabled'=>false],
        ['type'=>'elapsed','sec'=>300,'sound'=>'bell1','count'=>1,'volume'=>0.5,'enabled'=>false],
        ['type'=>'remaining','sec'=>600,'sound'=>'bell1','count'=>1,'volume'=>0.4,'enabled'=>true],
        ['type'=>'remaining','sec'=>300,'sound'=>'bell1','count'=>1,'volume'=>0.55,'enabled'=>true],
        ['type'=>'end','sec'=>0,'sound'=>'bell2','count'=>2,'volume'=>0.7,'enabled'=>true],
        ['type'=>'overtime','sec'=>60,'sound'=>'bell2','count'=>1,'volume'=>0.6,'enabled'=>false]
      ]
    ];
  }
  public static function load(string $id): array {
    $st=Storage::readJson(Storage::roomPath($id),[]);
    return $st?array_replace_recursive(self::defaults($id),$st):self::defaults($id);
  }
  public static function save(string $id,array $st): bool {
    $st['updatedAt']=time();
    return Storage::writeJson(Storage::roomPath($id),$st);
  }
}
