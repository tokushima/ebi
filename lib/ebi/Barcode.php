<?php
namespace ebi;
/**
 * バーコード
 * @author tokushima
 *
 */
class Barcode{
	private static function svg($width,$height,$barcord){
		return sprintf(
			'<?xml version="1.0" standalone="no" ?>'.PHP_EOL.
			'<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">'.PHP_EOL.
			'<svg viewBox="0 0 %s %s" version="1.1" xmlns="http://www.w3.org/2000/svg">'.PHP_EOL.
			'%s'.PHP_EOL.
			'</svg>',
			$width,$height,$barcord);
	}
	
	/**
	 * JAN13のSVG文字列を返す
	 * @param string $code バーコードにする数値
	 * @param mixed{} $opt 
	 * 
	 * opt:
	 * 	string $color #000000
	 * 	number $bar_height バーコードの高さ
	 * 	number $module_width 1モジュールの幅
	 *  boolean $show_text コード文字列を表示する
	 * 	number $font_size フォントサイズ
	 * 	string $font_family フォント名
	 * 
	 * @throws \ebi\exception\InvalidArgumentException
	 * @return string
	 */
	public static function JAN13($code,$opt=[]){
		$color = $opt['color'] ?? '#000000';
		$bar_height = $opt['bar_height'] ?? 22.85 / 2;
		$module_width = $opt['module_width'] ?? 0.33;
		$show_text = $opt['show_text'] ?? true;
		$font_size = $opt['font_size'] ?? 2;
		$font_family = $opt['font_family'] ?? 'OCRB';
		
		$get_checkdigit_JAN = function($code){
			$odd = $even = 0;
			for($i=0;$i<12;$i+=2){
				$even += (int)$code[$i];
				$odd += (int)$code[$i+1];
			}
			$sum = (string)(($odd * 3) + $even);
			$digit1 = (int)$sum[strlen($sum)-1];
			$check = ($digit1 > 9 || $digit1 < 1) ? 0 : (10 - $digit1);
			return $check;
		};
		
		$get_data_JAN = function($code){
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
		};
		
		$barcord = sprintf('<g fill="%s">',$color);
		$x = $module_width * 11;
		$y = 0;
		$code = sprintf('%012d',$code);
		
		if(!ctype_digit($code)){
			throw new \ebi\exception\InvalidArgumentException();
		}
		$code = (strlen($code) > 12) ? $code : $code.$get_checkdigit_JAN($code);
		$data = $get_data_JAN($code);
		
		foreach($data as $d){
			foreach($d as $bw){
				if($bw < 0){
					$x += ($bw * -1) * $module_width;
				}else{
					$barcord .= sprintf('<rect x="%s" y="%s" width="%s" height="%s" />'.PHP_EOL,$x,$y,($bw * $module_width),$bar_height);
					$x += ($bw * $module_width);
				}
			}
		}
		$x += (7 * $module_width);
		$y += $bar_height;
		
		if($show_text){
			$y += $font_size;
			$tx = 11 * $module_width;
			$step = ($x - (18 * $module_width)) / strlen($code);
			
			for($i=0;$i<strlen($code);$i++){
				$barcord .= sprintf('<text x="%s" y="%s" font-family="%s" font-size="%s">%s</text>',
					$tx,
					$y,
					$font_family,
					$font_size,
					$code[$i]
				);
				$tx += $step;
			}
		}
		$barcord .= '</g>';
		
		return self::svg($x,$y,$barcord);
	}
	
	/**
	 * NW-7 (CODABAR)
	 * @param string $code
	 * @param mixed{} $opt 
	 * 
	 * opt:
	 * 	string $color #000000
	 * 	number $bar_height バーコードの高さ
	 * 	number $module_width 1モジュールの幅
	 *  boolean $show_text コード文字列を表示する
	 * 	number $font_size フォントサイズ
	 * 	string $font_family フォント名
	 * 
	 * @return string
	 */
	public static function NW7($code,$opt=[]){
		if(!preg_match('/^[0123456789ABCD\-\$:\/\.\+]+$/i',$code)){
			throw new \ebi\exception\InvalidArgumentException('invalid characters detected');
		}
		if(!preg_match('/^[ABCD]/i',$code) || !preg_match('/[ABCD]$/i',$code)){
			throw new \ebi\exception\InvalidArgumentException('Start / Stop code is not [A, B, C, D]');
		}
		$color = $opt['color'] ?? '#000000';
		$bar_height = $opt['bar_height'] ?? 22.85 / 2;
		$module_width = $opt['module_width'] ?? 0.191;
		$show_text = $opt['show_text'] ?? true;
		$font_size = $opt['font_size'] ?? 2;
		$font_family = $opt['font_family'] ?? 'OCRB';
		
		$bits = [
			'0'=>[1,-1,1,-1,1,-3,3,-1],
			'1'=>[1,-1,1,-1,3,-3,1,-1],
			'2'=>[1,-1,1,-3,1,-1,3,-1],
			'3'=>[3,-3,1,-1,1,-1,1,-1],
			'4'=>[1,-1,3,-1,1,-3,1,-1],
			'5'=>[3,-1,1,-1,1,-3,1,-1],
			'6'=>[1,-3,1,-1,1,-1,3,-1],
			'7'=>[1,-3,1,-1,3,-1,1,-1],
			'8'=>[1,-3,3,-1,1,-1,1,-1],
			'9'=>[3,-1,1,-3,1,-1,1,-1],
			'-'=>[1,-1,1,-3,3,-1,1,-1],
			'$'=>[1,-1,3,-3,1,-1,1,-1],
			':'=>[3,-1,1,-1,3,-1,3,-1],
			'/'=>[3,-1,3,-1,1,-1,3,-1],
			'.'=>[3,-1,3,-1,3,-1,1,-1],
			'+'=>[1,-1,3,-1,3,-1,3,-1],
			'A'=>[1,-1,3,-3,1,-3,1,-1],
			'B'=>[1,-3,1,-3,1,-1,3,-1],
			'C'=>[1,-1,1,-3,1,-3,3,-1],
			'D'=>[1,-1,1,-3,3,-3,1,-1],
		];
		
		$fcode = strtoupper($code);
		$data = [];
		for($i=0;$i<strlen($fcode);$i++){
			$data = array_merge($data,$bits[$fcode[$i]]);
		}
		$x = 10 * $module_width;
		$y = 0;
		$barcord = sprintf('<g fill="%s">',$color);
		
		foreach($data as $bw){
			if($bw < 0){
				$x += ($bw * -1) * $module_width;
			}else{
				$barcord .= sprintf('<rect x="%s" y="%s" width="%s" height="%s" />'.PHP_EOL,$x,$y,($bw * $module_width),$bar_height);
				$x += ($bw * $module_width);
			}
		}
		$barcord .= '</g>';
		$x += 10 * $module_width;
		$y += $bar_height;
		
		if($show_text){
			$y += $font_size;
			$tx = 10 * $module_width;
			
			for($i=0;$i<strlen($fcode);$i++){
				$barcord .= sprintf('<text x="%s" y="%s" font-family="%s" font-size="%s">%s</text>',
					$tx,
					$y,
					$font_family,
					$font_size,
					$code[$i]
				);
				$tx += $module_width * (preg_match('/[\d\-\$]/',$code[$i]) ? 12 : 14);
			}
		}
		return self::svg($x,$y,$barcord);
	}
}


