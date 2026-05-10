<?php
class Upload {
  public static function dir(): string { $c=cfg(); return $c['chime_dir'] ?? ($c['storage_dir'].'/chimes'); }
  public static function list(): array { $out=[]; foreach(glob(self::dir().'/*')?:[] as $f){ if(is_file($f)) $out[]=basename($f);} sort($out); return $out; }
  public static function save(array $file): array { if(($file['error']??1)!==0) return [false,'upload_error']; $name=basename((string)$file['name']); if(!preg_match('/^[A-Za-z0-9._-]+\.(mp3|wav)$/i',$name)) return [false,'invalid_name']; $tmp=$file['tmp_name']; $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp); if(!in_array($mime,['audio/mpeg','audio/wav','audio/x-wav','application/octet-stream'],true)) return [false,'invalid_mime']; if(!is_dir(self::dir())) @mkdir(self::dir(),0775,true); $dest=self::dir().'/'.$name; return [move_uploaded_file($tmp,$dest),$name]; }
}
