<?php
$mail = new \ebi\Mail();
$mail->message("メッセージ");
eq("メッセージ", $mail->get('message'));
meq('B%a%C%;!<%8',$mail->manuscript());



$mail = new \ebi\Mail();
$mail->message("メッセージ");
$mail->attach('hoge.txt','ほげ');
eq("メッセージ", $mail->get('message'));
meq('B%a%C%;!<%8',$mail->manuscript());
meq('44G744GS',$mail->manuscript());

$mail = new \ebi\Mail();
$mail->message("メッセージ");
$mail->html('<html><body>ふご</body></html>');
eq("メッセージ", $mail->get('message'));
meq('B%a%C%;!<%8',$mail->manuscript());
meq('B$U$4',$mail->manuscript());


$mail = new \ebi\Mail();
$mail->message("メッセージ");
$mail->attach('hoge.txt','ほげ');
$mail->html('<html><body>ふご</body></html>');
eq("メッセージ", $mail->get('message'));
meq('B%a%C%;!<%8',$mail->manuscript());
meq('B$U$4',$mail->manuscript());
meq('44G744GS',$mail->manuscript());

