<?php
namespace ebi;
/**
 * 文字列比較
 * @author tokushima
 *
 */
class Diff{
	/**
	 * 比較する
	 * @param string $src1
	 * @param string $src2
	 * @return array
	 */
	public static function parse($src1,$src2){
		$result = [];
		$line1 = explode("\n",$src1);
		$line2 = explode("\n",$src2);
		$max = sizeof($line1);
		$i = 0;

		$status_func = function($st,$l1,$v1,$l2,$v2){
			$dv = ($st < 0) ? $v1 : $v2;
			$d1 = ($st == 1) ? null : ($l1 + 1);
			$d2 = ($st < 0) ? null : ($l2 + 1);
			return [$st,$d1,$d2,$dv];
		};
		
		foreach(array_keys($line2) as $k){
			for($y=$i;$y<$max;$y++){
				if($line2[$k] == $line1[$y]){
					break;
				}
			}
			if($max > $y){
				for($x=$i+1;$x<$y;$x++){
					$result[] = $status_func(-1,$x,$line1[$x],$k,$line2[$k]);
				}
				$i = $y;
				$result[] = $status_func(0,$i,$line1[$i],$k,$line2[$k]);
			}else{
				$result[] = $status_func(1,$i,null,$k,$line2[$k]);
				$i++;
			}
		}
		$i++;
		for(;$i<$max;$i++){
			$result[] = $status_func(-1,$i,$line1[$i],null,null);
		}
		return $result;
	}
}
