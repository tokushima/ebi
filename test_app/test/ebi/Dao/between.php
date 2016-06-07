<?php
use \ebi\Q;

\test\db\FindBetween::create_table();
\test\db\FindBetween::find_delete();

(new \test\db\FindBetween())->int(1)->char('A')->date('2016/01/01')->timestamp('2016/01/01 11:00:00')->save();
(new \test\db\FindBetween())->int(2)->char('B')->date('2016/02/02')->timestamp('2016/02/02 12:00:00')->save();
(new \test\db\FindBetween())->int(3)->char('C')->date('2016/03/03')->timestamp('2016/03/03 13:00:00')->save();
(new \test\db\FindBetween())->int(4)->char('D')->date('2016/04/04')->timestamp('2016/04/04 14:00:00')->save();


eq(3,\test\db\FindBetween::find_count(Q::between('timestamp','2016/01/01','2016/03/03')));
eq(2,\test\db\FindBetween::find_count(Q::between('timestamp','2016/01/01','2016/03/02')));
eq(3,\test\db\FindBetween::find_count(Q::between('timestamp','2016/01','2016/03')));
eq(2,\test\db\FindBetween::find_count(Q::between('char','B','C')));
eq(3,\test\db\FindBetween::find_count(Q::between('int',2,4)));

eq(3,\test\db\FindBetween::find_count(Q::between('date','2016/01/01','2016/03/03')));
eq(2,\test\db\FindBetween::find_count(Q::between('date','2016/01/01','2016/03/02')));
eq(3,\test\db\FindBetween::find_count(Q::between('date','2016/01','2016/03')));
