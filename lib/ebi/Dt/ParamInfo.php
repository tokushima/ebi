<?php
namespace ebi\Dt;

/**
 * パラメータ情報（名前、型、説明）
 */
class ParamInfo extends \ebi\Obj{
	protected string $name = '';
	protected string $type = '';
	protected ?string $summary = '';
	private array $opt = [];

	public function __construct(string $name, string $type, string $summary='', array $opt=[]){
		$this->name = trim($name);
		$this->type = trim($type);
		$this->summary = trim($summary);
		$this->opt = $opt;
	}

	public function set_opt(string $n, $val): void{
		$this->opt[$n] = $val;
	}

	public function opt(string $n, $def=null){
		return $this->opt[$n] ?? $def;
	}

	public function is_type_class(): bool{
		return (bool)preg_match('/[A-Z]/', $this->type);
	}

	public function fm_type(): string{
		return $this->type;
	}

	public function plain_type(): string{
		$type = $this->fm_type();

		return match(substr($type, -2)){
			'{}', '[]' => substr($type, 0, -2),
			default => $type,
		};
	}

	/**
	 * @var type description 形式のDocBlockをパース
	 */
	public static function parse_var(string $doc): array{
		$result = [];
		$m = [];

		if(preg_match_all("/@var\s+([^\s]+)(?:\s+(.*))?/", $doc, $m)){
			foreach(array_keys($m[1]) as $n){
				$result[] = new static(
					'val',
					$m[1][$n],
					trim($m[2][$n])
				);
			}
		}
		return $result;
	}

	/**
	 * DocBlockからパラメータをパース
	 */
	public static function parse(string $varname, string $doc): array{
		$result = [];
		$m = [];

		if(preg_match_all("/@".$varname."\s+([^\s]+)\s+\\$(\w+)(.*)/", $doc, $m)){
			foreach(array_keys($m[2]) as $n){
				$summary = $m[3][$n];
				$opt = [];

				if(strpos($summary, '@[') !== false){
					[$summary, $anon] = explode('@[', $summary, 2);

					try{
						$opt = \ebi\AttributeReader::activation('@['.$anon);
					}catch(\ParseError $e){
						throw new \ebi\exception\InvalidAnnotationException('annotation error : `@['.$anon.'`');
					}
				}
				$result[] = new static(
					$m[2][$n],
					$m[1][$n],
					$summary,
					$opt
				);
			}
		}
		return $result;
	}
}
