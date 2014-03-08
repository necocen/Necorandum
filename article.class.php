<?php
require_once "config.inc.php";
require_once "model.class.php";

class Article extends Model
{
	static protected $table_name = "articles";
	static protected $fields = ["title" => "string",
															"text" => "string",
															"created_at" => "DateTime",
															"updated_at" => "DateTime"];

	// 指定されたページの記事を配列で返します。
	// ページが指定されない(NULL)場合は全部返します。
	public static function articles($page = NULL)
	{
		return self::articles_with_tag(NULL, $page);
	}

	// 指定されたタグの記事のうち、指定されたページのものを配列で返します。
	// タグが指定されない(NULL)場合は全部のタグを対象とします。
	// ページが指定されない(NULL)場合は全件返します。
	// ページは1-based.
	public static function articles_with_tag($tag = NULL, $page = NULL)
	{
		$limit = [];
		$where = NULL;
		/*
		if(!is_null($tag))
		{
			$where = [""];
		}
			*/
		if(!is_null($page) && $page > 0)
		{
			$number_per_page = $GLOBALS["config"]["system"]["articles_per_page"];
			$limit = [$number_per_page * ($page - 1), $number_per_page];
		}

		return self::find_all(["order_by" => "created_at", "desc" => TRUE] + $limit);
	}

	// タグの配列を返します。
	public function tags()
	{
		return []; // to be implemented.
	}
}
?>
