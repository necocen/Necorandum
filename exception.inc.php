<?php

// エラーを例外に変換
set_error_handler(function($errno, $errstr, $errfile, $errline) {
	throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

define("NCRD_ERROR_ARTICLE_NOT_FOUND", 1);
define("NCRD_ERROR_SESSION_NOT_START", 2);
define("NCRD_ERROR_TOKEN_NOT_GENERATED", 3);
define("NCRD_ERROR_FUNCTION_NOT_IMPLEMENTED", 4);

class NecorandumException extends Exception
{
	protected $httpErrorCode;
	
	public function __construct($code)
	{
		parent::__construct(self::_codeToMessage($code), $code);
	}

	private static function _codeToMessage($code)
	{
		switch($code)
		{
		case NCRD_ERROR_ARTICLE_NOT_FOUND:
			return "記事が見つかりません。";
		case NCRD_ERROR_SESSION_NOT_START:
			return "セッションの開始に失敗しました。";
		case NCRD_ERROR_TOKEN_NOT_GENERATED:
			return "トークンの生成に失敗しました。";
		case NCRD_ERROR_FUNCTION_NOT_IMPLEMENTED:
			return "まだ実装されていない機能です。";
		default:
			return "不明なエラーです。";
		}
	}

	public function getHttpErrorCode()
	{
		switch($this->code)
		{
		case NCRD_ERROR_ARTICLE_NOT_FOUND:
			return 404;
		case NCRD_ERROR_SESSION_NOT_START:
		case NCRD_ERROR_TOKEN_NOT_GENERATED:
		case NCRD_ERROR_FUNCTION_NOT_IMPLEMENTED:
			return 500;
		default:
			return 404;
		}
	}
}

?>
