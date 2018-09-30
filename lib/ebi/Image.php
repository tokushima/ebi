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
		
	private $canvas;
	private $font_path;
	
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
	public static function filled_rectangle($width,$height,$color='#FFFFFF'){
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
	 * 矩形を描画する
	 * @param integer $x
	 * @param integer $y
	 * @param integer $width
	 * @param integer $height
	 * @param string $color
	 * @return \ebi\Image
	 */
	public function rectangle($x,$y,$width,$height,$color){
		list($r,$g,$b) = self::color2rgb($color);
		imagerectangle($this->canvas,$x,$y,$x + $width,$y + $height,imagecolorallocate($this->canvas,$r,$g,$b));
		
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
	 * @param boolean $minimum widthまたはheightの値を最小値とする
	 * @throws \ebi\exception\ImageException
	 * @return \ebi\Image
	 */
	public function resize($width,$height=null,$minimum=true){
		list($w,$h) = $this->get_size();
		$m = \ebi\Calc::magnification($w,$h,$width,$height,$minimum);
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
	 * フォントを設定する
	 * @param string $font_path
	 * @return \ebi\Image
	 */
	public function font($font_path){
		$this->font_path = $font_path;
		
		return $this;
	}
	
	/**
	 * テキストを画像に書き込む、座標は左下が原点
	 * @param integer $x
	 * @param integer $y
	 * @param string $font_color
	 * @param number $font_point_size
	 * @param string $text
	 * @param number $angle
	 * @throws \ebi\exception\UndefinedException
	 * @return \ebi\Image
	 */
	public function text($x,$y,$font_color,$font_point_size,$text,$angle=0){
		if(empty($this->font_path)){
			throw new \ebi\exception\UndefinedException('undefined font');
		}
		list(,$text_height) = $this->get_textbox_size($font_point_size,$text,$angle);
		
		$angle = $angle * -1;
		
		list($r,$g,$b) = self::color2rgb($font_color);
		
		imagettftext(
			$this->canvas,
			$font_point_size,
			$angle,
			$x,
			($y + $text_height),
			imagecolorallocate($this->canvas,$r,$g,$b),
			$this->font_path,
			$text
		);
		
		return $this;
	}
	
	/**
	 * テキストボックスのサイズ
	 * @param number $font_point_size
	 * @param number $text
	 * @param number $angle
	 * @return number[]
	 */
	public function get_textbox_size($font_point_size,$text,$angle=0){
		$font_box = imageftbbox($font_point_size,$angle, $this->font_path, $text);
		return [($font_box[2] - $font_box[0]),($font_box[1] - $font_box[7])];
	}
	
	/**
	 * 画像を結合する
	 * @param integer $x
	 * @param integer $y
	 * @param \ebi\Image $img
	 * @param number $pct
	 * @return \ebi\Image
	 */
	public function merge($x,$y,\ebi\Image $img,$pct=100){
		list($wight,$height) = $img->get_size();
		
		imagecopymerge(
			$this->canvas,
			$img->canvas,
			ceil($x),
			ceil($y),
			0,
			0,
			$wight,
			$height,
			$pct
		);
		
		return $this;
	}
	
	/**
	 * 写真配置計算
	 * @param integer $width 台紙の幅
	 * @param integer $height 台紙の高さ
	 * @param integer $resize_type 0: フチなし, 1: フチあり, 2: 広フチ, 3: 下フチ
	 * @number[] x, y, width, height
	 */
	public static function get_photo_layout($width,$height,$resize_type){
		$gap = ceil($width * 0.045);
		$margin = ceil($height * 0.15);

		switch($resize_type){
			case 0:
				$pw = $width;
				$ph = $height;
				$x = 0;
				$y = 0;
				break;
			case 1:
				$pw = $width - $gap - $gap;
				$ph = $height - $gap - $gap;
				$x = $gap;
				$y = $gap;
				break;
			case 2:
				$pw = $width - $gap - $gap;
				$ph = $height - $margin - $margin;
				$x = $gap;
				$y = $margin;
				break;
			case 3:
				$pw = $width - $gap - $gap;
				$ph = $height - $gap - $margin;
				$x = $gap;
				$y = $gap;
				break;
		}
		return [$x,$y,$pw,$ph];
	}
	
	
	/**
	 * グリッドレイアウト配置情報
	 * @param number $width
	 * @param number $height
	 * @param array $image_layout [path=>[x%,y%,w%,h%]]
	 * @param integer $grid_gap px
	 * @return number{} [filename=>[width,height,crop_x,crop_y,merge_x,merge_y]]
	 */
	public static function get_grid_layout($width,$height,array $image_layout,$grid_gap=0){
		$grid_info = [];
		
		$w = $width - $grid_gap;
		$h = $height - $grid_gap;
		
		foreach($image_layout as $filename => $layout){
			$info = self::get_info($filename);
			
			$mx = ($w * $layout[0] / 100) + $grid_gap;
			$my = ($h * $layout[1] / 100) + $grid_gap;
			
			$lw = ($w * $layout[2] / 100) - $grid_gap;
			$lh = ($h * $layout[3] / 100) - $grid_gap;
			
			$m = \ebi\Calc::magnification($info['width'],$info['height'],$lw,$lh);
			$cw = ($info['width'] * $m);
			$ch = ($info['height'] * $m);
			
			$cx = floor(($cw - $lw) / 2);
			$cx = ($cx >= 0) ? $cx : 0;
			
			if($info['orientation'] == self::ORIENTATION_PORTRAIT){
				$cy = 0;
			}else{
				$cy = floor(($ch - $lh) / 2);
				$cy = ($cy >= 0) ? $cy : 0;
			}
			$grid_info[$filename] = [
				'width'=>$lw,
				'height'=>$lh,
				'crop_x'=>$cx,
				'crop_y'=>$cy,
				'merge_x'=>$mx,
				'merge_y'=>$my,
			];
		}
		return $grid_info;
	}
	
	/**
	 * グリッドレイアウトでマージする
	 * @param array $image_layout [path=>[x%,y%,w%,h%]]
	 * @param integer $grid_gap px
	 */
	public function grid(array $image_layout,$grid_gap=0){
		list($width,$height) = $this->get_size();
		
		$grid_info = self::get_grid_layout($width,$height,$image_layout,$grid_gap);
		
		foreach($grid_info as $filename => $info){
			$img = new static($filename);
			$img->resize($info['width'],$info['height']);
			$img->crop($info['width'],$info['height'],$info['crop_x'],$info['crop_y']);
			
			$this->merge(ceil($info['merge_x']),ceil($info['merge_y']),$img);
		}
		return $this;
	}
	
	/**
	 * 写真をリサイズしてcropする
	 * @param integer $width
	 * @param integer $height
	 */
	public function photo_crop($width,$height){
		if($this->get_orientation() == self::ORIENTATION_PORTRAIT){
			$this->resize($width,$height,true)->crop($width, $height,null,0);
		}else{
			$this->resize($width,$height,true)->crop($width, $height);
		}
		return $this;
	}
	/**
	 * 切り取ってサムネイルを作成する
	 * @param integer $width 幅
	 * @param integer $height 高さ
	 * @return \ebi\Image
	 */
	public function thumbnail($width,$height){
		return $this->resize($width, $height)->crop($width, $height);
	}
	
	/**
	 * カラーモードからRGB（10進数）を返す
	 * @param string $color_code
	 * @return integer[] R,G,B
	 */
	public static function color2rgb($color_code){
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
}