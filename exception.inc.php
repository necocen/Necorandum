<?php

// エラーを例外に変換
set_error_handler(function($errno, $errstr, $errfile, $errline) {
	throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});


class NecorandumException extends Exception
{
	const ArticleNotFound = 1;
	const SessionNotStarted = 2;
	const TokenNotGenerated = 3;
	const FunctionNotImplemented = 4;
	const SearchNotFound = 1;
	
	protected $httpErrorCode;
	
	public function __construct($code)
	{
		parent::__construct(self::_codeToMessage($code), $code);
	}

	private static function _codeToMessage($code)
	{
		switch($code)
		{
		case self::ArticleNotFound:
			return "記事が見つかりません。";
		case self::SearchNotFound:
			return "検索結果に合致する記事はありません。";
		case self::SessionNotStarted:
			return "セッションの開始に失敗しました。";
		case self::TokenNotGenerated:
			return "トークンの生成に失敗しました。";
		case self::FunctionNotImplemented:
			return "まだ実装されていない機能です。";
		default:
			return "不明なエラーです。";
		}
	}

	public function getHttpErrorCode()
	{
		switch($this->code)
		{
		case self::ArticleNotFound:
		case self::SearchNotFound:
			return 404;
		default:
			return 500;
		}
	}
}

?>
