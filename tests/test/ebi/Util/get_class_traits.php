<?php
eq(['test\model\TraitA','test\model\TraitB','test\model\TraitC'],\ebi\Util::get_class_traits(\test\model\AbcDefTraitAB::class));
eq([],\ebi\Util::get_class_traits(\stdClass::class));
