<?php

abstract class OC_Minimizer
{
	protected $files = array();

	protected function appendIfExist($root, $webroot, $file) {
                if (is_file($root.'/'.$file)) {
			$this->files[] = array($root, $webroot, $file);
                        return true;
                }
                return false;
	}

	public function getLastModified($files) {
		$last_modified = 0;
		foreach($files as $file_info) {
			$file = $file_info[0] . '/' . $file_info[2];
			$filemtime = filemtime($file);
			if ($filemtime > $last_modified) {
				$last_modified = $filemtime;
			}
		}
		return $last_modified;
	}

	abstract public function minimizeFiles($files);

	public function output($files) {
		header('Content-Type: '.$this->contentType);
		OC_Response::enableCaching();
		$last_modified = $this->getLastModified($files);
		OC_Response::setLastModifiedHeader($last_modified);

		$out = $this->minimizeFiles($files);
		OC_Response::setETagHeader(md5($out));
		header('Content-Length: '.strlen($out));
		echo $out;
	}
}
