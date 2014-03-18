<?php

require_once "vendor/autoload.php"; // for composer, twig
require_once "config.inc.php";
require_once "common.inc.php";

if(!init_necorandum())
{
	header(sprintf("HTTP/1.1 500 %s", "neko"));
	die("fatal");
}


$mode = NULL;
$admin = FALSE;
if(array_key_exists("admin", $_GET)) $admin = (intval($_GET["admin"]) === 1);
if(array_key_exists("mode", $_GET)) $mode = strtolower($_GET["mode"]);

$redirect_to = NULL;

if($admin)
{
	if($mode === "create")
	{
		$article = new Article();
		$article->title = strval($_POST["article-title"]);
		$article->text = strval($_POST["article-text"]);
		$article->save();
		$redirect_to = "/";
	}
	else if($mode === "update")
	{
	}
	else
	{
	}
}

// リダイレクト
if(is_string($redirect_to) && strlen($redirect_to) > 0)
{
	header("Location: " . $redirect_to);
	die("Redirect");
}

// 圧縮バッファ
//ob_start("ob_gzhandler");
ob_start();
// twig
$layout_variables = [
	"config" => $GLOBALS["config"],
	"system" => $GLOBALS["system"]
	];
	

$template = "layout.twig";

if($admin)
{
	$layout_variables += ["admin" => TRUE, "embed_ga" => FALSE];
	$template = "admin.twig";
}
else
{
	$layout_variables += ["articles" => Article::all()];
}


print $GLOBALS["twig"]->render($template, $layout_variables);


$mime_type = NULL;

// MIMEタイプヘッダ出力
if(is_null($mime_type)) $mime_type = "application/xhtml+xml";
header(sprintf("Content-Type: %s; charset=utf-8", $mime_type));

finalize();

// バッファ出力
ob_end_flush();

?>
