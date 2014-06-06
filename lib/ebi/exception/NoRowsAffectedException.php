<?php
namespace ebi\exception;
/**
 * 結果行数が0だった場合にスローされる例外です
 * @author tokushima
 */
class NoRowsAffectedException extends \ebi\Exception{
	protected $message = 'no rows affected';
}
