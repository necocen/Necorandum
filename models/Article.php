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

	function tags_string()
	{
		return implode(",", array_map(function ($tag) { return $tag->name; }, $this->tags->all()));
	}

	function permalink()
	{
		return "http://" . $GLOBALS["system"]["base"] . "/" . sprintf("%d", $this->id);
	}
}

?>