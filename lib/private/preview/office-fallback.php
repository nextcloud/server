<?php
/**
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OC\Preview;

/* //There is no (good) php-only solution for converting 2003 word documents to pdfs / pngs ...
class DOC extends Provider {

	public function getMimeType() {
		return '/application\/msword/';
	}

	public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview) {
		require_once('');
	}

}

\OC\Preview::registerProvider('OC\Preview\DOC');
*/

class DOCX extends Provider {

	public function getMimeType() {
		return '/application\/vnd.openxmlformats-officedocument.wordprocessingml.document/';
	}

	public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview) {
		require_once('phpdocx/classes/TransformDoc.inc');

		$tmpDoc = $fileview->toTmpFile($path);

		$transformdoc = new \TransformDoc();
		$transformdoc->setStrFile($tmpDoc);
		$transformdoc->generatePDF($tmpDoc);

		$pdf = new \imagick($tmpDoc . '[0]');
		$pdf->setImageFormat('jpg');

		unlink($tmpDoc);

		$image = new \OC_Image($pdf);

		return $image->valid() ? $image : false;
	}

}

\OC\Preview::registerProvider('OC\Preview\DOCX');

class MSOfficeExcel extends Provider {

	public function getMimeType() {
		return null;
	}

	public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview) {
		require_once('PHPExcel/Classes/PHPExcel.php');
		require_once('PHPExcel/Classes/PHPExcel/IOFactory.php');

		$absPath = $fileview->toTmpFile($path);
		$tmpPath = \OC_Helper::tmpFile();

		$rendererName = \PHPExcel_Settings::PDF_RENDERER_DOMPDF;
		$rendererLibraryPath = \OC::$THIRDPARTYROOT . '/3rdparty/dompdf';

		\PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath);

		$phpexcel = new \PHPExcel($absPath);
		$excel = \PHPExcel_IOFactory::createWriter($phpexcel, 'PDF');
		$excel->save($tmpPath);

		$pdf = new \imagick($tmpPath . '[0]');
		$pdf->setImageFormat('jpg');

		unlink($absPath);
		unlink($tmpPath);

		$image = new \OC_Image();
		$image->loadFromData($pdf);

		return $image->valid() ? $image : false;
	}

}

class XLS extends MSOfficeExcel {

	public function getMimeType() {
		return '/application\/vnd.ms-excel/';
	}

}

\OC\Preview::registerProvider('OC\Preview\XLS');

class XLSX extends MSOfficeExcel {

	public function getMimeType() {
		return '/application\/vnd.openxmlformats-officedocument.spreadsheetml.sheet/';
	}

}

\OC\Preview::registerProvider('OC\Preview\XLSX');

/* //There is no (good) php-only solution for converting powerpoint documents to pdfs / pngs ...
class MSOfficePowerPoint extends Provider {

	public function getMimeType() {
		return null;
	}

	public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview) {
		return false;
	}

}

class PPT extends MSOfficePowerPoint {

	public function getMimeType() {
		return '/application\/vnd.ms-powerpoint/';
	}

}

\OC\Preview::registerProvider('OC\Preview\PPT');

class PPTX extends MSOfficePowerPoint {

	public function getMimeType() {
		return '/application\/vnd.openxmlformats-officedocument.presentationml.presentation/';
	}

}

\OC\Preview::registerProvider('OC\Preview\PPTX');
*/