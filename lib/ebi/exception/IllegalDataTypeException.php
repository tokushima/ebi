<?php
namespace ebi\exception;
/**
 * データ型が期待する型と異なる場合にスローされる例外です
 */
class IllegalDataTypeException extends \ebi\Exception{
	protected ?int $http_status = 422;
	protected $message = 'illegal data type';
}
