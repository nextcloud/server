<?php
/**
 * Copyright (c) 2013-2014 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OC\Preview;

use Imagick;

if (extension_loaded('imagick')) {

        $checkImagick = new Imagick();

        if(count($checkImagick->queryFormats('TIFF')) === 1) {

                class TIFF extends Provider {

                        public function getMimeType() {
                                return '/image\/tiff/';
                        }

                        public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview) {
                                $tmpPath = $fileview->toTmpFile($path);

                                //create imagick object from TIFF
                                try{
                                        $tiff = new Imagick($tmpPath . '[0]');
                                        $tiff->setImageFormat('png');
                                } catch (\Exception $e) {
                                        \OC_Log::write('core', $e->getmessage(), \OC_Log::ERROR);
                                        return false;
                                }

                                unlink($tmpPath);

                                //new image object
                                $image = new \OC_Image($tiff);
                                //check if image object is valid
                                return $image->valid() ? $image : false;
                        }

                }

                \OC\Preview::registerProvider('OC\Preview\TIFF');
        }
}
