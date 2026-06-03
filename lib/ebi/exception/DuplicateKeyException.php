<?php
namespace ebi\exception;
/**
 * 既に存在するキーで登録しようとした場合にスローされる例外です
 */
class DuplicateKeyException extends \ebi\Exception{
	protected ?int $http_status = 409;
	protected $message = 'duplicate key';
}
