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
$id = 0;
$tag_id = 0;
if(array_key_exists("admin", $_GET)) $admin = (intval($_GET["admin"]) === 1);
if(array_key_exists("mode", $_GET)) $mode = strtolower($_GET["mode"]);
if(array_key_exists("id", $_GET)) $id = intval($_GET["id"]);
if($id === 0 && array_key_exists("tagid", $_GET)) $tag_id = intval($_GET["tagid"]);

$redirect_to = NULL;
$error = NULL;
$info = [];
$warn = [];
$mime_type = NULL;

// ここでやるのはリダイレクト系だけ
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
		if(array_key_exists("article-id", $_POST))
		{
			if(update_article($_POST))
			{
				$redirect_to = "/"; // TODO: ID
				$info += ["記事を更新しました"];
			}
			else
			{
				$redirect_to = "/admin/edit/"; // TODO: ID
				$warn += ["記事の更新に失敗しました"];
			}
		}
		else if(array_key_exists("tag-id", $_POST))
		{
			$redirect_to = "/admin";
			$warn += ["実装されていない機能です"];
		}
		else
		{
			$redirect_to = "/admin";
			$warn += ["不正なIDへの編集です"];
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
	if($id != 0)
	{
		// 記事単体ページのレイアウトはちょっと変える可能性がある
	}
	else if($tag_id != 0)
	{
		$template = "layout.twig";
		$tag = Tag::with("articles")->where("id", "=", $tag_id)->first();
		// TODO: ０件のケース
		$layout_variables += ["articles" => $tag->articles()->orderBy("created_at", "desc")->with("tags")->get()];
	}
	else
	{
		$template = "layout.twig";
		$layout_variables += ["articles" => Article::with("tags")->orderBy("created_at", "desc")->get()];
	}
}


print $GLOBALS["twig"]->render($template, $layout_variables);

// MIMEタイプヘッダ出力
if(is_null($mime_type)) $mime_type = "application/xhtml+xml";
header(sprintf("Content-Type: %s; charset=utf-8", $mime_type));

finalize();

// バッファ出力
ob_end_flush();

?>
