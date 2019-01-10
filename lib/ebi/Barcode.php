<?php
namespace ebi;
/**
 * バーコード
 * @author tokushima
 *
 */
class Barcode{
	private static function get_checkdigit_JAN($code){
		$odd = $even = 0;
		for($i=0;$i<12;$i+=2){
			$even += (int)$code[$i];
			$odd += (int)$code[$i+1];
		}
		$sum = (string)(($odd * 3) + $even);
		$digit1 = (int)$sum[strlen($sum)-1];
		$check = ($digit1 > 9 || $digit1 < 1) ? 0 : (10 - $digit1);
		return $check;
	}
	private static function get_data_JAN($code){
		$data = [[],[]];
		
		$parity_pattern = [ // 0:偶数 1:奇数
			'111111','110100','110010','110001','101100',
			'100110','100011','101010','101001','100101'
		];
		$pattern = $parity_pattern[$code[0]];
		$parity = [];
		
		$parity[0][0] = [ // 左 パリティ 偶数
			[-1,1,-2,3],[-1,2,-2,2],[-2,2,-1,2],[-1,1,-4,1], [-2,3,-1,1],
			[-1,3,-2,1],[-4,1,-1,1],[-2,1,-3,1], [-3,1,-2,1],[-2,1,-1,3]
		];
		$parity[0][1] = [ // 左 パリティ 奇数
			[-3,2,-1,1],[-2,2,-2,1], [-2,1,-2,2],[-1,4,-1,1],[-1,1,-3,2],
			[-1,2,-3,1],[-1,1,-1,4],[-1,3,-1,2],[-1,2,-1,3], [-3,1,-1,2]
		];
		$parity[1] = [ // 右 パリティ
			[3,-2,1,-1],[2,-2,2,-1],[2,-1,2,-2],[1,-4,1,-1],[1,-1,3,-2],
			[1,-2,3,-1],[1,-1,1,-4],[1,-3,1,-2],[1,-2,1,-3],[3,-1,1,-2]
		];
		
		foreach(str_split(substr($code,1)) as $k => $n){
			if($k < 6){
				$data[0][] = $parity[0][$pattern[$k]][$n];
			}else{
				$data[1][] = $parity[1][$n];
			}
		}
		
		return array_merge(
			[[1,-1,1]],
			$data[0],
			[[-1,1,-1,1,-1]],
			$data[1],
			[[1,-1,1]]
		);
	}
	
	private static function svg($width,$height,$barcord,$color){
		return sprintf(
			'<?xml version="1.0" standalone="no" ?>'.PHP_EOL.
			'<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">'.PHP_EOL.
			'<svg width="%d" height="%d" version="1.1" xmlns="http://www.w3.org/2000/svg"><g fill="%s">'.PHP_EOL.
			'%s'.PHP_EOL.
			'</g></svg>',
			$width,$height,$color,$barcord);
	}
	
	/**
	 * JAN13のSVG文字列を返す
	 * @param string $code バーコードにする数値
	 * @param string $color #000000
	 * @param number $bar_height バーコードの高さ
	 * @param number $font_size フォントサイズ
	 * @param string $font_name フォント名
	 * @throws \ebi\exception\InvalidArgumentException
	 * @return string
	 */
	public static function JAN13($code,$color='#00000',$bar_height=65,$font_size=20,$font_name='OCRB'){
		if(!is_array($code)){
			$code = [$code];
		}
		$barsize = 2;
		$margin = 2;
		$barcord = '';
		$y = $margin;
		
		foreach($code as $cd){
			if($y > $margin){
				$y += 8 * 6;
			}
			$x = $margin;
			$cd = sprintf('%012d',$cd);
			
			if(!ctype_digit($cd)){
				throw new \ebi\exception\InvalidArgumentException();
			}
			$value = (strlen($cd) > 12) ? $cd : $cd.self::get_checkdigit_JAN($cd);
			$data = self::get_data_JAN($value);
			
			foreach($data as $d){
				foreach($d as $bw){
					if($bw < 0){
						$x += ($bw * -1) * $barsize;
					}else{
						$barcord .= sprintf('<rect x="%d" y="%d" width="%d" height="%d" />'.PHP_EOL,$x,$y,($bw * $barsize),$bar_height,$color);
						$x += ($bw * $barsize);
					}
				}
			}
			$y += $bar_height;
			
			if(!empty($font_name) && !empty($font_size)){
				$kerning = ceil($font_size / 4);
				$y += $font_size;
				$barcord .= sprintf('<text x="%d" y="%d" font-family="%s" font-size="%d" kerning="%d">%s</text>',
					$margin,
					$y,
					$font_name,
					$font_size,
					$kerning,
					$value
				);
				
				$x = ($x < strlen($value) * $font_size) ? (strlen($value) * $font_size) : $x;
			}
		}		
		return self::svg($x + $margin,$y + $margin,$barcord,$color);
	}
}


