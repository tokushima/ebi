<?php
namespace ebi;

/**
 * #[Parameter] / #[Response] / #[VarAttr] の type に指定できる値型（Type）。
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

	/**
	 * この type トークンの値が \ebi\Validator 通過後に持つ PHP の型名を返す。
	 *
	 * File だけは Validator のスカラ経路を通らないため mixed。
	 * @see \ebi\Attribute\VarAttr  モデルプロパティ宣言の規約
	 */
	public function phpType(): string{
		return match($this){
			self::String, self::Text, self::Email, self::Alnum      => 'string',
			self::Int, self::Datetime, self::Date, self::Time,
			self::Intdate, self::Serial                             => 'int',
			self::Float                                             => 'float',
			self::Bool                                             => 'bool',
			self::Arr, self::Map                                   => 'array',
			self::File, self::Mixed                                => 'mixed',
		};
	}
}
