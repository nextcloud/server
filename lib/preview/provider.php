<?php
/**
 * provides search functionalty
 */
abstract class OC_Preview_Provider{
	private $options;

	public function __construct($options) {
		$this->options=$options;
	}
	
	abstract public function getMimeType();

	/**
	 * search for $query
	 * @param string $query
	 * @return
	 */
	abstract public function getThumbnail($path, $maxX, $maxY, $scalingup,$fileview);
}
