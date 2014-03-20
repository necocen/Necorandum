<?php

class Article extends \LaravelBook\Ardent\Ardent
{
	public static $relationsData = [
		"tags" => [self::BELONGS_TO_MANY, "Tag", "table" => "article_tag_relations"]
		];

	public static $rules = [
		"title" => "required|min:1",
		"text" => "required|min:1"
		];
}

?>