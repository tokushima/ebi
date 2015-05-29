<?php
namespace ebi;

class Epub{
	private $title;
	private $width;
	private $height;
	private $page;
	
	public function __construct($title,$width,$height){
		$this->title = $title;
		$this->width = $width;
		$this->height = $height;
	}
	public function image($page_no,$x,$y,$width,$height,$path){
		$this->page[$page_no][] = [1,$x,$y,$width,$height,$path];
	}
	public function text($page_no,$x,$y,$width,$height,$path){
		$this->page[$page_no][] = [2,$x,$y,$width,$height,$path];
	}
	
	private function xhtml($page_no){
		$xml = new \ebi\Xml('html');
		$xml->attr('xmlns','http://www.w3.org/1999/xhtml');
		$xml->attr('xmlns:epub','http://www.idpf.org/2007/ops');
		$xml->attr('xml:lang','ja');
		
		$head = new \ebi\Xml('head');
		$head->add((new \ebi\Xml('meta'))->attr('charset','UTF-8'));
		$head->add((new \ebi\Xml('title',$this->title)));
		$head->add((new \ebi\Xml('meta'))->attr('name','viewport')->attr('content',sprintf('width=%d, height=%d',$this->width,$this->height)));
		
		$body = new \ebi\Xml('body');
		$main = (new \ebi\Xml('div'))->attr('class','main');
		$svg = (new \ebi\Xml('svg'))
					->attr('xmlns','http://www.w3.org/2000/svg')
					->attr('version','1.1')
					->attr('xmlns:xlink','http://www.w3.org/1999/xlink')
					->attr('width','100%')
					->attr('height','100%')
					->attr('viewBox',sprintf('0 0 %d %d',$this->width,$this->height));
		
		if(isset($this->page[$page_no]) && is_array($this->page[$page_no])){
			foreach($this->page[$page_no] as $obj){
				if($obj[0] == 1){
					$o = (new \ebi\Xml('image'))
							->attr('width', $img[3])
							->attr('height',$img[4])
							->attr('xlink:href',$img[5]);
				}else if($obj[0] == 2){
					$o = (new \ebi\Xml('image'))
							->attr('width', $img[3])
							->attr('height',$img[4])
							->attr('xlink:href',$img[5]);					
				}
				$svg->add($o);
			}
		}
		$main->add($svg);
		$body->add($main);
		$xml->add($head);
		$xml->add($body);
		
		return '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.'<!DOCTYPE html>'.$xml->get();
	}
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