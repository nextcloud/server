<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.audio.aa.php                                         //
// module for analyzing Audible Audiobook files                //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////


class getid3_aa extends getid3_handler
{

	function Analyze() {
		$info = &$this->getid3->info;

		fseek($this->getid3->fp, $info['avdataoffset'], SEEK_SET);
		$AAheader  = fread($this->getid3->fp, 8);

		$magic = "\x57\x90\x75\x36";
		if (substr($AAheader, 4, 4) != $magic) {
			$info['error'][] = 'Expecting "'.getid3_lib::PrintHexBytes($magic).'" at offset '.$info['avdataoffset'].', found "'.getid3_lib::PrintHexBytes(substr($AAheader, 4, 4)).'"';
			return false;
		}

		// shortcut
		$info['aa'] = array();
		$thisfile_au = &$info['aa'];

		$info['fileformat']            = 'aa';
		$info['audio']['dataformat']   = 'aa';
		$info['audio']['bitrate_mode'] = 'cbr'; // is it?
		$thisfile_au['encoding']       = 'ISO-8859-1';

		$thisfile_au['filesize'] = getid3_lib::BigEndian2Int(substr($AUheader,  0, 4));
		if ($thisfile_au['filesize'] > ($info['avdataend'] - $info['avdataoffset'])) {
			$info['warning'][] = 'Possible truncated file - expecting "'.$thisfile_au['filesize'].'" bytes of data, only found '.($info['avdataend'] - $info['avdataoffset']).' bytes"';
		}

		$info['audio']['bits_per_sample'] = 16; // is it?
		$info['audio']['sample_rate'] = $thisfile_au['sample_rate'];
		$info['audio']['channels']    = $thisfile_au['channels'];

		//$info['playtime_seconds'] = 0;
		//$info['audio']['bitrate'] = 0;

		return true;
	}

}


?>