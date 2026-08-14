<?php
namespace test\dt;
class CatchService{
	public function work(): void{
		throw new \ebi\exception\NotFoundException('nf');
	}
}
