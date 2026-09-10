<?php
namespace ebi\Attribute;

/**
 * Daoクラスを読み取り専用にするAttribute
 *
 * @example
 * #[ReadonlyModel]
 * class ReadOnlyModel extends \ebi\Dao {}
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ReadonlyModel{
	public function __construct(){}
}
