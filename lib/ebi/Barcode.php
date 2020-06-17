<?php
namespace ebi;
/**
 * バーコード
 * @author tokushima
 *
 */
class Barcode{
	private $data = [];
	
	public function __construct($data){
		$this->data = $data;
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
	 *
	 * @return array
	 */
	public static function NW7($code){
		if(!preg_match('/^[0123456789ABCD\-\$:\/\.\+]+$/i',$code)){
			throw new \ebi\exception\InvalidArgumentException('invalid characters detected');
		}
		if(!preg_match('/^[ABCD]/i',$code) || !preg_match('/[ABCD]$/i',$code)){
			throw new \ebi\exception\InvalidArgumentException('Start / Stop code is not [A, B, C, D]');
		}
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
		return new self([$data]);
	}
	
	public static function JAN13($code){
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
		$code = sprintf('%012d',$code);
		
		if(!ctype_digit($code)){
			throw new \ebi\exception\InvalidArgumentException();
		}
		$code = (strlen($code) > 12) ? $code : $code.$get_checkdigit_JAN($code);
		return new self($get_data_JAN($code));
	}
	
	/**
	 * Imageで返す
	 * @param array $opt
	 * 
	 * opt:
	 * 	string $color #000000
	 * 	number $bar_height バーコードの高さ
	 * 	number $module_width 1モジュールの幅
	 * 
	 * @return \ebi\Image
	 */
	public function image($opt=[]){
		$color = $opt['color'] ?? '#000000';
		$bar_height = $opt['bar_height'] ?? 50;
		$module_width = $opt['module_width'] ?? 2;
		$x = $module_width * 11;
		$y = 0;
		
		$image = \ebi\Image::create(400, 100);
		foreach($this->data as $d){
			foreach($d as $bw){
				if($bw < 0){
					$x += ($bw * -1) * $module_width;
				}else{
					$image->rectangle($x, $y, ($bw * $module_width), $bar_height, $color,1,true);
					$x += ($bw * $module_width);
				}
			}
		}
		return $image;
	}
	
	/**
	 * SVG文字列を返す
	 * @param mixed{} $opt
	 *
	 * opt:
	 * 	string $color #000000
	 * 	number $bar_height バーコードの高さ
	 * 	number $module_width 1モジュールの幅
	 *  number $width
	 *  number $height
	 *
	 * @throws \ebi\exception\InvalidArgumentException
	 * @return string
	 */
	public function svg($opt=[]){
		$color = $opt['color'] ?? '#000000';
		$bar_height = $opt['bar_height'] ?? 50;
		$module_width = $opt['module_width'] ?? 2;
		$width = $opt['width'] ?? 0;
		$height = $opt['height'] ?? 0;
		
		$x = $module_width * 11;
		$y = 0;
		$barcord = sprintf('<g fill="%s">',$color);
		
		foreach($this->data as $d){
			foreach($d as $bw){
				if($bw < 0){
					$x += ($bw * -1) * $module_width;
				}else{
					$barcord .= sprintf('<rect x="%s" y="%s" width="%s" height="%s" />'.PHP_EOL,$x,$y,($bw * $module_width),$bar_height);
					$x += ($bw * $module_width);
				}
			}
		}
		$barcord .= '</g>';
		$viewbix = (!empty($width) && !empty($height)) ? sprintf('viewBox="0 0 %s %s"',$width,$height) : '';
		
		return sprintf(
			'<?xml version="1.0" standalone="no" ?>'.PHP_EOL.
			'<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">'.PHP_EOL.
			'<svg version="1.1" xmlns="http://www.w3.org/2000/svg" %s>'.PHP_EOL.
			'%s'.PHP_EOL.
			'</svg>',
			$viewbix,$barcord
		);
	}
}


