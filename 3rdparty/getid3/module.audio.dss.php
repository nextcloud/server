<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.audio.dss.php                                        //
// module for analyzing Digital Speech Standard (DSS) files    //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////


class getid3_dss extends getid3_handler
{

	function Analyze() {
		$info = &$this->getid3->info;

		fseek($this->getid3->fp, $info['avdataoffset'], SEEK_SET);
		$DSSheader  = fread($this->getid3->fp, 1256);

		if (!preg_match('#^(\x02|\x03)dss#', $DSSheader)) {
			$info['error'][] = 'Expecting "[02-03] 64 73 73" at offset '.$info['avdataoffset'].', found "'.getid3_lib::PrintHexBytes(substr($DSSheader, 0, 4)).'"';
			return false;
		}

		// some structure information taken from http://cpansearch.perl.org/src/RGIBSON/Audio-DSS-0.02/lib/Audio/DSS.pm

		// shortcut
		$info['dss'] = array();
		$thisfile_dss        = &$info['dss'];

		$info['fileformat']            = 'dss';
		$info['audio']['dataformat']   = 'dss';
		$info['audio']['bitrate_mode'] = 'cbr';
		//$thisfile_dss['encoding']              = 'ISO-8859-1';

		$thisfile_dss['version']        =                            ord(substr($DSSheader,   0,   1));
		$thisfile_dss['date_create']    = $this->DSSdateStringToUnixDate(substr($DSSheader,  38,  12));
		$thisfile_dss['date_complete']  = $this->DSSdateStringToUnixDate(substr($DSSheader,  50,  12));
		//$thisfile_dss['length']         =                         intval(substr($DSSheader,  62,   6)); // I thought time was in seconds, it's actually HHMMSS
		$thisfile_dss['length']         = intval((substr($DSSheader,  62, 2) * 3600) + (substr($DSSheader,  64, 2) * 60) + substr($DSSheader,  66, 2));
		$thisfile_dss['priority']       =                            ord(substr($DSSheader, 793,   1));
		$thisfile_dss['comments']       =                           trim(substr($DSSheader, 798, 100));


		//$info['audio']['bits_per_sample']  = ?;
		//$info['audio']['sample_rate']      = ?;
		$info['audio']['channels']     = 1;

		$info['playtime_seconds'] = $thisfile_dss['length'];
		$info['audio']['bitrate'] = ($info['filesize'] * 8) / $info['playtime_seconds'];

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