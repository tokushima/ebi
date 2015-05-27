<?php
namespace ebi;

class Epub{
	/**
	 * 
	 * @param 書き出し先のepubファイルパス $filename
	 * @param コンテンツのフォルダ(OEBPSの中身) $target_dir
	 * @return string
	 */
	public static function write($filename,$target_dir){
		if(substr($target_dir,-1) == '/'){
			$target_dir = substr($target_dir,0,-1);
		}
		$base_dir = $target_dir;
		
		$zip = new \ZipArchive();
		if($zip->open($filename,(\ZipArchive::CM_STORE|\ZipArchive::CREATE)) === true){
			$zip->addFromString('mimetype','application/epub+zip');
			$zip->close();
		
			$zip = new \ZipArchive();
			if($zip->open($filename,(\ZipArchive::CREATE|\ZipArchive::CM_SHRINK)) === true){
		
				$dirs = function($dir,$base_dir) use(&$dirs){
					$list = [5=>[],0=>[]];
					
					if($h = opendir($dir)){
						while($p = readdir($h)){
							if($p != '.' && $p != '..' && substr($p,0,1) != '.'){
								$s = sprintf('%s/%s',$dir,$p);
								
								if(is_dir($s)){
									$list[5][str_replace($base_dir,'OEBPS/',$s)] = $s;
									$r = $dirs($s,$base_dir);
									
									$list[5] = array_merge($list[5],$r[5]);
									$list[0] = array_merge($list[0],$r[0]);
								}else{
									$list[0][str_replace($base_dir,'OEBPS/',$s)] = $s;
								}
							}
						}
						closedir($h);
					}
					return $list;
				};
		
				$list = $dirs($target_dir,$target_dir.'/');

				foreach([5,0] as $t){
					ksort($list[$t]);
					
					foreach($list[$t] as $a => $n){
						if($t == 0){
							$zip->addFile($n,$a);
						}else{
							$zip->addEmptyDir($a);
						}
					}
				}
				
				$zip->addEmptyDir('META-INF');
				$zip->addFromString('META-INF/container.xml', 
					'<?xml version="1.0"?>'.
					'<container version="1.0" xmlns="urn:oasis:names:tc:opendocument:xmlns:container">'.
					'<rootfiles>'.
					'<rootfile full-path="OEBPS/content.opf" media-type="application/oebps-package+xml" />'.
					'</rootfiles>'.
					'</container>'
				);
				$zip->close();
			}
		}
	}
}