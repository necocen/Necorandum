<?php

class Article extends ActiveRecord\Model
{
	static $has_many = [["article_tag_relations", "class_name" => "ArticleTagRelation"],
											["tags", "through" => "article_tag_relations", "order" => "name asc"]];
}

?>