<?php
$mail = new \ebi\Mail();
$mail->cc("test1@ebi.org","abc");
$mail->cc("test2@ebi.org");
$mail->cc("test3@ebi.org","ghi");
eq(array(
	'test1@ebi.org' => '"=?ISO-2022-JP?B?YWJj?=" <test1@ebi.org>',
	'test2@ebi.org' => '"test2@ebi.org" <test2@ebi.org>',
	'test3@ebi.org' => '"=?ISO-2022-JP?B?Z2hp?=" <test3@ebi.org>',
),$mail->get('cc'));

