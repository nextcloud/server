<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.audio-video.swf.php                                  //
// module for analyzing Shockwave Flash files                  //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////


class getid3_swf
{

	function getid3_swf(&$fd, &$ThisFileInfo, $ReturnAllTagData=false) {
//$start_time = microtime(true);
		$ThisFileInfo['fileformat']          = 'swf';
		$ThisFileInfo['video']['dataformat'] = 'swf';

		// http://www.openswf.org/spec/SWFfileformat.html

		fseek($fd, $ThisFileInfo['avdataoffset'], SEEK_SET);

		$SWFfileData = fread($fd, $ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']); // 8 + 2 + 2 + max(9) bytes NOT including Frame_Size RECT data

		$ThisFileInfo['swf']['header']['signature']  = substr($SWFfileData, 0, 3);
		switch ($ThisFileInfo['swf']['header']['signature']) {
			case 'FWS':
				$ThisFileInfo['swf']['header']['compressed'] = false;
				break;

			case 'CWS':
				$ThisFileInfo['swf']['header']['compressed'] = true;
				break;

			default:
				$ThisFileInfo['error'][] = 'Expecting "FWS" or "CWS" at offset '.$ThisFileInfo['avdataoffset'].', found "'.$ThisFileInfo['swf']['header']['signature'].'"';
				unset($ThisFileInfo['swf']);
				unset($ThisFileInfo['fileformat']);
				return false;
				break;
		}
		$ThisFileInfo['swf']['header']['version'] = getid3_lib::LittleEndian2Int(substr($SWFfileData, 3, 1));
		$ThisFileInfo['swf']['header']['length']  = getid3_lib::LittleEndian2Int(substr($SWFfileData, 4, 4));

//echo __LINE__.'='.number_format(microtime(true) - $start_time, 3).'<br>';

		if ($ThisFileInfo['swf']['header']['compressed']) {

			$SWFHead     = substr($SWFfileData, 0, 8);
			$SWFfileData = substr($SWFfileData, 8);
			if ($decompressed = @gzuncompress($SWFfileData)) {

				$SWFfileData = $SWFHead.$decompressed;

			} else {

				$ThisFileInfo['error'][] = 'Error decompressing compressed SWF data ('.strlen($SWFfileData).' bytes compressed, should be '.($ThisFileInfo['swf']['header']['length'] - 8).' bytes uncompressed)';
				return false;

			}

		}
//echo __LINE__.'='.number_format(microtime(true) - $start_time, 3).'<br>';

		$FrameSizeBitsPerValue = (ord(substr($SWFfileData, 8, 1)) & 0xF8) >> 3;
		$FrameSizeDataLength   = ceil((5 + (4 * $FrameSizeBitsPerValue)) / 8);
		$FrameSizeDataString   = str_pad(decbin(ord(substr($SWFfileData, 8, 1)) & 0x07), 3, '0', STR_PAD_LEFT);
		for ($i = 1; $i < $FrameSizeDataLength; $i++) {
			$FrameSizeDataString .= str_pad(decbin(ord(substr($SWFfileData, 8 + $i, 1))), 8, '0', STR_PAD_LEFT);
		}
		list($X1, $X2, $Y1, $Y2) = explode("\n", wordwrap($FrameSizeDataString, $FrameSizeBitsPerValue, "\n", 1));
		$ThisFileInfo['swf']['header']['frame_width']  = getid3_lib::Bin2Dec($X2);
		$ThisFileInfo['swf']['header']['frame_height'] = getid3_lib::Bin2Dec($Y2);

		// http://www-lehre.informatik.uni-osnabrueck.de/~fbstark/diplom/docs/swf/Flash_Uncovered.htm
		// Next in the header is the frame rate, which is kind of weird.
		// It is supposed to be stored as a 16bit integer, but the first byte
		// (or last depending on how you look at it) is completely ignored.
		// Example: 0x000C  ->  0x0C  ->  12     So the frame rate is 12 fps.

		// Byte at (8 + $FrameSizeDataLength) is always zero and ignored
		$ThisFileInfo['swf']['header']['frame_rate']  = getid3_lib::LittleEndian2Int(substr($SWFfileData,  9 + $FrameSizeDataLength, 1));
		$ThisFileInfo['swf']['header']['frame_count'] = getid3_lib::LittleEndian2Int(substr($SWFfileData, 10 + $FrameSizeDataLength, 2));

		$ThisFileInfo['video']['frame_rate']         = $ThisFileInfo['swf']['header']['frame_rate'];
		$ThisFileInfo['video']['resolution_x']       = intval(round($ThisFileInfo['swf']['header']['frame_width']  / 20));
		$ThisFileInfo['video']['resolution_y']       = intval(round($ThisFileInfo['swf']['header']['frame_height'] / 20));
		$ThisFileInfo['video']['pixel_aspect_ratio'] = (float) 1;

		if (($ThisFileInfo['swf']['header']['frame_count'] > 0) && ($ThisFileInfo['swf']['header']['frame_rate'] > 0)) {
			$ThisFileInfo['playtime_seconds'] = $ThisFileInfo['swf']['header']['frame_count'] / $ThisFileInfo['swf']['header']['frame_rate'];
		}
//echo __LINE__.'='.number_format(microtime(true) - $start_time, 3).'<br>';


		// SWF tags

		$CurrentOffset = 12 + $FrameSizeDataLength;
		$SWFdataLength = strlen($SWFfileData);

		while ($CurrentOffset < $SWFdataLength) {
//echo __LINE__.'='.number_format(microtime(true) - $start_time, 3).'<br>';

			$TagIDTagLength = getid3_lib::LittleEndian2Int(substr($SWFfileData, $CurrentOffset, 2));
			$TagID     = ($TagIDTagLength & 0xFFFC) >> 6;
			$TagLength = ($TagIDTagLength & 0x003F);
			$CurrentOffset += 2;
			if ($TagLength == 0x3F) {
				$TagLength = getid3_lib::LittleEndian2Int(substr($SWFfileData, $CurrentOffset, 4));
				$CurrentOffset += 4;
			}

			unset($TagData);
			$TagData['offset'] = $CurrentOffset;
			$TagData['size']   = $TagLength;
			$TagData['id']     = $TagID;
			$TagData['data']   = substr($SWFfileData, $CurrentOffset, $TagLength);
			switch ($TagID) {
				case 0: // end of movie
					break 2;

				case 9: // Set background color
					//$ThisFileInfo['swf']['tags'][] = $TagData;
					$ThisFileInfo['swf']['bgcolor'] = strtoupper(str_pad(dechex(getid3_lib::BigEndian2Int($TagData['data'])), 6, '0', STR_PAD_LEFT));
					break;

				default:
					if ($ReturnAllTagData) {
						$ThisFileInfo['swf']['tags'][] = $TagData;
					}
					break;
			}

			$CurrentOffset += $TagLength;
		}

		return true;
	}

}


?>