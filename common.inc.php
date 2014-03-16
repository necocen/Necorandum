<?php
require_once "config.inc.php";

// 初期化します
function init_necorandum()
{
	// set timezone
	date_default_timezone_set("Asia/Tokyo");
	
	// authenticate
//	$GLOBALS["system"]["authenticated"] = FALSE;

	/*
	$GLOBALS["parsedown"] = new Parsedown();
	$GLOBALS["parsedown"]->setBreaksEnabled(TRUE);
		*/
	
	// データベース設定
	$GLOBALS["mysql"] = mysqli_init();

	// 接続
	if(!$GLOBALS["mysql"]->real_connect($GLOBALS["config"]["db"]["server"],
																			$GLOBALS["config"]["db"]["user"],
																			$GLOBALS["config"]["db"]["password"],
																			$GLOBALS["config"]["db"]["name"])) return FALSE;
	$GLOBALS["mysql"]->set_charset("utf8");

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
	$GLOBALS["mysql"]->close();
}

function base_url()
{
	return $GLOBALS["system"]["base"];
}

// HTML special chars
function h($text)
{
	return htmlspecialchars($text, ENT_QUOTES, "UTF-8");
}

?>
