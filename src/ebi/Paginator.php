<?php
namespace ebi;
/**
 * ページを管理するモデル
 * @author tokushima
 */
class Paginator implements \IteratorAggregate{
	private $query_name = 'page';
	private $vars = [];
	private $current;
	private $offset;
	private $limit;
	private $order;
	private $total;
	private $first;
	private $last;
	private $contents = [];
	private $dynamic = false;
	private $tmp = [null,null,[],null,false];

	public function getIterator(){
		return new \ArrayIterator([
			'current'=>$this->current()
			,'limit'=>$this->limit()
			,'offset'=>$this->offset()
			,'total'=>$this->total()
			,'order'=>$this->order()
		]);
	}
	/**
	 * pageを表すクエリの名前
	 * @param string $name
	 * @return string
	 */
	public function query_name($name=null){
		if(isset($name)){
			$this->query_name = $name;
		}
		return (empty($this->query_name)) ? 'page' : $this->query_name;
	}
	/**
	 * query文字列とする値をセットする
	 * @param string $key
	 * @param string $value
	 */
	public function vars($key,$value){
		$this->vars[$key] = $value;
	}
	/**
	 * 現在位置
	 * @param integer $value
	 * @return mixed
	 */
	public function current($value=null){
		if(isset($value) && !$this->dynamic){
			$value = intval($value);
			$this->current = ($value === 0) ? 1 : $value;
			$this->offset = $this->limit * round(abs($this->current - 1));
		}
		return $this->current;
	}
	/**
	 * 終了位置
	 * @param integer $value
	 * @return integer
	 */
	public function limit($value=null){
		if(isset($value)) $this->limit = $value;
		return $this->limit;
	}
	/**
	 * 開始位置
	 * @param integer $value
	 * @return integer
	 */
	public function offset($value=null){
		if(isset($value)) $this->offset = $value;
		return $this->offset;
	}
	/**
	 * 最後のソートキー
	 * @param string $value
	 * @param boolean $asc
	 * return string
	 */
	public function order($value=null,$asc=true){
		if(isset($value)) $this->order = ($asc ? '' :'-').(string)(is_array($value) ? array_shift($value) : $value);
		return $this->order;
	}
	/**
	 * 合計
	 * @param integer $value
	 * @return integer
	 */
	public function total($value=null){
		if(isset($value) && !$this->dynamic){
			$this->total = intval($value);
			$this->first = 1;
			$this->last = ($this->total == 0 || $this->limit == 0) ? 0 : intval(ceil($this->total / $this->limit));
		}
		return $this->total;
	}
	/**
	 * 最初のページ番号
	 * @return integer
	 */
	public function first(){
		return $this->first;
	}
	/**
	 * 最後のページ番号
	 * @return integer
	 */
	public function last(){
		return $this->last;
	}
	/**
	 * 指定のページ番号が最初のページか
	 * @param integer $page
	 * @return boolean
	 */
	public function is_first($page){
		return ((int)$this->which_first($page) !== (int)$this->first);
	}
	/**
	 * 指定のページ番号が最後のページか
	 * @param integer $page
	 * @return boolean
	 */
	public function is_last($page){
		return ($this->which_last($page) !== $this->last());
	}
	/**
	 * 動的コンテンツのPaginaterか
	 * @return boolean
	 */
	public function is_dynamic(){
		return $this->dynamic;
	}
	/**
	 * コンテンツ
	 * @param mixed $mixed
	 * @return array
	 */
	public function contents($mixed=null){
		if(isset($mixed)){
			if($this->dynamic){
				if(!$this->tmp[4] && $this->current == (isset($this->tmp[3]) ? (isset($mixed[$this->tmp[3]]) ? $mixed[$this->tmp[3]] : null) : $mixed)) $this->tmp[4] = true;
				if($this->tmp[4]){
					if($this->tmp[0] === null && ($size=sizeof($this->contents)) <= $this->limit){
						if(($size+1) > $this->limit){
							$this->tmp[0] = $mixed;
						}else{
							$this->contents[] = $mixed;
						}
					}
				}else{
					if(sizeof($this->tmp[2]) >= $this->limit) array_shift($this->tmp[2]);
					$this->tmp[2][] = $mixed;
				}
			}else{
				$this->total($this->total+1);
				if($this->page_first() <= $this->total && $this->total <= ($this->offset + $this->limit)){
					$this->contents[] = $mixed;
				}
			}
		}
		return $this->contents;
	}
	/**
	 * 動的コンテンツのPaginater
	 * @param integer $paginate_by １ページの要素数
	 * @param string $marker 基点となる値
	 * @param string $key 対象とするキー
	 * @return self
	 */
	public static function dynamic_contents($paginate_by=20,$marker=null,$key=null){
		$self = new self($paginate_by);
		$self->dynamic = true;
		$self->tmp[3] = $key;
		$self->current = $marker;
		$self->total = $self->first = $self->last = null;
		return $self;
	}
	public function __construct($paginate_by=20,$current=1,$total=0){
		$this->limit($paginate_by);
		$this->total($total);
		$this->current($current);
	}
	/**
	 * 
	 * 配列をvarsにセットする
	 * @param string[] $array
	 * @return self $this
	 */
	public function cp(array $array){
		foreach($array as $name => $value){
			if(ctype_alpha($name[0])) $this->vars[$name] = (string)$value;
		}
		return $this;
	}
	/**
	 * 次のページ番号
	 * @return integer
	 */
	public function next(){
		if($this->dynamic) return $this->tmp[0];
		return $this->current + 1;
	}
	/**
	 * 前のページ番号
	 * @return integer
	 */
	public function prev(){
		if($this->dynamic){
			if(!isset($this->tmp[1]) && sizeof($this->tmp[2]) > 0) $this->tmp[1] = array_shift($this->tmp[2]);
			return $this->tmp[1];
		}
		return $this->current - 1;
	}
	/**
	 * 次のページがあるか
	 * @return boolean
	 */
	public function is_next(){
		if($this->dynamic) return isset($this->tmp[0]);
		return ($this->last > $this->current);
	}
	/**
	 * 前のページがあるか
	 * @return boolean
	 */
	public function is_prev(){
		if($this->dynamic) return ($this->prev() !== null);
		return ($this->current > 1);
	}
	/**
	 * 前のページを表すクエリ
	 * @return string
	 */
	public function query_prev(){
		$prev = $this->prev();
		$vars = array_merge($this->vars,array($this->query_name()=>($this->dynamic && isset($this->tmp[3]) ? (isset($prev[$this->tmp[3]]) ? $prev[$this->tmp[3]] : null) : $prev)));
		if(isset($this->order)) $vars['order'] = $this->order;
		return Query::get($vars);
	}
	/**
	 * 次のページを表すクエリ
	 * @return string
	 */
	public function query_next(){
		$vars = array_merge($this->vars,array($this->query_name()=>(($this->dynamic) ? $this->tmp[0] : $this->next())));
		if(isset($this->order)) $vars['order'] = $this->order;
		return Query::get($vars);
	}
	/**
	 * orderを変更するクエリ
	 * @param string $order
	 * @param string $pre_order
	 * @return string
	 */
	public function query_order($order){
		if(isset($this->vars['order'])){
			$this->order = $this->vars['order'];
			unset($this->vars['order']);
		}
		return Query::get(array_merge(
							$this->vars
							,array('order'=>$order,'porder'=>$this->order())
						));
	}
	/**
	 * 指定のページを表すクエリ
	 * @param integer $current 現在のページ番号
	 * @return string
	 */
	public function query($current){
		$vars = array_merge($this->vars,array($this->query_name()=>$current));
		if(isset($this->order)) $vars['order'] = $this->order;
		return Query::get($vars);
	}
	
