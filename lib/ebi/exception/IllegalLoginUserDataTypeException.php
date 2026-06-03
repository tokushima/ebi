<?php
namespace ebi\exception;
/**
 * ログインユーザの型が期待する型と異なる場合にスローされる例外です
 */
class IllegalLoginUserDataTypeException extends \ebi\Exception{
	protected ?int $http_status = 500;
	protected $message = 'illegal login user data type';
}
