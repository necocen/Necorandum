<?php

function create_article($post)
{
	if(!isset($post["article-text"])) return FALSE;
	
	$article = new Article();
	$article->title = strval(isset($post["article-title"]) ? $post["article-title"] : "");
	$article->text = strval($post["article-text"]);
	if(!$article->save()) return FALSE;

	$tag_ids = tag_ids(isset($post["article-tags"]) ? $post["article-tags"] : "");

	$article->tags()->attach($tag_ids);

	return $article->save();
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
	if(!isset($post["article-id"])) return FALSE;
	if(!isset($post["article-text"])) return FALSE;
	
	$article = Article::find(intval($post["article-id"]));

	if(is_null($article)) return FALSE;
	
	$article->title = strval(isset($post["article-title"]) ? $post["article-title"] : "");
	$article->text = strval($post["article-text"]);


	$tag_ids = tag_ids(isset($post["article-tags"]) ? $post["article-tags"] : "");

	$article->tags()->sync($tag_ids);

	return $article->save();
}

?>
