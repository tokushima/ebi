<?php
namespace ebi;
/**
 * Epubの書き出し
 * @author tokushima
 *
 */
class Epub{
	/**
	 * ディレクトリからコンテンツ情報(OEBPSの中身)を生成する
	 * @param string $dir
	 * @param string $base_dir
	 * @return string{}
	 */
	public static function get_items($dir,$base_dir=null){
		if(substr($dir,-1) == '/'){
			$dir = substr($dir,0,-1);
		}
		if(empty($base_dir)){
			$base_dir = $dir;
		}	
		$list = [5=>[],0=>[]];
			
		if($h = opendir($dir)){
			while($p = readdir($h)){
				if($p != '.' && $p != '..' && substr($p,0,1) != '.'){
					$s = sprintf('%s/%s',$dir,$p);
	
					if(is_dir($s)){
						$list[5][str_replace($base_dir,'',$s)] = $s;
						$r = self::get_items($s,$base_dir);
						
						$list[5] = array_merge($list[5],$r[5]);
						$list[0] = array_merge($list[0],$r[0]);
					}else{
						$list[0][str_replace($base_dir,'',$s)] = $s;
					}
				}
			}
			closedir($h);
		}
		return $list;
	}
	/**
	 * 
	 * @param $filename 書き出し先のepubファイルパス 
	 * @param $items[] コンテンツ情報(OEBPSの中身)
	 * @return string
	 */
	public static function archive($filename,array $items){
		$hasopf = false;
		
		if(isset($items[0])){
			foreach($items[0] as $local => $path){
				if(substr($path,-4) == '.opf'){
					$hasopf = true;
					break;
				}
			}
		}
		if(!$hasopf){
			throw new \InvalidArgumentException('required *.opf file');
		}
		
		$zip = new \ZipArchive();
		
		if($zip->open($filename,(\ZipArchive::CM_STORE|\ZipArchive::CREATE)) === true){
			$zip->addFromString('mimetype','application/epub+zip');
			$zip->close();
		
			$zip = new \ZipArchive();
			if($zip->open($filename,(\ZipArchive::CREATE|\ZipArchive::CM_SHRINK)) === true){
				foreach([5,0] as $t){
					ksort($items[$t]);
					
					foreach($items[$t] as $local => $path){
						if($t == 0){
							if(substr($path,-4) == '.opf'){
								$local = 'content.opf';
							}
							$zip->addFile($path,'OEBPS/'.$local);
						}else{
							$zip->addEmptyDir('OEBPS/'.$local);
						}
					}
				}
				$zip->addEmptyDir('META-INF');
				$zip->addFromString('META-INF/container.xml', 
					'<?xml version="1.0"?>'.PHP_EOL.
					'<container version="1.0" xmlns="urn:oasis:names:tc:opendocument:xmlns:container">'.PHP_EOL.
					'<rootfiles>'.PHP_EOL.
					'<rootfile full-path="OEBPS/content.opf" media-type="application/oebps-package+xml" />'.PHP_EOL.
					'</rootfiles>'.PHP_EOL.
					'</container>'
				);
				$zip->close();
			}
		}
	}
}