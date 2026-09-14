<?php
namespace test\object;
use \ebi\Attribute\Prop;
class Call extends \ebi\Obj{
	#[Prop(type:'int')]
	public $aaa;
	#[Prop(type:'int[]')]
	public $bbb;
	#[Prop(type:'string{}')]
	public $ccc;
	public $ddd;
	#[Prop(type:'datetime')]
	public $eee;
	#[Prop(type:'string', column:'Acol')]
	public $fff;
	#[Prop(type:'string', set:false)]
	protected $ggg = 'hoge';
	#[Prop(type:'bool')]
	public $hhh;
	private $iii;

	protected function __set_ddd__($a,$b){
		$this->ddd = $a.$b;
	}
	public function nextDay(){
		return date('Y/m/d H:i:s',$this->eee + 86400);
	}
	protected function ___cn___(){
		if($this->prop_anon($this->_,'column') === null) throw new \Exception($this->_);
		return $this->prop_anon($this->_,'column');
	}
}
