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
	
	const MODE_IMAGICK = 1;
	const MODE_GD = 2;
	
	private $canvas;
	private $mode;
	private $font_path;
	
	/**
	 * 
	 * @param string $filename
	 */
	public function __construct($filename,$mode=null){
		if($filename != __FILE__){
			try{
				if($mode != 2 && extension_loaded('imagick')){
					$this->canvas = new \Imagick($filename);
					$this->mode = 1;
				}else{
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
					$this->mode = 2;
				}
			}catch(\Exception $e){
				throw new \ebi\exception\ImageException();
			}
		}
	}
	
	/**
	 * バイナリ文字列から画像を読み込む
	 * @param string $string
	 * @return \ebi\Image
	 */
	public static function readImageBlob($string){
		$self = new static(__FILE__);
		
		try{
			if(extension_loaded('imagick')){
				$self->canvas = new \Imagick();
				if($self->canvas->readImageBlob($string) !== true){
					throw \ebi\exception\ImageException();
				}
				$self->mode = 1;
			}else{
				$self->canvas = imagecreatefromstring($string);
				
				if($self->canvas === false){
					throw \ebi\exception\ImageException();
				}
				$self->mode = 2;
			}
		}catch(\Exception $e){
			throw \ebi\exception\ImageException();
		}
		return $self;
	}
	
	
	/**
	 * ファイルに書き出す
	 * @param string $filename
	 * @return \ebi\Image
	 */
	public function write($filename){
		\ebi\Util::mkdir(dirname($filename));
		
		if($this->mode == 1){
			$this->canvas->writeImage($filename);
		}else{
			$type = 'jpg';
			
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
		}
		return $this;
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
		
		if($this->mode == 1){
			$this->canvas->setImageFormat($format);
			print($this->canvas);
		}else{
			switch($format){
				case 'jpeg':
					imagejpeg($this->canvas);
					break;
				case 'png':
					imagepng($this->canvas);
					break;
				case 'gif':
					imagegif($this->canvas);
					break;
				default:
					imagejpeg($this->canvas);
			}
		}
		exit;
	}
	
	public function __destruct(){
		if($this->mode == 1){
			$this->canvas->clear();
		}else{
			imagedestroy($this->canvas);
		}
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
		
		if($x === null || $y === null){
			$x = ($w - $width) / 2;
			$y = ($h - $height) / 2;
			
			list($x,$y) = [($x >= 0) ? $x : 0,($y >= 0) ? $y : 0];
		}
		if($x < 0){
			$x = $w + $x;
		}
		if($y < 0){
			$y = $h + $y;
		}
		
		if($this->mode == 1){
			$this->canvas->cropImage($width,$height,$x,$y);
		}else{
			$canvas = imagecrop($this->canvas, ['x'=>$x,'y'=>$y,'width'=>$width,'height'=>$height]);
			
			if($canvas === false){
				throw new \ebi\exception\ImageException();
			}
			imagedestroy($this->canvas);
			$this->canvas = $canvas;
		}
		return $this;
	}
	/**
	 * サイズ
	 * @return integer[] width,height
	 */
	public function get_size(){
		if($this->mode == 1){
			$w = $this->canvas->getImageWidth();
			$h = $this->canvas->getImageHeight();
		}else{
			$w = imagesx($this->canvas);
			$h = imagesy($this->canvas);
		}
		return [$w,$h];
	}
	
	/**
	 * 画像のサイズを変更する
	 * @param integer $width 変更後の幅
	 * @param integer $height 変更後の高さ
	 * @throws \ebi\exception\ImageException
	 * @return \ebi\Image
	 */
	public function resize($width,$height=null){
		list($w,$h) = $this->get_size();
		$rw = empty($width) ? 1 : $width;
		$rh = empty($height) ? 1 : $height;
				
		if(!empty($width) && !empty($height)){
			$aw = $rw / $w;
			$ah = $rh / $h;
			$a = max($aw,$ah);
		}else if(!isset($height)){
			$a = $rw / $w;
		}else{
			$a = $rh / $h;
		}
		$cw = $w * $a;
		$ch = $h * $a;
		
		if($this->mode == 1){
			$this->canvas->scaleImage($cw,$ch);
		}else{
			$canvas = imagecreatetruecolor($cw,$ch);
			if(false === imagecopyresampled($canvas,$this->canvas,0,0,0,0,$cw,$ch,$w,$h)){
				throw new \ebi\exception\ImageException();
			}			
			imagedestroy($this->canvas);
			$this->canvas = $canvas;
		}
		return $this;
	}
	
	/**
	 * 回転
	 * @param integer $angle 角度
	 * @param string $background_color
	 * @return \ebi\Image
	 */
	public function rotate($angle,$background_color='#000000'){
		if($this->mode == 1){
			$this->canvas->rotateImage($background_color,$angle);
		}else{
			list($r,$g,$b) = self::color2rgb($background_color);
			
			$color = imagecolorallocate($r,$g,$b);
			$canvas = imagerotate($this->canvas,$angle,(($color === false) ? 0 : $color));
			imagedestroy($this->canvas);
			$this->canvas = $canvas;
		}
		return $this;
	}
	
	/**
	 * フォントを設定する
	 * @param string $font_path
	 * @return \ebi\Image
	 */
	public function set_font($font_path){
		$this->font_path = $font_path;
		
		return $this;
	}
	
	/**
	 * テキストを画像に書き込む
	 * @param integer $x
	 * @param integer $y
	 * @param string $font_color
	 * @param number $font_point_size
	 * @param string $text
	 * @param number $angle
	 * @throws \ebi\exception\UndefinedException
	 * @return \ebi\Image
	 */
	public function add_text($x,$y,$font_color,$font_point_size,$text,$angle=0){
		if(empty($this->font_path)){
			throw new \ebi\exception\UndefinedException('undefined font');
		}
		
		if($this->mode == 1){
			$draw = new \ImagickDraw();
			$draw->setFillColor($font_color);
			$draw->setFont($this->font_path);
			$draw->setFontSize($font_point_size);
			
			$this->canvas->annotateImage(
				$draw,
				$x,
				$y,
				$angle,
				$text
			);
		}else{
			list($r,$g,$b) = self::color2rgb($font_color);
			
			imagettftext(
				$this->canvas,
				($font_point_size*0.7),
				$angle,
				$x,
				$y,
				imagecolorallocate($this->canvas,$r,$g,$b),
				$this->font_path,
				$text
			);
		}
		return $this;
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
	 * 切り取ってサムネイルを作成する
	 * @param integer $width 幅
	 * @param integer $height 高さ
	 * @return \ebi\Image
	 */
	public function thumbnail($width,$height){
		return $this->resize($width, $height)->crop($width, $height);
	}
	
	/**
	 * 画像の向き
	 * @return  integer
	 */
	public function get_orientation(){
		list($w,$h) = $this->get_size();
		
		$d = $h / $w;
		
		if($d <= 1.02 && $d >= 0.98){
			return self::ORIENTATION_SQUARE;
		}else if($d > 1){
			return self::ORIENTATION_PORTRAIT;
		}else{
			return self::ORIENTATION_LANDSCAPE;
		}
	}
	
	/**
	 * 画像の情報(width, height, mime, bits, channels)を取得する
	 * @param string $filename
	 * @return mixed{}
	 */
	public static function get_info($filename){
		$info = getimagesize($filename);
		
		return [
			'width'=>$info[0],
			'height'=>$info[1],
			'mime'=>$info['mime'] ?? null,
			'bits'=>$info['bits'] ?? null,
			'channels'=>$info['channels'] ?? null,
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
		
		if(preg_match('/^%PDF\-(.+)/',$value,$m)){
			return preg_replace('/[^\d\.]/','',$m[1]);
		}
		throw new \ebi\exception\IllegalDataTypeException();
	}
	
	/**
	 * 塗りつぶした矩形をファイルまたは標準出力に出力する
	 * @param integer $width
	 * @param integer $height
	 * @param string $color
	 * @param string $filename 
	 */
	public static function filled_rectangle($width,$height,$color,$filename=null){
		list($r,$g,$b) = self::color2rgb($color);
		
		$canvas = imagecreatetruecolor($width,$height);
		imagefilledrectangle($canvas,0,0,$width,$height,imagecolorallocate($canvas,$r,$g,$b));
		
		if(empty($filename)){
			header('Content-Type: image/png');
			imagepng($canvas);
		}else{
			\ebi\Util::mkdir(dirname($filename));
			imagepng($canvas,$filename);
		}
		imagedestroy($canvas);
	}
}