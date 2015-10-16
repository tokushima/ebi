<?php
$mail = new \ebi\Mail();
$mail->to("test1@email.address","abc");
$mail->to("test2@email.address");
$mail->to("test3@email.address","ghi");
eq(array(
	'test1@email.address' => '"=?ISO-2022-JP?B?YWJj?=" <test1@email.address>',
	'test2@email.address' => '"test2@email.address" <test2@email.address>',
	'test3@email.address' => '"=?ISO-2022-JP?B?Z2hp?=" <test3@email.address>',
),$mail->get('to'));

