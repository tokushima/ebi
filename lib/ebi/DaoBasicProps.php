<?php
namespace ebi;

use ebi\Attribute\Prop;

trait DaoBasicProps{
	#[Prop(type: 'serial')]
	protected int $id;

	#[Prop(type: 'datetime', summary: '作成日', auto_now_add: true, expose: false)]
	protected int $create_date;

	#[Prop(type: 'datetime', summary: '更新日', auto_now: true)]
	protected int $update_date;
}
