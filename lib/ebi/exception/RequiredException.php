<?php
namespace ebi\exception;
/**
 * 要求された値が空の場合にスローされる例外です
 */
class RequiredException extends \ebi\Exception{
	protected ?int $http_status = 400;
}
