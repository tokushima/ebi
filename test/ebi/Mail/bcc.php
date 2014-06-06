<?php
$mail = new \ebi\Mail();
$mail->bcc("test1@ebi.org","abc");
$mail->bcc("test2@ebi.org");
$mail->bcc("test3@ebi.org","ghi");
eq(array(
'test1@ebi.org'=>'"=?ISO-2022-JP?B?YWJj?=" <test1@ebi.org>',
'test2@ebi.org'=>'"test2@ebi.org" <test2@ebi.org>',
'test3@ebi.org'=>'"=?ISO-2022-JP?B?Z2hp?=" <test3@ebi.org>',
),$mail->get('bcc'));

