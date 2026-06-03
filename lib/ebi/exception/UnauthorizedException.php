<?php
namespace ebi\exception;
/**
 * 認証に失敗した場合にスローされる例外です
 */
class UnauthorizedException extends \ebi\Exception{
	protected ?int $http_status = 401;
	protected $message = 'Unauthorized';
}
