<?php
namespace ebi\Attribute;

/**
 * プロパティの型と制約を定義するAttribute
 *
 * type 未指定時はプロパティの PHP 型宣言から推論される。
 * セマンティック型（email, datetime, alnum など）のみ type を明示する。
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
		public int|float|null $min=null,
		public int|float|null $max=null,
		public ?string $cond=null,
		public ?string $column=null,
		public bool $extra=false,
		public ?string $ctype=null,
		public ?string $base=null,
		public ?int $length=null,
	){}
}
