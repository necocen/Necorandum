<?php
require_once "config.inc.php";

// 初期化します
function init_necorandum()
{
	// set timezone
	date_default_timezone_set("Asia/Tokyo");

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

	// セッション・ハンドラ
	if(!session_set_save_handler(new MySQLSessionHandler(), TRUE))
	{
		return FALSE;
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

class MySQLSessionHandler implements SessionHandlerInterface
{
	public function open($save_path, $name)
	{
		return TRUE;
	}

	public function close()
	{
		return TRUE;
	}

	public function destroy($session_id)
	{
		if(!is_string($session_id)) return FALSE;

		return Session::destroy($session_id);
	}

	public function read($session_id)
	{
		$session = Session::find($session_id);
		if(is_null($session)) return "";
		return $session->data;
	}

	public function write($session_id, $session_data)
	{
		$session = Session::find($session_id);
		if(is_null($session))
		{
			$session = new Session();
			$session->id = $session_id;
		}
		$session->data = $session_data;
		$session->touch();
		return $session->save();
	}

	public function gc($maxlifetime)
	{
		return Session::where('updated_at', '<', (new DateTime())->sub(new DateInterval(strval($maxlifetime) . "S")))->delete();
	}
}

?>
