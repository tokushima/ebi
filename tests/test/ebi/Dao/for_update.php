<?php

use \ebi\Q;

\test\db\Data::create_table();
\test\db\Data::find_delete();


$obj = \test\db\Data::sample();


\ebi\Dao::start_record();

\test\db\Data::find_all(Q::eq('id', $obj->id()), Q::for_update());

$record = \ebi\Dao::stop_record();

// eq(strpos($record[0][0],' FOR UPDATE') !== false);

