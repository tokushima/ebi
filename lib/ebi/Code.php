<?php
namespace ebi;

class Code{
	/**
	 * コードから数値に変換する
	 */
	public static function decode(string $codebase, string $code): int{
		$base = strlen($codebase);
		$rtn = 0;
		$exp = strlen($code) - 1;
	
		for($i=0;$i<$exp;$i++){
			$p = strpos($codebase,$code[$i]);
			$rtn = $rtn + (pow($base,$exp-$i) * $p);
		}
		return $rtn + strpos($codebase,$code[$exp]);
	}

	/**
	 * 数値からコードに変換する
	 */
	public static function encode(string $codebase, int $num): string{
		$base = strlen($codebase);
		$rtn = '';
		$exp = 1;
		while(pow($base,$exp) <= $num){
			$exp++;
		}
		for($i=$exp-1;$i>0;$i--){
			$y = pow($base,$i);
	
			$d = (int)($num / $y);
			$rtn = $rtn.$codebase[$d];
			$num = $num - ($y * $d);
		}
		return $rtn.$codebase[$num];
	}

	/**
	 * 指定桁で作成できる最大値
	 */
	public static function max(string $codebase, int $length): int{
		return pow(strlen($codebase),$length)-1;
	}

	/**
	 * 指定桁を作成する場合の最小値
	 */
	public static function min(string $codebase, int $length): int{
		return pow(strlen($codebase),$length-1);
	}
	
	/**
	 * 指定桁でランダムに作成する
	 */
	public static function rand(string $codebase, int $length=1): string{
		if(empty($codebase)){
			throw new \ebi\exception\InvalidArgumentException('codebase is empty');
		}
		$cl = strlen($codebase) - 1;
		$r = $codebase[mt_rand(1,$cl)];
		
		for($i=1;$i<$length;$i++){
			$r = $r.$codebase[mt_rand(0,$cl)];
		}
		return $r;
	}
	
	/**
	 * コード文字列をトリムする
	 */
	public static function trim(string $code): string{
		return str_replace(['-','ー','−','―','‐'],'',trim(mb_convert_kana($code,'as')));
	}
	
	/**
	 * パスワードの生成
	 */
	public static function password(int $length=8): string{
		if($length < 8){
			$length = 8;
		}
		
		$base = [
			'ABCDEFGHJKLMNPQRSTUVWXY',
			'abcdefghijkmnpqrstuvwxyz',
			'23456789!#$%&=@+/<>?'
		];
		
		$p = '';
		$rand = [];
		while(sizeof($rand) < 3){
			for($i=0,$p='',$rand=[];$i<$length;$i++){
				$rand[$r=rand(0,2)] = true;
				$p .= self::rand($base[$r]);
			}
		}
		return $p;
	}
}