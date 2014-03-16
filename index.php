<?php

require_once "vendor/autoload.php"; // for composer, twig
require_once "config.inc.php";
require_once "common.inc.php";
require_once "article.class.php";

if(!init_necorandum())
{
	header(sprintf("HTTP/1.1 500 %s", "neko"));
	die("fatal");
}

// 圧縮バッファ
ob_start("ob_gzhandler");

$mt = new MtHaml\Environment("twig", ["enable_escaper" => FALSE, "format" => "xhtml"]);

$twig_loader = new MtHaml\Support\Twig\Loader($mt, new Twig_Loader_Filesystem("./templates"));
$twig = new Twig_Environment($twig_loader, ['cache' => FALSE]);
$twig->addExtension(new MtHaml\Support\Twig\Extension());

$parsedown = new Parsedown();
$parsedown->setBreaksEnabled(TRUE);

// parsedown+α フィルタ
$parsedown_filter = new Twig_SimpleFilter("parsedown", function ($string) use($parsedown) {
	// parsedown
	$string = $parsedown->parse($string);

	// 見出しレベルの調整
	$string = str_replace("<h3>", "<h5>", $string);
	$string = str_replace("</h3>", "</h5>", $string);
	$string = str_replace("<h2>", "<h4>", $string);
	$string = str_replace("</h2>", "</h4>", $string);
	$string = str_replace("<h1>", "<h3>", $string);
	$string = str_replace("</h1>", "</h3>", $string);

	// XHTMLではpreタグの先頭改行が表示されてしまうので消す
	$string = str_replace("\n</pre>", "</pre>", str_replace("<pre>\n", "<pre>", $string));

	return $string;
});
$twig->addFilter($parsedown_filter);


$layout_variables = [
	"base" => base_url(),
	"ga_code" => $GLOBALS["config"]["site"]["ga_code"],
	"title" => $GLOBALS["config"]["site"]["title"],
	"author" => $GLOBALS["config"]["site"]["author"],
	"articles" => Article::all()
	];

print $twig->render("layout.haml", $layout_variables);

$mime_type = NULL;

// MIMEタイプヘッダ出力
if(is_null($mime_type)) $mime_type = "application/xhtml+xml";
header(sprintf("Content-Type: %s; charset=utf-8", $mime_type));

finalize();

// バッファ出力
ob_end_flush();

?>
