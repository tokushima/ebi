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
	 * @var int
	 */
	const ORIENTATION_PORTRAIT = 1;
	/**
	 * 横向き
	 * @var int
	 */
	const ORIENTATION_LANDSCAPE = 2;
	/**
	 * 正方形
	 * @var int
	 */
	const ORIENTATION_SQUARE = 3;
	
	const CHANNELS_GRAY = 1;
	const CHANNELS_RGB = 3;
	const CHANNELS_CMYK = 4;
	
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
	 * @param string $font_path ttfファイルパス
	 * @param string $font_name フォント名
	 */
	public static function set_font($font_path,$font_name=null){
		if(empty($font_name)){
			$font_name = preg_replace('/^(.+)\..+$/','\\1',basename($font_path));
		}
		if(!is_file($font_path)){
			throw new \ebi\exception\NotFoundException('font not found');
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
	 * @param int $width
	 * @param int $height
	 * @param string $color #FFFFFF, nullの場合は透明
	 * @return \ebi\Image
	 */
	public static function create($width,$height,$color=null){
		$self = new static(__FILE__);
		
		try{
			$self->canvas = imagecreatetruecolor($width,$height);
			$alpha = 0;
			
			if(empty($color)){
				$color = '#FFFFFF';
				imagealphablending($self->canvas, false);
				imagesavealpha($self->canvas, true);
				$alpha = 127;
			}
			[$r,$g,$b] = self::color2rgb($color);
			
			imagefill(
				$self->canvas,
				0,
				0,
				imagecolorallocatealpha($self->canvas,$r,$g,$b,$alpha)
			);
		}catch(\Exception $e){
			throw new \ebi\exception\ImageException();
		}
		return $self;
	}
	
	/**
	 * 矩形を描画する
	 * @param int $x
	 * @param int $y
	 * @param int $width
	 * @param int $height
	 * @param string $color
	 * @param int $thickness 線の太さ (塗り潰し時無効)
	 * @param bool$fill 塗りつぶす
	 * @param int $alpha 0〜127 (透明) PNGでのみ有効
	 * @return \ebi\Image
	 */
	public function rectangle($x,$y,$width,$height,$color,$thickness=1,$fill=false,$alpha=0){
		[$r, $g, $b] = self::color2rgb($color);
		$c = ($alpha > 0) ? imagecolorallocatealpha($this->canvas,$r,$g,$b,$alpha) : imagecolorallocate($this->canvas,$r,$g,$b);
		
		if($fill){
			imagefilledrectangle($this->canvas,$x,$y,$x + $width,$y + $height,$c);
		}else{
			imagesetthickness($this->canvas,$thickness);
			imagerectangle($this->canvas,$x,$y,$x + $width,$y + $height,$c);
		}
		return $this;
	}
	
	/**
	 * 楕円を描画する
	 * @param int $cx 中心点x
	 * @param int $cy 中心点y
	 * @param int $width
	 * @param int $height
	 * @param string $color
	 * @param float $thickness 線の太さ (塗り潰し時無効)
	 * @param bool $fill 塗りつぶす
	 * @param float $alpha 0〜127 (透明) PNGでのみ有効
	 * @return \ebi\Image
	 */
	public function ellipse($cx,$cy,$width,$height,$color,$thickness=1,$fill=false,$alpha=0){
		[$r, $g, $b] = self::color2rgb($color);
		$c = ($alpha > 0) ? imagecolorallocatealpha($this->canvas,$r,$g,$b,$alpha) : imagecolorallocate($this->canvas,$r,$g,$b);
		
		if($fill){
			imagefilledellipse($this->canvas,$cx,$cy,$width,$height,$c);
		}else{
			imagesetthickness($this->canvas,$thickness);
			
			for($i=0;$i<$thickness;$i++){
				$width--;
				imageellipse($this->canvas,$cx,$cy,$width,$height,$c);
				$height--;
			}
		}
		return $this;
	}
	
	/**
	 * 線を描画
	 * @param int $sx 始点x
	 * @param int $sy 始点y
	 * @param int $ex 終点x
	 * @param int $ey 終点y
	 * @param string $color
	 * @param float $thickness 線の太さ (塗り潰し時無効)
	 * @param float $alpha 0〜127 (透明) PNGでのみ有効
	 * @return \ebi\Image
	 */
	public function line($sx,$sy,$ex,$ey,$color,$thickness=1,$alpha=0){
		[$r, $g, $b] = self::color2rgb($color);
		$c = ($alpha > 0) ? imagecolorallocatealpha($this->canvas,$r,$g,$b,$alpha) : imagecolorallocate($this->canvas,$r,$g,$b);
		
		imagesetthickness($this->canvas,$thickness);
		imageline($this->canvas,$sx,$sy,$ex,$ey,$c);
		return $this;
	}
	
	/**
	 * 画像にフィルタを適用する
	 * 
	 * filter_type
	 *  IMG_FILTER_NEGATE: 色反転
	 *  IMG_FILTER_GRAYSCALE: グレイスケール
	 *  IMG_FILTER_EDGEDETECT: エッジの強調
	 *  IMG_FILTER_EMBOSS: エンボス
	 *  IMG_FILTER_GAUSSIAN_BLUR: ガウス
	 *  IMG_FILTER_SELECTIVE_BLUR: ぼかし
	 *  IMG_FILTER_MEAN_REMOVAL: スケッチ風
	 *  
	 *  IMG_FILTER_BRIGHTNESS: 輝度, arg1(レベル)=-255〜255
	 *  IMG_FILTER_CONTRAST: コントラスト, arg1(レベル)=-255〜255
	 *  IMG_FILTER_SMOOTH: 滑らかさ, arg1(レベル)=-8〜8
	 *  
	 *  IMG_FILTER_PIXELATE: モザイク効果, arg1(ブロックのピクセルサイズ), arg2(モザイク効果)=boolean
	 *  
	 *  IMG_FILTER_COLORIZE: カラーバランス, arg1(R)=0〜255, arg2(G)=0〜255, arg3(B)=0〜255, arg4(Alpha)=0〜127 
	 *  
	 * @param int $filter_type IMG_FILTER_*
	 * @param int $arg1
	 * @param mixed $arg2 IMG_FILTER_PIXELATE: boolean, IMG_FILTER_COLORIZE: integer
	 * @param int $arg3
	 * @param int $arg4
	 * @return \ebi\Image
	 * 
	 * @see http://php.net/manual/ja/function.imagefilter.php
	 */
	public function filter($filter_type,$arg1=0,$arg2=0,$arg3=0,$arg4=0){
		switch($filter_type){
			case IMG_FILTER_BRIGHTNESS:
			case IMG_FILTER_CONTRAST:
			case IMG_FILTER_SMOOTH:
				imagefilter($this->canvas,$filter_type,$arg1);
				break;
			case IMG_FILTER_PIXELATE:
				imagefilter($this->canvas,$filter_type,$arg1,\ebi\Util::is_true($arg2));
				break;
			case IMG_FILTER_COLORIZE:
				imagefilter($this->canvas,$filter_type,$arg1,$arg2,$arg3,$arg4);
				break;
			default:
				imagefilter($this->canvas,$filter_type);
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
	 * @param int $width
	 * @param int $height
	 */
	public function crop_resize($width,$height){
		$this->resize($width,$height,true)->crop($width, $height);
		return $this;
	}
	/**
	 * 画像の一部を抽出する
	 * @param int $width 抽出する幅
	 * @param int $height 抽出する高さ
	 * @param int $x 抽出する領域の左上の X 座標
	 * @param int $y 抽出する領域の左上の Y 座標
	 * @throws \ebi\exception\ImageException
	 * @return \ebi\Image
	 */
	public function crop($width,$height,$x=null,$y=null){
		[$w, $h] = $this->get_size();
		
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
	 * @return int[] width,height
	 */
	public function get_size(){
		$w = imagesx($this->canvas);
		$h = imagesy($this->canvas);
		
		return [$w,$h];
	}
	
	/**
	 * 画像のサイズを変更する
	 * @param int $width 変更後の幅
	 * @param int $height 変更後の高さ
	 * @param bool $aspect_ratio アスペクト比を維持する
	 * @throws \ebi\exception\ImageException
	 * @return \ebi\Image
	 */
	public function resize($width,$height=null,$aspect_ratio=true){
		[$w, $h] = $this->get_size();
		$m = self::magnification($w,$h,$width,$height,$aspect_ratio);
		$cw = ceil($w * $m);
		$ch = ceil($h * $m);
		
		$canvas = imagecreatetruecolor($cw,$ch);
		imagealphablending($canvas, false);
		imagesavealpha($canvas, true);
		
		if(false === imagecopyresampled($canvas,$this->canvas,0,0,0,0,$cw,$ch,$w,$h)){
			throw new \ebi\exception\ImageException();
		}
		imagedestroy($this->canvas);
		$this->canvas = $canvas;
		
		return $this;
	}
	
	/**
	 * 回転 (右回り)
	 * @param int $angle 角度
	 * @param string $background_color
	 * @return \ebi\Image
	 */
	public function rotate($angle,$background_color='#000000'){
		[$r, $g, $b] = self::color2rgb($background_color);
		
		$color = imagecolorallocate($this->canvas,$r,$g,$b);
		$canvas = imagerotate($this->canvas,$angle * -1,(($color === false) ? 0 : $color));
		imagedestroy($this->canvas);
		$this->canvas = $canvas;
		
		return $this;
	}
	
	/**
	 * テキストを画像に書き込む
	 * @param int $x 左上座標
	 * @param int $y　左上座標
	 * @param string $font_color #FFFFFF
	 * @param float $font_point_size フォントサイズ
	 * @param string $font_name set_fontで指定したフォント名
	 * @param string $text テキスト
	 * @param float $angle 回転軸は左下
	 * @return \ebi\Image
	 */
	public function text($x,$y,$font_color,$font_point_size,$font_name,$text,$angle=0){
		if(!isset(self::$font_path[$font_name])){
			throw new \ebi\exception\UndefinedException('undefined font `'.$font_name.'`');
		}
		[$r, $g, $b] = self::color2rgb($font_color);
		
		imagettftext(
			$this->canvas,
			$font_point_size,
			($angle * -1),
			$x,
			($y + $font_point_size),
			imagecolorallocate($this->canvas,$r,$g,$b),
			self::$font_path[$font_name],
			$text
		);
		return $this;
	}
	
	/**
	 * テキストの幅と高さ
	 * @param float $font_point_size フォントサイズ
	 * @param string $font_name フォント名
	 * @param string $text テキスト
	 * @param float $angle 回転軸は左下
	 * @throws \ebi\exception\UndefinedException
	 * @return float[] [width,height]
	 */
	public function get_text_size($font_point_size,$font_name,$text,$angle=0){
		if(!isset(self::$font_path[$font_name])){
			throw new \ebi\exception\UndefinedException('undefined font `'.$font_name.'`');
		}
		$info = imageftbbox(
			$font_point_size,
			($angle * -1),
			self::$font_path[$font_name],
			$text
		);
		
		$w = $info[2] - $info[0];
		$h = $info[3] - $info[5];
		
		return [$w,$h];
	}
	
	
	/**
	 * 画像を結合する
	 * $pctを指定した場合はアルファ透過が有効になりPNGの透過情報が失われる
	 * 
	 * @param int $x
	 * @param int $y
	 * @param \ebi\Image $img
	 * @param int $pct 0〜100
	 * @return \ebi\Image
	 */
	public function merge($x,$y,\ebi\Image $img,$pct=100){
		[$width, $height] = $img->get_size();
		
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
	 * @return int[] R,G,B
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
	 * @return int
	 */
	public function get_orientation(){
		[$w, $h] = $this->get_size();
		return self::judge_orientation($w, $h);
	}
	
	private static function judge_orientation($w,$h){
		if($w > 0 && $h > 0){
			$d = $h / $w;
			
			if($d <= 1.02 && $d >= 0.98){
				return self::ORIENTATION_SQUARE;
			}else if($d > 1){
				return self::ORIENTATION_PORTRAIT;
			}
			return self::ORIENTATION_LANDSCAPE;
		}
		return null;
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
	 *  int width
	 *  int height
	 *  int orientation 画像の向き 1: PORTRAIT, 2: LANDSCAPE, 3: SQUARE
	 *  string mime 画像形式のMIMEタイプ
	 *  int bits
	 *  int channels 1: GRAY, 3: RGB, 4: CMYK
	 *  bool broken 画像ファイルとして破損しているか
	 *  
	 * @param string $filename
	 * @return mixed{}
	 * @see http://jp2.php.net/manual/ja/function.getimagesize.php
	 * @see http://jp2.php.net/manual/ja/function.image-type-to-mime-type.php
	 */
	public static function get_info($filename){
		if(!is_file($filename)){
			throw new \ebi\exception\AccessDeniedException($filename.' not found');
		}
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
	 * @param int $width (px)
	 * @param int $height (px)
	 * @param string $color
	 * @param float $opacity 0..1
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
	 * @param float $a_width
	 * @param float $a_height
	 * @param float $b_width
	 * @param float $b_height
	 * @param bool $aspect_ratio アスペクト比を維持する
	 * @return float
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