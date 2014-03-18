<?php

class Tag extends ActiveRecord\Model
{
	static $has_many = [["article_tag_relations", "class_name" => "ArticleTagRelation"],
											["articles", "through" => "article_tag_relations", "order" => "created_at desc"]];
}

?>
