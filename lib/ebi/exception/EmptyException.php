<?php
namespace ebi\exception;
/**
 * 値が空だった場合にスローされる例外です
 */
class EmptyException extends \ebi\Exception{
	protected $message = 'empty';
}
