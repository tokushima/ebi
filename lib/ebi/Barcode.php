<?php
namespace ebi;
/**
 * バーコード
 * @author tokushima
 *
 */
class Barcode{
	protected $data = [];
	protected $type = [];
	
	protected $color;
	protected $bar_height;
	protected $module_width;
	
	public function __construct($data, $type, $opt){
		$this->data = $data;
		$this->type = $type;
		$this->setopt($opt);
	}
	
	/**
	 * NW-7 (CODABAR)
	 * @param string $code A0123456789A
	 * @throws \ebi\exception\InvalidArgumentException
	 * @return $this
	 */
	public static function NW7($code){
		if(!preg_match('/^[0123456789ABCD\-\$:\/\.\+]+$/i',$code)){
			throw new \ebi\exception\InvalidArgumentException('detected invalid characters');
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
		$data = [-11]; // quietzone
		for($i=0;$i<strlen($fcode);$i++){
			$data = array_merge($data,$bits[$fcode[$i]]);
		}
		$data[] = -11; // quietzone
		return new static([$data], [], [
			'bar_height'=>\ebi\Calc::mm2px(10),
			'module_width'=>\ebi\Calc::mm2px(0.6),
		]);
	}
	
	/**
	 * EAN13 (JAN13) 4549995186550
	 * @param string $code
	 * @throws \ebi\exception\InvalidArgumentException
	 * @return $this
	 */
	public static function EAN13($code){
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
				[[-11]], // quietzone
				[[1,-1,1]],
				$data[0],
				[[-1,1,-1,1,-1]],
				$data[1],
				[[1,-1,1]],
				[[-7]] // quietzone
			);
		};
		$code = sprintf('%012d',$code);
		
