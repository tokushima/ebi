<?php
$mail = new \ebi\Mail();
$mail->return_path("test1@email.address");
$mail->return_path("test2@email.address");
$mail->from("test_from@email.address");
$mail->to("test_to@email.address");

eq("test2@email.address",$mail->get()['return_path']);

