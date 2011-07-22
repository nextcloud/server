<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.audio.shorten.php                                    //
// module for analyzing Shorten Audio files                    //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////


class getid3_rkau
{

	function getid3_rkau(&$fd, &$ThisFileInfo) {

		fseek($fd, $ThisFileInfo['avdataoffset'], SEEK_SET);
		$RKAUHeader = fread($fd, 20);
		if (substr($RKAUHeader, 0, 3) != 'RKA') {
			$ThisFileInfo['error'][] = 'Expecting "RKA" at offset '.$ThisFileInfo['avdataoffset'].', found "'.substr($RKAUHeader, 0, 3).'"';
			return false;
		}

		$ThisFileInfo['fileformat']            = 'rkau';
		$ThisFileInfo['audio']['dataformat']   = 'rkau';
		$ThisFileInfo['audio']['bitrate_mode'] = 'vbr';

		$ThisFileInfo['rkau']['raw']['version']   = getid3_lib::LittleEndian2Int(substr($RKAUHeader, 3, 1));
		$ThisFileInfo['rkau']['version']          = '1.'.str_pad($ThisFileInfo['rkau']['raw']['version'] & 0x0F, 2, '0', STR_PAD_LEFT);
		if (($ThisFileInfo['rkau']['version'] > 1.07) || ($ThisFileInfo['rkau']['version'] < 1.06)) {
			$ThisFileInfo['error'][] = 'This version of getID3() can only parse RKAU files v1.06 and 1.07 (this file is v'.$ThisFileInfo['rkau']['version'].')';
			unset($ThisFileInfo['rkau']);
			return false;
		}

		$ThisFileInfo['rkau']['source_bytes']     = getid3_lib::LittleEndian2Int(substr($RKAUHeader,  4, 4));
		$ThisFileInfo['rkau']['sample_rate']      = getid3_lib::LittleEndian2Int(substr($RKAUHeader,  8, 4));
		$ThisFileInfo['rkau']['channels']         = getid3_lib::LittleEndian2Int(substr($RKAUHeader, 12, 1));
		$ThisFileInfo['rkau']['bits_per_sample']  = getid3_lib::LittleEndian2Int(substr($RKAUHeader, 13, 1));

		$ThisFileInfo['rkau']['raw']['quality']   = getid3_lib::LittleEndian2Int(substr($RKAUHeader, 14, 1));
		$this->RKAUqualityLookup($ThisFileInfo['rkau']);

		$ThisFileInfo['rkau']['raw']['flags']            = getid3_lib::LittleEndian2Int(substr($RKAUHeader, 15, 1));
		$ThisFileInfo['rkau']['flags']['joint_stereo']   = (bool) (!($ThisFileInfo['rkau']['raw']['flags'] & 0x01));
		$ThisFileInfo['rkau']['flags']['streaming']      =  (bool)  ($ThisFileInfo['rkau']['raw']['flags'] & 0x02);
		$ThisFileInfo['rkau']['flags']['vrq_lossy_mode'] =  (bool)  ($ThisFileInfo['rkau']['raw']['flags'] & 0x04);

		if ($ThisFileInfo['rkau']['flags']['streaming']) {
			$ThisFileInfo['avdataoffset'] += 20;
			$ThisFileInfo['rkau']['compressed_bytes']  = getid3_lib::LittleEndian2Int(substr($RKAUHeader, 16, 4));
		} else {
			$ThisFileInfo['avdataoffset'] += 16;
			$ThisFileInfo['rkau']['compressed_bytes'] = $ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset'] - 1;
		}
		// Note: compressed_bytes does not always equal what appears to be the actual number of compressed bytes,
		// sometimes it's more, sometimes less. No idea why(?)

		$ThisFileInfo['audio']['lossless']        = $ThisFileInfo['rkau']['lossless'];
		$ThisFileInfo['audio']['channels']        = $ThisFileInfo['rkau']['channels'];
		$ThisFileInfo['audio']['bits_per_sample'] = $ThisFileInfo['rkau']['bits_per_sample'];
		$ThisFileInfo['audio']['sample_rate']     = $ThisFileInfo['rkau']['sample_rate'];

		$ThisFileInfo['playtime_seconds']         = $ThisFileInfo['rkau']['source_bytes'] / ($ThisFileInfo['rkau']['sample_rate'] * $ThisFileInfo['rkau']['channels'] * ($ThisFileInfo['rkau']['bits_per_sample'] / 8));
		$ThisFileInfo['audio']['bitrate']         = ($ThisFileInfo['rkau']['compressed_bytes'] * 8) / $ThisFileInfo['playtime_seconds'];

		return true;

	}


	function RKAUqualityLookup(&$RKAUdata) {
		$level   = ($RKAUdata['raw']['quality'] & 0xF0) >> 4;
		$quality =  $RKAUdata['raw']['quality'] & 0x0F;

		$RKAUdata['lossless']          = (($quality == 0) ? true : false);
		$RKAUdata['compression_level'] = $level + 1;
		if (!$RKAUdata['lossless']) {
			$RKAUdata['quality_setting'] = $quality;
		}

		return true;
	}

}

?>