<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.archive.gzip.php                                     //
// module for analyzing GZIP files                             //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////
//                                                             //
// Module originally written by                                //
//      Mike Mozolin <teddybearØmail*ru>                       //
//                                                             //
/////////////////////////////////////////////////////////////////


class getid3_gzip {

	// public: Optional file list - disable for speed.
	var $option_gzip_parse_contents = false; // decode gzipped files, if possible, and parse recursively (.tar.gz for example)

	function getid3_gzip(&$fd, &$ThisFileInfo) {
		$ThisFileInfo['fileformat'] = 'gzip';

		$start_length = 10;
		$unpack_header = 'a1id1/a1id2/a1cmethod/a1flags/a4mtime/a1xflags/a1os';
		//+---+---+---+---+---+---+---+---+---+---+
		//|ID1|ID2|CM |FLG|     MTIME     |XFL|OS |
		//+---+---+---+---+---+---+---+---+---+---+
		@fseek($fd, 0);
		$buffer = @fread($fd, $ThisFileInfo['filesize']);

		$arr_members = explode("\x1F\x8B\x08", $buffer);
		while (true) {
			$is_wrong_members = false;
			$num_members = intval(count($arr_members));
			for ($i = 0; $i < $num_members; $i++) {
				if (strlen($arr_members[$i]) == 0) {
					continue;
				}
				$buf = "\x1F\x8B\x08".$arr_members[$i];

				$attr = unpack($unpack_header, substr($buf, 0, $start_length));
				if (!$this->get_os_type(ord($attr['os']))) {
					// Merge member with previous if wrong OS type
					$arr_members[$i - 1] .= $buf;
					$arr_members[$i] = '';
					$is_wrong_members = true;
					continue;
				}
			}
			if (!$is_wrong_members) {
				break;
			}
		}

		$ThisFileInfo['gzip']['files'] = array();

		$fpointer = 0;
		$idx = 0;
		for ($i = 0; $i < $num_members; $i++) {
			if (strlen($arr_members[$i]) == 0) {
				continue;
			}
			$thisThisFileInfo = &$ThisFileInfo['gzip']['member_header'][++$idx];

			$buff = "\x1F\x8B\x08".$arr_members[$i];

			$attr = unpack($unpack_header, substr($buff, 0, $start_length));
			$thisThisFileInfo['filemtime']      = getid3_lib::LittleEndian2Int($attr['mtime']);
			$thisThisFileInfo['raw']['id1']     = ord($attr['cmethod']);
			$thisThisFileInfo['raw']['id2']     = ord($attr['cmethod']);
			$thisThisFileInfo['raw']['cmethod'] = ord($attr['cmethod']);
			$thisThisFileInfo['raw']['os']      = ord($attr['os']);
			$thisThisFileInfo['raw']['xflags']  = ord($attr['xflags']);
			$thisThisFileInfo['raw']['flags']   = ord($attr['flags']);

			$thisThisFileInfo['flags']['crc16']    = (bool) ($thisThisFileInfo['raw']['flags'] & 0x02);
			$thisThisFileInfo['flags']['extra']    = (bool) ($thisThisFileInfo['raw']['flags'] & 0x04);
			$thisThisFileInfo['flags']['filename'] = (bool) ($thisThisFileInfo['raw']['flags'] & 0x08);
			$thisThisFileInfo['flags']['comment']  = (bool) ($thisThisFileInfo['raw']['flags'] & 0x10);

			$thisThisFileInfo['compression'] = $this->get_xflag_type($thisThisFileInfo['raw']['xflags']);

			$thisThisFileInfo['os'] = $this->get_os_type($thisThisFileInfo['raw']['os']);
			if (!$thisThisFileInfo['os']) {
				$ThisFileInfo['error'][] = 'Read error on gzip file';
				return false;
			}

			$fpointer = 10;
			$arr_xsubfield = array();
			// bit 2 - FLG.FEXTRA
			//+---+---+=================================+
			//| XLEN  |...XLEN bytes of "extra field"...|
			//+---+---+=================================+
			if ($thisThisFileInfo['flags']['extra']) {
				$w_xlen = substr($buff, $fpointer, 2);
				$xlen = getid3_lib::LittleEndian2Int($w_xlen);
				$fpointer += 2;

				$thisThisFileInfo['raw']['xfield'] = substr($buff, $fpointer, $xlen);
				// Extra SubFields
				//+---+---+---+---+==================================+
				//|SI1|SI2|  LEN  |... LEN bytes of subfield data ...|
				//+---+---+---+---+==================================+
				$idx = 0;
				while (true) {
					if ($idx >= $xlen) {
						break;
					}
					$si1 = ord(substr($buff, $fpointer + $idx++, 1));
					$si2 = ord(substr($buff, $fpointer + $idx++, 1));
					if (($si1 == 0x41) && ($si2 == 0x70)) {
						$w_xsublen = substr($buff, $fpointer + $idx, 2);
						$xsublen = getid3_lib::LittleEndian2Int($w_xsublen);
						$idx += 2;
						$arr_xsubfield[] = substr($buff, $fpointer + $idx, $xsublen);
						$idx += $xsublen;
					} else {
						break;
					}
				}
				$fpointer += $xlen;
			}
			// bit 3 - FLG.FNAME
			//+=========================================+
			//|...original file name, zero-terminated...|
			//+=========================================+
			// GZIP files may have only one file, with no filename, so assume original filename is current filename without .gz
			$thisThisFileInfo['filename'] = eregi_replace('.gz$', '', $ThisFileInfo['filename']);
			if ($thisThisFileInfo['flags']['filename']) {
				while (true) {
					if (ord($buff[$fpointer]) == 0) {
						$fpointer++;
						break;
					}
					$thisThisFileInfo['filename'] .= $buff[$fpointer];
					$fpointer++;
				}
			}
			// bit 4 - FLG.FCOMMENT
			//+===================================+
			//|...file comment, zero-terminated...|
			//+===================================+
			if ($thisThisFileInfo['flags']['comment']) {
				while (true) {
					if (ord($buff[$fpointer]) == 0) {
						$fpointer++;
						break;
					}
					$thisThisFileInfo['comment'] .= $buff[$fpointer];
					$fpointer++;
				}
			}
			// bit 1 - FLG.FHCRC
			//+---+---+
			//| CRC16 |
			//+---+---+
			if ($thisThisFileInfo['flags']['crc16']) {
				$w_crc = substr($buff, $fpointer, 2);
				$thisThisFileInfo['crc16'] = getid3_lib::LittleEndian2Int($w_crc);
				$fpointer += 2;
			}
			// bit 0 - FLG.FTEXT
			//if ($thisThisFileInfo['raw']['flags'] & 0x01) {
			//	Ignored...
			//}
			// bits 5, 6, 7 - reserved

			$thisThisFileInfo['crc32']    = getid3_lib::LittleEndian2Int(substr($buff, strlen($buff) - 8, 4));
			$thisThisFileInfo['filesize'] = getid3_lib::LittleEndian2Int(substr($buff, strlen($buff) - 4));

			$ThisFileInfo['gzip']['files'] = getid3_lib::array_merge_clobber($ThisFileInfo['gzip']['files'], getid3_lib::CreateDeepArray($thisThisFileInfo['filename'], '/', $thisThisFileInfo['filesize']));

			if ($this->option_gzip_parse_contents) {
				// Try to inflate GZip
				$csize = 0;
				$inflated = '';
				$chkcrc32 = '';
				if (function_exists('gzinflate')) {
					$cdata = substr($buff, $fpointer);
					$cdata = substr($cdata, 0, strlen($cdata) - 8);
					$csize = strlen($cdata);
					$inflated = gzinflate($cdata);

					// Calculate CRC32 for inflated content
					$thisThisFileInfo['crc32_valid'] = (bool) (sprintf('%u', crc32($inflated)) == $thisThisFileInfo['crc32']);

					// determine format
					$formattest = substr($inflated, 0, 32774);
					$newgetID3 = new getID3();
					$determined_format = $newgetID3->GetFileFormat($formattest);
					unset($newgetID3);

	        		// file format is determined
	        		switch (@$determined_format['module']) {
	        			case 'tar':
							// view TAR-file info
							if (file_exists(GETID3_INCLUDEPATH.$determined_format['include']) && @include_once(GETID3_INCLUDEPATH.$determined_format['include'])) {
								if (($temp_tar_filename = tempnam('*', 'getID3')) === false) {
									// can't find anywhere to create a temp file, abort
									$ThisFileInfo['error'][] = 'Unable to create temp file to parse TAR inside GZIP file';
									break;
								}
								if ($fp_temp_tar = fopen($temp_tar_filename, 'w+b')) {
									fwrite($fp_temp_tar, $inflated);
									rewind($fp_temp_tar);
									$getid3_tar = new getid3_tar($fp_temp_tar, $dummy);
									$ThisFileInfo['gzip']['member_header'][$idx]['tar'] = $dummy['tar'];
									unset($dummy);
									unset($getid3_tar);
									fclose($fp_temp_tar);
									unlink($temp_tar_filename);
								} else {
									$ThisFileInfo['error'][] = 'Unable to fopen() temp file to parse TAR inside GZIP file';
									break;
								}
							}
							break;

	        			case '':
	        			default:
	        				// unknown or unhandled format
	        				break;
					}
				}
			}
		}
		return true;
	}

	// Converts the OS type
	function get_os_type($key) {
		static $os_type = array(
			'0'   => 'FAT filesystem (MS-DOS, OS/2, NT/Win32)',
			'1'   => 'Amiga',
			'2'   => 'VMS (or OpenVMS)',
			'3'   => 'Unix',
			'4'   => 'VM/CMS',
			'5'   => 'Atari TOS',
			'6'   => 'HPFS filesystem (OS/2, NT)',
			'7'   => 'Macintosh',
			'8'   => 'Z-System',
			'9'   => 'CP/M',
			'10'  => 'TOPS-20',
			'11'  => 'NTFS filesystem (NT)',
			'12'  => 'QDOS',
			'13'  => 'Acorn RISCOS',
			'255' => 'unknown'
		);
		return @$os_type[$key];
	}

	// Converts the eXtra FLags
	function get_xflag_type($key) {
		static $xflag_type = array(
			'0' => 'unknown',
			'2' => 'maximum compression',
			'4' => 'fastest algorithm'
		);
		return @$xflag_type[$key];
	}
}

?>
