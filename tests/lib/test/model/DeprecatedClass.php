<?php
namespace test\model;
/**
 * 推奨しないクラス
 * @var string $aaa @deprecated 2017-03-27 あああ
 * @var string $bbb @['hash'=>false]
 * @var string $ccc @deprecated 2017-03-28 いいい @['hash'=>false]
 * @var string $ddd
 * 
 * @deprecated 2017-04-01
 */
class DeprecatedClass extends \ebi\Obj{
	protected $aaa;
	protected $bbb;
	protected $ccc;
	protected $ddd;
}