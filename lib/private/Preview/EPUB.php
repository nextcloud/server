<?php

namespace OC\Preview;

use ZipArchive;

class EPUB extends Provider {
	/**
	 * {@inheritDoc}
	 */
	public function getMimeType() {
		return '/application\/epub\+zip/';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview) {
		$zip = new ZipArchive();

		$tmpPath = $fileview->toTmpFile($path);
		$res = $zip->open($tmpPath);
		$picture = null;
		if ($res === TRUE) {
			/* Need to parse the container file to find the cover-image. */
			$containerxmlfile = $zip->getFromName('META-INF/container.xml');
			$containerxml = simplexml_load_string($containerxmlfile);

			/* The OPF file should have a more or less direct connection to the cover-image. */
			$opffilepath = (string)$containerxml->rootfiles->rootfile->attributes()->{'full-path'};
			$opffile = $zip->getFromName($opffilepath);

			/* Fuck you haystack, almost got the needle. */
			$opffile = str_replace('xmlns=', 'ns=', $opffile);
			$opfxml = simplexml_load_string($opffile);
			$opfxml->registerXPathNamespace('opf','http://www.idpf.org/2007/opf');

			$coverimagefile = (string)$opfxml->xpath('//item[@id=//meta[@name="cover"]/@content]/@href')[0];
            $coverpath = (dirname($opffilepath) == '.') ? "" : dirname($opffilepath) . '/';
			$picture = $zip->getFromName( $coverpath . $coverimagefile);

		}

		$zip->close();
		unlink($tmpPath);

		if(!is_null($picture)) {
			$image = new \OC_Image();
			$image->loadFromData($picture);

			if ($image->valid()) {
				$image->scaleDownToFit($maxX, $maxY);

				return $image;
			}
		}

		return false;
	}
}