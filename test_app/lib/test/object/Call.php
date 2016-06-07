<?php
namespace test\object;
/**
 * 
 * @var number $aaa
 * @var number[] $bbb
 * @var string{} $ccc
 * @var timestamp $eee
 * @var string $fff @['column'=>'Acol','table'=>'BTbl']
 * @var string $ggg @['set'=>false]
 * @var boolean $hhh
 * 
 * 
 */
class Call extends \ebi\Object{
	public $aaa;
	public $bbb;
	public $ccc;
	public $ddd;
	public $eee;
	public $fff;
	protected $ggg = 'hoge';
	public $hhh;
	private $iii;
				
	protected function __set_ddd__($a,$b){
		$this->ddd = $a.$b;
	}
	public function nextDay(){
		return date('Y/m/d H:i:s',$this->eee + 86400);
	}
	protected function ___cn___(){
		if($this->prop_anon($this->_,'column') === null || $this->prop_anon($this->_,'table') === null) throw new \Exception($this->_);
		return [$this->prop_anon($this->_,'table'),$this->prop_anon($this->_,'column')];
	}
}