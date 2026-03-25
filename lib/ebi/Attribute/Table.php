<?php
namespace ebi\Attribute;

/**
 * Daoクラスのテーブル設定を定義するAttribute
 *
 * @example
 * #[Table(name: 'users', create: true)]
 * class User extends \ebi\Dao {}
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Table{
	public function __construct(
		public ?string $name=null,
		public bool $create=true,
	){}
}
