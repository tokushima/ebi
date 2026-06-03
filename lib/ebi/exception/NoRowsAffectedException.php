<?php
namespace ebi\exception;
/**
 * 結果行数が0だった場合にスローされる例外です
 */
class NoRowsAffectedException extends \ebi\Exception{
	protected ?int $http_status = 404;
	protected $message = 'no rows affected';
}
