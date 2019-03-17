<?php
namespace ebi;
/**
 * 
 * @author tokushima
 *
 */
class Image{
	/**
	 * 縦向き
	 * @var integer
	 */
	const ORIENTATION_PORTRAIT = 1;
	/**
	 * 横向き
	 * @var integer
	 */
	const ORIENTATION_LANDSCAPE = 2;
	/**
	 * 正方形
	 * @var integer
	 */
	const ORIENTATION_SQUARE = 3;
	
	const CHANNELS_GLAY = 1;
	const CHANNELS_RGB = 3;
	const CHANNELS_CMYK = 4;
	
	const TEXT_ALIGN_LEFT = 0;
	const TEXT_ALIGN_CENTER = 1;
	const TEXT_ALIGN_RIGHT = 2;
	
	const TEXT_VALIGN_TOP = 0;
	const TEXT_VALIGN_MIDDLE = 1;
	const TEXT_VALIGN_BOTTOM = 2;

	private static $font_path = [];
	private $canvas;
	
	/**
	 * 
	 * @param string $filename
	 */
	public function __construct($filename){
		if($filename != __FILE__){
			try{
				$size = getimagesize($filename);
				
				switch($size['mime']){
					case  'image/jpeg':
						$this->canvas = imagecreatefromjpeg($filename);
						break;
					case 'image/png':
						$this->canvas = imagecreatefrompng($filename);
						break;
					case 'image/gif':
						$this->canvas = imagecreatefromgif($filename);
						break;
					default:
						throw new \ebi\exception\ImageException();
				}
			}catch(\Exception $e){
				throw new \ebi\exception\ImageException($filename);
			}
		}
	}
	
	public function __destruct(){
		if(is_resource($this->canvas)){
			imagedestroy($this->canvas);
		}
	}
	
	/**
	 * フォントファイルパスに名前を設定する
	 * @param string $font_path
	 * @param string $font_name
	 */
	public static function set_font($font_path,$font_name=null){
		if(empty($font_name)){
			$font_name = preg_replace('/^(.+)\..+$/','\\1',basename($font_path));
		}
		self::$font_path[$font_name] = $font_path;
	}
	
	/**
	 * バイナリ文字列から画像を読み込む
	 * @param string $string
	 * @return \ebi\Image
	 */
	public static function read($string){
		$self = new static(__FILE__);
		
		try{
			$self->canvas = imagecreatefromstring($string);
			
			if($self->canvas === false){
				throw new \ebi\exception\ImageException();
			}
		}catch(\Exception $e){
			throw new \ebi\exception\ImageException($e->getMessage());
		}
		return $self;
	}

	/**
	 * 塗りつぶした矩形を作成する
	 * @param integer $width
	 * @param integer $height
	 * @param string $color
	 * @return \ebi\Image
	 */
	public static function create($width,$height,$color='#FFFFFF'){
		$self = new static(__FILE__);
		
		try{
			list($r,$g,$b) = self::color2rgb($color);
			
			$self->canvas = imagecreatetruecolor($width,$height);
			imagefilledrectangle(
				$self->canvas,
				0,
				0,
				$width,
				$height,
				imagecolorallocate($self->canvas,$r,$g,$b)
			);
		}catch(\Exception $e){
			throw new \ebi\exception\ImageException();
		}
		return $self;
	}
	
