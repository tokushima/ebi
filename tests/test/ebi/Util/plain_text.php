<?php
$text = \ebi\Util::plain_text('
	aaa
	bbb
');
eq('aaa'."\n".'bbb',$text);


$text = \ebi\Util::plain_text('
hoge
hoge
');
eq('hoge'."\n".'hoge',$text);


$text = \ebi\Util::plain_text('
hoge
hoge
hoge
hoge
');
eq('hoge'."\n".'hoge'."\n".'hoge'."\n".'hoge',$text);


