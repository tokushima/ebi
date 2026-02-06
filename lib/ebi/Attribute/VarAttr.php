<?php
namespace ebi\Attribute;

/**
 * プロパティの型と制約を定義するAttribute
 *
 * @example
 * #[VarAttr(type: 'string', max: 100, require: true)]
 * protected $name;
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class VarAttr{
	public function __construct(
		public string $type='string',
		public ?string $summary=null,
		public bool $primary=false,
		public bool $auto_now=false,
		public bool $auto_now_add=false,
		public bool $auto_code_add=false,
		public bool $hash=true,
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
