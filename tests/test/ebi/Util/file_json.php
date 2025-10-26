<?php
$vars = ['a'=>1,'b'=>2,'c'=>3];


$filename = \ebi\WorkingStorage::tmpfile();
\ebi\Util::file_write_json($filename, $vars);
eq($vars,\ebi\Util::file_read_json($filename));



$filename_format = \ebi\WorkingStorage::tmpfile();
\ebi\Util::file_write_json($filename_format, $vars,true);
eq($vars,\ebi\Util::file_read_json($filename_format));


neq(\ebi\Util::file_read($filename),\ebi\Util::file_read($filename_format));



