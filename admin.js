(function(){

	/// [リストア]を表示する<li>要素
	var restoreLabel = null;
	
	/// 入力内容を送りつける
	function sendEditingText(preview) {
		$.ajax({
		type: "POST",
		url: "./",
		data: {
		ajax: (preview ? "preview" : "backup"),
			"article-title": $("input#article-title").val(),
			"article-tags": $("input#article-tags").val(),
			"article-text": $("textarea#article-text").val()},
		dataType: "html",
		success: function(data, status, xhr) {
			// 必要ならここで
		}
		});
	};

	function deleteBackup() {
		$.ajax({
		type: "POST",
		url: "./",
		data: {ajax: "delete-backup"},
		dataType: "text"
		});
	};

	/// バックアップの有無を調べて存在すればリストアボタンを表示する
	function requestBackup() {
		$.ajax({
		type: "POST",
		url: "./",
		data: {ajax: "restore"},
		dataType: "json",
		success: function(data, status, xhr) {
			if(data)
			{
				if($("aside#info").length === 0)
				{
					$("div#main").prepend("<aside id=\"info\"><ul></ul></aside>");
				}
				restoreLabel = $("<li>バックアップされた内容があります：</li>");
				var a = $("<a href=\"javascript:void(0)\">リストア</a>").on("click", function(e){
					$("input#article-title").val(data.title);
					$("input#article-tags").val(data.tags);
					$("textarea#article-text").val(data.text);
					cleanUpRestoreLabel();
				});
				var b = $("<a href=\"javascript:void(0)\">削除</a>").on("click", function(e){
					deleteBackup();
					cleanUpRestoreLabel();
				});
				$("aside#info ul").append(restoreLabel.append(a).append(" / ").append(b));
			}
		}
		});
	};

	/// リストアボタンを消去
	function cleanUpRestoreLabel()
	{
		if(restoreLabel === null) return;
		if(restoreLabel.siblings.length === 1)
		{
			restoreLabel.parent().parent().remove();
		}
		else
		{
			restoreLabel.remove();
		}
		restoreLabel = null;
	};
	
	$(document).ready(function(){
		
		if($("input#article-title").length > 0) // 記事編集画面のみ
		{
			setInterval(function(){
				var articleTitle = $("input#article-title").val();
				var articleTags = $("input#article-tags").val();
				var articleText = $("textarea#article-text").val();
				return function(e) {
					var newArticleTitle = $("input#article-title").val();
					var newArticleTags = $("input#article-tags").val();
					var newArticleText = $("textarea#article-text").val();
					if(articleTitle !== newArticleTitle ||
						 articleTags !== newArticleTags ||
						 articleText !== newArticleText)
					{
						sendEditingText(false);
						cleanUpRestoreLabel();
					}
					articleTitle = newArticleTitle;
					articleTags = newArticleTags;
					articleText = newArticleText;
				}
			}(), 5000);
			requestBackup();
		}
	});
})();
