<?php
namespace ebi\exception;
/**
 * Content-Lengthが上限を超えた場合にスローされる例外です
 */
class ContentLengthException extends \ebi\Exception{
	protected ?int $http_status = 413;
	protected $message = 'Content-Length has exceeded the limit';
}
