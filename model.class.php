<?php

require_once "config.inc.php";

abstract class Model
{
	// いろいろなデータをもちます
	// commonで指定されているもの以外にid(int)をもちます
	protected $data = [];

	// これは新しくつくられたモデルクラスですか？それともデータベースから読まれたものですか？
	protected $is_new = TRUE;

	// この子は削除されました
	protected $is_destroyed = FALSE;

	// テーブルの名前を保存します
	// 継承先で上書きしてネ
	static protected $table_name = "";

	// フィールド。
	// Array("text" => "string", "title" => "string", "is_commentable" => "bool");のように。
	// idはこちらで用意します
	static protected $fields = [];

	// コンストラクタ
	function __construct()
	{
		$argc = func_num_args();
		if($argc === 1 && is_array(func_get_arg(0)))
		{
			$values = func_get_arg(0);
			if (array_key_exists("id", $values))
			{
				// idが渡されている→すでにDB上にある
				$this->is_new = FALSE;
			}
		}
		else $values = NULL;

		$overall_fields = static::$fields + ["id" => "int"];
		foreach($overall_fields as $field => $type)
		{
			if(!is_null($values) && array_key_exists($field, $values) && self::has_appropriate_type($values[$field], $type))
				$this->data[$field] = $values[$field];
			else
				$this->data[$field] = self::default_for_type($type);
		}
		
	}

	// プロパティ
	// こいつは特に継承する必要は無いはず
	public function __get($name)
	{
		if($this->is_destroyed) return NULL;
		if(!$this->is_new && $name === "id") return $this->data["id"];
		else if(array_key_exists($name, $this->data)) return $this->data[$name];
		else return NULL;
	}

	public function __set($name, $value)
	{
		if($this->is_destroyed) return NULL; // もうない

		// 定義されていれば（必要ならばキャストして）代入します
		if(array_key_exists($name, static::$fields))
		{
			 if(self::has_appropriate_type($value, static::$fields[$name]))
				 $this->data[$name] = $value;
			 else
				 $this->data[$name] = self::to_appropriate_type($value, static::$fields[$name]);
		}
	}

	// IDを指定してオブジェクトを探して返します
	// 存在しない場合はNULLを返します
	public static function find($id)
	{
		$result = self::find_all(["where" => ["id = ?", $id]]);
		if(count($result) != 1) return NULL; // 一件だけじゃない場合はなんか失敗しているのでNULL
		else return $result[0];
	}

	//
	// "order_by" => (string) ソートに使用するキー
	//     "desc" =>   (bool) TRUEなら降順、デフォルトはFALSEで昇順
	//    "limit" =>    (int) 返す行数
	//            or  (array) [0]行目から[1]行
	//    "where" =>  (array) [0]の条件に[1]以降の値を順に代入
	public static function find_all($array = NULL)
	{
		// ORDER BY?
		$order_by = "";
		if($array !== NULL && array_key_exists("order_by", $array))
			$order_by = " ORDER BY `" . $array["order_by"] . "`" . ((array_key_exists("desc", $array) && $array["desc"] === TRUE) ? " DESC" : " ASC");

		// WHERE?
		$where = "";
		if($array !== NULL && array_key_exists("where", $array))
		{
			// 引数があってwhere句が指定されている
			if(is_array($array["where"]) && is_string($array["where"][0]))
				// where句は条件文字列と値の配列になっている
				$where = " WHERE (" . strval($array["where"][0]) . ")";
			else if(is_string($array["where"]))
				// where句は単一の文字列である
				$where = " WHERE (" . strval($array["where"]) . ")";
		}

		// LIMIT?
		$limit = "";
		if($array !== NULL && array_key_exists("limit", $array))
		{
			// 引数があってlimit句が指定されている
			if(is_array($array["limit"]))
			{
				// 配列で与えられている場合
				if(array_key_exists(0, $array["limit"]) && array_key_exists(1, $array["limit"]) && is_int($array["limit"][0]) && is_int($array["limit"][1]))
					// 値が二つ与えられている場合
					$limit = " LIMIT " . strval($array["limit"][0]) . ", " . strval($array["limit"][1]);
			}
			else if(is_int($array["limit"]))
				// 数値で与えられている場合
				$limit = " LIMIT " . strval($array["limit"]);
		}

		// プリペアド・ステートメントの生成
		$statement = $GLOBALS["mysql"]->prepare("SELECT * FROM `" . static::table_name() . "`$where$order_by$limit");
		if($statement === FALSE) return []; // プリペアド・ステートメントがうまくできなかった

		if(is_array($array["where"]) && count($array["where"]) > 1)
		{
			// 値の設定と型の指定
			$params = [];
			$types = [];
			$values = array_slice($array["where"], 1);
			
			foreach($values as $value)
			{
				if(is_string($value)) // string
				{
					$params[] = $value;
					$types[] = "s";
				}
				else if($value instanceof DateTime) // 日付ならISO8601型(ex. '2005-08-15T15:52:01+0000')のstring
				{
					$params[] = $value->format(DateTime::ISO8601);
					$types[] = "s";
				}
				else if(is_integer($value)) // integer
				{
					$params[] = $value;
					$types[] = "i";
				}
				else if(is_bool($value)) // BOOLなら0/1のinteger
				{
					$params[] = $value ? 1 : 0;
					$types[] = "i";
				}
				else if(is_float($value) || is_double($value)) // double
				{
					$params[] = (double)$value;
					$types[] = "d";
				}
			}
			
			// ステートメントに値をバインド
			$statement_params[] = implode("", $types);
			for($i = 0; $i < count($params); $i++)
			{
				$statement_params[] = &$params[$i];
			}
			call_user_func_array(array($statement, 'bind_param'), $statement_params);
		}
		
		// ステートメント実行
		if(!$statement->execute()) return;

		// 結果に値をバインド
		$meta = $statement->result_metadata();
		$fields = [];
		while($field = $meta->fetch_field())
		{
			$fields[] = &$row[$field->name];
		}
		call_user_func_array(array($statement, 'bind_result'), $fields);

		// 値をオブジェクトに変換して配列に格納
		$returns = [];
		while($statement->fetch())
		{
			$c = [];
			foreach($row as $key => $val)
				$c[$key] = $val;
			
			$returns[] = new static($c);
		}

		// ステートメント終了
		$statement->close();

		// 返す
		return $returns;
	}

