<?php

namespace OC\Preview\Storage;

use Icewind\Streams\CountWrapper;
use OC\Files\Storage\Local;
use OC\Preview\Db\Preview;
use OCP\Files\Storage\IStorage;
use OCP\IPreview;

class LocalPreviewStorage implements IPreviewStorage {
	private const PREVIEW_DIRECTORY = "__preview";

	public function __construct(private readonly string $rootFolder) {
	}

	public function writePreview(Preview $preview, $stream): false|int {
		$previewPath = $this->constructPath($preview);
		['basename' => $basename, 'dirname' => $dirname] = pathinfo($previewPath);
		$currentDir = $this->rootFolder . DIRECTORY_SEPARATOR . self::PREVIEW_DIRECTORY;
		mkdir($currentDir);
		foreach (explode('/', $dirname) as $suffix) {
			$currentDir .= "/$suffix";
			mkdir($currentDir);
		}
		$file = @fopen($this->rootFolder . DIRECTORY_SEPARATOR . self::PREVIEW_DIRECTORY . DIRECTORY_SEPARATOR . $previewPath, "w");
		return fwrite($file, $stream);
	}

	public function readPreview(Preview $preview) {
		$previewPath = $this->constructPath($preview);
		return @fopen($this->rootFolder . DIRECTORY_SEPARATOR . self::PREVIEW_DIRECTORY . DIRECTORY_SEPARATOR . $previewPath, "r");
	}

	public function deletePreview(Preview $preview) {
		$previewPath = $this->constructPath($preview);
		@unlink($this->rootFolder . DIRECTORY_SEPARATOR . self::PREVIEW_DIRECTORY . DIRECTORY_SEPARATOR . $previewPath);
	}

	private function constructPath(Preview $preview): string {
		return implode('/', str_split(substr(md5((string)$preview->getFileId()), 0, 7))) . '/' . $preview->getFileId() . '/' . $preview->getName();
	}
}
