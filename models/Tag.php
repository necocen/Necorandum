<?php

class Tag extends \LaravelBook\Ardent\Ardent
{
	public static $relationsData = [
		"articles" => [self::BELONGS_TO_MANY, "Article", "table" => "article_tag_relations"]
		];

	public static $rules = [
		"name" => "required|min:1"
		];

}

?>
