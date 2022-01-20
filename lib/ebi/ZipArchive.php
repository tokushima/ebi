<?php

namespace ebi;

/**
 * Zip で圧縮されたファイルアーカイブ
 */
class ZipArchive
{
	private $zip;
	private $wrote = false;
	private $filename;

	public function __construct(string $filename, bool $append = false)
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

	private function dirs(string $dir, string $basedir, ?string $entry_name): array{
		$list = [5 => [], 0 => []];

		if ($h = opendir($dir)) {
			while ($p = readdir($h)) {
				if ($p != '.' && $p != '..' && strpos($p, '/.') === false) {
					$s = sprintf('%s/%s', $dir, $p);

					if (is_dir($s)) {
						$ln = str_replace($basedir, $entry_name ?? '', $s);
						$list[5][$ln] = $s;
						$r = $this->dirs($s, $basedir, $entry_name);
						$list[5] = array_merge($list[5], $r[5]);
						$list[0] = array_merge($list[0], $r[0]);
					} else {
						$ln = str_replace($basedir, $entry_name ?? '', $s);
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
	 */
	public function add(string $filename, ?string $entry_name = null): void{
		if ($this->wrote && $this->zip->open($this->filename, \ZipArchive::CREATE) === true) {
			$this->wrote = false;
		}
		if (is_dir($filename)) {
			$entry_name = \ebi\Util::path_slash($entry_name, null, false);
			$dir = \ebi\Util::path_slash(realpath($filename), null, true);
			$list = $this->dirs($dir, $dir, $entry_name);

			foreach (array_keys($list[5]) as $ln) {
				$this->zip->addEmptyDir($ln);
			}
			foreach ($list[0] as $ln => $path) {
				$this->zip->addFile($path, $ln);
			}
		} else {
			if (is_file($filename)) {
				if (empty($entry_name)) {
					$entry_name = basename($filename);
				}
				$this->zip->addFile($filename, $entry_name);
			} else {
				throw new \ebi\exception\UnknownFileException($filename);
			}
		}
	}

	/**
	 * 内容を指定して、ファイルを ZIP アーカイブに追加する
	 */
	public function add_from_string(string $contents, string $entry_name): void{
		if ($this->wrote && $this->zip->open($this->filename, \ZipArchive::CREATE) === true) {
			$this->wrote = false;
		}
		$this->zip->addFromString($entry_name, $contents);
	}

	public function __destruct(){
		if (!$this->wrote) {
			$this->zip->unchangeAll();
		}
	}

	/**
	 * 書き出す
	 * @return 書き出したZIPファイルパス
	 */
	public function write(): string{
		if (!$this->wrote) {
			\ebi\Util::mkdir(dirname($this->filename));
			$this->wrote = true;
			$this->zip->close();

			$this->filename = realpath($this->filename);
		}
		return $this->filename;
	}

	/**
	 * アーカイブの内容を展開する
	 */
	public static function extract(string $filename, ?string $output_dir = null): string{
		$zip = new \ZipArchive();
		if ($zip->open($filename) !== true) {
			throw new \ebi\exception\AccessDeniedException('failed to open stream');
		}
		if (empty($output_dir)) {
			$output_dir = \ebi\WorkingStorage::tmpdir();
		}
		if (!is_dir($output_dir)) {
			\ebi\Util::mkdir($output_dir);
		}
		$output_dir = \ebi\Util::path_slash($output_dir, null, false);

		$zip->extractTo($output_dir);
		$zip->close();

		return $output_dir;
	}
}
