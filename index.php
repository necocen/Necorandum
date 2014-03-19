<?php

require_once "vendor/autoload.php"; // for composer, twig
require_once "config.inc.php";
require_once "common.inc.php";
require_once "controller.inc.php";

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
$error = NULL;
$info = [];
$warn = [];
$mime_type = NULL;

if($admin)
{
	if($mode === "create")
	{
		if(create_article($_POST))
		{
			$redirect_to = "/";
			$info += ["記事を投稿しました"];
		}
		else
		{
			$redirect_to = "/admin/new";
			$warn += ["記事の投稿に失敗しました"];
		}
	}
	else if($mode === "update")
	{
		if(update_article($_POST))
		{
			$redirect_to = "/";
			$info += ["記事を更新しました"];
		}
		else
		{
			$redirect_to = "/admin/edit/"; // TODO: ID
			$warn += ["記事の更新に失敗しました"];
		}
	}
	else if($mode === "logout")
	{
		// TODO: ログアウト
	}
	else
	{
//		$error = 404; // Not Found
	}
}


// リダイレクト
if(is_string($redirect_to) && strlen($redirect_to) > 0)
{
	// TODO: info, warn
	header("Location: " . $redirect_to);
	die("Redirect");
}

// 圧縮バッファ
ob_start("ob_gzhandler");


// twig
$layout_variables = [
	"config" => $GLOBALS["config"],
	"system" => $GLOBALS["system"]
	];
	

$template = "layout.twig";

if(!is_null($error)) // エラー？
{
	// 404ページ？
}
else if($admin) // 管理ページ？
{
	$layout_variables += ["admin" => TRUE, "embed_ga" => FALSE];
	$template = "admin.twig";
}
else
{
	$layout_variables += ["articles" => Article::all()];
}


print $GLOBALS["twig"]->render($template, $layout_variables);

// MIMEタイプヘッダ出力
if(is_null($mime_type)) $mime_type = "application/xhtml+xml";
header(sprintf("Content-Type: %s; charset=utf-8", $mime_type));

finalize();

// バッファ出力
ob_end_flush();

?>
