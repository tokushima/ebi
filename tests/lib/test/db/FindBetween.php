<?php
namespace test\db;
use \ebi\Attribute\Prop;
class FindBetween extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	protected ?int $int = null;
	protected ?string $char = null;
	#[Prop(type:'date')]
	protected ?int $date = null;
	#[Prop(type:'datetime')]
	protected ?int $timestamp = null;
}
