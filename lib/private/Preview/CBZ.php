<?php

namespace OC\Preview;

use ZipArchive;

//.cbz
class CBZ extends Provider {
    /**
     * {@inheritDoc}
     */
    public function getMimeType() {
        return '/application\/comicbook\+zip/';
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
            $picture = $zip->getFromIndex(0);
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