	/**
	 * 定義配列を統合してイメージを作成
	 * @param integer $width
	 * @param integer $height
	 * @param array $layers 
	 * @param array $opt
	 * @return \ebi\Image
	 */
	public static function flatten($width,$height,array $layers,array $opt=[]){
		$background_color = $opt['background-color'] ?? '#FFFFFF';
		$transparent_color = $opt['transparent-color'] ?? null;
		$default_font = $opt['font'] ?? null;
		$img = self::create($width, $height,$background_color);
		
		usort($layers,function($a,$b){
			$za = $a['z'] ?? 0;
			$zb = $b['z'] ?? 0;
			
			if($za == $zb){
				return 0;
			}
			return ($za < $zb) ? -1 : 1;
		});
		
		foreach($layers as $layer){
			$x = $layer['x'] ?? 0;
			$y = $layer['y'] ?? 0;
			
			$pct = $layer['pct'] ?? 100;
			
			if($width >= $x && $height >= $y){
				if(isset($layer['src'])){
					if($layer['src'] instanceof self){
						if(!empty($transparent_color)){
							$layer['src']->transparent_color($transparent_color);
						}
						
						$img->merge($x, $y, $layer['src'],$pct);
					}else if(is_file($layer['src'])){
						$m = new static($layer['src']);
						
						if(!empty($transparent_color)){
							$m->transparent_color($transparent_color);
						}
						$img->merge($x,$y,$m,$pct);
					}
				}else if(isset($layer['text'])){
					$font_color = $layer['color'] ?? '#000000';
					$font_size = $layer['size'] ?? 16;
					$font_name = $layer['font'] ?? $default_font;
					
					$extrainfo = [
						'angle'=>($layer['angle'] ?? 0),
						'width'=>($layer['width'] ?? ($width - $x)),
						'height'=>($layer['height'] ?? null),
						'align'=>($layer['align'] ?? 0),
						'valign'=>($layer['valign'] ?? 0),
						'linespacing'=>(($layer['leading'] ?? $font_size) / $font_size),
					];
					
					if($pct == 100){
						$img->text($x, $y, $font_color, $font_size, $font_name, $layer['text'],$extrainfo);
					}else{
						$font_color = strtoupper($font_color);
						$transparent_color = (strtoupper($font_color) == '#FFFFFF') ? '#000000' : '#FFFFFF';
						
						$m = self::create($width, $height,$transparent_color);
						$m->text($x, $y, $font_color, $font_size, $font_name, $layer['text'],$extrainfo);
						$m->transparent_color($transparent_color);
						
						$img->merge(0,0,$m,$pct);
					}
				}
			}
		}
		return $img;
	}
	
	/**
	 * 矩形を描画する
	 * @param integer $x
	 * @param integer $y
	 * @param integer $width
	 * @param integer $height
	 * @param string $color
	 * @return \ebi\Image
	 */
	public function rectangle($x,$y,$width,$height,$color,$fill=false){
		list($r,$g,$b) = self::color2rgb($color);
		
		if($fill){
			imagefilledrectangle($this->canvas,$x,$y,$x + $width,$y + $height,imagecolorallocate($this->canvas,$r,$g,$b));
		}else{
			imagerectangle($this->canvas,$x,$y,$x + $width,$y + $height,imagecolorallocate($this->canvas,$r,$g,$b));
		}
		return $this;
	}
	
	
	/**
	 * ファイルに書き出す
	 * @param string $filename
	 * @return string
	 */
	public function write($filename=null){
		if(empty($filename)){
			$filename = \ebi\WorkingStorage::tmpfile(null,'.jpg');
		}
		\ebi\Util::mkdir(dirname($filename));
		
		$type = 'jpg';
		
		$m = [];
		if(preg_match('/\.([\w]+)$/',$filename,$m)){
			$type = strtolower($m[1]);
		}
		switch($type){
			case 'jpeg':
			case 'jpg':
				imagejpeg($this->canvas,$filename);
				break;
			case 'png':
				imagepng($this->canvas,$filename);
				break;
			case 'gif':
				imagegif($this->canvas,$filename);
				break;
			default:
				imagejpeg($this->canvas,$filename);
		}
		return $filename;
	}	
	
	/**
	 * 画像をブラウザに出力する
	 * @return \ebi\Image
	 */
	public function output($format='jpeg'){
		$format = strtolower($format);
	
		switch($format){
			case 'png':
				header('Content-Type: image/png');
				break;
			case 'gif':
				header('Content-Type: image/gif');
				break;
			default:
				header('Content-Type: image/jpeg');
				$format = 'jpeg';
		}		
		switch($format){
			case 'png':
				imagepng($this->canvas);
				break;
			case 'gif':
				imagegif($this->canvas);
				break;
			default:
				imagejpeg($this->canvas);
		}
		exit;
	}
	
	/**
	 * 画像を返す
	 * @param string $format
	 * @return string
	 */
	public function get($format='jpeg'){
		$format = strtolower($format);
		
		ob_start();
			switch($format){
				case 'png':
					imagepng($this->canvas);
					break;
				case 'gif':
					imagegif($this->canvas);
					break;
				default:
					imagejpeg($this->canvas);
			}
		return ob_get_clean();		
	}
	
