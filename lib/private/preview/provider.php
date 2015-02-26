<?php
namespace OC\Preview;

abstract class Provider {
	private $options;

	public function __construct($options) {
		$this->options = $options;
	}

	/**
	 * @return string Regex with the mimetypes that are supported by this provider
	 */
	abstract public function getMimeType();

	/**
	 * Check if a preview can be generated for $path
	 *
	 * @param \OC\Files\FileInfo $file
	 * @return bool
	 */
	public function isAvailable($file) {
		return true;
	}

	/**
	 * get thumbnail for file at path $path
	 * @param string $path Path of file
	 * @param int $maxX The maximum X size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param int $maxY The maximum Y size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param bool $scalingup Disable/Enable upscaling of previews
	 * @param \OC\Files\View $fileview fileview object of user folder
	 * @return mixed
	 * 		false if no preview was generated
	 *		OC_Image object of the preview
	 */
	abstract public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview);
}
