<?php
namespace ebi;
/**
 * Image ユーティリティ群
 */
class Image{
	/**
	 * JPGを指定のサイズにリサイズする
	 * @param string $filename
	 * @param string $out_filename
	 * @param integer $width
	 * @param integer $height
	 * @param integer $quality
	 * @return integer[] dst_width,dst_height
	 */
	public static function jpeg_resize($filename,$out_filename,$width,$height,$quality=100){
		$src_image = imagecreatefromjpeg($filename);
		$src_width = imagesx($src_image);
		$src_height = imagesy($src_image);
	
		$wp = ($width > $src_width) ? 1 : ($width / $src_width);
		$hp = ($height > $src_height) ? 1 : ($height / $src_height);
	
		if($wp == 1 && $hp == 1){
			$xp = 1;
		}else if($wp == 1 && $hp < 1){
			$wp = $hp;
		}else if($wp < 1 && $hp == 1){
			$hp = $wp;
		}
		$xp = ($wp > $hp) ? $hp : $wp;
	
		$dst_width = floor($src_width * $xp);
		$dst_height = floor($src_height * $xp);
	
		$dst_image = imagecreatetruecolor($dst_width,$dst_height);
		imagecopyresampled($dst_image,$src_image,0,0,0,0,$dst_width,$dst_height,$src_width,$src_height);
	
		imagedestroy($src_image);
		imagejpeg($dst_image,$out_filename,$quality);
		imagedestroy($dst_image);
	
		return [$dst_width,$dst_height];
	}
}