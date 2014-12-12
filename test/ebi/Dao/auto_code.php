<?php
/**
 * ユニークなコードが生成できなくなるとGenerateUniqueCodeRetryLimitOverExceptionが発生する
 */
\test\db\AutoCode::create_table();
\test\db\AutoCode::find_delete();

try{
	for($i=0;$i<100;$i++){
		$obj = new \test\db\AutoCode();
		$obj->save();
	}
	failure($i);
}catch(\ebi\exception\GenerateUniqueCodeRetryLimitOverException $e){
}
