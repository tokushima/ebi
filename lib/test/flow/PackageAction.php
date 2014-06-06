<?php
namespace test\flow;

class PackageAction{
	/**
	 * @automap
	 */
	public function index(){
		
	}
	/**
	 * @automap
	 * @throws \LogicException
	 */
	public function throw_over(){
		throw new \LogicException('throw_over');
	}
}