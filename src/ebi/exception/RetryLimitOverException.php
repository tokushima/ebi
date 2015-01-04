<?php
namespace ebi\exception;
/**
 * リトライ回数を超えた
 * @author tokushima
 */
class RetryLimitOverException extends \ebi\Exception{
	public $message = 'retry limit over';
}
