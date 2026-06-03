<?php
namespace ebi\exception;
/**
 * 一意性制約に違反した場合にスローされる例外です
 */
class UniqueException extends \ebi\Exception{
	protected ?int $http_status = 409;
	protected $message = 'unique constraint violation';
}
