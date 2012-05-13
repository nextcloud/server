<?php
/**
 * local storage backnd in temporary folder for testing purpores
 */
class OC_Filestorage_Temporary extends OC_Filestorage_Local{
	public function __construct($arguments){
		$this->datadir=OC_Helper::tmpFolder();
	}

	public function cleanUp(){
		OC_Helper::rmdirr($this->datadir);
	}

	public function __destruct(){
		$this->cleanUp();
	}
}
