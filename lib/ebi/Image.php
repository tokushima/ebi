<?php
namespace ebi;

class Image{
	private $canvas;
	private $mode;
	
	public static function jpeg($filename){
		$self = new static();
		
		if(extension_loaded('imagick')){
			$self->canvas = new \Imagick($filename);
			$self->mode = 1;
		}else{
			$self->canvas = imagecreatefromjpeg($filename);
			$self->mode = 2;
		}
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
	public function cropping($width,$height){
		if($this->mode == 1){
			list($cw,$ch) = $this->get_resize_info($this->canvas->getImageWidth(),$this->canvas->getImageHeight(), $width, $height);
			$this->canvas->cropThumbnailImage($cw,$ch);
		}else{
			$original_w = imagesx($this->canvas);
			$original_h = imagesy($this->canvas);
			list($cw,$ch) = $this->get_resize_info($original_w,$original_h, $width, $height);
			
			$x = 0;
			$y = 0;
			
			if($cw > $width){
				$x = ($cw - $width) / 2;
			}
			if($ch > $height){
				$y = ($ch - $height) / 2;
			}
			
			$canvas = imagecreatetruecolor($cw,$ch);
			imagecopyresampled($canvas,$this->canvas,0,0,0,0,$cw,$ch,$original_w,$original_h);
			
			if($cw > $width || $ch > $height){
				$canvas = imagecrop($canvas, ['x'=>$x,'y'=>$y,'width'=>$width,'height'=>$height]);
			}
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