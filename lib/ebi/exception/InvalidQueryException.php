<?php
namespace ebi\exception;
/**
 * クエリの実行に失敗した場合にスローされる例外です
 */
class InvalidQueryException extends \ebi\Exception{
	protected ?int $http_status = 500;
	protected $message = 'invalid query';
}
