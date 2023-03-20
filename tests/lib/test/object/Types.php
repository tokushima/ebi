<?php
namespace test\object;

/**
 * @var mixed $aa
 * @var mixed $aaa
 * @var string $bb
 * @var serial $cc
 * @var float $dd
 * @var bool $ee
 * @var datetime $ff
 * @var time $gg
 * @var string{} $ii
 * @var string[] $jj
 * @var email $kk
 * @var date $ll
 * @var alnum $mm @['additional_chars'=>'_']
 * @var intdate $nn
 * @var int $oo
 * @var text $pp
 * @var float $qq @["decimal_places"=>2]		
*/
class Types extends \ebi\Obj{
	protected $aa;
	protected $aaa;
	protected $bb;
	protected $cc;
	protected $dd;
	protected $ee;
	protected $ff;
	protected $gg;
	protected $ii;
	protected $jj;
	protected $kk;
	protected $ll;
	protected $mm;
	protected $nn;
	protected $oo;
	protected $pp;
	protected $qq;
			
	protected function __set_aaa__($value){
		$this->aaa = (($value === null) ? "" : "ABC").$value;
	}
	protected function __get_aaa__(){
		return empty($this->aaa) ? null : "[".$this->aaa."]";
	}
}
