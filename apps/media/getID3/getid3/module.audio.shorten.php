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


class getid3_shorten
{

	function getid3_shorten(&$fd, &$ThisFileInfo) {

		fseek($fd, $ThisFileInfo['avdataoffset'], SEEK_SET);

		$ShortenHeader = fread($fd, 8);
		if (substr($ShortenHeader, 0, 4) != 'ajkg') {
			$ThisFileInfo['error'][] = 'Expecting "ajkg" at offset '.$ThisFileInfo['avdataoffset'].', found "'.substr($ShortenHeader, 0, 4).'"';
			return false;
		}
		$ThisFileInfo['fileformat']            = 'shn';
		$ThisFileInfo['audio']['dataformat']   = 'shn';
		$ThisFileInfo['audio']['lossless']     = true;
		$ThisFileInfo['audio']['bitrate_mode'] = 'vbr';

		$ThisFileInfo['shn']['version'] = getid3_lib::LittleEndian2Int(substr($ShortenHeader, 4, 1));

		fseek($fd, $ThisFileInfo['avdataend'] - 12, SEEK_SET);
		$SeekTableSignatureTest = fread($fd, 12);
		$ThisFileInfo['shn']['seektable']['present'] = (bool) (substr($SeekTableSignatureTest, 4, 8) == 'SHNAMPSK');
		if ($ThisFileInfo['shn']['seektable']['present']) {
			$ThisFileInfo['shn']['seektable']['length'] = getid3_lib::LittleEndian2Int(substr($SeekTableSignatureTest, 0, 4));
			$ThisFileInfo['shn']['seektable']['offset'] = $ThisFileInfo['avdataend'] - $ThisFileInfo['shn']['seektable']['length'];
			fseek($fd, $ThisFileInfo['shn']['seektable']['offset'], SEEK_SET);
			$SeekTableMagic = fread($fd, 4);
			if ($SeekTableMagic != 'SEEK') {

				$ThisFileInfo['error'][] = 'Expecting "SEEK" at offset '.$ThisFileInfo['shn']['seektable']['offset'].', found "'.$SeekTableMagic.'"';
				return false;

			} else {

				// typedef struct tag_TSeekEntry
				// {
				//   unsigned long SampleNumber;
				//   unsigned long SHNFileByteOffset;
				//   unsigned long SHNLastBufferReadPosition;
				//   unsigned short SHNByteGet;
				//   unsigned short SHNBufferOffset;
				//   unsigned short SHNFileBitOffset;
				//   unsigned long SHNGBuffer;
				//   unsigned short SHNBitShift;
				//   long CBuf0[3];
				//   long CBuf1[3];
				//   long Offset0[4];
				//   long Offset1[4];
				// }TSeekEntry;

				$SeekTableData = fread($fd, $ThisFileInfo['shn']['seektable']['length'] - 16);
				$ThisFileInfo['shn']['seektable']['entry_count'] = floor(strlen($SeekTableData) / 80);
				//$ThisFileInfo['shn']['seektable']['entries'] = array();
				//$SeekTableOffset = 0;
				//for ($i = 0; $i < $ThisFileInfo['shn']['seektable']['entry_count']; $i++) {
				//	$SeekTableEntry['sample_number'] = getid3_lib::LittleEndian2Int(substr($SeekTableData, $SeekTableOffset, 4));
				//	$SeekTableOffset += 4;
				//	$SeekTableEntry['shn_file_byte_offset'] = getid3_lib::LittleEndian2Int(substr($SeekTableData, $SeekTableOffset, 4));
				//	$SeekTableOffset += 4;
				//	$SeekTableEntry['shn_last_buffer_read_position'] = getid3_lib::LittleEndian2Int(substr($SeekTableData, $SeekTableOffset, 4));
				//	$SeekTableOffset += 4;
				//	$SeekTableEntry['shn_byte_get'] = getid3_lib::LittleEndian2Int(substr($SeekTableData, $SeekTableOffset, 2));
				//	$SeekTableOffset += 2;
				//	$SeekTableEntry['shn_buffer_offset'] = getid3_lib::LittleEndian2Int(substr($SeekTableData, $SeekTableOffset, 2));
				//	$SeekTableOffset += 2;
				//	$SeekTableEntry['shn_file_bit_offset'] = getid3_lib::LittleEndian2Int(substr($SeekTableData, $SeekTableOffset, 2));
				//	$SeekTableOffset += 2;
				//	$SeekTableEntry['shn_gbuffer'] = getid3_lib::LittleEndian2Int(substr($SeekTableData, $SeekTableOffset, 4));
				//	$SeekTableOffset += 4;
				//	$SeekTableEntry['shn_bit_shift'] = getid3_lib::LittleEndian2Int(substr($SeekTableData, $SeekTableOffset, 2));
				//	$SeekTableOffset += 2;
				//	for ($j = 0; $j < 3; $j++) {
				//		$SeekTableEntry['cbuf0'][$j] = getid3_lib::LittleEndian2Int(substr($SeekTableData, $SeekTableOffset, 4));
				//		$SeekTableOffset += 4;
				//	}
				//	for ($j = 0; $j < 3; $j++) {
				//		$SeekTableEntry['cbuf1'][$j] = getid3_lib::LittleEndian2Int(substr($SeekTableData, $SeekTableOffset, 4));
				//		$SeekTableOffset += 4;
				//	}
				//	for ($j = 0; $j < 4; $j++) {
				//		$SeekTableEntry['offset0'][$j] = getid3_lib::LittleEndian2Int(substr($SeekTableData, $SeekTableOffset, 4));
				//		$SeekTableOffset += 4;
				//	}
				//	for ($j = 0; $j < 4; $j++) {
				//		$SeekTableEntry['offset1'][$j] = getid3_lib::LittleEndian2Int(substr($SeekTableData, $SeekTableOffset, 4));
				//		$SeekTableOffset += 4;
				//	}
                //
				//	$ThisFileInfo['shn']['seektable']['entries'][] = $SeekTableEntry;
				//}

			}

		}

		if ((bool) ini_get('safe_mode')) {
			$ThisFileInfo['error'][] = 'PHP running in Safe Mode - backtick operator not available, cannot run shntool to analyze Shorten files';
			return false;
		}

		if (GETID3_OS_ISWINDOWS) {

			$RequiredFiles = array('shorten.exe', 'cygwin1.dll', 'head.exe');
			foreach ($RequiredFiles as $required_file) {
				if (!is_readable(GETID3_HELPERAPPSDIR.$required_file)) {
					$ThisFileInfo['error'][] = GETID3_HELPERAPPSDIR.$required_file.' does not exist';
					return false;
				}
			}
			$commandline = GETID3_HELPERAPPSDIR.'shorten.exe -x "'.$ThisFileInfo['filenamepath'].'" - | '.GETID3_HELPERAPPSDIR.'head.exe -c 64';
			$commandline = str_replace('/', '\\', $commandline);

		} else {

	        static $shorten_present;
	        if (!isset($shorten_present)) {
                $shorten_present = file_exists('/usr/local/bin/shorten') || `which shorten`;
            }
            if (!$shorten_present) {
                $ThisFileInfo['error'][] = 'shorten binary was not found in path or /usr/local/bin';
                return false;
            }
            $commandline = (file_exists('/usr/local/bin/shorten') ? '/usr/local/bin/' : '' ) . 'shorten -x '.escapeshellarg($ThisFileInfo['filenamepath']).' - | head -c 64';

		}

		$output = `$commandline`;

		if (!empty($output) && (substr($output, 12, 4) == 'fmt ')) {

			getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.audio-video.riff.php', __FILE__, true);

			$fmt_size = getid3_lib::LittleEndian2Int(substr($output, 16, 4));
			$DecodedWAVFORMATEX = getid3_riff::RIFFparseWAVEFORMATex(substr($output, 20, $fmt_size));
			$ThisFileInfo['audio']['channels']        = $DecodedWAVFORMATEX['channels'];
			$ThisFileInfo['audio']['bits_per_sample'] = $DecodedWAVFORMATEX['bits_per_sample'];
			$ThisFileInfo['audio']['sample_rate']     = $DecodedWAVFORMATEX['sample_rate'];

			if (substr($output, 20 + $fmt_size, 4) == 'data') {

				$ThisFileInfo['playtime_seconds'] = getid3_lib::LittleEndian2Int(substr($output, 20 + 4 + $fmt_size, 4)) / $DecodedWAVFORMATEX['raw']['nAvgBytesPerSec'];

			} else {

				$ThisFileInfo['error'][] = 'shorten failed to decode DATA chunk to expected location, cannot determine playtime';
				return false;

			}

			$ThisFileInfo['audio']['bitrate'] = (($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']) / $ThisFileInfo['playtime_seconds']) * 8;

		} else {

			$ThisFileInfo['error'][] = 'shorten failed to decode file to WAV for parsing';
			return false;

		}

		return true;
	}

}

?>