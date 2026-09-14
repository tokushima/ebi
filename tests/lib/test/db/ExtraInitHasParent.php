<?php
namespace test\db;
use \ebi\Attribute\Prop;
class ExtraInitHasParent extends InitHasParent{
	#[Prop(type:'mixed', extra:true)]
	protected mixed $extra_value = null;
}
