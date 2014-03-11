<?php

require_once "config.inc.php";
require_once "common.inc.php";

function indent($text, $count)
{
	$tab = "";
	for($i = 0; $i < $count; $i++)	$tab .= "\t";
	return $tab . str_replace("\n", "\n" . $tab, $text);
}

function xml_declaration()
{
	return sprintf("<%sxml version=\"1.0\" encoding=\"UTF-8\"%s>\n", "?", "?");
}

function ga_script($ga_code)
{
	$output = <<<EOM

		<script type="text/javascript">
//<![CDATA[
var _gaq = _gaq || [];
_gaq.push(['_setAccount', '{$ga_code}']);
_gaq.push(['_trackPageview']);

(function() {
	var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
	ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
})();
// ]]>
		</script>
EOM
;
	return $output;
}

function layout($head, $contents, $foot)
{
	$head_indented = indent($head, 4);
	$contents_indented = indent($contents, 4);
	$title = h($GLOBALS["config"]["site"]["title"]);
	$base = base_url();
	$ga = ga_script($GLOBALS["config"]["site"]["ga_code"]);
	$version = $GLOBALS["version"];
	$author = h($GLOBALS["config"]["site"]["author"]);
	$email = h($GLOBALS["config"]["site"]["email"]);
	$description = h($GLOBALS["config"]["site"]["description"]);
	$res = xml_declaration();
	$res .= <<<EOM
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja">
	<head>
		<meta name="ROBOTS" content="INDEX, FOLLOW" />
		<meta name="generator" content="Necorandum {$version}" />
		<meta name="description" content="{$description}" />
		<meta name="author" content="{$author}" />
		<meta name="reply-to" content="{$email}" />
		<base href="http://{$base}/" />
		<link rev="made" href="mailto:{$email}" />
		<link rel="start contents index" href="./" title="トップページ" />
		<link rel="alternate" href="./atom" type="application/atom+xml" title="Atom1.0" />
		<link rel="alternate" href="./rss2" type="application/rss+xml" title="RSS2.0" />
		<link rel="stylesheet" href="./css/main.css" type="text/css" media="screen" />
		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/2.0.0/jquery.min.js"></script>
		<script type="text/javascript" src="./main.js"></script>{$ga}
		<title>{$title}</title>
	</head>
	<body>
		<div id="container">
			<div id="head">
				<div id="title">
					<h1>{$title}</h1>
				</div>
				<div id="menu">
					
				</div>
			</div>
			<hr />
			<div id="main">
{$contents_indented}
			</div>
			<hr />
			<div id="foot">
				<address>presented by {$author} under CC0.</address>
			</div>
		</div>
	</body>
</html>

EOM
;

// blockquote中の改行(\rにしてインデント回避)を\nに戻す
return str_replace("\r", "\n", $res);
}

?>
