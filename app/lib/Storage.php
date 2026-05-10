<?php
class Storage {
  public static function roomPath(string $id): string { return cfg()['storage_dir'].'/rooms/'.$id.'.json'; }
  public static function readJson(string $path, array $default=[]): array { if(!is_file($path)) return $default; $fp=@fopen($path,'rb'); if(!$fp) return $default; $json=''; if(@flock($fp, LOCK_SH)){ $json=(string)stream_get_contents($fp); @flock($fp,LOCK_UN);} fclose($fp); $d=json_decode($json,true); return is_array($d)?$d:$default; }
  public static function writeJson(string $path, array $data): bool { $dir=dirname($path); if(!is_dir($dir)) @mkdir($dir,0775,true); $tmp=$path.'.tmp'; $fp=@fopen($tmp,'wb'); if(!$fp) return false; $ok=false; if(@flock($fp, LOCK_EX)){ $ok=fwrite($fp, json_encode($data, JSON_UNESCAPED_UNICODE))!==false; fflush($fp); @flock($fp,LOCK_UN);} fclose($fp); if(!$ok){ @unlink($tmp); return false;} return @rename($tmp,$path); }
}
