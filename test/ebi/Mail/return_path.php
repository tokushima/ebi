<?php
$mail = new \ebi\Mail();
$mail->return_path("test1@ebi.org");
$mail->return_path("test2@ebi.org");
eq("test2@ebi.org",$mail->get('return_path'));