	/**
	 * 指定した幅と高さに合うようにリサイズとトリミングをする
	 * @param integer $width
	 * @param integer $height
	 */
	public function crop_resize($width,$height){
		if($this->get_orientation() == self::ORIENTATION_PORTRAIT){
			$this->resize($width,$height,true)->crop($width, $height,null,0);
		}else{
			$this->resize($width,$height,true)->crop($width, $height);
		}
		return $this;
	}
	/**
	 * 画像の一部を抽出する
	 * @param integer $width 抽出する幅
	 * @param integer $height 抽出する高さ
	 * @param integer $x 抽出する領域の左上の X 座標
	 * @param integer $y 抽出する領域の左上の Y 座標
	 * @throws \ebi\exception\ImageException
	 * @return \ebi\Image
	 */
	public function crop($width,$height,$x=null,$y=null){
		list($w,$h) = $this->get_size();
		
		if($width >= $w && $height >= $h){
			return $this;
		}
		if($x === null){
			$x = floor(($w - $width) / 2);
			$x = ($x >= 0) ? $x : 0;
		}
		if($y === null){
			$y = floor(($h - $height) / 2);
			$y = ($y >= 0) ? $y : 0;
		}
		$canvas = imagecrop($this->canvas, [
			'x'=>ceil($x),
			'y'=>ceil($y),
			'width'=>ceil($width),
			'height'=>ceil($height)
		]);
		
		if($canvas === false){
			throw new \ebi\exception\ImageException();
		}
		imagedestroy($this->canvas);
		$this->canvas = $canvas;
		
		return $this;
	}
	/**
	 * サイズ
	 * @return integer[] width,height
	 */
	public function get_size(){
		$w = imagesx($this->canvas);
		$h = imagesy($this->canvas);
		
		return [$w,$h];
	}
	
	/**
	 * 画像のサイズを変更する
	 * @param integer $width 変更後の幅
	 * @param integer $height 変更後の高さ
	 * @param boolean $aspect_ratio アスペクト比を維持する
	 * @throws \ebi\exception\ImageException
	 * @return \ebi\Image
	 */
	public function resize($width,$height=null,$aspect_ratio=true){
		list($w,$h) = $this->get_size();
		$m = self::magnification($w,$h,$width,$height,$aspect_ratio);
		$cw = ceil($w * $m);
		$ch = ceil($h * $m);
		
		$canvas = imagecreatetruecolor($cw,$ch);
		if(false === imagecopyresampled($canvas,$this->canvas,0,0,0,0,$cw,$ch,$w,$h)){
			throw new \ebi\exception\ImageException();
		}			
		imagedestroy($this->canvas);
		$this->canvas = $canvas;
		
		return $this;
	}
	
	/**
	 * 回転
	 * @param integer $angle 角度
	 * @param string $background_color
	 * @return \ebi\Image
	 */
	public function rotate($angle,$background_color='#000000'){
		list($r,$g,$b) = self::color2rgb($background_color);
		
		$color = imagecolorallocate($this->canvas,$r,$g,$b);
		$canvas = imagerotate($this->canvas,$angle,(($color === false) ? 0 : $color));
		imagedestroy($this->canvas);
		$this->canvas = $canvas;
		
		return $this;
	}
	
	/**
	 * 透明色の設定
	 * @param string $color
	 * @return \ebi\Image
	 */
	public function transparent_color($color='#000000'){
		list($r,$g,$b) = self::color2rgb($color);
		
		$transparent_color = imagecolorallocate($this->canvas,$r,$g,$b);
		
		imagecolortransparent($this->canvas, $transparent_color);
		
		return $this;
	}
	
