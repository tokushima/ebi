<?php
namespace test\object;
use \ebi\Attribute\Prop;
/**
 * 型変換の検証用 Obj。Validator 通過後の値は型により int/float/string など揺れるため
 * （例: time は "12:00.345" で float 720.345 を返す）、プロパティには型宣言を付けず、
 * 型は #[Prop(type:)] で与える。
 */
class Types extends \ebi\Obj{
	#[Prop(type:'mixed')]
	protected $aa;
	#[Prop(type:'mixed')]
	protected $aaa;
	#[Prop(type:'string')]
	protected $bb;
	#[Prop(type:'serial')]
	protected $cc;
	#[Prop(type:'float')]
	protected $dd;
	#[Prop(type:'bool')]
	protected $ee;
	#[Prop(type:'datetime')]
	protected $ff;
	#[Prop(type:'time')]
	protected $gg;
	#[Prop(type:'map', items:'string')]
	protected $ii;
	#[Prop(type:'array', items:'string')]
	protected $jj;
	#[Prop(type:'email')]
	protected $kk;
	#[Prop(type:'date')]
	protected $ll;
	#[Prop(type:'alnum', additional_chars:'_')]
	protected $mm;
	#[Prop(type:'intdate')]
	protected $nn;
	#[Prop(type:'int')]
	protected $oo;
	#[Prop(type:'text')]
	protected $pp;
	#[Prop(type:'float', decimal_places:2)]
	protected $qq;

	protected function __set_aaa__($value){
		$this->aaa = (($value === null) ? "" : "ABC").$value;
	}
	protected function __get_aaa__(){
		return empty($this->aaa) ? null : "[".$this->aaa."]";
	}
}
