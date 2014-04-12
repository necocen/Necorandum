<?php

function create_article($post)
{
	if(!isset($post["token"]) || !check_token($post["token"])) return FALSE;
	if(!isset($post["article-text"])) return FALSE;

	try
	{
		$article = new Article();
		$article->title = strval(isset($post["article-title"]) ? $post["article-title"] : "");
		$article->text = strval($post["article-text"]);
		$article->draft = isset($post["article-draft"]) ? intval($post["article-draft"]) : 0;
		if(!$article->save()) return FALSE;
		
		$tag_ids = tag_ids(isset($post["article-tags"]) ? $post["article-tags"] : "");
		
		$article->tags()->attach($tag_ids);
		
		return $article->save();
	}
	catch(Exception $e)
	{
		return FALSE;
	}
}


/// タグ指定文字列（コンマ区切り）からタグのIDの配列を返します。
/// 該当するタグがなければ自分で作ります。
function tag_ids($tags_string)
{
	return array_unique(array_filter(array_map(function($string) {
		$tag_name = trim($string);
		if(strlen($tag_name) === 0) return NULL;
		$tag = Tag::where("name", $tag_name)->first();
		
		if($tag)
			return $tag->id;
		
		$tag = new Tag();
		$tag->name = $tag_name;
		if($tag->save())
			return $tag->id;
		
		return NULL;
	}, explode(",", $tags_string)), function($val) { return !is_null($val); }), SORT_NUMERIC);
}

function update_article($post)
{
	if(!isset($post["token"]) || !check_token($post["token"])) return FALSE;
	if(!isset($post["article-id"])) return FALSE;
	if(!isset($post["article-text"])) return FALSE;

	try
	{
		$article = Article::find(intval($post["article-id"]));
		
		if(is_null($article)) return FALSE;
		
		$article->title = strval(isset($post["article-title"]) ? $post["article-title"] : "");
		$article->text = strval($post["article-text"]);
		$article->draft = isset($post["article-draft"]) ? intval($post["article-draft"]) : 0;
		
		$tag_ids = tag_ids(isset($post["article-tags"]) ? $post["article-tags"] : "");
		
		$article->tags()->sync($tag_ids);
		
		return $article->save();
	}
	catch(Exception $e)
	{
		return FALSE;
	}
}

function delete_article($post)
{
	if(!isset($post["token"]) || !check_token($post["token"])) return FALSE;
	if(!isset($post["article-id"])) return FALSE;

	try
	{
		return Article::destroy(intval($post["article-id"]));
	}
	catch(Exception $e)
	{
		return FALSE;
	}
}

function update_config($post)
{
	if(!isset($post["token"]) || !check_token($post["token"])) return FALSE;
	if(!isset($post["password-old"])) return FALSE;
	if(!isset($post["password-new"])) return FALSE;
	if(!isset($post["password-confirm"])) return FALSE;

	if($post["password-new"] !== $post["password-confirm"]) return FALSE;

	$config = Configuration::first();
	if(blowfish($post["password-old"]) !== $config->password) return FALSE;

	$config->password = blowfish($post["password-new"]);
	setcookie("password", $config->password, time() + 86400 * $GLOBALS["config"]["system"]["cookie_expire_date"], "/");

	return $config->save();
}

?>
