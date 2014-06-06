<?php
$vars = array('abc'=>'ABC');
$mail = new \ebi\Mail();
$mail->to("test@ebi.org");
$mail->send_template('send.xml',$vars);
$xml = \ebi\Dt::find_mail('test@ebi.org');
eq('ボディーテストABC'.PHP_EOL,$xml->message());
eq('テストサブジェクト',$xml->subject());


$vars = array('abc'=>'ABC');
$mail = new \ebi\Mail();
$mail->to("test@ebi.org");
$mail->send_template('send_html.xml',$vars);
$xml = \ebi\Dt::find_mail('test@ebi.org');
eq('ボディーテストABC'.PHP_EOL,$xml->message());
eq('テストサブジェクト',$xml->subject());
meq('Content-Type: text/html;',$xml->manuscript());
meq('<p class="abc">ピーボディー</p>',mb_convert_encoding($xml->manuscript(),'UTF8','JIS'));
meq('send_html.css',mb_convert_encoding($xml->manuscript(),'UTF8','JIS'));
