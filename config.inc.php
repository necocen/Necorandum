<?php
// 設定を読むだけ

$GLOBALS["config"] = yaml_parse_file("config.yml");
$GLOBALS["version"] = "0.1";

?>
