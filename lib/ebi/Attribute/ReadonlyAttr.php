<?php
namespace ebi\Attribute;

/**
 * Daoクラスを読み取り専用にするAttribute
 *
 * @example
 * #[ReadonlyAttr]
 * class ReadOnlyModel extends \ebi\Dao {}
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ReadonlyAttr{
	public function __construct(){}
}
