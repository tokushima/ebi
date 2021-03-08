<?php

namespace ebi;

/**
 * Zip で圧縮されたファイルアーカイブ
 */
class ZipArchive
{
	private $zip;
	private $writed = false;
	private $filename;

	public function __construct($filename, $append = false)
	{
		$this->filename = $filename;
		$this->zip = new \ZipArchive();

		$mode = file_exists($filename) ?
			((!$append || filesize($filename) === 0) ?
				\ZipArchive::OVERWRITE :
				\ZipArchive::CREATE) :
			\ZipArchive::CREATE;

		if ($this->zip->open($filename, $mode) !== true) {
			throw new \ebi\exception\AccessDeniedException();
		}
	}

	private function dirs($dir, $basedir, $entryname)
	{
		$list = [5 => [], 0 => []];

		if ($h = opendir($dir)) {
			while ($p = readdir($h)) {
				if ($p != '.' && $p != '..' && strpos($p, '/.') === false) {
					$s = sprintf('%s/%s', $dir, $p);

					if (is_dir($s)) {
						$ln = str_replace($basedir, $entryname, $s);
						$list[5][$ln] = $s;
						$r = $this->dirs($s, $basedir, $entryname);
						$list[5] = array_merge($list[5], $r[5]);
						$list[0] = array_merge($list[0], $r[0]);
					} else {
						$ln = str_replace($basedir, $entryname, $s);
						$list[0][$ln] = $s;
					}
				}
			}
			closedir($h);
		}
		return $list;
	}

	/**
	 * 指定したパスからファイルを ZIP アーカイブに追加する
	 * @var string $filename 追加するファイルへのパス
	 * @var string $localname ZIP アーカイブ内部での名前
	 */
	public function add($filename, $entryname = null)
	{
		if ($this->writed && $this->zip->open($this->filename, \ZipArchive::CREATE) === true) {
			$this->writed = false;
		}
		if (is_dir($filename)) {
			$entryname = \ebi\Util::path_slash($entryname, null, false);
			$dir = \ebi\Util::path_slash(realpath($filename), null, true);
			$list = $this->dirs($dir, $dir, $entryname);

			foreach (array_keys($list[5]) as $ln) {
				$this->zip->addEmptyDir($ln);
			}
			foreach ($list[0] as $ln => $path) {
				$this->zip->addFile($path, $ln);
			}
		} else {
			if (is_file($filename)) {
				if (empty($entryname)) {
					$entryname = basename($filename);
				}
				$this->zip->addFile($filename, $entryname);
			} else {
				throw new \ebi\exception\UnknownFileException($filename);
			}
		}
	}

	/**
	 * 内容を指定して、ファイルを ZIP アーカイブに追加する
	 * @var string $contents 内容
	 * @var string $entryname ZIP アーカイブ内部での名前
	 */
	public function add_from_string($contents, $entryname)
	{
		if ($this->writed && $this->zip->open($this->filename, \ZipArchive::CREATE) === true) {
			$this->writed = false;
		}
		$this->zip->addFromString($entryname, $contents);
	}

	public function __destruct()
	{
		if (!$this->writed) {
			$this->zip->unchangeAll();
		}
	}

	/**
	 * 書き出す
	 * @return string 書き出したZIPファイルパス
	 */
	public function write()
	{
		if (!$this->writed) {
			\ebi\Util::mkdir(dirname($this->filename));
			$this->writed = true;
			$this->zip->close();

			$this->filename = realpath($this->filename);
		}
		return $this->filename;
	}

	/**
	 * アーカイブの内容を展開する
	 * @param string $zipfile 展開するZIPファイル
	 * @param string $outpath 展開先のファイルパス
	 * @return string 展開先のファイルパス
	 */
	public static function extract($zipfile, $outpath = null)
	{
		$zip = new \ZipArchive();
		if ($zip->open($zipfile) !== true) {
			throw new \ebi\exception\AccessDeniedException('failed to open stream');
		}
		if (empty($outpath)) {
			$outpath = \ebi\WorkingStorage::tmpdir();
		}
		if (!is_dir($outpath)) {
			\ebi\Util::mkdir($outpath);
		}
		$outpath = \ebi\Util::path_slash($outpath, null, false);

		$zip->extractTo($outpath);
		$zip->close();

		return $outpath;
	}
}
