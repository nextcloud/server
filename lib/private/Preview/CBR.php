<?php

namespace OC\Preview;

class CBR extends Provider {
	/**
	 * {@inheritDoc}
	 */
	public function getMimeType() {
		return '/application\/comicbook\+rar/';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview) {

		$tmpPath = $fileview->toTmpFile($path);
		$res = rar_open($tmpPath);
		$picture = null;
		if (!is_null($res)) {
			$rar = rar_list($res);

			/* I can't figure out a way to do this without extracting to the file system. */
			$extractToFolder = dirname($tmpPath) . '/';
			$rar[0]->extract($extractToFolder);
			$picture = $extractToFolder . $rar[0]->getName();
		}

		rar_close($res);
		unlink($tmpPath);

		if(!is_null($picture)) {
			$image = new \OC_Image();
			$image->loadFromFile($picture);

			if ($image->valid()) {
				unlink($picture);
				$image->scaleDownToFit($maxX, $maxY);

				return $image;
			}
		}

		return false;
	}
}