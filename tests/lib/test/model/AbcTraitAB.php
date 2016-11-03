<?php
namespace test\model;

class AbcTraitAB extends \test\model\Abc{
	use \test\model\TraitA,
	\test\model\TraitB,
	\test\model\TraitC;
}