<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.audio.lpac.php                                       //
// module for analyzing LPAC Audio files                       //
// dependencies: module.audio-video.riff.php                   //
//                                                            ///
/////////////////////////////////////////////////////////////////

getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.audio-video.riff.php', __FILE__, true);

class getid3_lpac
{

	function getid3_lpac(&$fd, &$ThisFileInfo) {

		fseek($fd, $ThisFileInfo['avdataoffset'], SEEK_SET);
		$LPACheader = fread($fd, 14);
		if (substr($LPACheader, 0, 4) != 'LPAC') {
			$ThisFileInfo['error'][] = 'Expected "LPAC" at offset '.$ThisFileInfo['avdataoffset'].', found "'.$StreamMarker.'"';
			return false;
		}
		$ThisFileInfo['avdataoffset'] += 14;

		$ThisFileInfo['fileformat']            = 'lpac';
		$ThisFileInfo['audio']['dataformat']   = 'lpac';
		$ThisFileInfo['audio']['lossless']     = true;
		$ThisFileInfo['audio']['bitrate_mode'] = 'vbr';

		$ThisFileInfo['lpac']['file_version'] = getid3_lib::BigEndian2Int(substr($LPACheader,  4, 1));
		$flags['audio_type']                  = getid3_lib::BigEndian2Int(substr($LPACheader,  5, 1));
		$ThisFileInfo['lpac']['total_samples']= getid3_lib::BigEndian2Int(substr($LPACheader,  6, 4));
		$flags['parameters']                  = getid3_lib::BigEndian2Int(substr($LPACheader, 10, 4));

		$ThisFileInfo['lpac']['flags']['is_wave'] = (bool) ($flags['audio_type'] & 0x40);
		$ThisFileInfo['lpac']['flags']['stereo']  = (bool) ($flags['audio_type'] & 0x04);
		$ThisFileInfo['lpac']['flags']['24_bit']  = (bool) ($flags['audio_type'] & 0x02);
		$ThisFileInfo['lpac']['flags']['16_bit']  = (bool) ($flags['audio_type'] & 0x01);

		if ($ThisFileInfo['lpac']['flags']['24_bit'] && $ThisFileInfo['lpac']['flags']['16_bit']) {
			$ThisFileInfo['warning'][] = '24-bit and 16-bit flags cannot both be set';
		}

		$ThisFileInfo['lpac']['flags']['fast_compress']             =  (bool) ($flags['parameters'] & 0x40000000);
		$ThisFileInfo['lpac']['flags']['random_access']             =  (bool) ($flags['parameters'] & 0x08000000);
		$ThisFileInfo['lpac']['block_length']                       = pow(2, (($flags['parameters'] & 0x07000000) >> 24)) * 256;
		$ThisFileInfo['lpac']['flags']['adaptive_prediction_order'] =  (bool) ($flags['parameters'] & 0x00800000);
		$ThisFileInfo['lpac']['flags']['adaptive_quantization']     =  (bool) ($flags['parameters'] & 0x00400000);
		$ThisFileInfo['lpac']['flags']['joint_stereo']              =  (bool) ($flags['parameters'] & 0x00040000);
		$ThisFileInfo['lpac']['quantization']                       =         ($flags['parameters'] & 0x00001F00) >> 8;
		$ThisFileInfo['lpac']['max_prediction_order']               =         ($flags['parameters'] & 0x0000003F);

		if ($ThisFileInfo['lpac']['flags']['fast_compress'] && ($ThisFileInfo['lpac']['max_prediction_order'] != 3)) {
			$ThisFileInfo['warning'][] = 'max_prediction_order expected to be "3" if fast_compress is true, actual value is "'.$ThisFileInfo['lpac']['max_prediction_order'].'"';
		}
		switch ($ThisFileInfo['lpac']['file_version']) {
			case 6:
				if ($ThisFileInfo['lpac']['flags']['adaptive_quantization']) {
					$ThisFileInfo['warning'][] = 'adaptive_quantization expected to be false in LPAC file stucture v6, actually true';
				}
				if ($ThisFileInfo['lpac']['quantization'] != 20) {
					$ThisFileInfo['warning'][] = 'Quantization expected to be 20 in LPAC file stucture v6, actually '.$ThisFileInfo['lpac']['flags']['Q'];
				}
				break;

			default:
				//$ThisFileInfo['warning'][] = 'This version of getID3() only supports LPAC file format version 6, this file is version '.$ThisFileInfo['lpac']['file_version'].' - please report to info@getid3.org';
				break;
		}

		$dummy = $ThisFileInfo;
		$riff = new getid3_riff($fd, $dummy);
		unset($riff);
		$ThisFileInfo['avdataoffset']                = $dummy['avdataoffset'];
		$ThisFileInfo['riff']                        = $dummy['riff'];
		$ThisFileInfo['error']                       = $dummy['error'];
		$ThisFileInfo['warning']                     = $dummy['warning'];
		$ThisFileInfo['lpac']['comments']['comment'] = $dummy['comments'];
		$ThisFileInfo['audio']['sample_rate']        = $dummy['audio']['sample_rate'];

		$ThisFileInfo['audio']['channels']    = ($ThisFileInfo['lpac']['flags']['stereo'] ? 2 : 1);

		if ($ThisFileInfo['lpac']['flags']['24_bit']) {
			$ThisFileInfo['audio']['bits_per_sample'] = $ThisFileInfo['riff']['audio'][0]['bits_per_sample'];
		} elseif ($ThisFileInfo['lpac']['flags']['16_bit']) {
			$ThisFileInfo['audio']['bits_per_sample'] = 16;
		} else {
			$ThisFileInfo['audio']['bits_per_sample'] = 8;
		}

		if ($ThisFileInfo['lpac']['flags']['fast_compress']) {
			 // fast
			$ThisFileInfo['audio']['encoder_options'] = '-1';
		} else {
			switch ($ThisFileInfo['lpac']['max_prediction_order']) {
				case 20: // simple
					$ThisFileInfo['audio']['encoder_options'] = '-2';
					break;
				case 30: // medium
					$ThisFileInfo['audio']['encoder_options'] = '-3';
					break;
				case 40: // high
					$ThisFileInfo['audio']['encoder_options'] = '-4';
					break;
				case 60: // extrahigh
					$ThisFileInfo['audio']['encoder_options'] = '-5';
					break;
			}
		}

		$ThisFileInfo['playtime_seconds'] = $ThisFileInfo['lpac']['total_samples'] / $ThisFileInfo['audio']['sample_rate'];
		$ThisFileInfo['audio']['bitrate'] = (($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']) * 8) / $ThisFileInfo['playtime_seconds'];

		return true;
	}

}


?>