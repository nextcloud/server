<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.audio.mod.php                                        //
// module for analyzing MOD Audio files                        //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////


class getid3_mod extends getid3_handler
{

	function Analyze() {
		$info = &$this->getid3->info;
		fseek($this->getid3->fp, $info['avdataoffset'], SEEK_SET);
		$fileheader = fread($this->getid3->fp, 1088);
		if (preg_match('#^IMPM#', $fileheader)) {
			return $this->getITheaderFilepointer();
		} elseif (preg_match('#^Extended Module#', $fileheader)) {
			return $this->getXMheaderFilepointer();
		} elseif (preg_match('#^.{44}SCRM#', $fileheader)) {
			return $this->getS3MheaderFilepointer();
		} elseif (preg_match('#^.{1080}(M\\.K\\.|M!K!|FLT4|FLT8|[5-9]CHN|[1-3][0-9]CH)#', $fileheader)) {
			return $this->getMODheaderFilepointer();
		}
		$info['error'][] = 'This is not a known type of MOD file';
		return false;
	}


	function getMODheaderFilepointer() {
		$info = &$this->getid3->info;
		fseek($this->getid3->fp, $info['avdataoffset'] + 1080);
		$FormatID = fread($this->getid3->fp, 4);
		if (!preg_match('#^(M.K.|[5-9]CHN|[1-3][0-9]CH)$#', $FormatID)) {
			$info['error'][] = 'This is not a known type of MOD file';
			return false;
		}

		$info['fileformat'] = 'mod';

		$info['error'][] = 'MOD parsing not enabled in this version of getID3() ['.$this->getid3->version().']';
		return false;
	}

	function getXMheaderFilepointer() {
		$info = &$this->getid3->info;
		fseek($this->getid3->fp, $info['avdataoffset']);
		$FormatID = fread($this->getid3->fp, 15);
		if (!preg_match('#^Extended Module$#', $FormatID)) {
			$info['error'][] = 'This is not a known type of XM-MOD file';
			return false;
		}

		$info['fileformat'] = 'xm';

		$info['error'][] = 'XM-MOD parsing not enabled in this version of getID3() ['.$this->getid3->version().']';
		return false;
	}

	function getS3MheaderFilepointer() {
		$info = &$this->getid3->info;
		fseek($this->getid3->fp, $info['avdataoffset'] + 44);
		$FormatID = fread($this->getid3->fp, 4);
		if (!preg_match('#^SCRM$#', $FormatID)) {
			$info['error'][] = 'This is not a ScreamTracker MOD file';
			return false;
		}

		$info['fileformat'] = 's3m';

		$info['error'][] = 'ScreamTracker parsing not enabled in this version of getID3() ['.$this->getid3->version().']';
		return false;
	}

	function getITheaderFilepointer() {
		$info = &$this->getid3->info;
		fseek($this->getid3->fp, $info['avdataoffset']);
		$FormatID = fread($this->getid3->fp, 4);
		if (!preg_match('#^IMPM$#', $FormatID)) {
			$info['error'][] = 'This is not an ImpulseTracker MOD file';
			return false;
		}

		$info['fileformat'] = 'it';

		$info['error'][] = 'ImpulseTracker parsing not enabled in this version of getID3() ['.$this->getid3->version().']';
		return false;
	}

}


?>