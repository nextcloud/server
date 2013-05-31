<?php
/**
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OC\Preview;

class MSOffice2003 extends Provider {

	public function getMimeType(){
		return null;
	}

	public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview){
		return false;
	}
}


class MSOffice2007 extends Provider {

	public function getMimeType(){
		return null;
	}

	public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview) {
		require_once('phpdocx/classes/TransformDoc.inc');

		$tmpdoc = $fileview->toTmpFile($path);

		$transformdoc = new \TransformDoc();
		$transformdoc->setStrFile($tmpdoc);
		$transformdoc->generatePDF($tmpdoc);

		$pdf = new \imagick($tmpdoc . '[0]');
		$pdf->setImageFormat('jpg');

		unlink($tmpdoc);

		//new image object
		$image = new \OC_Image($pdf);
		//check if image object is valid
		if (!$image->valid()) return false;

		return $image;
	}
}

class DOC extends MSOffice2003 {

	public function getMimeType() {
		return '/application\/msword/';
	}

}

\OC\Preview::registerProvider('OC\Preview\DOC');

class DOCX extends MSOffice2007 {

	public function getMimeType() {
		return '/application\/vnd.openxmlformats-officedocument.wordprocessingml.document/';
	}

}

\OC\Preview::registerProvider('OC\Preview\DOCX');

class XLS extends MSOffice2003 {

	public function getMimeType() {
		return '/application\/vnd.ms-excel/';
	}

}

\OC\Preview::registerProvider('OC\Preview\XLS');

class XLSX extends MSOffice2007 {

	public function getMimeType() {
		return '/application\/vnd.openxmlformats-officedocument.spreadsheetml.sheet/';
	}

}

\OC\Preview::registerProvider('OC\Preview\XLSX');

class PPT extends MSOffice2003 {

	public function getMimeType() {
		return '/application\/vnd.ms-powerpoint/';
	}

}

\OC\Preview::registerProvider('OC\Preview\PPT');

class PPTX extends MSOffice2007 {

	public function getMimeType() {
		return '/application\/vnd.openxmlformats-officedocument.presentationml.presentation/';
	}

}

\OC\Preview::registerProvider('OC\Preview\PPTX');