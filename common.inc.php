<?php
require_once "config.inc.php";

// 初期化します
function init_necorandum()
{
	// set timezone
	date_default_timezone_set("Asia/Tokyo");
	
	// authenticate
//	$GLOBALS["system"]["authenticated"] = FALSE;

	$use_parsedown = TRUE;

	// Ardent
	\LaravelBook\Ardent\Ardent::configureAsExternal([
		"driver"    => "mysql",
		"host"      => $GLOBALS["config"]["db"]["server"],
		"port"      => 3306,
		"database"  => $GLOBALS["config"]["db"]["name"],
		"username"  => $GLOBALS["config"]["db"]["user"],
		"password"  => $GLOBALS["config"]["db"]["password"],
		"charset"   => "utf8",
		"collation" => "utf8_unicode_ci"]);

	// twig(debug)
	$twig_loader = new Twig_Loader_Filesystem("./templates");
	$twig = new Twig_Environment($twig_loader, ["cache" => FALSE, "debug" => TRUE]);
	$twig->addExtension(new Twig_Extension_Debug());

	// Parsedown or PHP Markdown+Extra
	if($use_parsedown)
	{
		$parser = new Parsedown();
		$parser->setBreaksEnabled(TRUE);
	}
	else
	{
		$parser = new Michelf\MarkdownExtra;
	}
	
	// markdown+α フィルタ
	$parsedown_filter = new Twig_SimpleFilter("markdown", function ($string) use($parser, $use_parsedown) {

		// parsedown
		if($use_parsedown)
		{
			$string = $parser->parse($string);
		}
		else
		{
			$string = $parser->transform($string);
		}
		
		// 見出しレベルの調整
		$string = str_replace("<h3>", "<h4>", $string);
		$string = str_replace("</h3>", "</h4>", $string);
		$string = str_replace("<h2>", "<h3>", $string);
		$string = str_replace("</h2>", "</h3>", $string);
		$string = str_replace("<h1>", "<h2>", $string);
		$string = str_replace("</h1>", "</h2>", $string);
		
		// XHTMLではpreタグの先頭改行が表示されてしまうので消す(Parsedownのみ)
		if($use_parsedown)
		{
			$string = str_replace("\n</pre>", "</pre>", str_replace("<pre>\n", "<pre>", $string));
		}
		
		return $string;
	});
	
	$twig->addFilter($parsedown_filter);

	$twig->getExtension("core")->setDateFormat("Y-m-d H:i:s", "%d days");
	$GLOBALS["twig"] = $twig;
	

	// 初回起動ですか？
//	$GLOBALS["system"]["first"] = !has_config_table();

	// ベースアドレス
	$path_info = pathinfo($_SERVER["SCRIPT_NAME"]);
	$dir_name = "";
	if(isset($path_info["dirname"]))
		$dir_name = $path_info["dirname"];
	if($dir_name !== "/" && $dir_name !== "\\")
		$GLOBALS["system"]["base"] =  $_SERVER["SERVER_NAME"] . $dir_name;
	else
		$GLOBALS["system"]["base"] =  $_SERVER["SERVER_NAME"];
	$GLOBALS["system"]["host"] = $_SERVER["SERVER_NAME"];

	// 設定読み込み（ただし初回起動時は無視）
//	if(!$GLOBALS["system"]["first"])
//		load_config();

	// 期限切れトークン削除
//	clear_token();

	$password = NULL;
	if(array_key_exists("admin", $GLOBALS["config"]) && array_key_exists("password", $GLOBALS["config"]["admin"]))
		$password = strval($GLOBALS["config"]["admin"]["password"]);

	$article = Article::first();

	$configuration = Configuration::first();
	if(!$configuration)
	{
		$configuration = new Configuration();
		if(is_null($password)) return FALSE;
	}

	// config.ymlにパスワードがあったら上書きする
	if(!is_null($password))
	{
		$configuration->password = blowfish($password);
		$configuration->save();
	}
	
	return TRUE;
}


function finalize()
{
}

// ハッシュ
function blowfish($string)
{
	return crypt($string, $GLOBALS["config"]["system"]["blowfish_salt"]);
}

?>
