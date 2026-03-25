<?php
namespace ebi\exception;
/**
 * 認証失敗の場合にスローされる例外です
 */
class UnauthorizedException extends \ebi\Exception{
	protected $message = 'Unauthorized';
	protected ?int $http_status = 401;
}
