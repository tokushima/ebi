<?php
namespace test\db;
use \ebi\Attribute\Prop;
/**
 * Findが先に必要
 */
class RefFindExt extends RefFind{
	#[Prop(via:'value')]
	protected ?string $order = null;
}
