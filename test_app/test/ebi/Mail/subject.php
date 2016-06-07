<?php
$mail = new \ebi\Mail();
$mail->subject("改行は\r\n削除される");
eq("改行は削除される", $mail->get('subject'));
meq('Subject: =?ISO-2022-JP?B?GyRCMn45VCRPOm89fCQ1JGwkaxsoQg==?=',$mail->manuscript());



