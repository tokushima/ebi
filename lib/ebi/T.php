<?php
namespace ebi;

/**
 * #[Parameter] / #[Response] / #[VarAttr] の type に指定できる値型（Type）。
 *
 * \ebi\Validator が扱えるスカラ型に限定した正準トークン集合。type の許可値を型として定義する
 * （呼び出し側は文字列 'int' 等でも可＝後方互換。クラス型は \Foo\Bar::class を渡す）。
 *
 * この enum が表す「型」の投影は3つ。混同しないこと:
 *   - value（'datetime' 等のトークン）: OpenAPI スキーマ生成と \ebi\Validator のディスパッチに使う。
 *   - phpType(): 値が \ebi\Validator 通過後に持つ PHP 型（int/string/bool/float/array/mixed）。
 *     Parameter/Response/Obj/Dao いずれも Validator を通るので汎用。DB 方言には依存しない。
 *   - DB カラム型（bool→INT(1) 等）: T の関知外。DB 方言依存でコネクタが算出する（daoType 等は持たない）。
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

	/**
	 * この type トークンの値が \ebi\Validator 通過後に持つ PHP の型名を返す。
	 *
	 * #[Parameter] / #[Response] / #[VarAttr] はいずれも値を \ebi\Validator::type で正規化するため
	 * （app\Request / \ebi\Obj / \ebi\Dao 共通）、この対応はモデルに限らず汎用に使える。
	 * datetime/date/time/intdate/serial→int, text/email/alnum→string 等のセマンティック型は、
	 * value（'datetime' 等）ではなくこの実体型で PHP 宣言型と突き合わせる
	 * （ReflectionNamedType::getName() が返す int/string/float/bool/array と一致する）。
	 *
	 * 例外は File（#[Parameter] 専用・値は file_info=string|array で Validator のスカラ経路を通らない）
	 * ＝ここでは mixed を返す。
	 *
	 * DB のカラム型（bool→INT(1) 等）は DB 方言依存でコネクタが算出する別概念なので、ここには持たない。
	 * モデルプロパティ宣言の規約は \ebi\Attribute\VarAttr の docコメント参照。
	 * @see \ebi\Attribute\VarAttr
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