		if(!ctype_digit($code)){
			throw new \ebi\exception\InvalidArgumentException('detected invalid characters');
		}
		$code = (strlen($code) > 12) ? $code : $code.$get_checkdigit_JAN($code);
		return new static($get_data_JAN($code), [], [
			'bar_height'=>\ebi\Calc::mm2px(22.86),
			'module_width'=>\ebi\Calc::mm2px(0.33),
		]);
	}
	
	/**
	 * CODE39
	 * @param string $code 1234567890ABCDEF
	 * @throws \ebi\exception\InvalidArgumentException
	 * @return $this
	 */	
	public static function CODE39($code){
		if(!preg_match('/^[\w\-\. \$\/\+%]+$/i',$code)){
			throw new \ebi\exception\InvalidArgumentException('detected invalid characters');
		}
		
		$pattern = [
			'0'=>123,'1'=>334,'2'=>434,'3'=>531,'4'=>124,'5'=>321,'6'=>421,'7'=>135,'8'=>333,'9'=>433,
			'A'=>344,'B'=>444,'C'=>541,'D'=>164,'E'=>361,'F'=>461,'G'=>145,'H'=>343,'I'=>443,'J'=>163,'K'=>316,'L'=>416,'M'=>517,
			'N'=>176,'O'=>377,'P'=>477,'Q'=>118,'R'=>312,'S'=>412,'T'=>172,'U'=>214,'V'=>614,'W'=>811,'X'=>774,'Y'=>271,'Z'=>671,
			'-'=>715,'.'=>213,' '=>613,'*'=>773,'$'=>751,'/'=>737,'+'=>747,'%'=>157
		];
		$cahrbar = [null,111,221,211,112,212,122,121,222];
		
		$data = [];
		$data[] = -10; // quietzone
		
		$fcode = strtoupper('*'.$code.'*');
		for($i=0;$i<strlen($fcode);$i++){
			$ptn = (string)$pattern[$fcode[$i]];
			$bits = $cahrbar[$ptn[0]].$cahrbar[$ptn[1]].$cahrbar[$ptn[2]];
			
			for($c=0;$c<9;$c++){
				$data[] = $bits[$c] * (($c % 2 === 0) ? 1 : -1);
			}
			$data[] = -1; // gap
		}
		$data[] = -10; // quietzone
		return new static([$data], [], [
			'bar_height'=>\ebi\Calc::mm2px(10),
			'module_width'=>\ebi\Calc::mm2px(0.33),
		]);
	}
	
	/**
	 * 郵便カスタマーバーコードード
	 * @param string $zip 1050011
	 * @param string $address ４丁目２−８ （町域以降の住所）
	 * @return $this
	 * @see https://www.post.japanpost.jp/zipcode/zipmanual/index.html
	 */
	public static function CustomerBarcode($zip,$address=''){
		$data = $type = [];
		// CC1=!, CC2=#, CC3=%, CC4=@, CC5=(, CC6=), CC7=[, CC8=]
		
		$bits = [
			'0'=>[1,4,4],'1'=>[1,1,4],'2'=>[1,3,2],'3'=>[3,1,2],'4'=>[1,2,3],
			'5'=>[1,4,1],'6'=>[3,2,1],'7'=>[2,1,3],'8'=>[2,3,1],'9'=>[4,1,1],
			'!'=>[3,2,4],'#'=>[3,4,2],'%'=>[2,3,4],'@'=>[4,3,2],'('=>[2,4,3],
			')'=>[4,2,3],'['=>[4,4,1],']'=>[1,1,1],'-'=>[4,1,4],
		];
		$alphabits = [
			'A'=>'!0','B'=>'!1','C'=>'!2','D'=>'!3','E'=>'!4','F'=>'!5','G'=>'!6','H'=>'!7','I'=>'!8','J'=>'!9',
			'K'=>'#0','L'=>'#1','M'=>'#2','N'=>'#3','O'=>'#4','P'=>'#5','Q'=>'#6','R'=>'#7','S'=>'#8','T'=>'#9',
			'U'=>'%0','V'=>'%1','W'=>'%2','X'=>'%3','Y'=>'%4','Z'=>'%5',
		];
		$cdbits = [
			'0'=>0,'1'=>1,'2'=>2,'3'=>3,'4'=>4,'5'=>5,'6'=>6,'7'=>7,'8'=>8,'9'=>9,
			'-'=>10,'!'=>11,'#'=>12,'%'=>13,'@'=>14,'('=>15,')'=>16,'['=>17,']'=>18,
		];
		
		$zip = mb_convert_kana($zip,'a');
		$zip = str_replace('-','',$zip);
		
		if(!ctype_digit($zip)){
			throw new \ebi\exception\InvalidArgumentException('detected invalid characters');
		}
		if(!empty($address)){
			$address = mb_convert_kana($address,'as');
			$address = mb_strtoupper($address);
			$address = preg_replace('/[&\/・.]/u','',$address);
			$address = preg_replace('/[A-Z]{2,}/u','-',$address);
			
			$m = [];
			if(preg_match_all('/([一二三四五六七八九十]+)(丁目|丁|番地|番|号|地割|線|の|ノ)/u',$address,$m)){
				foreach($m[0] as $k => $v){
					$v = preg_replace('/([一二三四五六七八九]+)十([一二三四五六七八九])/u','${1}${2}',$v);
					$v = preg_replace('/([一二三四五六七八九]+)十/u','${1}0',$v);
					$v = preg_replace('/十([一二三四五六七八九]+)/u','1${1}',$v);
					
					$address = str_replace($m[0][$k],str_replace(['一','二','三','四','五','六','七','八','九','十'],[1,2,3,4,5,6,7,8,9,10],$v),$address);
				}
			}
			$address = preg_replace('/[^\w-]/','-',$address);
			
			$address = preg_replace('/(\d)F$/','$1',$address);
			$address = preg_replace('/(\d)F/','$1-',$address);
			$address = preg_replace('/[-]+/','-',$address);
			$address = preg_replace('/-([A-Z]+)/','$1',$address);
			$address = preg_replace('/([A-Z]+)-/','$1',$address);
			
			if($address[0] === '-'){
				$address = substr($address,1);
			}
			if(substr($address,-1) === '-'){
				$address = substr($address,0,-1);
			}
		}
		
		$chardata = '';
		$str = $zip.$address;
		for($i=0;$i<strlen($str);$i++){
			$chardata .= ctype_alpha($str[$i]) ? $alphabits[$str[$i]] : $str[$i];
		}
		for($i=strlen($chardata);$i<20;$i++){
			$chardata .= '@';
		}
		$chardata = substr($chardata,0,20);
		
		// start
		array_push($data,-1,-1,-1,1,-1,1);
		array_push($type,0,0,0,1,0,3);
		
		$cdsum = 0;
		for($i=0;$i<strlen($chardata);$i++){
			foreach($bits[$chardata[$i]] as $t){
				array_push($data,-1,1);
				array_push($type,0,$t);
			}
			$cdsum += $cdbits[$chardata[$i]];
		}
		
		// ( N + sum ) % 19 === 0
		$cd = array_search(($cdsum % 19 === 0) ? 0 : 19 - ($cdsum % 19),$cdbits);
		
		// check digit
		foreach($bits[$cd] as $t){
			array_push($data,-1,1);
			array_push($type,0,$t);
		}
		
		// end
		array_push($data,-1,1,-1,1,-1,-1);
		array_push($type,0,3,0,1,0,0);
		
		return new static([$data], [$type], [
			'bar_height'=>\ebi\Calc::mm2px(3.6),
			'module_width'=>\ebi\Calc::mm2px(0.6),		
		]);
	}
	
	protected function bar_type($i,$j){
		if(!empty($this->type)){
			$div_bar = $this->bar_height / 3;

			switch($this->type[$i][$j] ?? 1){
				case 1: // ロングバー
					return [0, $this->bar_height];
				case 2: // セミロングバー（上）
					return [0, $div_bar * 2];
				case 3: // セミロングバー（下）
					return [$div_bar, $this->bar_height];
				case 4: // タイミングバー
					return [$div_bar, $div_bar * 2];
				default:
			}
		}
		return [0,$this->bar_height];
	}

	protected function setopt($opt){
		$this->color = $opt['color'] ?? '#000000';
		$this->bar_height = $opt['bar_height'] ?? $this->bar_height;
		$this->module_width = $opt['module_width'] ?? $this->module_width;
	}

	/**
	 * Imageで返す
	 * @param array $opt
	 * 
	 * opt:
	 * 	string $color #000000
	 *  string $bgcolor #FFFFFF
	 * 	number $bar_height バーコードの高さ
	 * 	number $module_width 1モジュールの幅
	 * 
	 * @return \ebi\Image
	 */
	public function image($opt=[]){
		$this->setopt($opt);
		$w = 0;
		foreach($this->data as $d){
			foreach($d as $bw){
				$w += ($bw < 0) ? ($bw * -1) * $this->module_width : ($bw * $this->module_width);
			}
		}
		
		$x = 0;
		$image = \ebi\Image::create($w, $this->bar_height, $opt['bgcolor'] ?? null);
		
		foreach($this->data as $i => $d){
			foreach($d as $j => $bw){
				if($bw < 0){
					$x += ($bw * -1) * $this->module_width;
				}else{
					list($sy,$ey) = $this->bar_type($i,$j);
					
					for($j=0;$j<$bw;$j++){
						$x++;
						$image->line($x, $sy, $x, $ey, $this->color);
					}
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
		$this->setopt($opt);
		$width = $opt['width'] ?? 0;
		$height = $opt['height'] ?? 0;
		$x = 0;
		
		$barcord = sprintf('<g fill="%s">',$this->color);
		
		foreach($this->data as $i => $d){
			foreach($d as $j => $bw){
				if($bw < 0){
					$x += ($bw * -1) * $this->module_width;
				}else{
					list($y,$h) = $this->bar_type($i,$j);
					$barcord .= sprintf(
						'<rect x="%s" y="%s" width="%s" height="%s" />'.PHP_EOL,
						$x,$y,($bw * $this->module_width),$h
					);
					$x += ($bw * $this->module_width);
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

	/**
	 * 登録されたデータ [data[], type[]]
	 * @return array [number[],$number[]]
	 */
	public function raw(){
		return [$this->data,$this->type];
	}
}


