<?php
\test\db\Abc::find_delete();

$a = new \test\db\Abc();
$a->value('A');
$a->save();
\test\db\Abc::commit();


$b = new \testman\Browser();
$b->vars('value','B');
$b->do_post(url('test_index::abc'));

$b = new \testman\Browser();
$b->vars('value','C');
$b->do_post(url('test_index::abc'));

eq(3,\test\db\Abc::find_count('id'));

