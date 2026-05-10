<?php
ini_set('display_errors', '0');
date_default_timezone_set('Asia/Tokyo');
function cfg(){ static $c=null; if($c===null){ $base=require __DIR__.'/../config/config.php'; $local=__DIR__.'/../config/config.local.php'; if(is_file($local)){ $ov=require $local; if(is_array($ov)) $base=array_replace($base,$ov); } $c=$base; } return $c; }
function rooms_cfg(){ static $r=null; if($r===null){$r=require __DIR__.'/../config/rooms.php';} return $r; }
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function room_id_ok(string $id): bool { return preg_match('/^[A-Za-z0-9_-]{1,32}$/',$id)===1; }
function send_json($x,int $code=200){ http_response_code($code); header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store'); echo json_encode($x, JSON_UNESCAPED_UNICODE); exit; }
function same_origin_ok(): bool { $scheme=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https://':'http://'; $self=$scheme.($_SERVER['HTTP_HOST']??''); $origin=$_SERVER['HTTP_ORIGIN']??''; if($origin!=='') return strcasecmp(rtrim($origin,'/'),rtrim($self,'/'))===0; return true; }
function require_post(){ if(($_SERVER['REQUEST_METHOD']??'')!=='POST') send_json(['ok'=>false,'error'=>'method_not_allowed'],405); if(!same_origin_ok()) send_json(['ok'=>false,'error'=>'bad_origin'],403); }
function base_path(): string { $script=dirname($_SERVER['SCRIPT_NAME']??''); return rtrim($script,'/'); }
