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

	// active record
	$mysql_address = "mysql://" .
		$GLOBALS["config"]["db"]["user"] . ":" .
			$GLOBALS["config"]["db"]["password"] . "@" .
				$GLOBALS["config"]["db"]["server"] . "/" .
					$GLOBALS["config"]["db"]["name"] . "?charset=utf8";

	ActiveRecord\Config::initialize(
		function($cfg) use ($mysql_address)
		{
			$cfg->set_model_directory("./models");
			$cfg->set_connections(["production" => $mysql_address]);
			$cfg->set_default_connection("production");
		});
	

	// twig
	$twig_loader = new Twig_Loader_Filesystem("./templates");
	$twig = new Twig_Environment($twig_loader, ['cache' => FALSE]);

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
		$string = str_replace("<h3>", "<h5>", $string);
		$string = str_replace("</h3>", "</h5>", $string);
		$string = str_replace("<h2>", "<h4>", $string);
		$string = str_replace("</h2>", "</h4>", $string);
		$string = str_replace("<h1>", "<h3>", $string);
		$string = str_replace("</h1>", "</h3>", $string);
		
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
	
	return TRUE;
}

function finalize()
{
}


// HTML special chars
function h($text)
{
	return htmlspecialchars($text, ENT_QUOTES, "UTF-8");
}

?>
