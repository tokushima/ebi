<?php
$mail = new \ebi\Mail();
$mail->from('from@ebi.org');
$mail->to('to@ebi.org');
$mail->send('subject','body');


$dao = \ebi\SmtpBlackholeDao::find_get(\ebi\Q::order('-id'));
eq('subject',$dao->subject());
eq('body',$dao->message());
eq('to@ebi.org',$dao->to());


