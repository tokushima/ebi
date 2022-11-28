<?php
namespace test\db;
/**
 * Traitを含むモデル
 * @var serial $id
 * @var string $value;
 */
class InTraitDate extends \ebi\Dao{
	use \test\db\TraitDate;
	
	protected $id;
	protected $value;
}