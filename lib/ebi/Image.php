<?php
namespace ebi;

class Image{
	private $canvas;
	private $mode;
	
	public static function jpeg($filename){
		$self = new static();
		
		if(false && extension_loaded('imagick')){
			$self->canvas = new \Imagick($filename);
			$self->mode = 1;
		}else{
			$self->canvas = imagecreatefromjpeg($filename);
			$self->mode = 2;
		}
		\ebi\Log::trace($self->mode);
		return $self;
	}
	public static function jpeg_string($string){
		$self = new static();
		
		if(extension_loaded('imagick')){
			$self->canvas = new \Imagick();
			$self->canvas->readImageBlob($string);
			$self->mode = 1;
		}else{
			$self->canvas = imagecreatefromstring($string);
			$self->mode = 2;
		}
		return $self;
	}
	
	public function __destruct(){
		if($this->mode == 1){
			$this->canvas->clear();			
		}else{
			imagedestroy($this->canvas);
		}
	}
	
	public function output($filename=null){
		if($this->mode == 1){
			if(empty($filename)){
				header('Content-Type: image/jpeg');
				print($this->canvas);
			}else{
				$this->canvas->writeImage($filename);
			}
		}else{
			if(empty($filename)){
				header('Content-Type: image/jpeg');
				imagejpeg($this->canvas);
			}else{
				imagejpeg($this->canvas,$filename);
			}
		}
	}
	
	/**
	 * JPG画像ファイルを指定のサイズで縮小・切り取り
	 * 
	 * @param string $filename 入力ファイル名
	 * @param integer $width 幅(px)
	 * @param integer $height 高さ(px)
	 * @param string $output 出力先ファイル名
	 */
	public function crop($width,$height,$x=null,$y=null){
		if($x === null || $y === null){
			list($x,$y) = $this->crop_xy($width, $height);
		}
		if($this->mode == 1){
			$this->canvas->cropImage($width,$height,$x,$y);
		}else{
			$canvas = imagecrop($this->canvas, ['x'=>$x,'y'=>$y,'width'=>$width,'height'=>$height]);
			
			if($canvas === false){
				throw new \LogicException();
			}
			
			imagedestroy($this->canvas);
			$this->canvas = $canvas;
		}
		return $this;
	}
	private function crop_xy($width,$height){
		if($this->mode == 1){
			$ow = $this->canvas->getImageWidth();
			$oh = $this->canvas->getImageHeight();
		}else{
			$ow = imagesx($this->canvas);
			$oh = imagesy($this->canvas);
		}
		$x = ($ow - $width) / 2;
		$y = ($oh - $height) / 2;
		
		return [($x >= 0) ? $x : 0,($y >= 0) ? $y : 0];
	}
	
	public function resize($width,$height){
		// TODO
		if($this->mode == 1){
			$this->canvas->scaleImage($width,$height);
		}else{
			$canvas = imagecreatetruecolor($width,$height);
			imagecopyresampled($canvas,$this->canvas,0,0,0,0,$width,$height,imagesx($this->canvas),imagesy($this->canvas));
			
			imagedestroy($this->canvas);
			$this->canvas = $canvas;
		}
		return $this;
	}


	private function get_resize_info($original_w,$original_h,$width,$height){
		$aw = $width / $original_w;
		$ah = $height / $original_h;
		$a = max($aw,$ah);
		
		$cw = $original_w * $a;
		$ch = $original_h * $a;
		
		return [$cw,$ch];
	}
}