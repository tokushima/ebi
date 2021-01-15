<?php
namespace ebi;
/**
 * アーカイブの作成、解凍を行う
 * @author tokushima
 */
class Archive{
	private $tree = [5=>[],0=>[]];

	public function __construct($path=null){
		if(isset($path) && is_dir($path)){
			$this->add($path,false);
		}
	}

	private function dirs($dir){
		$list = [5=>[],0=>[]];
		if($h = opendir($dir)){
			while($p = readdir($h)){
				if($p != '.' && $p != '..'){
					$s = sprintf('%s/%s',$dir,$p);

					if(is_dir($s)){
						$list[5][$s] = $s;
						$r = $this->dirs($s);
						$list[5] = array_merge($list[5],$r[5]);
						$list[0] = array_merge($list[0],$r[0]);
					}else{
						$list[0][$s] = $s;
					}
				}
			}
			closedir($h);
		}
		return $list;
	}

	/**
	 * 指定したパスからアーカイブに追加する
	 * @param string $path 追加するファイルへのパス
	 * @param string $key アーカイブ内部での名前
	 * @return $this
	 */	
	public function add($path,$key=null){
		$path = str_replace('\\','/',$path);

		if(is_dir($path)){
			$key = ($key === false) ? '' : (empty($key) ? basename($path) : $key);
			$rp = empty($key) ? $path.'/' : $path;

			foreach($this->dirs($path) as $t => $list){
				foreach($list as $p){
					$this->tree[$t][str_replace($rp,$key,$p)] = $p;
				}
			}
		}else if(is_file($path)){
			$this->tree[0][empty($key) ? basename($path) : $key] = $path;
		}else{
			throw new \ebi\exception\UnknownFileException();
		}
		return $this;
	}

	/**
	 * tarを出力する
	 * @param string $filename 出力するファイルパス
	 * @return $this
	 */
	public function write($filename){
		$fp = fopen($filename,'wb');
		
		foreach([5,0] as $t){
			if(!empty($this->tree[$t])){
				ksort($this->tree[$t]);
			}
			foreach($this->tree[$t] as $a => $n){
				if(strpos($n,'/.') === false){
					if($t == 0){
						$i = stat($n);
						$rp = fopen($n,'rb');
							fwrite($fp,$this->tar_head($t,$a,filesize($n),fileperms($n),$i[4],$i[5],filemtime($n)));
							
							while(!feof($rp)){
								$buf = fread($rp,512);
								if($buf !== ''){
									fwrite($fp,pack('a512',$buf));
								}
							}
						fclose($rp);
					}else{
						fwrite($fp,$this->tar_head($t,$a,0,0777));
					}
				}
			}
		}
		fwrite($fp,pack('a1024',null));
		fclose($fp);
		return $this;
	}
	private function tar_head($type,$filename,$filesize=0,$fileperms=0777,$uid=0,$gid=0,$update_date=null){
		if(strlen($filename) > 99){
			throw new \ebi\exception\InvalidArgumentException('invalid filename (max length 100) `'.$filename.'`');
		}
		if($update_date === null){
			$update_date = time();
		}
		$checksum = 256;
		$first = pack(
			'a100a8a8a8a12A12',$filename,
			sprintf('%06s ',decoct($fileperms)),sprintf('%06s ',decoct($uid)),sprintf('%06s ',decoct($gid)),
			sprintf('%011s ',decoct(($type === 0) ? $filesize : 0)),sprintf('%11s',decoct($update_date))
		);
		$last = pack('a1a100a6a2a32a32a8a8a155a12',$type,null,null,null,null,null,null,null,null,null);
		
		for($i=0;$i<strlen($first);$i++){
			$checksum += ord($first[$i]);
		}
		for($i=0;$i<strlen($last);$i++){
			$checksum += ord($last[$i]);
		}
		return $first.pack('a8',sprintf('%6s ',decoct($checksum))).$last;
	}
	/**
	 * tgzを出力する
	 * @param string $filename 出力するファイルパス
	 * @return $this
	 */
	public function gzwrite($filename){
		$fp = gzopen($filename,'wb9');
			$this->write($filename.'.tar');
			$fr = fopen($filename.'.tar','rb');
				while(!feof($fr)){
					gzwrite($fp,fread($fr,4096));
				}
			fclose($fr);
		gzclose($fp);
		unlink($filename.'.tar');
		chmod($filename,0666);
		return $this;
	}
	/**
	 * zipを出力する
	 * @param string $filename 出力するファイルパス
	 * @param boolean $append 追記する
	 * @return $this
	 */
	public function zipwrite($filename,$append=false){
		$zip = new \ZipArchive();
		\ebi\Util::mkdir(dirname($filename));
		
		$mode = file_exists($filename) ? 
			((!$append || filesize($filename) === 0) ? 
				\ZipArchive::OVERWRITE : 
				\ZipArchive::CREATE
			) :
			\ZipArchive::CREATE;
		
		if($zip->open($filename,$mode) === true){
			foreach([5,0] as $t){
				ksort($this->tree[$t]);
				
				foreach($this->tree[$t] as $a => $n){
					if(strpos($n,'/.') === false){
						if($t == 0){
							$zip->addFile($n,$a);
						}else{
							$zip->addEmptyDir($a);
						}
					}
				}
			}
			$zip->close();
			chmod($filename,0666);
		}else{
			throw new \ebi\exception\AccessDeniedException('Failed to write ZIP file');
		}
		return $this;
	}
	
