<?php
namespace ebi;
/**
 * @var serial $id
 * @var timestamp $create_date 作成日 @['auto_now_add'=>true,'hash'=>false]
 * @var timestamp $update_date 更新日 @['auto_now'=>true]
 */
trait DaoBasicProps{
	protected $id;
	protected $create_date;
	protected $update_date;
}
