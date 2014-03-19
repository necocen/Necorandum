<?php

class ArticleTagRelation extends ActiveRecord\Model
{
	static $table_name = "article_tag_relations";
	static $belongs_to = [["article"], ["tag"]];
}

?>
