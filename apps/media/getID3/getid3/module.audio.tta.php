<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.audio.tta.php                                        //
// module for analyzing TTA Audio files                        //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////


class getid3_tta
{

	function getid3_tta(&$fd, &$ThisFileInfo) {

		$ThisFileInfo['fileformat']            = 'tta';
		$ThisFileInfo['audio']['dataformat']   = 'tta';
		$ThisFileInfo['audio']['lossless']     = true;
		$ThisFileInfo['audio']['bitrate_mode'] = 'vbr';

		fseek($fd, $ThisFileInfo['avdataoffset'], SEEK_SET);
		$ttaheader = fread($fd, 26);

		$ThisFileInfo['tta']['magic'] = substr($ttaheader,  0,  3);
		if ($ThisFileInfo['tta']['magic'] != 'TTA') {
			$ThisFileInfo['error'][] = 'Expecting "TTA" at offset '.$ThisFileInfo['avdataoffset'].', found "'.$ThisFileInfo['tta']['magic'].'"';
			unset($ThisFileInfo['fileformat']);
			unset($ThisFileInfo['audio']);
			unset($ThisFileInfo['tta']);
			return false;
		}

		switch ($ttaheader{3}) {
			case "\x01": // TTA v1.x
			case "\x02": // TTA v1.x
			case "\x03": // TTA v1.x
				// "It was the demo-version of the TTA encoder. There is no released format with such header. TTA encoder v1 is not supported about a year."
				$ThisFileInfo['tta']['major_version'] = 1;
				$ThisFileInfo['avdataoffset'] += 16;

				$ThisFileInfo['tta']['compression_level']   = ord($ttaheader{3});
				$ThisFileInfo['tta']['channels']            = getid3_lib::LittleEndian2Int(substr($ttaheader,  4,  2));
				$ThisFileInfo['tta']['bits_per_sample']     = getid3_lib::LittleEndian2Int(substr($ttaheader,  6,  2));
				$ThisFileInfo['tta']['sample_rate']         = getid3_lib::LittleEndian2Int(substr($ttaheader,  8,  4));
				$ThisFileInfo['tta']['samples_per_channel'] = getid3_lib::LittleEndian2Int(substr($ttaheader, 12,  4));

				$ThisFileInfo['audio']['encoder_options']   = '-e'.$ThisFileInfo['tta']['compression_level'];
				$ThisFileInfo['playtime_seconds']           = $ThisFileInfo['tta']['samples_per_channel'] / $ThisFileInfo['tta']['sample_rate'];
				break;

			case '2': // TTA v2.x
				// "I have hurried to release the TTA 2.0 encoder. Format documentation is removed from our site. This format still in development. Please wait the TTA2 format, encoder v4."
				$ThisFileInfo['tta']['major_version'] = 2;
				$ThisFileInfo['avdataoffset'] += 20;

				$ThisFileInfo['tta']['compression_level']   = getid3_lib::LittleEndian2Int(substr($ttaheader,  4,  2));
				$ThisFileInfo['tta']['audio_format']        = getid3_lib::LittleEndian2Int(substr($ttaheader,  6,  2));
				$ThisFileInfo['tta']['channels']            = getid3_lib::LittleEndian2Int(substr($ttaheader,  8,  2));
				$ThisFileInfo['tta']['bits_per_sample']     = getid3_lib::LittleEndian2Int(substr($ttaheader, 10,  2));
				$ThisFileInfo['tta']['sample_rate']         = getid3_lib::LittleEndian2Int(substr($ttaheader, 12,  4));
				$ThisFileInfo['tta']['data_length']         = getid3_lib::LittleEndian2Int(substr($ttaheader, 16,  4));

				$ThisFileInfo['audio']['encoder_options']   = '-e'.$ThisFileInfo['tta']['compression_level'];
				$ThisFileInfo['playtime_seconds']           = $ThisFileInfo['tta']['data_length'] / $ThisFileInfo['tta']['sample_rate'];
				break;

			case '1': // TTA v3.x
				// "This is a first stable release of the TTA format. It will be supported by the encoders v3 or higher."
				$ThisFileInfo['tta']['major_version'] = 3;
				$ThisFileInfo['avdataoffset'] += 26;

				$ThisFileInfo['tta']['audio_format']        = getid3_lib::LittleEndian2Int(substr($ttaheader,  4,  2)); // getid3_riff::RIFFwFormatTagLookup()
				$ThisFileInfo['tta']['channels']            = getid3_lib::LittleEndian2Int(substr($ttaheader,  6,  2));
				$ThisFileInfo['tta']['bits_per_sample']     = getid3_lib::LittleEndian2Int(substr($ttaheader,  8,  2));
				$ThisFileInfo['tta']['sample_rate']         = getid3_lib::LittleEndian2Int(substr($ttaheader, 10,  4));
				$ThisFileInfo['tta']['data_length']         = getid3_lib::LittleEndian2Int(substr($ttaheader, 14,  4));
				$ThisFileInfo['tta']['crc32_footer']        =                              substr($ttaheader, 18,  4);
				$ThisFileInfo['tta']['seek_point']          = getid3_lib::LittleEndian2Int(substr($ttaheader, 22,  4));

				$ThisFileInfo['playtime_seconds']           = $ThisFileInfo['tta']['data_length'] / $ThisFileInfo['tta']['sample_rate'];
				break;

			default:
				$ThisFileInfo['error'][] = 'This version of getID3() only knows how to handle TTA v1 and v2 - it may not work correctly with this file which appears to be TTA v'.$ttaheader{3};
				return false;
				break;
		}

		$ThisFileInfo['audio']['encoder']         = 'TTA v'.$ThisFileInfo['tta']['major_version'];
		$ThisFileInfo['audio']['bits_per_sample'] = $ThisFileInfo['tta']['bits_per_sample'];
		$ThisFileInfo['audio']['sample_rate']     = $ThisFileInfo['tta']['sample_rate'];
		$ThisFileInfo['audio']['channels']        = $ThisFileInfo['tta']['channels'];
		$ThisFileInfo['audio']['bitrate']         = (($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']) * 8) / $ThisFileInfo['playtime_seconds'];

		return true;
	}

}


?>