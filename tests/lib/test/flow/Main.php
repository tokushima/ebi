<?php
namespace test\flow;
use \ebi\Q;

class Main extends \ebi\flow\Request{
	public function get_after_vars(){
		return [
			'helper'=>new \test\flow\Helper(),
		];
	}
	
	/**
	 * @automap
	 */
	public function index(){
		$paginator = \ebi\Paginator::request($this,10);
		$paginator->total(1000);
		
		$notes = \test\flow\model\Note::find_all();
		
		return $this->ar_vars([
			'paginator'=>$paginator,
			'notes'=>$notes,
		]);
	}
	
	/**
	 * @automap
	 */
	public function  vote(){
		$note = \test\flow\model\Note::find_get(Q::eq('id',$this->in_vars('note_id')));
		$note->vote($note->vote() + (int)$this->in_vars('point'));
		$note->save();
		
		return [
			'point'=>$note->vote(),
		];
	}
}
