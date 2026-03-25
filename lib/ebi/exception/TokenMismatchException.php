<?php
namespace ebi\exception;
/**
 * Token mismatch
 */
class TokenMismatchException extends \ebi\Exception{
	protected ?int $http_status = 403;
}
