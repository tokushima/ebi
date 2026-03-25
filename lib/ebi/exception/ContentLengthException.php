<?php
namespace ebi\exception;
/**
 * Content-lengthが制限を超えた場合の例外
 */
class ContentLengthException extends \ebi\Exception{
	public $message = 'Content-Length has exceeded the limit';
	protected ?int $http_status = 413;
}
