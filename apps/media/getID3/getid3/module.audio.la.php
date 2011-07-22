<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.audio.la.php                                         //
// module for analyzing LA audio files                         //
// dependencies: module.audio.riff.php                         //
//                                                            ///
/////////////////////////////////////////////////////////////////

getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.audio-video.riff.php', __FILE__, true);

class getid3_la
{

	function getid3_la(&$fd, &$ThisFileInfo) {
		$offset = 0;
		fseek($fd, $ThisFileInfo['avdataoffset'], SEEK_SET);
		$rawdata = fread($fd, GETID3_FREAD_BUFFER_SIZE);

		switch (substr($rawdata, $offset, 4)) {
			case 'LA02':
			case 'LA03':
			case 'LA04':
				$ThisFileInfo['fileformat']          = 'la';
				$ThisFileInfo['audio']['dataformat'] = 'la';
				$ThisFileInfo['audio']['lossless']   = true;

				$ThisFileInfo['la']['version_major'] = (int) substr($rawdata, $offset + 2, 1);
				$ThisFileInfo['la']['version_minor'] = (int) substr($rawdata, $offset + 3, 1);
				$ThisFileInfo['la']['version']       = (float) $ThisFileInfo['la']['version_major'] + ($ThisFileInfo['la']['version_minor'] / 10);
				$offset += 4;

				$ThisFileInfo['la']['uncompressed_size'] = getid3_lib::LittleEndian2Int(substr($rawdata, $offset, 4));
				$offset += 4;
				if ($ThisFileInfo['la']['uncompressed_size'] == 0) {
					$ThisFileInfo['error'][] = 'Corrupt LA file: uncompressed_size == zero';
					return false;
				}

				$WAVEchunk = substr($rawdata, $offset, 4);
				if ($WAVEchunk !== 'WAVE') {
					$ThisFileInfo['error'][] = 'Expected "WAVE" ('.getid3_lib::PrintHexBytes('WAVE').') at offset '.$offset.', found "'.$WAVEchunk.'" ('.getid3_lib::PrintHexBytes($WAVEchunk).') instead.';
					return false;
				}
				$offset += 4;

				$ThisFileInfo['la']['fmt_size'] = 24;
				if ($ThisFileInfo['la']['version'] >= 0.3) {

					$ThisFileInfo['la']['fmt_size']    = getid3_lib::LittleEndian2Int(substr($rawdata, $offset, 4));
					$ThisFileInfo['la']['header_size'] = 49 + $ThisFileInfo['la']['fmt_size'] - 24;
					$offset += 4;

				} else {

					// version 0.2 didn't support additional data blocks
					$ThisFileInfo['la']['header_size'] = 41;

				}

				$fmt_chunk = substr($rawdata, $offset, 4);
				if ($fmt_chunk !== 'fmt ') {
					$ThisFileInfo['error'][] = 'Expected "fmt " ('.getid3_lib::PrintHexBytes('fmt ').') at offset '.$offset.', found "'.$fmt_chunk.'" ('.getid3_lib::PrintHexBytes($fmt_chunk).') instead.';
					return false;
				}
				$offset += 4;
				$fmt_size = getid3_lib::LittleEndian2Int(substr($rawdata, $offset, 4));
				$offset += 4;

				$ThisFileInfo['la']['raw']['format']  = getid3_lib::LittleEndian2Int(substr($rawdata, $offset, 2));
				$offset += 2;

				$ThisFileInfo['la']['channels']       = getid3_lib::LittleEndian2Int(substr($rawdata, $offset, 2));
				$offset += 2;
				if ($ThisFileInfo['la']['channels'] == 0) {
					$ThisFileInfo['error'][] = 'Corrupt LA file: channels == zero';
						return false;
				}

				$ThisFileInfo['la']['sample_rate'] = getid3_lib::LittleEndian2Int(substr($rawdata, $offset, 4));
				$offset += 4;
				if ($ThisFileInfo['la']['sample_rate'] == 0) {
					$ThisFileInfo['error'][] = 'Corrupt LA file: sample_rate == zero';
						return false;
				}

				$ThisFileInfo['la']['bytes_per_second']     = getid3_lib::LittleEndian2Int(substr($rawdata, $offset, 4));
				$offset += 4;
				$ThisFileInfo['la']['bytes_per_sample']     = getid3_lib::LittleEndian2Int(substr($rawdata, $offset, 2));
				$offset += 2;
				$ThisFileInfo['la']['bits_per_sample']      = getid3_lib::LittleEndian2Int(substr($rawdata, $offset, 2));
				$offset += 2;

				$ThisFileInfo['la']['samples']              = getid3_lib::LittleEndian2Int(substr($rawdata, $offset, 4));
				$offset += 4;

				$ThisFileInfo['la']['raw']['flags']         = getid3_lib::LittleEndian2Int(substr($rawdata, $offset, 1));
				$offset += 1;
				$ThisFileInfo['la']['flags']['seekable']             = (bool) ($ThisFileInfo['la']['raw']['flags'] & 0x01);
				if ($ThisFileInfo['la']['version'] >= 0.4) {
					$ThisFileInfo['la']['flags']['high_compression'] = (bool) ($ThisFileInfo['la']['raw']['flags'] & 0x02);
				}

				$ThisFileInfo['la']['original_crc']         = getid3_lib::LittleEndian2Int(substr($rawdata, $offset, 4));
				$offset += 4;

				// mikeØbevin*de
				// Basically, the blocksize/seekevery are 61440/19 in La0.4 and 73728/16
				// in earlier versions. A seekpoint is added every blocksize * seekevery
				// samples, so 4 * int(totalSamples / (blockSize * seekEvery)) should
				// give the number of bytes used for the seekpoints. Of course, if seeking
				// is disabled, there are no seekpoints stored.
				if ($ThisFileInfo['la']['version'] >= 0.4) {
					$ThisFileInfo['la']['blocksize'] = 61440;
					$ThisFileInfo['la']['seekevery'] = 19;
				} else {
					$ThisFileInfo['la']['blocksize'] = 73728;
					$ThisFileInfo['la']['seekevery'] = 16;
				}

				$ThisFileInfo['la']['seekpoint_count'] = 0;
				if ($ThisFileInfo['la']['flags']['seekable']) {
					$ThisFileInfo['la']['seekpoint_count'] = floor($ThisFileInfo['la']['samples'] / ($ThisFileInfo['la']['blocksize'] * $ThisFileInfo['la']['seekevery']));

					for ($i = 0; $i < $ThisFileInfo['la']['seekpoint_count']; $i++) {
						$ThisFileInfo['la']['seekpoints'][] = getid3_lib::LittleEndian2Int(substr($rawdata, $offset, 4));
						$offset += 4;
					}
				}

				if ($ThisFileInfo['la']['version'] >= 0.3) {

					// Following the main header information, the program outputs all of the
					// seekpoints. Following these is what I called the 'footer start',
					// i.e. the position immediately after the La audio data is finished.
					$ThisFileInfo['la']['footerstart'] = getid3_lib::LittleEndian2Int(substr($rawdata, $offset, 4));
					$offset += 4;

					if ($ThisFileInfo['la']['footerstart'] > $ThisFileInfo['filesize']) {
						$ThisFileInfo['warning'][] = 'FooterStart value points to offset '.$ThisFileInfo['la']['footerstart'].' which is beyond end-of-file ('.$ThisFileInfo['filesize'].')';
						$ThisFileInfo['la']['footerstart'] = $ThisFileInfo['filesize'];
					}

				} else {

					// La v0.2 didn't have FooterStart value
					$ThisFileInfo['la']['footerstart'] = $ThisFileInfo['avdataend'];

				}

				if ($ThisFileInfo['la']['footerstart'] < $ThisFileInfo['avdataend']) {
					if ($RIFFtempfilename = tempnam('*', 'id3')) {
						if ($RIFF_fp = fopen($RIFFtempfilename, 'w+b')) {
							$RIFFdata = 'WAVE';
							if ($ThisFileInfo['la']['version'] == 0.2) {
								$RIFFdata .= substr($rawdata, 12, 24);
							} else {
								$RIFFdata .= substr($rawdata, 16, 24);
							}
							if ($ThisFileInfo['la']['footerstart'] < $ThisFileInfo['avdataend']) {
								fseek($fd, $ThisFileInfo['la']['footerstart'], SEEK_SET);
								$RIFFdata .= fread($fd, $ThisFileInfo['avdataend'] - $ThisFileInfo['la']['footerstart']);
							}
							$RIFFdata = 'RIFF'.getid3_lib::LittleEndian2String(strlen($RIFFdata), 4, false).$RIFFdata;
							fwrite($RIFF_fp, $RIFFdata, strlen($RIFFdata));
							$dummy = $ThisFileInfo;
							$dummy['filesize']     = strlen($RIFFdata);
							$dummy['avdataoffset'] = 0;
							$dummy['avdataend']    = $dummy['filesize'];

							$riff = new getid3_riff($RIFF_fp, $dummy);
							if (empty($dummy['error'])) {
								$ThisFileInfo['riff'] = $dummy['riff'];
							} else {
								$ThisFileInfo['warning'][] = 'Error parsing RIFF portion of La file: '.implode($dummy['error']);
							}
							unset($riff);
							unset($dummy);
							fclose($RIFF_fp);
						}
						unlink($RIFFtempfilename);
					}
				}

				// $ThisFileInfo['avdataoffset'] should be zero to begin with, but just in case it's not, include the addition anyway
				$ThisFileInfo['avdataend']    = $ThisFileInfo['avdataoffset'] + $ThisFileInfo['la']['footerstart'];
				$ThisFileInfo['avdataoffset'] = $ThisFileInfo['avdataoffset'] + $offset;

				//$ThisFileInfo['la']['codec']                = RIFFwFormatTagLookup($ThisFileInfo['la']['raw']['format']);
				$ThisFileInfo['la']['compression_ratio']    = (float) (($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']) / $ThisFileInfo['la']['uncompressed_size']);
				$ThisFileInfo['playtime_seconds']           = (float) ($ThisFileInfo['la']['samples'] / $ThisFileInfo['la']['sample_rate']) / $ThisFileInfo['la']['channels'];
				if ($ThisFileInfo['playtime_seconds'] == 0) {
					$ThisFileInfo['error'][] = 'Corrupt LA file: playtime_seconds == zero';
					return false;
				}

				$ThisFileInfo['audio']['bitrate']            = ($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']) * 8 / $ThisFileInfo['playtime_seconds'];
				//$ThisFileInfo['audio']['codec']              = $ThisFileInfo['la']['codec'];
				$ThisFileInfo['audio']['bits_per_sample']    = $ThisFileInfo['la']['bits_per_sample'];
				break;

			default:
				if (substr($rawdata, $offset, 2) == 'LA') {
					$ThisFileInfo['error'][] = 'This version of getID3() (v'.GETID3_VERSION.') doesn\'t support LA version '.substr($rawdata, $offset + 2, 1).'.'.substr($rawdata, $offset + 3, 1).' which this appears to be - check http://getid3.sourceforge.net for updates.';
				} else {
					$ThisFileInfo['error'][] = 'Not a LA (Lossless-Audio) file';
				}
				return false;
				break;
		}

		$ThisFileInfo['audio']['channels']    = $ThisFileInfo['la']['channels'];
		$ThisFileInfo['audio']['sample_rate'] = (int) $ThisFileInfo['la']['sample_rate'];
		$ThisFileInfo['audio']['encoder']     = 'LA v'.$ThisFileInfo['la']['version'];

		return true;
	}

}


?>