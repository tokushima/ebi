<?php
namespace ebi;

/**
 * #[Parameter] / #[Response] の type に指定できる値型（Type）。
 *
 * \ebi\Validator が扱えるスカラ型に限定した正準トークン集合。type の許可値を型として定義する
 * （呼び出し側は文字列 'int' 等でも可＝後方互換。クラス型は \Foo\Bar::class を渡す）。
 *
 * エイリアスは正準へ集約（Validator/OpenApi は両方解釈するため挙動不変）:
 *   integer → Int / boolean → Bool / number → Float / timestamp → Datetime
 */
enum T: string{
	case String   = 'string';
	case Text     = 'text';
	case Int      = 'int';
	case Float    = 'float';
	case Bool     = 'bool';
	case Datetime = 'datetime';
	case Date     = 'date';
	case Time     = 'time';
	case Intdate  = 'intdate';
	case Serial   = 'serial';
	case Email    = 'email';
	case Alnum    = 'alnum';
	case File     = 'file';
	case Mixed    = 'mixed';
	case Arr      = 'array';
	case Map      = 'map';
}
