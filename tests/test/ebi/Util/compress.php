<?php
$text = 'AAABBBCCCC';
$a = \ebi\Util::compress($text);
eq($text,\ebi\Util::uncompress($a));


$a = \ebi\Util::compress($text,true);
eq($text,\ebi\Util::uncompress($a,true));

