<?php
namespace test\db;
use \ebi\Attribute\Prop;
/**
 * Traitを含むモデル
 */
class InTraitDate extends \ebi\Dao{
	use \test\db\TraitDate;

	#[Prop(type:'serial')]
	protected ?int $id = null;
	protected ?string $value = null;
}