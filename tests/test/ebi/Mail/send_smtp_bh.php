<?php
$mail = new \ebi\Mail();
$mail->from('from@email.address');
$mail->to('to@email.address');
$mail->send('subject','body');


$dao = \ebi\SmtpBlackholeDao::find_get(\ebi\Q::order('-id'));
eq('subject',$dao->subject());
eq('body',$dao->message());
eq('to@email.address',$dao->to());


