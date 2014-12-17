<?php
/**
 * APPモードをsingle_daoにすること
 */
include_once('bootstrap.php');

/**
 * 1ファイル中に定義しても使える
 * 
 * @var string $code @['auto_code_add'=>true,'max'=>5]
 * @author tokushima
 *
 */
class SingeDaoTest extends \ebi\Dao{
	protected $id;
	protected $code;
}
\SingeDaoTest::drop_table();
\SingeDaoTest::create_table();


for($i=1;$i<=100000000;$i++){
	$test = new \SingeDaoTest();
	$test->save();

	var_dump($test->id().' '.$test->code());
}
