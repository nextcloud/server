<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.audio.bink.php                                       //
// module for analyzing Bink or Smacker audio-video files      //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////


class getid3_bink
{

	function getid3_bink(&$fd, &$ThisFileInfo) {

$ThisFileInfo['error'][] = 'Bink / Smacker files not properly processed by this version of getID3()';

		fseek($fd, $ThisFileInfo['avdataoffset'], SEEK_SET);
		$fileTypeID = fread($fd, 3);
		switch ($fileTypeID) {
			case 'BIK':
				return $this->ParseBink($fd, $ThisFileInfo);
				break;

			case 'SMK':
				return $this->ParseSmacker($fd, $ThisFileInfo);
				break;

			default:
				$ThisFileInfo['error'][] = 'Expecting "BIK" or "SMK" at offset '.$ThisFileInfo['avdataoffset'].', found "'.$fileTypeID.'"';
				return false;
				break;
		}

		return true;

	}

	function ParseBink(&$fd, &$ThisFileInfo) {
		$ThisFileInfo['fileformat']          = 'bink';
		$ThisFileInfo['video']['dataformat'] = 'bink';

		$fileData = 'BIK'.fread($fd, 13);

		$ThisFileInfo['bink']['data_size']   = getid3_lib::LittleEndian2Int(substr($fileData, 4, 4));
		$ThisFileInfo['bink']['frame_count'] = getid3_lib::LittleEndian2Int(substr($fileData, 8, 2));

		if (($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']) != ($ThisFileInfo['bink']['data_size'] + 8)) {
			$ThisFileInfo['error'][] = 'Probably truncated file: expecting '.$ThisFileInfo['bink']['data_size'].' bytes, found '.($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']);
		}

		return true;
	}

	function ParseSmacker(&$fd, &$ThisFileInfo) {
		$ThisFileInfo['fileformat']          = 'smacker';
		$ThisFileInfo['video']['dataformat'] = 'smacker';

		return false;
	}

}

?>