<?php
\test\db\ColumnName::create_table();
\test\db\ColumnName::find_delete();


(new \test\db\ColumnName())->value('aaa')->save();
(new \test\db\ColumnName())->value('bbb')->save();
(new \test\db\ColumnName())->value('ccc')->save();


foreach(\test\db\ColumnName::find() as $o){
	eq(3,strlen($o->value()));
}


	