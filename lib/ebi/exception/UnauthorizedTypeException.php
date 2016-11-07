<?php
namespace ebi\exception;
/**
 * 認証ユーザのタイプが指定以外の場合にスローされる例外です
 * @author tokushima
 */
class UnauthorizedTypeException extends \ebi\Exception{
	protected $message = 'Unauthorized type';
}
