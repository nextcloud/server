<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.audio.au.php                                         //
// module for analyzing Digital Speech Standard (DSS) files    //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////


class getid3_dss
{

	function getid3_dss(&$fd, &$ThisFileInfo) {

		fseek($fd, $ThisFileInfo['avdataoffset'], SEEK_SET);
		$DSSheader  = fread($fd, 1256);

		if (substr($DSSheader, 0, 4) != "\x02".'dss') {
			$ThisFileInfo['error'][] = 'Expecting "[x02]dss" at offset '.$ThisFileInfo['avdataoffset'].', found "'.substr($DSSheader, 0, 4).'"';
			return false;
		}

		// some structure information taken from http://cpansearch.perl.org/src/RGIBSON/Audio-DSS-0.02/lib/Audio/DSS.pm

		// shortcut
		$ThisFileInfo['dss'] = array();
		$thisfile_dss        = &$ThisFileInfo['dss'];

		$ThisFileInfo['fileformat']            = 'dss';
		$ThisFileInfo['audio']['dataformat']   = 'dss';
		$ThisFileInfo['audio']['bitrate_mode'] = 'cbr';
		//$thisfile_dss['encoding']              = 'ISO-8859-1';

		$thisfile_dss['date_create']    = $this->DSSdateStringToUnixDate(substr($DSSheader,  38,  12));
		$thisfile_dss['date_complete']  = $this->DSSdateStringToUnixDate(substr($DSSheader,  50,  12));
		$thisfile_dss['length']         =                         intval(substr($DSSheader,  62,   6));
		$thisfile_dss['priority']       =                            ord(substr($DSSheader, 793,   1));
		$thisfile_dss['comments']       =                           trim(substr($DSSheader, 798, 100));


		//$ThisFileInfo['audio']['bits_per_sample']  = ?;
		//$ThisFileInfo['audio']['sample_rate']      = ?;
		$ThisFileInfo['audio']['channels']     = 1;

		$ThisFileInfo['playtime_seconds'] = $thisfile_dss['length'];
		$ThisFileInfo['audio']['bitrate'] = ($ThisFileInfo['filesize'] * 8) / $ThisFileInfo['playtime_seconds'];

		return true;
	}

	function DSSdateStringToUnixDate($datestring) {
		$y = substr($datestring,  0, 2);
		$m = substr($datestring,  2, 2);
		$d = substr($datestring,  4, 2);
		$h = substr($datestring,  6, 2);
		$i = substr($datestring,  8, 2);
		$s = substr($datestring, 10, 2);
		$y += (($y < 95) ? 2000 : 1900);
		return mktime($h, $i, $s, $m, $d, $y);
	}

}


?>