<?php
namespace test\model;
/**
 * 推奨しないプロパティ
 * @var string $aaa @deprecated 2017-03-27 あああ
 * @var string $bbb @['hash'=>false]
 * @var string $ccc @deprecated 2017-03-28 いいい @['hash'=>false]
 * @var string $ddd
 * @var \test\model\DeprecatedClass $eee 
 */
class DeprecatedModel extends \ebi\Obj{
	protected $aaa;
	protected $bbb;
	protected $ccc;
	protected $ddd;
	protected $eee;
	
	/**
	 * @deprecated 2017-03-29 ううう
	 */
	public function hoge(){
		
	}
}