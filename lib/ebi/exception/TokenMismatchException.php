<?php
namespace ebi\exception;
/**
 * トークンが一致しない場合にスローされる例外です
 */
class TokenMismatchException extends \ebi\Exception{
	protected ?int $http_status = 403;
	protected $message = 'token mismatch';
}
