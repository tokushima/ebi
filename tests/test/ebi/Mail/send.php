<?php
$vars = ['abc'=>'ABC'];
$mail = new \ebi\Mail();
$mail->to("test@email.address");
$mail->send_template('send.xml',$vars);
$xml = \ebi\Dt::find_mail('test@email.address');
eq(<<< __DATA
123ABC456

=======================
 Signature
  tokushima
=======================

__DATA
,$xml->message());
eq('テストサブジェクト',$xml->subject());


$vars = ['abc'=>'ABC'];
$mail = new \ebi\Mail();
$mail->to("test@email.address");
$mail->send_template('send_html.xml',$vars);
$xml = \ebi\Dt::find_mail('test@email.address');
eq('123ABC456'."\n",$xml->message());
eq('テストサブジェクト',$xml->subject());
meq('Content-Type: text/html;',$xml->manuscript());
meq('<p class="abc">ピーボディー</p>',mb_convert_encoding($xml->manuscript(),'UTF8','JIS'));
meq('send_html.css',mb_convert_encoding($xml->manuscript(),'UTF8','JIS'));
