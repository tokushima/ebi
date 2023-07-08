<?php
namespace ebi;

class Paginator implements \IteratorAggregate{
	private string $query_name = 'page';
	private array $vars = [];
	private ?int $current = null;
	private int $offset = 0;
	private int $limit;
	private ?string $order = null;
	private int $total;
	private int $first;
	private int $last;

	public function getIterator(): \Traversable{
		return new \ArrayIterator([
			'current'=>$this->current(),
			'pages'=>ceil($this->total() / $this->limit()),
			'limit'=>$this->limit(),
			'offset'=>$this->offset(),
			'total'=>$this->total(),
			'order'=>$this->order(),
		]);
	}
	/**
	 * pageを表すクエリの名前
	 */
	public function query_name(?string $name=null): string{
		if(isset($name)){
			$this->query_name = $name;
		}
		return (empty($this->query_name)) ? 'page' : $this->query_name;
	}
	/**
	 * query文字列とする値をセットする
	 * @param mixed $value
	 */
	public function vars(string $key, $value): void{
		$this->vars[$key] = $value;
	}
	/**
	 * 現在位置
	 * @return mixed
	 */
	public function current(?int $value=null){
		if(isset($value)){
			$value = intval($value);
			$this->current = ($value === 0) ? 1 : $value;
			$this->offset = $this->limit * round(abs($this->current - 1));
		}
		return $this->current;
	}
	/**
	 * 終了位置
	 */
	public function limit(?int $value=null): int{
		if(isset($value)) $this->limit = intval($value);
		return $this->limit;
	}
	/**
	 * 開始位置
	 */
	public function offset(?int $value=null): int{
		if(isset($value)) $this->offset = $value;
		return $this->offset;
	}
	/**
	 * 最後のソートキー
	 * @param mixed $value string|array
	 */
	public function order($value=null, bool $asc=true): ?string{
		if(isset($value)){
			$this->order = ($asc ? '' :'-').(string)(is_array($value) ? array_shift($value) : $value);
		}
		return $this->order;
	}
	/**
	 * ソートキーが設定されているか
	 */
	public function has_order(): bool{
		return !empty($this->order);
	}
	/**
	 * 合計
	 */
	public function total(?int $value=null): int{
		if(isset($value)){
			$this->total = intval($value);
			$this->first = 1;
			$this->last = ($this->total == 0 || $this->limit == 0) ? 0 : intval(ceil($this->total / $this->limit));
		}
		return $this->total;
	}
	/**
	 * 最初のページ番号
	 */
	public function first(): int{
		return $this->first;
	}
	/**
	 * 最後のページ番号
	 */
	public function last(): int{
		return $this->last;
	}
	/**
	 * 指定のページ番号が最初のページか
	 */
	public function is_first(int $page): bool{
		return ((int)$this->which_first($page) !== (int)$this->first);
	}
	/**
	 * 指定のページ番号が最後のページか
	 */
	public function is_last(int $page): bool{
		return ($this->which_last($page) !== $this->last());
	}

	/**
	 * RequestのPaginator
	 */
	public static function request(\ebi\Request $req, int $default_paginate_by=20, int $max_paginate_by=100): self{
		$paginate_by = $req->in_vars('paginate_by', $default_paginate_by);
	
		if($paginate_by > $max_paginate_by){
			$paginate_by = $max_paginate_by;
		}
		$self = new self($paginate_by,$req->in_vars('page',1));
		
		if($req->is_vars('order')){
			$o = (string)$req->in_vars('order');
			$p = (string)$req->in_vars('porder');
			
			if(!empty($o) && $o == $p){
				if($o[0] == '-'){
					$o = substr($o,1);
				}else{
					$o = '-'.$o;
				}
				$req->vars('order',$o);
			}
			$self->order($o);
		}
		$self->cp($req->ar_vars());
	
		return $self;
	}
	
	public function __construct(int $paginate_by=20, int $current=1, int $total=0){
		$this->limit($paginate_by);
		$this->total($total);
		$this->current($current);
	}
	/**
	 * 
	 * 配列をvarsにセットする
	 */
	public function cp(array $array): self{
		foreach($array as $name => $value){
			if(ctype_alpha((string)$name[0]) && !is_array($value)) $this->vars[$name] = (string)$value;
		}
		return $this;
	}
	/**
	 * 次のページ番号
	 */
	public function next(): int{
		return $this->current + 1;
	}
	/**
	 * 前のページ番号
	 */
	public function prev(): int{
		return $this->current - 1;
	}
	/**
	 * 次のページがあるか
	 * @return bool
	 */
	public function is_next(){
		return ($this->last > $this->current);
	}
	/**
	 * 前のページがあるか
	 */
	public function is_prev(): bool{
		return ($this->current > 1);
	}
	/**
	 * 前のページを表すクエリ
	 */
	public function query_prev(): string{
		$prev = $this->prev();
		$vars = array_merge($this->vars,[
			$this->query_name() => $prev
		]);
		
		if(isset($this->order)){
			$vars['order'] = $this->order;
		}
		return http_build_query($vars);
	}
	/**
	 * 次のページを表すクエリ
	 */
	public function query_next(): string{
		$vars = array_merge(
			$this->vars,
			[$this->query_name()=>$this->next()]
		);
		if(isset($this->order)){
			$vars['order'] = $this->order;
		}
		return http_build_query($vars);
	}
	/**
	 * orderを変更するクエリ
	 */
	public function query_order(string $order): string{
		if(isset($this->vars['order'])){
			$this->order = $this->vars['order'];
			unset($this->vars['order']);
			unset($this->vars['page']);
		}
		return http_build_query(array_merge(
			$this->vars
			,['order'=>$order,'porder'=>$this->order()]
		));
	}
	/**
	 * 指定のページを表すクエリ
	 */
	public function query(int $current): string{
		$vars = array_merge($this->vars,[$this->query_name()=>$current]);
		if(isset($this->order)){
			$vars['order'] = $this->order;
		}
		return http_build_query($vars);
	}
	
	/**
	 * 現在のページの最初の位置
	 */
	public function page_first(): int{
		return $this->offset + 1;
	}
	/**
	 * 現在のページの最後の位置
	 */
	public function page_last(): int{
		return (($this->offset + $this->limit) < $this->total) ? 
			($this->offset + $this->limit) : 
			$this->total;
	}
	/**
	 * ページの最初の位置を返す
	 */
	public function which_first(?int $paginate=null): int{
		if($paginate === null){
			return $this->first;
		}
		$paginate = $paginate - 1;
		$first = ($this->current > ($paginate/2)) ? @ceil($this->current - ($paginate/2)) : 1;
		$last = ($this->last > ($first + $paginate)) ? ($first + $paginate) : $this->last;
		return (($last - $paginate) > 0) ? ($last - $paginate) : $first;
	}
	/**
	 * ページの最後の位置を返す
	 */
	public function which_last(?int $paginate=null): int{
		if($paginate === null) return $this->last;
		$paginate = $paginate - 1;
		$first = ($this->current > ($paginate/2)) ? @ceil($this->current - ($paginate/2)) : 1;
		return ($this->last > ($first + $paginate)) ? ($first + $paginate) : $this->last;
	}
	/**
	 * ページとして有効な範囲のページ番号を有する配列を作成する
	 */
	public function range(int $counter=10): array{
		if($this->which_last($counter) > 0){
			return range((int)$this->which_first($counter),(int)$this->which_last($counter));
		}
		return [1];
	}
	/**
	 * rangeが存在するか
	 */
	public function has_range(): bool{
		return ($this->last > 1);
	}
}