	/**
	 * テキストを画像に書き込む
	 * @param integer $x 左上座標
	 * @param integer $y　左上座標
	 * @param string $font_color
	 * @param number $font_point_size フォントサイズ
	 * @param string $font_name
	 * @param string $text
	 * 
	 * @param number $angle 回転軸は左下
	 * @param number $linespacing 行間隔、フォントサイズとの比率
	 * @return \ebi\Image
	 */
	public function text($x,$y,$font_color,$font_point_size,$font_name,$text,$extrainfo=[]){
		if(!isset(self::$font_path[$font_name])){
			throw new \ebi\exception\UndefinedException('undefined font `'.$font_name.'`');
		}
		$angle = ($extrainfo['angle'] ?? 0) * -1;
		$linespacing = $extrainfo['linespacing'] ?? 1;
		$box_width = $extrainfo['width'] ?? null;
		$box_height = $extrainfo['height'] ?? null;
		$box_align = $extrainfo['align'] ?? 0;
		$box_valign = $extrainfo['valign'] ?? 0;
		
		$font_box = imageftbbox(
			$font_point_size,
			$angle,
			self::$font_path[$font_name],
			$text,
			['linespacing'=>$linespacing]
		);
		$text_width = $font_box[2] - $font_box[0];
		
		if(!empty($box_width)){
			if($text_width > $box_width){
				$len = mb_strlen($text);
	
				$t = '';
				$s = 0;
				for($i=0;$i<$len;$i++){
					$font_box = imageftbbox(
						$font_point_size,
						$angle,
						self::$font_path[$font_name],
						mb_substr($text,$s,$i-$s+1),
						['linespacing'=>$linespacing]
					);
					$w = $font_box[2] - $font_box[0];
					
					if($w > $box_width){
						$t .= mb_substr($text,$s,$i-$s).PHP_EOL;
						$s = $i;
						$i--;
					}
				}
				$text = $t.(($s < $i) ? mb_substr($text,$s) : '');
			}
			
			if($box_align > 0 || $box_valign > 0){
				$font_box = imageftbbox(
					$font_point_size,
					$angle,
					self::$font_path[$font_name],
					$text,
					['linespacing'=>$linespacing]
				);
				$text_width = $font_box[2] - $font_box[0];
				$text_height = $font_box[3] - $font_box[5];
				
				if($box_width > $text_width){
					if($box_align === self::TEXT_ALIGN_CENTER){
						$x = ($x + $box_width - $text_width) / 2;
					}else if($box_align === self::TEXT_ALIGN_RIGHT){
						$x = $x + $box_width - $text_width;
					}
				}
				if($box_height > $text_height){
					if($box_valign === self::TEXT_VALIGN_MIDDLE){
						$y = ($y + $box_height - $text_height) / 2;
					}else if($box_valign === self::TEXT_VALIGN_BOTTOM){
						$y = $y + $box_height - $text_height;
					}
				}
			}
		}
		list($r,$g,$b) = self::color2rgb($font_color);
	
		imagefttext(
			$this->canvas,
			$font_point_size,
			$angle,
			$x,
			($y + $font_point_size),
			imagecolorallocate($this->canvas,$r,$g,$b),
			self::$font_path[$font_name],
			$text,
			['linespacing'=>$linespacing]
		);
		return $this;
	}
		
	/**
	 * 画像を結合する
	 * $pctを指定した場合はアルファ透過が有効になりPNGの透過情報が失われる
	 * 
	 * @param integer $x
	 * @param integer $y
	 * @param \ebi\Image $img
	 * @param number $pct
	 * @return \ebi\Image
	 */
	public function merge($x,$y,\ebi\Image $img,$pct=100){
		list($width,$height) = $img->get_size();
		
		if($pct == 100){
			imagecopy($this->canvas,$img->canvas,ceil($x),ceil($y),0,0,$width,$height);
		}else{
			imagecopymerge($this->canvas,$img->canvas,ceil($x),ceil($y),0,0,$width,$height,$pct);
		}
		return $this;
	}
	
	/**
	 * カラーモードからRGB（10進数）を返す
	 * @param string $color_code
	 * @return integer[] R,G,B
	 */
	private static function color2rgb($color_code){
		if(substr($color_code,0,1) == '#'){
			$color_code = substr($color_code,1);
		}
		if(strlen($color_code) == 6){
			$r = hexdec(substr($color_code,0,2));
			$g = hexdec(substr($color_code,2,2));
			$b = hexdec(substr($color_code,4,2));
		}else{
			$r = hexdec(substr($color_code,0,1));
			$g = hexdec(substr($color_code,1,1));
			$b = hexdec(substr($color_code,2,1));
		}
		return [$r,$g,$b];
	}
		
	/**
	 * 画像の向き
	 * @return  integer
	 */
	public function get_orientation(){
		list($w,$h) = $this->get_size();
		return self::judge_orientation($w, $h);
	}
	
