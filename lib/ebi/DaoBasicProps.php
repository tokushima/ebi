<?php
namespace ebi;

use ebi\Attribute\VarAttr;

trait DaoBasicProps{
	#[VarAttr(type: 'serial')]
	protected int $id;

	#[VarAttr(type: 'datetime', summary: '作成日', auto_now_add: true, expose: false)]
	protected int $create_date;

	#[VarAttr(type: 'datetime', summary: '更新日', auto_now: true)]
	protected int $update_date;
}