	/**
	 * tarを解凍する
	 * @param string $inpath 解凍するファイルパス
	 * @param string $outpath 展開先のファイルパス
	 * @return string 解凍先のファイルパス
	 */
	public static function untar($inpath,$outpath=null){
		if(empty($outpath)){
			$outpath = \ebi\WorkingStorage::tmpdir();
		}
		if(!is_dir($outpath)){
			\ebi\Util::mkdir($outpath);
		}
		$outpath = \ebi\Util::path_slash($outpath,null,false);
		
		$fr = fopen($inpath,'rb');
		
		while(!feof($fr)){
			$buf = fread($fr,512);
			
			if(strlen($buf) < 512){
				break;
			}
			$data = unpack(
				'A100name/a8mode/a8uid/a8gid/a12size/a12mtime/'
				.'a8chksum/'
				.'a1typeflg/a100linkname/a6magic/a2version/a32uname/a32gname/a8devmajor/a8devminor/a155prefix',
				$buf
			);
			
			if(!empty($data['name'])){
				if($data['name'][0] == '/'){
					$data['name'] = substr($data['name'],1);
				}
				$f = $outpath.'/'.$data['name'];
				
				switch((int)$data['typeflg']){
					case 0:	
						$size = base_convert($data['size'],8,10);
						$cur = ftell($fr);
						
						if(!is_dir(dirname($f))){
							\ebi\Util::mkdir(dirname($f),0775);
						}
						$fw = fopen($f,'wb');
							for($i=0;$i<=$size;$i+=512){
								fwrite($fw,fread($fr,512));
							}
						fclose($fw);
						$skip = $cur + (ceil($size / 512) * 512);
						fseek($fr,$skip,SEEK_SET);
						break;
					case 5:
						if(!is_dir($f)){
							\ebi\Util::mkdir($f,775);
						}
						break;
				}
			}
		}
		fclose($fr);
		
		return $outpath;
	}
	/**
	 * tar.gz(tgz)を解凍してファイル書き出しを行う
	 * @param string $tarfile 解凍するtarファイル
	 * @param string $outpath 解凍先のファイルパス
	 * @return string 解凍先のファイルパス
	 */
	public static function untgz($tarfile,$outpath=null){
		if(empty($outpath)){
			$outpath = \ebi\WorkingStorage::tmpdir();
		}
		if(!is_dir($outpath)){
			\ebi\Util::mkdir($outpath);
		}
		$outpath = \ebi\Util::path_slash($outpath,null,false);
		$untar_path = $outpath.'/'.uniqid().'.tar';
		
		$fr = gzopen($tarfile,'rb');
		$ft = fopen($untar_path,'wb');
		
		while(!gzeof($fr)){
			fwrite($ft,gzread($fr,4096));
		}
		
		fclose($ft);
		gzclose($fr);
		
		$outpath = self::untar($untar_path,$outpath);
		unlink($untar_path);
		
		return $outpath;
	}
	/**
	 * zipを解凍してファイル書き出しを行う
	 * @param string $zipfile 解凍するZIPファイル
	 * @param string $outpath 解凍先のファイルパス
	 * @return string 解凍先のファイルパス
	 */
	public static function unzip($zipfile,$outpath=null){
		$zip = new \ZipArchive();
		if($zip->open($zipfile) !== true){
			throw new \ebi\exception\AccessDeniedException('failed to open stream');
		}
		if(empty($outpath)){
			$outpath = \ebi\WorkingStorage::tmpdir();
		}
		if(!is_dir($outpath)){
			\ebi\Util::mkdir($outpath);
		}
		$outpath = \ebi\Util::path_slash($outpath,null,false);
		
		$zip->extractTo($outpath);
		$zip->close();
		
		return $outpath;
	}
}