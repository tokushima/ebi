<?php
namespace ebi\exception;
/**
 * 認証失敗の場合にスローされる例外です
 * @author tokushima
 */
class UnauthorizedException extends \ebi\Exception{
	protected $message = 'Unauthorized';
}
