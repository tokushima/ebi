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
	
	/**
	 * 用紙サイズ(mm)
	 * @param string $type
	 * @throws \ebi\exception\InvalidArgumentException
	 * @return number[]
	 */
	public static function get_size_mm($type){
		switch(strtoupper($type)){
			case 'A0': return [841,1189];
			case 'A1': return [594,841];
			case 'A2': return [420,594];
			case 'A3': return [297,420];
			case 'A4': return [210,297];
			case 'A5': return [148,210];
			case 'A6': return [105,148];
			case 'A7': return [74,105];
			case 'A8': return [52,74];
			case 'A9': return [37,52];
			case 'A10': return [26,38];
			case 'A11': return [18,26];
			case 'A12': return [13,18];
			
			case 'A3+': return [329,483];
				
			case 'B0': return [1030,1456];
			case 'B1': return [728,1030];
			case 'B2': return [515,728];
			case 'B3': return [364,515];
			case 'B4': return [257,364];
			case 'B5': return [182,257];
			case 'B6': return [128,182];
			case 'B7': return [91,128];
			case 'B8': return [64,91];
			case 'B9': return [45,64];
			case 'B10': return [32,45];
			case 'B11': return [22,32];
			case 'B12': return [16,32];
			
			case 'DSC': return [89,119];
			case 'L': return [89,127];
			case 'HAGAKI': return [100,148];
			case 'KG': return [102,152];
			case '2L': return [127,178];
			case 'CABINET': return [130,180];
			case '6P': return [203,254]; // 六切
			case '4P': return [254,305]; // 四切
			case 'HP': return [356,432]; // 半切
			case 'ZENSHI': return [457,560]; // 全紙
			case 'DAIZENSHI': return [508,610]; // 大全紙
			case 'ZENBAI': return [600,900]; // 全倍				
		}
		throw new \ebi\exception\InvalidArgumentException();
	}
}