<?php
namespace test\db;
use \ebi\Attribute\Prop;
class MatchData extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	protected ?string $data1 = null;
	protected ?string $data2 = null;
	protected ?string $data3 = null;
}
