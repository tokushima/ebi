<?php
$mail = new \ebi\Mail();
$mail->unsubscribe("test2@email.address");
$mail->from("test_from@email.address");
$mail->to("test_to@email.address");

eq("<mailto:test2@email.address>",$mail->get()['unsubscribe']);


$mail = new \ebi\Mail();
$mail->unsubscribe("http://localhost/unsubscribe");
$mail->from("test_from@email.address");
$mail->to("test_to@email.address");

eq("<http://localhost/unsubscribe>",$mail->get()['unsubscribe']);


$mail = new \ebi\Mail();
$mail->unsubscribe("aaaaaaaaaaaaaaaaaa");
$mail->from("test_from@email.address");
$mail->to("test_to@email.address");

eq("",$mail->get()['unsubscribe']);

