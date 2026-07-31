<?php
namespace ebi\exception;
/**
 * HTTPメソッドが許可されていない場合にスローされる例外です
 */
class MethodNotAllowedHttpException extends \ebi\Exception{
	protected ?int $http_status = 405;
	protected $message = 'method not allowed';
}
