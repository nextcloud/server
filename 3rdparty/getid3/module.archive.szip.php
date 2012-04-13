<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.archive.szip.php                                     //
// module for analyzing SZIP compressed files                  //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////


class getid3_szip extends getid3_handler
{

	function Analyze() {
		$info = &$this->getid3->info;

		fseek($this->getid3->fp, $info['avdataoffset'], SEEK_SET);
		$SZIPHeader = fread($this->getid3->fp, 6);
		if (substr($SZIPHeader, 0, 4) != "SZ\x0A\x04") {
			$info['error'][] = 'Expecting "53 5A 0A 04" at offset '.$info['avdataoffset'].', found "'.getid3_lib::PrintHexBytes(substr($SZIPHeader, 0, 4)).'"';
			return false;
		}
		$info['fileformat']            = 'szip';
		$info['szip']['major_version'] = getid3_lib::BigEndian2Int(substr($SZIPHeader, 4, 1));
		$info['szip']['minor_version'] = getid3_lib::BigEndian2Int(substr($SZIPHeader, 5, 1));

		while (!feof($this->getid3->fp)) {
			$NextBlockID = fread($this->getid3->fp, 2);
			switch ($NextBlockID) {
				case 'SZ':
					// Note that szip files can be concatenated, this has the same effect as
					// concatenating the files. this also means that global header blocks
					// might be present between directory/data blocks.
					fseek($this->getid3->fp, 4, SEEK_CUR);
					break;

				case 'BH':
					$BHheaderbytes  = getid3_lib::BigEndian2Int(fread($this->getid3->fp, 3));
					$BHheaderdata   = fread($this->getid3->fp, $BHheaderbytes);
					$BHheaderoffset = 0;
					while (strpos($BHheaderdata, "\x00", $BHheaderoffset) > 0) {
						//filename as \0 terminated string  (empty string indicates end)
						//owner as \0 terminated string (empty is same as last file)
						//group as \0 terminated string (empty is same as last file)
						//3 byte filelength in this block
						//2 byte access flags
						//4 byte creation time (like in unix)
						//4 byte modification time (like in unix)
						//4 byte access time (like in unix)

						$BHdataArray['filename'] = substr($BHheaderdata, $BHheaderoffset, strcspn($BHheaderdata, "\x00"));
						$BHheaderoffset += (strlen($BHdataArray['filename']) + 1);

						$BHdataArray['owner'] = substr($BHheaderdata, $BHheaderoffset, strcspn($BHheaderdata, "\x00"));
						$BHheaderoffset += (strlen($BHdataArray['owner']) + 1);

						$BHdataArray['group'] = substr($BHheaderdata, $BHheaderoffset, strcspn($BHheaderdata, "\x00"));
						$BHheaderoffset += (strlen($BHdataArray['group']) + 1);

						$BHdataArray['filelength'] = getid3_lib::BigEndian2Int(substr($BHheaderdata, $BHheaderoffset, 3));
						$BHheaderoffset += 3;

						$BHdataArray['access_flags'] = getid3_lib::BigEndian2Int(substr($BHheaderdata, $BHheaderoffset, 2));
						$BHheaderoffset += 2;

						$BHdataArray['creation_time'] = getid3_lib::BigEndian2Int(substr($BHheaderdata, $BHheaderoffset, 4));
						$BHheaderoffset += 4;

						$BHdataArray['modification_time'] = getid3_lib::BigEndian2Int(substr($BHheaderdata, $BHheaderoffset, 4));
						$BHheaderoffset += 4;

						$BHdataArray['access_time'] = getid3_lib::BigEndian2Int(substr($BHheaderdata, $BHheaderoffset, 4));
						$BHheaderoffset += 4;

						$info['szip']['BH'][] = $BHdataArray;
					}
					break;

				default:
					break 2;
			}
		}

		return true;

	}

}

?>