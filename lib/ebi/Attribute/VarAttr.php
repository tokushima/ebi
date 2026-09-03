<?php
namespace ebi\Attribute;

/**
 * プロパティの型と制約を定義するAttribute
 *
 * type 未指定時はプロパティの PHP 型宣言から推論される。
 * セマンティック型（email, datetime, alnum など）のみ type を明示する。
 * nullable 未指定時もプロパティの PHP 型宣言（?付きかどうか）から推論される。
 *
 * @example
 * #[VarAttr]                                       // PHP型から推論
 * protected ?int $age = null;
 *
 * #[VarAttr(max: 100)]                             // 制約のみ追加（型はPHP宣言から）
 * protected ?string $name = null;
 *
 * #[VarAttr(type: 'email')]                        // セマンティック型は明示
 * protected ?string $email = null;
 *
 * #[VarAttr(nullable: false)]                      // 型宣言によらず非nullを明示
 * protected $code;
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class VarAttr{
	public function __construct(
		public string $type='',
		public ?string $items=null,
		public ?string $summary=null,
		public bool $primary=false,
		public bool $auto_now=false,
		public bool $auto_now_add=false,
		public bool $auto_code_add=false,
		public bool $expose=true,
		public bool $get=true,
		public bool $set=true,
		public bool $unique=false,
		public string|array|null $unique_together=null,
		public bool $require=false,
		public ?bool $nullable=null,
		public int|float|null $min=null,
		public int|float|null $max=null,
		public ?string $cond=null,
		public ?string $column=null,
		public bool $extra=false,
		public ?string $ctype=null,
		public ?string $base=null,
		public ?int $length=null,
		// enum: (推奨) backed enum の FQCN 文字列(EnumClass::class)＝値/ラベルの単一ソース。または [値 => ラベル] 連想(後方互換)。
		public array|string|null $enum=null,
	){}
}