	// 保存
	// だめだった場合FALSEを返します、成功ならTRUE
	public function save()
	{
		if($this->is_destroyed) return FALSE;

		$types = []; // 型のリスト
		$params = []; // パラメータのリスト
		$columns = []; // カラム名のリスト

		foreach(static::$fields as $field => $type)
		{
			if(!array_key_exists($field, $this->data))
				continue;
			
			$value = $this->data[$field];
			if(is_null($value))
				continue;

			$columns[] = $field;
			
			if(is_string($value)) // string
			{
				$params[] = $value;
				$types[] = "s";
			}
			else if($value instanceof DateTime) // 日付ならISO8601型(ex. '2005-08-15T15:52:01+0000')のstring
			{
				$params[] = $value->format(DateTime::ISO8601);
				$types[] = "s";
			}
			else if(is_integer($value)) // integer
			{
				$params[] = $value;
				$types[] = "i";
			}
			else if(is_bool($value)) // BOOLなら0/1のinteger
			{
				$params[] = $value ? 1 : 0;
				$types[] = "i";
			}
			else if(is_float($value) || is_double($value)) // double
			{
				$params[] = (double)$value;
				$types[] = "d";
			}
			else
			{
				// わからんものはとりあえず文字列化
				$param[] = strval($value);
				$types[] = "";
			}
		}

		// ステートメントの作成
		if($this->is_new)
		{
			$query = "INSERT INTO `" . static::table_name() . "` (" . implode(", ", array_map(function($column){ return "`" . $column . "`";}, $columns)) . ") VALUES (" . implode(", ", array_fill(0, count($columns), "?")) . ");";
		}
		else
		{
			$query = "UPDATE `" . static::table_name() . "` SET " . implode(", ", array_map(function($column){return "`" . $column . "` = ?";}, $columns)) . " WHERE `id` = " . strval($this->data["id"]) . ";";
		}
		$statement = $GLOBALS["mysql"]->prepare($query);
		if($statement === FALSE) return FALSE; // プリペアド・ステートメントがうまくできなかった
		
		// ステートメントに値をバインド
		$statement_params[] = implode("", $types);
		for($i = 0; $i < count($params); $i++)
		{
			$statement_params[] = &$params[$i];
		}
		call_user_func_array(array($statement, 'bind_param'), $statement_params);

		// ステートメント実行
		if($statement->execute() !== TRUE) return FALSE;

		// idの附番
		if($this->is_new)
		{
			$this->data["id"] = $statement->insert_id;
		}

		$this->is_new = FALSE; // もはや新しくはない
		
		// 成功したんでしょう
		return TRUE;
	}

	// 削除します
	public function destroy()
	{
		if($this->is_destroyed) return; // 今はもうない

		if($this->is_new) // 新入りかい？
			$this->is_destroyed = TRUE;
		else // ほんとにDELETEします
			$this->is_destroyed = $GLOBALS["mysql"]->query("DELETE FROM `" . static::table_name() . "` WHERE `id` = " . strval($this->data["id"]) . ";");
	}

	// テーブル名を寄越します
	public static function table_name()
	{
		return static::$table_name;
	}
	
	// 型チェック
	final protected static function has_appropriate_type($value, $type)
	{
		if($type === "string" && is_string($value)) return TRUE;
		if($type === "int" && is_int($value)) return TRUE;
		if($type === "bool" && is_bool($value)) return TRUE;
		if($type === "DateTime" && ($value instanceof DateTime)) return TRUE;

		return FALSE;
	}

	// 適切な型にキャストします
	final protected static function to_appropriate_type($value, $type)
	{
		if(self::has_appropriate_type($value, $type)) return $value;
		if($type === "string") return strval($value);
		if($type === "int") return intval($value);
		if($type === "bool") return (intval($value) === 1 ? TRUE : FALSE);
		if($type === "DateTime") return new DateTime(strval($value));
	}
	
	// デフォルト値
	final protected static function default_for_type($type)
	{
		if($type === "string") return "";
		if($type === "int") return 0;
		if($type === "bool") return FALSE;
		if($type === "DateTime") return NULL;
	}
}

?>
