<?php
$mail = new \ebi\Mail();
$mail->to("test1@ebi.org","abc");
$mail->to("test2@ebi.org");
$mail->to("test3@ebi.org","ghi");
eq(array(
	'test1@ebi.org' => '"=?ISO-2022-JP?B?YWJj?=" <test1@ebi.org>',
	'test2@ebi.org' => '"test2@ebi.org" <test2@ebi.org>',
	'test3@ebi.org' => '"=?ISO-2022-JP?B?Z2hp?=" <test3@ebi.org>',
),$mail->get('to'));

