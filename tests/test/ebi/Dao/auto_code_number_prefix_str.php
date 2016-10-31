<?php
\test\db\AutoCodeNumberPrefix::create_table();
\test\db\AutoCodeNumberPrefix::find_delete();


$obj = new \test\db\AutoCodeNumberPrefix();
$obj->save();



eq('ABC',substr($obj->code(),0,3));
eq(true,ctype_digit(substr($obj->code(),3)));


