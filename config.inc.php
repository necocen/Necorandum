<?php
// 設定を読むだけ

$GLOBALS["config"] = Symfony\Component\Yaml\Yaml::parse("config.yml");
$GLOBALS["version"] = "0.1";

?>
