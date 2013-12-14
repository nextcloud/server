<?php
namespace OC\Preview;

abstract class Provider {
	private $options;

	public function __construct($options) {
		$this->options=$options;
	}

	abstract public function getMimeType();

	/**
	 * get thumbnail for file at path $path
	 * @param string $path Path of file
	 * @param int $maxX The maximum X size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param int $maxY The maximum Y size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param bool $scalingUp Disable/Enable upscaling of previews
	 * @param object $fileview fileview object of user folder
	 * @return mixed
	 * 		false if no preview was generated
	 *		OC_Image object of the preview
	 */
	abstract public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview);
}
