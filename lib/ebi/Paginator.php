<?php
namespace ebi;

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

	public function getIterator(): \Traversable{
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
		if(isset($value) && !$this->dynamic){
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
		if(isset($value) && !$this->dynamic){
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
	 * 動的コンテンツのPaginatorか
	 * @return bool
	 * @deprecated
	 */
	public function is_dynamic(): bool{
		return $this->dynamic;
	}
	/**
	 * コンテンツ
	 * @param mixed $mixed
	 * @deprecated
	 */
	public function contents($mixed=null): array{
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
	 * RequestのPaginator
	 */
	public static function request(\ebi\Request $req, int $default_paginate_by=20, int $max_paginate_by=100): self{
		$paginate_by = $req->in_vars('paginate_by',$default_paginate_by);
	
		if($paginate_by > $max_paginate_by){
			$paginate_by = $max_paginate_by;
		}
		$self = new self($paginate_by,$req->in_vars('page',1));
		
		if($req->is_vars('order')){
			$o = $req->in_vars('order');
			$p = $req->in_vars('porder');
			
			if($o == $p){
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
			if(ctype_alpha($name[0]) && !is_array($value)) $this->vars[$name] = (string)$value;
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
	 * コンテンツを追加する
	 * @param mixed $mixed
	 * @deprecated
	 */
	public function add($mixed): bool{
		$this->contents($mixed);
		return (sizeof($this->contents) <= $this->limit);
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

	public function before_template(string $src): string{
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
				$func .= sprintf('<?php if(%s->total() > %s->limit()){ ?>',$param,$param);
			}
			$func .= sprintf('<?php try{ ?><?php if(%s instanceof \\ebi\\Paginator){ ?><ul class="pagination justify-content-center">',$param);
			if(isset($navi['prev'])){
				$func .= sprintf('<?php if(%s->is_prev()){ ?><li class="page-item prev"><a class="page-link" href="%s{%s.query_prev()}" rel="prev"><?php }else{ ?><li class="page-item prev disabled"><a class="page-link"><?php } ?>&laquo;</a></li>',$param,$href,$param);
			}
			if(isset($navi['first'])){
				$func .= sprintf('<?php if(%s->is_first(%d)){ ?><li page-item><a class="page-link" href="%s{%s.query(%s.first())}">{%s.first()}</a></li><li class="page-item disabled"><a class="page-link">...</a></li><?php } ?>',$param,$counter,$href,$param,$param,$param);
			}
			if(isset($navi['counter'])){
				$func .= sprintf('<?php if(%s->total() == 0){ ?>',$param)
								.sprintf('<li class="page-item active"><a class="page-link">1</a></li>')
							.'<?php }else{ ?>'
								.sprintf('<?php for(%s=%s->which_first(%d);%s<=%s->which_last(%d);%s++){ ?>',$counter_var,$param,$counter,$counter_var,$param,$counter,$counter_var)
									.sprintf('<?php if(%s == %s->current()){ ?>',$counter_var,$param)
										.sprintf('<li class="page-item active"><a class="page-link">{%s}</a></li>',$counter_var)
									.'<?php }else{ ?>'
										.sprintf('<li class="page-item"><a class="page-link" href="%s{%s.query(%s)}">{%s}</a></li>',$href,$param,$counter_var,$counter_var)
									.'<?php } ?>'
								.'<?php } ?>'
						.'<?php } ?>';
			}
			if(isset($navi['last'])){
				$func .= sprintf('<?php if(%s->is_last(%d)){ ?><li class="page-item disabled"><a class="page-link">...</a></li><li class="page-item"><a class="page-link" href="%s{%s.query(%s.last())}">{%s.last()}</a></li><?php } ?>',$param,$counter,$href,$param,$param,$param);
			}
			if(isset($navi['next'])){
				$func .= sprintf('<?php if(%s->is_next()){ ?><li class="page-item next"><a class="page-link" href="%s{%s.query_next()}" rel="next"><?php }else{ ?><li class="page-item next disabled"><a class="page-link"><?php } ?>&raquo;</a></li>',$param,$href,$param);
			}
			$func .= "<?php } ?><?php }catch(\\Exception \$e){} ?></ul>";
			if($lt == 'false'){
				$func .= sprintf('<?php } ?>',$param);
			}
			return $func;
		});
	}
}