	/**
	 * コンテンツを追加する
	 * @param mixed $mixed
	 * @return boolean
	 */
	public function add($mixed){
		$this->contents($mixed);
		return (sizeof($this->contents) <= $this->limit);
	}
	/**
	 * 現在のページの最初の位置
	 * @return integer
	 */
	public function page_first(){
		if($this->dynamic) return null;
		return $this->offset + 1;
	}
	/**
	 * 現在のページの最後の位置
	 * @return integer
	 */
	public function page_last(){
		if($this->dynamic) return null;
		return (($this->offset + $this->limit) < $this->total) ? ($this->offset + $this->limit) : $this->total;
	}
	/**
	 * ページの最初の位置を返す
	 * @param integer $paginate
	 * @return integer
	 */
	public function which_first($paginate=null){
		if($this->dynamic) return null;
		if($paginate === null) return $this->first;
		$paginate = $paginate - 1;
		$first = ($this->current > ($paginate/2)) ? @ceil($this->current - ($paginate/2)) : 1;
		$last = ($this->last > ($first + $paginate)) ? ($first + $paginate) : $this->last;
		return (($last - $paginate) > 0) ? ($last - $paginate) : $first;
	}
	/**
	 * ページの最後の位置を返す
	 * @param integer $paginate
	 * @return integer
	 */
	public function which_last($paginate=null){
		if($this->dynamic) return null;
		if($paginate === null) return $this->last;
		$paginate = $paginate - 1;
		$first = ($this->current > ($paginate/2)) ? @ceil($this->current - ($paginate/2)) : 1;
		return ($this->last > ($first + $paginate)) ? ($first + $paginate) : $this->last;
	}
	/**
	 * ページとして有効な範囲のページ番号を有する配列を作成する
	 * @param integer $counter ページ数
	 * @return integer[]
	 */
	public function range($counter=10){
		if($this->dynamic){
			return [];
		}
		if($this->which_last($counter) > 0){
			return range((int)$this->which_first($counter),(int)$this->which_last($counter));
		}
		return [1];
	}
	/**
	 * rangeが存在するか
	 * @return boolean
	 */
	public function has_range(){
		return (!$this->dynamic && $this->last > 1);
	}	
	public function before_template($src){
		return \ebi\Xml::find_replace($src, 'rt:paginator',function($xml){
			$param = '$'.$xml->in_attr('param','paginator');
			$navi = array_change_key_case(array_flip(explode(',',$xml->in_attr('navi','prev,next,first,last,counter'))));
			$counter = $xml->in_attr('counter',10);
			$lt = strtolower($xml->in_attr('lt','true'));
			$href = $xml->in_attr('href','?');
			
			$uniq = uniqid('');
			$counter_var = '$__counter__'.$uniq;
			$func = '';
			
			if($lt == 'false'){
				$func .= sprintf('<?php if(%s->is_dynamic() || %s->total() > %s->limit()){ ?>',$param,$param,$param);
			}
			$func .= sprintf('<?php try{ ?><?php if(%s instanceof \\ebi\\Paginator){ ?><ul class="pagination">',$param);
			if(isset($navi['prev'])){
				$func .= sprintf('<?php if(%s->is_prev()){ ?><li class="prev"><a href="%s{%s.query_prev()}" rel="prev"><?php }else{ ?><li class="prev disabled"><a><?php } ?>&laquo;</a></li>',$param,$href,$param);
			}
			if(isset($navi['first'])){
				$func .= sprintf('<?php if(!%s->is_dynamic() && %s->is_first(%d)){ ?><li><a href="%s{%s.query(%s.first())}">{%s.first()}</a></li><li class="disabled"><a>...</a></li><?php } ?>',$param,$param,$counter,$href,$param,$param,$param);
			}
			if(isset($navi['counter'])){
				$func .= sprintf('<?php if(!%s->is_dynamic()){ ?>',$param)
				.sprintf('<?php if(%s->total() == 0){ ?>',$param)
				.sprintf('<li class="active"><a>1</a></li>')
				.'<?php }else{ ?>'
						.sprintf('<?php for(%s=%s->which_first(%d);%s<=%s->which_last(%d);%s++){ ?>',$counter_var,$param,$counter,$counter_var,$param,$counter,$counter_var)
						.sprintf('<?php if(%s == %s->current()){ ?>',$counter_var,$param)
						.sprintf('<li class="active"><a>{%s}</a></li>',$counter_var)
						.'<?php }else{ ?>'
								.sprintf('<li><a href="%s{%s.query(%s)}">{%s}</a></li>',$href,$param,$counter_var,$counter_var)
								.'<?php } ?>'
										.'<?php } ?>'
												.'<?php } ?>'
														.'<?php } ?>';
			}
			if(isset($navi['last'])){
				$func .= sprintf('<?php if(!%s->is_dynamic() && %s->is_last(%d)){ ?><li class="disabled"><a>...</a></li><li><a href="%s{%s.query(%s.last())}">{%s.last()}</a></li><?php } ?>',$param,$param,$counter,$href,$param,$param,$param);
			}
			if(isset($navi['next'])){
				$func .= sprintf('<?php if(%s->is_next()){ ?><li class="next"><a href="%s{%s.query_next()}" rel="next"><?php }else{ ?><li class="next disabled"><a><?php } ?>&raquo;</a></li>',$param,$href,$param);
			}
			$func .= "<?php } ?><?php }catch(\\Exception \$e){} ?></ul>";
			if($lt == 'false'){
				$func .= sprintf('<?php } ?>',$param);
			}
			return $func;
		});
	}
}