<?php
namespace ebi;

interface MailHandler{
	public function send_mail(\ebi\Mail $mail): void;
}
