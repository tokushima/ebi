<?php
/**
 * save中に__before_save__,__after_save__は一度しか実行されない
 */
\test\db\SaveLoop::create_table();
\test\db\SaveLoop::find_delete();

\test\db\SaveLoopBeforeSave::create_table();
\test\db\SaveLoopBeforeSave::find_delete();

try{
	(new \test\db\SaveLoopBeforeSave())->value('V')->save();
	fail();
}catch(\ebi\exception\BadMethodCallException $e){
	// 失敗するはず
}



(new \test\db\SaveLoop())->value('V')->save();

$obj = \test\db\SaveLoop::find_get();
eq('BVA',$obj->value()); // after中のsaveで２度目のbeforeが実行される


