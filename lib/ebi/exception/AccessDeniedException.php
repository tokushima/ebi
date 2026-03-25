<?php
namespace ebi\exception;
/**
 * アクセスが拒否された場合にスローされる例外例外です
 */
class AccessDeniedException extends \ebi\Exception{
	protected ?int $http_status = 403;
}