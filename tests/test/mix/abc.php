<?php
\test\db\Abc::find_delete();

$a = new \test\db\Abc();
$a->value('A');
$a->save();
\test\db\Abc::commit();


$b = b();
$b->vars('value','B');
$b->do_post(url('index::abc'));

$b = b();
$b->vars('value','C');
$b->do_post(url('index::abc'));

eq(3,\test\db\Abc::find_count('id'));

