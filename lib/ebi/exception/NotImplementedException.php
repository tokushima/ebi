<?php
namespace ebi\exception;
/**
 * 要求されたメソッドまたは操作が実装されない場合にスローされる例外です
 */
class NotImplementedException extends \ebi\Exception{
	protected ?int $http_status = 501;
}