	private static function judge_orientation($w,$h){
		$d = $h / $w;
		
		if($d <= 1.02 && $d >= 0.98){
			return self::ORIENTATION_SQUARE;
		}else if($d > 1){
			return self::ORIENTATION_PORTRAIT;
		}
		return self::ORIENTATION_LANDSCAPE;
	}
	
	private static function check_file_type($filename,$header,$footer){
		$fp = fopen($filename,'rb');
		$a = unpack('H*',fread($fp,$header));
		fseek($fp,$footer * -1,SEEK_END);
		$b = unpack('H*',fread($fp,$footer));
		fclose($fp);
		return [($a[1] ?? null),($b[1] ?? null)];
	}
	
	/**
	 * 画像の情報
	 *  integer width
	 *  integer height
	 *  integer orientation 画像の向き 1: PORTRAIT, 2: LANDSCAPE, 3: SQUARE
	 *  string mime 画像形式のMIMEタイプ
	 *  integer bits
	 *  integer channels 1: GRAY, 3: RGB, 4: CMYK
	 *  boolean broken 画像ファイルとして破損しているか
	 *  
	 * @param string $filename
	 * @return mixed{}
	 * @see http://jp2.php.net/manual/ja/function.getimagesize.php
	 * @see http://jp2.php.net/manual/ja/function.image-type-to-mime-type.php
	 */
	public static function get_info($filename){
		$info = getimagesize($filename);
		$mime = $info['mime'] ?? null;
		$broken = null;
		
		if($mime == 'image/jpeg'){
			$broken = (['ffd8','ffd9'] != self::check_file_type($filename, 2, 2));
		}else if($mime == 'image/png'){
			$broken = (['89504e470d0a1a0a','0000000049454e44ae426082'] != self::check_file_type($filename, 8, 12));
		}else if($mime == 'image/gif'){
			$broken = (['474946','3b'] != self::check_file_type($filename, 3, 1));
		}
		
		return [
			'width'=>$info[0],
			'height'=>$info[1],
			'orientation'=>self::judge_orientation($info[0],$info[1]),
			'mime'=>$mime,
			'bits'=>$info['bits'] ?? null,
			'channels'=>$info['channels'] ?? null,
			'broken'=>$broken,
		];
	}
	
	/**
	 * PDFのバージョンを取得
	 * @param string $filename
	 * @throws \ebi\exception\IllegalDataTypeException
	 * @return string
	 */
	public static function get_pdf_version($filename){
		$fp = fopen($filename,'rb');
			$value = trim(fgets($fp));
		fclose($fp);
		
		$m = [];
		if(preg_match('/^%PDF\-(.+)/',$value,$m)){
			return preg_replace('/[^\d\.]/','',$m[1]);
		}
		throw new \ebi\exception\IllegalDataTypeException();
	}
	
	/**
	 * 矩形(SVG)
	 * @param integer $width (px)
	 * @param integer $height (px)
	 * @param string $color
	 * @param number $opacity 0..1
	 * @return string
	 */
	public static function get_rect_svg($width,$height,$color='#000000',$opacity=1){
		return sprintf(
			'<?xml version="1.0" standalone="no" ?>'.PHP_EOL.
			'<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">'.PHP_EOL.
			'<svg width="%d" height="%d" version="1.1" xmlns="http://www.w3.org/2000/svg"><g>'.PHP_EOL.
			'<rect x="0" y="0" width="%d" height="%d" fill="%s"  fill-opacity="%s" />'.PHP_EOL.
			'</g></svg>',
			$width,$height,$width,$height,$color,$opacity
		);
	}
	
	/**
	 * 拡大率
	 * @param number $a_width
	 * @param number $a_height
	 * @param number $b_width
	 * @param number $b_height
	 * @param boolean $aspect_ratio アスペクト比を維持する
	 * @return number
	 */
	private static function magnification($a_width,$a_height,$b_width,$b_height=null,$aspect_ratio=true){
		$rw = empty($b_width) ? 1 : $b_width;
		$rh = empty($b_height) ? 1 : $b_height;
		
		if(!empty($b_width) && !empty($b_height)){
			$aw = $rw / $a_width;
			$ah = $rh / $a_height;
			return $aspect_ratio ? max($aw,$ah) : min($aw,$ah);
		}else if(!isset($b_height)){
			return $rw / $a_width;
		}
		return $rh / $a_height;
	}
}