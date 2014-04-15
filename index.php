<?php

require_once "vendor/autoload.php"; // for composer, twig
require_once "config.inc.php";
require_once "exception.inc.php";
require_once "common.inc.php";
require_once "controller.inc.php";

if(!init_necorandum())
{
	http_header(500);
	die("fatal");
}

try
{
	$mode = NULL;
	$admin = FALSE;
	$error = NULL;
	$ajax = NULL;
	$id = 0;
	$tag_id = 0;
	$page = 1;
	if(array_key_exists("error", $_GET)) $error = intval($_GET["error"]);
	if(array_key_exists("admin", $_GET)) $admin = (intval($_GET["admin"]) === 1);
	if(array_key_exists("mode", $_GET)) $mode = strtolower($_GET["mode"]);
	if(array_key_exists("id", $_GET)) $id = intval($_GET["id"]);
	if($id === 0 && array_key_exists("tagid", $_GET)) $tag_id = intval($_GET["tagid"]);
	if(array_key_exists("page", $_GET)) $page = max(1, intval($_GET["page"]));

	// ajax
	if(array_key_exists("ajax", $_POST)) $ajax = strtolower($_POST["ajax"]);
	
	$redirect_to = NULL;
	$info = [];
	$warn = [];

	$layout_variables = [
		"config" => $GLOBALS["config"],
		"system" => $GLOBALS["system"],
		];

	if(!is_null($error))
	{
		$layout_variables += ["error" => TRUE, "message" => http_error_message($error), "status_code" => $error];
		header("Content-Type: text/html; charset=utf-8");
		header("Content-Script-Type: text/javascript");
		header("Content-Style-Type: text/css");
		
		print $GLOBALS["twig"]->render("layout_error.twig", $layout_variables);
		die();
	}

	if(!is_null($ajax))
	{
		if($ajax === "preview")
		{
			set_backup($_POST);
			$article = new Article();
			$article->title = $_SESSION["backup"]["title"];
			$article->text = $_SESSION["backup"]["text"];
			$tags = array_map("trim", explode(",", $_SESSION["backup"]["tags"]));
			header("Content-Type: text/html; charset=utf-8");
			print $GLOBALS["twig"]->render("article.twig", ["preview" => TRUE, "article" => $article, "tags" => $tags]);
		}
		else if($ajax === "backup")
		{
			set_backup($_POST);
		}
		else if($ajax === "restore")
		{
			if(array_key_exists("backup", $_SESSION) && !is_null($_SESSION["backup"]))
			{
				header("Content-Type: application/json; charset=utf-8");
				echo json_encode($_SESSION["backup"]);
			}
		}
		else if($ajax === "delete-backup")
		{
			delete_backup();
		}
		
		die();
	}
	
	// クッキーでログイン判定
	if(array_key_exists("password", $_COOKIE))
	{
		if($_COOKIE["password"] == Configuration::first()->password)
		{
			$_SESSION["login"] = TRUE;
			// クッキー延命
			setcookie("password", $_COOKIE["password"], time() + 86400 * $GLOBALS["config"]["system"]["cookie_expire_date"], "/");
		}
		else
		{
			// お前は誰なんだ
			$_SESSION["login"] = FALSE;
			setcookie("password", "", time() - 1, "/");
		}
	}
	
	// ここでやるのはリダイレクト系だけ
	if($admin)
	{
		if(!is_null($mode) && !$_SESSION["login"]) // 未ログイン時のnew/editもここにくる
		{
			$redirect_to = "/admin";
			$warn += ["ログインしていません"];
		}
		else if($mode === "create")
		{
			if(create_article($_POST))
			{
				$redirect_to = "/";
				if(isset($_POST["article-draft"]) && intval($_POST["article-draft"]) === 1)
				{
					$info += ["下書きを投稿しました"];
				}
				else
				{
					$info += ["記事を投稿しました"];
				}
				// バックアップ削除
				delete_backup();
			}
			else
			{
				$redirect_to = "/admin/new";
				$warn += ["記事の投稿に失敗しました"];
				// バックアップ保存
				set_backup($_POST);
			}
		}
		else if($mode === "update")
		{
			if(array_key_exists("article-id", $_POST))
			{
				$id = intval($_POST["article-id"]);
				if(update_article($_POST))
				{
					if(isset($_POST["article-draft"]) && intval($_POST["article-draft"]) === 1)
					{
						// 下書きの場合はトップへ
						$redirect_to = "/";
						$info += ["下書きを更新しました"];
					}
					else
					{
						$redirect_to = "/" . strval($id);
						$info += ["記事を更新しました"];
					}
					// バックアップ削除
					delete_backup();
				}
				else
				{
					$redirect_to = "/admin/edit/" . strval($id);
					$warn += ["記事の更新に失敗しました"];
					// バックアップ保存
					set_backup($_POST);
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
				$warn += ["不正な編集です"];
			}
		}
		else if($mode === "update_config")
		{
			if(update_config($_POST))
			{
				$redirect_to = "/admin";
				$info += ["設定を更新しました"];
			}
			else
			{
				$redirect_to = "/admin/config";
				$warn += ["設定の更新に失敗しました"];
			}
		}
		else if($mode === "delete")
		{
			if(array_key_exists("article-id", $_POST))
			{
				if(delete_article($_POST))
				{
					$redirect_to = "/admin";
					$info += ["記事を削除しました"];
				}
				else
				{
					$redirect_to = "/admin/edit/" . intval($_POST["article-id"]);
					$warn += ["記事の削除に失敗しました"];
				}
			}
			else
			{
				$redirect_to = "/admin";
				$warn += ["不正な編集です"];
			}
		}
		else if($mode === "logout")
		{
			$redirect_to = "/";
			$_SESSION["login"] = FALSE;
			setcookie("password", "", time() - 1, "/");
			$info += ["ログアウトしました"];
		}
	}
	else if($mode === "login")
	{
		$redirect_to = "/admin";
		if(blowfish($_POST["login-password"]) === Configuration::first()->password)
		{
			$_SESSION["login"] = TRUE;
			setcookie("password", Configuration::first()->password, time() + 86400 * $GLOBALS["config"]["system"]["cookie_expire_date"], "/");
			$info += ["ログインしました"];
		}
		else
		{
			$_SESSION["login"] = FALSE;
			$warn += ["ログインに失敗しました"];
		}
	}
	
	// リダイレクト
	if(is_string($redirect_to) && strlen($redirect_to) > 0)
	{
		$_SESSION["info"] = $info;
		$_SESSION["warn"] = $warn;
		header("Location: " . $redirect_to);
		finalize();
		die();
	}
	
	if(isset($_SESSION["info"]) && is_array($_SESSION["info"])) $info = $_SESSION["info"];
	if(isset($_SESSION["warn"]) && is_array($_SESSION["warn"])) $warn = $_SESSION["warn"];
	$_SESSION["info"] = [];
	$_SESSION["warn"] = [];
	
	// 圧縮バッファ
	ob_start("ob_gzhandler");

	header("Content-Type: text/html; charset=utf-8");
	header("Content-Script-Type: text/javascript");
	header("Content-Style-Type: text/css");

	$layout_variables += ["embed_ga" => TRUE, "login" => isset($_SESSION["login"]) ? $_SESSION["login"] : FALSE];

	if($admin) // 管理ページ？
	{
		// 未ログインでは$modeがNULLの場合しか来ない
		$layout_variables += ["admin" => TRUE];
		$layout_variables["embed_ga"] = FALSE;
		$template = "layout_admin.twig";
		if($mode === "new")
		{
			$token = generate_token();
			$layout_variables += ["token" => $token];
			$template = "layout_admin_article.twig";
		}
		else if($mode === "edit" && $id != 0)
		{
			$token = generate_token();
			$layout_variables += ["token" => $token];
			$template = "layout_admin_article.twig";
			$article = Article::with("tags")->find($id);
			if(is_null($article))
			{
				$warn += ["存在しない記事を編集しようとしています"];
			}
			else
			{
				$layout_variables += ["article" => $article];
			}
		}
		else if($mode === "tag" && $tag_id != 0)
		{
			// TODO: タグの編集
			throw new NecorandumException(NecorandumException::FunctionNotImplemented);
		}
		else if($mode === "config")
		{
			$token = generate_token();
			$layout_variables += ["token" => $token];
			$template = "layout_admin_config.twig";
		}
		else if($mode === "drafts")
		{
			$drafts = Article::where("draft", 1)->orderBy("created_at", "desc")->get();
			$layout_variables += ["drafts" => $drafts];
			$template = "layout_admin_drafts.twig";
		}
	}
	else
	{
		$tags = Tag::orderBy("name", "asc")->get();
		$layout_variables += ["tags" => $tags];
		if($id != 0)
		{
			// 記事単体ページのレイアウトはちょっと変える可能性がある
			$template = "layout_article.twig";
			$article = Article::where("draft", 0)->with("tags")->find($id);
			if(is_null($article)) throw new NecorandumException(NecorandumException::ArticleNotFound);
			$layout_variables += ["articles" => [$article], "solely" => TRUE];
		}
		else if($tag_id != 0)
		{
			$template = "layout_tag.twig";
			$tag = Tag::with("articles")->where("id", "=", $tag_id)->first();
			if(is_null($tag)) throw new NecorandumException(NecorandumException::ArticleNotFound);
			$app = $GLOBALS["config"]["system"]["articles_per_page"];
			$articles = $tag->articles()->where("draft", 0)->orderBy("created_at", "desc")->take($app)->skip(($page - 1) * $app)->with("tags")->get();

			$count = $tag->articles()->where("draft", 0)->count();
			$layout_variables += paginator($count, $page, sprintf("/tag/%d", $tag_id));
			
			if(count($articles) === 0) throw new NecorandumException(NecorandumException::ArticleNotFound);
			$layout_variables += ["articles" => $articles, "tag" => $tag, "page" => $page];
		}
		else
		{
			$template = "layout.twig";
			$app = $GLOBALS["config"]["system"]["articles_per_page"];
			$articles = Article::where("draft", 0)->with("tags")->orderBy("created_at", "desc")->take($app)->skip(($page - 1) * $app)->get();

			$count = Article::where("draft", 0)->count();
			$layout_variables += paginator($count, $page);
			if(count($articles) === 0) throw new NecorandumException(NecorandumException::ArticleNotFound);
			$layout_variables += ["articles" => $articles];
		}
	}

	$layout_variables += ["info" => $info, "warn" => $warn];
	print $GLOBALS["twig"]->render($template, $layout_variables);
}
catch(NecorandumException $e)
{
	http_header($e->getHttpErrorCode());
	$layout_variables += ["error" => TRUE, "status_code" => $e->getHttpErrorCode(), "message" => $e->getMessage()];
	print $GLOBALS["twig"]->render("layout_error.twig", $layout_variables);
}
catch(Exception $e)
{
	http_header(500);
//	$layout_variables += ["error" => TRUE, "status_code" => 500, "message" => "システム・エラーが発生しました。"];
		$layout_variables += ["error" => TRUE, "status_code" => 500, "message" => $e->getMessage()];
	print $GLOBALS["twig"]->render("layout_error.twig", $layout_variables);
}

finalize();

// バッファ出力
ob_end_flush();
?>
