<?php
namespace test\flow;

use \ebi\Attribute\Route;

class PackageAction{
	#[Route]
	public function index(){

	}
	/**
	 * @throws \LogicException
	 */
	#[Route]
	public function throw_over(){
		throw new \LogicException('throw_over');
	}
}