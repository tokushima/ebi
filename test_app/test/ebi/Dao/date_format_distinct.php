<?php
use \ebi\Q;

\test\db\DateTime::find_delete();
(new \test\db\DateTime())->ts('2015/07/21 12:13:14')->save();
(new \test\db\DateTime())->ts('2015/07/22 12:13:14')->save();
(new \test\db\DateTime())->ts('2015/07/23 13:13:14')->save();
(new \test\db\DateTime())->ts('2015/07/21 14:13:14')->save();


eq(
	['2015/07/21 00:00:00','2015/07/22 00:00:00','2015/07/23 00:00:00'],
	\test\db\DateTime::find_distinct('ts',Q::date_format('ts','Ymd'))
);

eq(
	['2000/01/01 12:00:00','2000/01/01 13:00:00','2000/01/01 14:00:00'],
	\test\db\DateTime::find_distinct('ts',Q::date_format('ts','H'))
);

