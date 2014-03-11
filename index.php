<?php

require_once "config.inc.php";
require_once "common.inc.php";
require_once "view.inc.php";
require_once "article.class.php";

if(!init_necorandum())
{
	header(sprintf("HTTP/1.1 500 %s", "neko"));
	die("fatal");
}

// 圧縮バッファ
ob_start("ob_gzhandler");

/*
$article = new Article();
$article->title = "test";
$article->text = "文字列";
$article->created_at = new DateTime();
$article->updated_at = new DateTime();
$article->save();
	*/

print layout("head", "contents", "foot");

$mime_type = NULL;

// MIMEタイプヘッダ出力
if(is_null($mime_type)) $mime_type = "application/xhtml+xml";
header(sprintf("Content-Type: %s; charset=utf-8", $mime_type));

finalize();
		 
// バッファ出力
ob_end_flush();

?>
