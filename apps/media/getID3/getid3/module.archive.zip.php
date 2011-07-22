<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.archive.zip.php                                      //
// module for analyzing pkZip files                            //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////


class getid3_zip
{

	function getid3_zip(&$fd, &$ThisFileInfo) {

		$ThisFileInfo['fileformat']      = 'zip';
		$ThisFileInfo['zip']['encoding'] = 'ISO-8859-1';
		$ThisFileInfo['zip']['files']    = array();

		$ThisFileInfo['zip']['compressed_size']   = 0;
		$ThisFileInfo['zip']['uncompressed_size'] = 0;
		$ThisFileInfo['zip']['entries_count']     = 0;

		if ($ThisFileInfo['filesize'] < pow(2, 31)) {
			$EOCDsearchData    = '';
			$EOCDsearchCounter = 0;
			while ($EOCDsearchCounter++ < 512) {

				fseek($fd, -128 * $EOCDsearchCounter, SEEK_END);
				$EOCDsearchData = fread($fd, 128).$EOCDsearchData;

				if (strstr($EOCDsearchData, 'PK'."\x05\x06")) {

					$EOCDposition = strpos($EOCDsearchData, 'PK'."\x05\x06");
					fseek($fd, (-128 * $EOCDsearchCounter) + $EOCDposition, SEEK_END);
					$ThisFileInfo['zip']['end_central_directory'] = $this->ZIPparseEndOfCentralDirectory($fd);

					fseek($fd, $ThisFileInfo['zip']['end_central_directory']['directory_offset'], SEEK_SET);
					$ThisFileInfo['zip']['entries_count'] = 0;
					while ($centraldirectoryentry = $this->ZIPparseCentralDirectory($fd)) {
						$ThisFileInfo['zip']['central_directory'][] = $centraldirectoryentry;
						$ThisFileInfo['zip']['entries_count']++;
						$ThisFileInfo['zip']['compressed_size']   += $centraldirectoryentry['compressed_size'];
						$ThisFileInfo['zip']['uncompressed_size'] += $centraldirectoryentry['uncompressed_size'];

						if ($centraldirectoryentry['uncompressed_size'] > 0) {
							$ThisFileInfo['zip']['files'] = getid3_lib::array_merge_clobber($ThisFileInfo['zip']['files'], getid3_lib::CreateDeepArray($centraldirectoryentry['filename'], '/', $centraldirectoryentry['uncompressed_size']));
						}
					}

					if ($ThisFileInfo['zip']['entries_count'] == 0) {
						$ThisFileInfo['error'][] = 'No Central Directory entries found (truncated file?)';
						return false;
					}

					if (!empty($ThisFileInfo['zip']['end_central_directory']['comment'])) {
						$ThisFileInfo['zip']['comments']['comment'][] = $ThisFileInfo['zip']['end_central_directory']['comment'];
					}

					if (isset($ThisFileInfo['zip']['central_directory'][0]['compression_method'])) {
						$ThisFileInfo['zip']['compression_method'] = $ThisFileInfo['zip']['central_directory'][0]['compression_method'];
					}
					if (isset($ThisFileInfo['zip']['central_directory'][0]['flags']['compression_speed'])) {
						$ThisFileInfo['zip']['compression_speed']  = $ThisFileInfo['zip']['central_directory'][0]['flags']['compression_speed'];
					}
					if (isset($ThisFileInfo['zip']['compression_method']) && ($ThisFileInfo['zip']['compression_method'] == 'store') && !isset($ThisFileInfo['zip']['compression_speed'])) {
						$ThisFileInfo['zip']['compression_speed']  = 'store';
					}

					return true;

				}
			}
		}

		if ($this->getZIPentriesFilepointer($fd, $ThisFileInfo)) {

			// central directory couldn't be found and/or parsed
			// scan through actual file data entries, recover as much as possible from probable trucated file
			if ($ThisFileInfo['zip']['compressed_size'] > ($ThisFileInfo['filesize'] - 46 - 22)) {
				$ThisFileInfo['error'][] = 'Warning: Truncated file! - Total compressed file sizes ('.$ThisFileInfo['zip']['compressed_size'].' bytes) is greater than filesize minus Central Directory and End Of Central Directory structures ('.($ThisFileInfo['filesize'] - 46 - 22).' bytes)';
			}
			$ThisFileInfo['error'][] = 'Cannot find End Of Central Directory - returned list of files in [zip][entries] array may not be complete';
			foreach ($ThisFileInfo['zip']['entries'] as $key => $valuearray) {
				$ThisFileInfo['zip']['files'][$valuearray['filename']] = $valuearray['uncompressed_size'];
			}
			return true;

		} else {

			unset($ThisFileInfo['zip']);
			$ThisFileInfo['fileformat'] = '';
			$ThisFileInfo['error'][] = 'Cannot find End Of Central Directory (truncated file?)';
			return false;

		}
	}


	function getZIPHeaderFilepointerTopDown(&$fd, &$ThisFileInfo) {
		$ThisFileInfo['fileformat'] = 'zip';

		$ThisFileInfo['zip']['compressed_size']   = 0;
		$ThisFileInfo['zip']['uncompressed_size'] = 0;
		$ThisFileInfo['zip']['entries_count']     = 0;

		rewind($fd);
		while ($fileentry = $this->ZIPparseLocalFileHeader($fd)) {
			$ThisFileInfo['zip']['entries'][] = $fileentry;
			$ThisFileInfo['zip']['entries_count']++;
		}
		if ($ThisFileInfo['zip']['entries_count'] == 0) {
			$ThisFileInfo['error'][] = 'No Local File Header entries found';
			return false;
		}

		$ThisFileInfo['zip']['entries_count']     = 0;
		while ($centraldirectoryentry = $this->ZIPparseCentralDirectory($fd)) {
			$ThisFileInfo['zip']['central_directory'][] = $centraldirectoryentry;
			$ThisFileInfo['zip']['entries_count']++;
			$ThisFileInfo['zip']['compressed_size']   += $centraldirectoryentry['compressed_size'];
			$ThisFileInfo['zip']['uncompressed_size'] += $centraldirectoryentry['uncompressed_size'];
		}
		if ($ThisFileInfo['zip']['entries_count'] == 0) {
			$ThisFileInfo['error'][] = 'No Central Directory entries found (truncated file?)';
			return false;
		}

		if ($EOCD = $this->ZIPparseEndOfCentralDirectory($fd)) {
			$ThisFileInfo['zip']['end_central_directory'] = $EOCD;
		} else {
			$ThisFileInfo['error'][] = 'No End Of Central Directory entry found (truncated file?)';
			return false;
		}

		if (!empty($ThisFileInfo['zip']['end_central_directory']['comment'])) {
			$ThisFileInfo['zip']['comments']['comment'][] = $ThisFileInfo['zip']['end_central_directory']['comment'];
		}

		return true;
	}


	function getZIPentriesFilepointer(&$fd, &$ThisFileInfo) {
		$ThisFileInfo['zip']['compressed_size']   = 0;
		$ThisFileInfo['zip']['uncompressed_size'] = 0;
		$ThisFileInfo['zip']['entries_count']     = 0;

		rewind($fd);
		while ($fileentry = $this->ZIPparseLocalFileHeader($fd)) {
			$ThisFileInfo['zip']['entries'][] = $fileentry;
			$ThisFileInfo['zip']['entries_count']++;
			$ThisFileInfo['zip']['compressed_size']   += $fileentry['compressed_size'];
			$ThisFileInfo['zip']['uncompressed_size'] += $fileentry['uncompressed_size'];
		}
		if ($ThisFileInfo['zip']['entries_count'] == 0) {
			$ThisFileInfo['error'][] = 'No Local File Header entries found';
			return false;
		}

		return true;
	}


	function ZIPparseLocalFileHeader(&$fd) {
		$LocalFileHeader['offset'] = ftell($fd);

		$ZIPlocalFileHeader = fread($fd, 30);

		$LocalFileHeader['raw']['signature']          = getid3_lib::LittleEndian2Int(substr($ZIPlocalFileHeader,  0, 4));
		if ($LocalFileHeader['raw']['signature'] != 0x04034B50) {
			// invalid Local File Header Signature
			fseek($fd, $LocalFileHeader['offset'], SEEK_SET); // seek back to where filepointer originally was so it can be handled properly
			return false;
		}
		$LocalFileHeader['raw']['extract_version']    = getid3_lib::LittleEndian2Int(substr($ZIPlocalFileHeader,  4, 2));
		$LocalFileHeader['raw']['general_flags']      = getid3_lib::LittleEndian2Int(substr($ZIPlocalFileHeader,  6, 2));
		$LocalFileHeader['raw']['compression_method'] = getid3_lib::LittleEndian2Int(substr($ZIPlocalFileHeader,  8, 2));
		$LocalFileHeader['raw']['last_mod_file_time'] = getid3_lib::LittleEndian2Int(substr($ZIPlocalFileHeader, 10, 2));
		$LocalFileHeader['raw']['last_mod_file_date'] = getid3_lib::LittleEndian2Int(substr($ZIPlocalFileHeader, 12, 2));
		$LocalFileHeader['raw']['crc_32']             = getid3_lib::LittleEndian2Int(substr($ZIPlocalFileHeader, 14, 4));
		$LocalFileHeader['raw']['compressed_size']    = getid3_lib::LittleEndian2Int(substr($ZIPlocalFileHeader, 18, 4));
		$LocalFileHeader['raw']['uncompressed_size']  = getid3_lib::LittleEndian2Int(substr($ZIPlocalFileHeader, 22, 4));
		$LocalFileHeader['raw']['filename_length']    = getid3_lib::LittleEndian2Int(substr($ZIPlocalFileHeader, 26, 2));
		$LocalFileHeader['raw']['extra_field_length'] = getid3_lib::LittleEndian2Int(substr($ZIPlocalFileHeader, 28, 2));

		$LocalFileHeader['extract_version']           = sprintf('%1.1f', $LocalFileHeader['raw']['extract_version'] / 10);
		$LocalFileHeader['host_os']                   = $this->ZIPversionOSLookup(($LocalFileHeader['raw']['extract_version'] & 0xFF00) >> 8);
		$LocalFileHeader['compression_method']        = $this->ZIPcompressionMethodLookup($LocalFileHeader['raw']['compression_method']);
		$LocalFileHeader['compressed_size']           = $LocalFileHeader['raw']['compressed_size'];
		$LocalFileHeader['uncompressed_size']         = $LocalFileHeader['raw']['uncompressed_size'];
		$LocalFileHeader['flags']                     = $this->ZIPparseGeneralPurposeFlags($LocalFileHeader['raw']['general_flags'], $LocalFileHeader['raw']['compression_method']);
		$LocalFileHeader['last_modified_timestamp']   = $this->DOStime2UNIXtime($LocalFileHeader['raw']['last_mod_file_date'], $LocalFileHeader['raw']['last_mod_file_time']);

		$FilenameExtrafieldLength = $LocalFileHeader['raw']['filename_length'] + $LocalFileHeader['raw']['extra_field_length'];
		if ($FilenameExtrafieldLength > 0) {
			$ZIPlocalFileHeader .= fread($fd, $FilenameExtrafieldLength);

			if ($LocalFileHeader['raw']['filename_length'] > 0) {
				$LocalFileHeader['filename']                = substr($ZIPlocalFileHeader, 30, $LocalFileHeader['raw']['filename_length']);
			}
			if ($LocalFileHeader['raw']['extra_field_length'] > 0) {
				$LocalFileHeader['raw']['extra_field_data'] = substr($ZIPlocalFileHeader, 30 + $LocalFileHeader['raw']['filename_length'], $LocalFileHeader['raw']['extra_field_length']);
			}
		}

		$LocalFileHeader['data_offset'] = ftell($fd);
		//$LocalFileHeader['compressed_data'] = fread($fd, $LocalFileHeader['raw']['compressed_size']);
		fseek($fd, $LocalFileHeader['raw']['compressed_size'], SEEK_CUR);

		if ($LocalFileHeader['flags']['data_descriptor_used']) {
			$DataDescriptor = fread($fd, 12);
			$LocalFileHeader['data_descriptor']['crc_32']            = getid3_lib::LittleEndian2Int(substr($DataDescriptor,  0, 4));
			$LocalFileHeader['data_descriptor']['compressed_size']   = getid3_lib::LittleEndian2Int(substr($DataDescriptor,  4, 4));
			$LocalFileHeader['data_descriptor']['uncompressed_size'] = getid3_lib::LittleEndian2Int(substr($DataDescriptor,  8, 4));
		}

		return $LocalFileHeader;
	}


	function ZIPparseCentralDirectory(&$fd) {
		$CentralDirectory['offset'] = ftell($fd);

		$ZIPcentralDirectory = fread($fd, 46);

		$CentralDirectory['raw']['signature']            = getid3_lib::LittleEndian2Int(substr($ZIPcentralDirectory,  0, 4));
		if ($CentralDirectory['raw']['signature'] != 0x02014B50) {
			// invalid Central Directory Signature
			fseek($fd, $CentralDirectory['offset'], SEEK_SET); // seek back to where filepointer originally was so it can be handled properly
			return false;
		}
		$CentralDirectory['raw']['create_version']       = getid3_lib::LittleEndian2Int(substr($ZIPcentralDirectory,  4, 2));
		$CentralDirectory['raw']['extract_version']      = getid3_lib::LittleEndian2Int(substr($ZIPcentralDirectory,  6, 2));
		$CentralDirectory['raw']['general_flags']        = getid3_lib::LittleEndian2Int(substr($ZIPcentralDirectory,  8, 2));
		$CentralDirectory['raw']['compression_method']   = getid3_lib::LittleEndian2Int(substr($ZIPcentralDirectory, 10, 2));
		$CentralDirectory['raw']['last_mod_file_time']   = getid3_lib::LittleEndian2Int(substr($ZIPcentralDirectory, 12, 2));
		$CentralDirectory['raw']['last_mod_file_date']   = getid3_lib::LittleEndian2Int(substr($ZIPcentralDirectory, 14, 2));
		$CentralDirectory['raw']['crc_32']               = getid3_lib::LittleEndian2Int(substr($ZIPcentralDirectory, 16, 4));
		$CentralDirectory['raw']['compressed_size']      = getid3_lib::LittleEndian2Int(substr($ZIPcentralDirectory, 20, 4));
		$CentralDirectory['raw']['uncompressed_size']    = getid3_lib::LittleEndian2Int(substr($ZIPcentralDirectory, 24, 4));
		$CentralDirectory['raw']['filename_length']      = getid3_lib::LittleEndian2Int(substr($ZIPcentralDirectory, 28, 2));
		$CentralDirectory['raw']['extra_field_length']   = getid3_lib::LittleEndian2Int(substr($ZIPcentralDirectory, 30, 2));
		$CentralDirectory['raw']['file_comment_length']  = getid3_lib::LittleEndian2Int(substr($ZIPcentralDirectory, 32, 2));
		$CentralDirectory['raw']['disk_number_start']    = getid3_lib::LittleEndian2Int(substr($ZIPcentralDirectory, 34, 2));
		$CentralDirectory['raw']['internal_file_attrib'] = getid3_lib::LittleEndian2Int(substr($ZIPcentralDirectory, 36, 2));
		$CentralDirectory['raw']['external_file_attrib'] = getid3_lib::LittleEndian2Int(substr($ZIPcentralDirectory, 38, 4));
		$CentralDirectory['raw']['local_header_offset']  = getid3_lib::LittleEndian2Int(substr($ZIPcentralDirectory, 42, 4));

		$CentralDirectory['entry_offset']              = $CentralDirectory['raw']['local_header_offset'];
		$CentralDirectory['create_version']            = sprintf('%1.1f', $CentralDirectory['raw']['create_version'] / 10);
		$CentralDirectory['extract_version']           = sprintf('%1.1f', $CentralDirectory['raw']['extract_version'] / 10);
		$CentralDirectory['host_os']                   = $this->ZIPversionOSLookup(($CentralDirectory['raw']['extract_version'] & 0xFF00) >> 8);
		$CentralDirectory['compression_method']        = $this->ZIPcompressionMethodLookup($CentralDirectory['raw']['compression_method']);
		$CentralDirectory['compressed_size']           = $CentralDirectory['raw']['compressed_size'];
		$CentralDirectory['uncompressed_size']         = $CentralDirectory['raw']['uncompressed_size'];
		$CentralDirectory['flags']                     = $this->ZIPparseGeneralPurposeFlags($CentralDirectory['raw']['general_flags'], $CentralDirectory['raw']['compression_method']);
		$CentralDirectory['last_modified_timestamp']   = $this->DOStime2UNIXtime($CentralDirectory['raw']['last_mod_file_date'], $CentralDirectory['raw']['last_mod_file_time']);

		$FilenameExtrafieldCommentLength = $CentralDirectory['raw']['filename_length'] + $CentralDirectory['raw']['extra_field_length'] + $CentralDirectory['raw']['file_comment_length'];
		if ($FilenameExtrafieldCommentLength > 0) {
			$FilenameExtrafieldComment = fread($fd, $FilenameExtrafieldCommentLength);

			if ($CentralDirectory['raw']['filename_length'] > 0) {
				$CentralDirectory['filename']                  = substr($FilenameExtrafieldComment, 0, $CentralDirectory['raw']['filename_length']);
			}
			if ($CentralDirectory['raw']['extra_field_length'] > 0) {
				$CentralDirectory['raw']['extra_field_data']   = substr($FilenameExtrafieldComment, $CentralDirectory['raw']['filename_length'], $CentralDirectory['raw']['extra_field_length']);
			}
			if ($CentralDirectory['raw']['file_comment_length'] > 0) {
				$CentralDirectory['file_comment']              = substr($FilenameExtrafieldComment, $CentralDirectory['raw']['filename_length'] + $CentralDirectory['raw']['extra_field_length'], $CentralDirectory['raw']['file_comment_length']);
			}
		}

		return $CentralDirectory;
	}

	function ZIPparseEndOfCentralDirectory(&$fd) {
		$EndOfCentralDirectory['offset'] = ftell($fd);

		$ZIPendOfCentralDirectory = fread($fd, 22);

		$EndOfCentralDirectory['signature']                   = getid3_lib::LittleEndian2Int(substr($ZIPendOfCentralDirectory,  0, 4));
		if ($EndOfCentralDirectory['signature'] != 0x06054B50) {
			// invalid End Of Central Directory Signature
			fseek($fd, $EndOfCentralDirectory['offset'], SEEK_SET); // seek back to where filepointer originally was so it can be handled properly
			return false;
		}
		$EndOfCentralDirectory['disk_number_current']         = getid3_lib::LittleEndian2Int(substr($ZIPendOfCentralDirectory,  4, 2));
		$EndOfCentralDirectory['disk_number_start_directory'] = getid3_lib::LittleEndian2Int(substr($ZIPendOfCentralDirectory,  6, 2));
		$EndOfCentralDirectory['directory_entries_this_disk'] = getid3_lib::LittleEndian2Int(substr($ZIPendOfCentralDirectory,  8, 2));
		$EndOfCentralDirectory['directory_entries_total']     = getid3_lib::LittleEndian2Int(substr($ZIPendOfCentralDirectory, 10, 2));
		$EndOfCentralDirectory['directory_size']              = getid3_lib::LittleEndian2Int(substr($ZIPendOfCentralDirectory, 12, 4));
		$EndOfCentralDirectory['directory_offset']            = getid3_lib::LittleEndian2Int(substr($ZIPendOfCentralDirectory, 16, 4));
		$EndOfCentralDirectory['comment_length']              = getid3_lib::LittleEndian2Int(substr($ZIPendOfCentralDirectory, 20, 2));

		if ($EndOfCentralDirectory['comment_length'] > 0) {
			$EndOfCentralDirectory['comment']                 = fread($fd, $EndOfCentralDirectory['comment_length']);
		}

		return $EndOfCentralDirectory;
	}


	function ZIPparseGeneralPurposeFlags($flagbytes, $compressionmethod) {
		$ParsedFlags['encrypted'] = (bool) ($flagbytes & 0x0001);

		switch ($compressionmethod) {
			case 6:
				$ParsedFlags['dictionary_size']    = (($flagbytes & 0x0002) ? 8192 : 4096);
				$ParsedFlags['shannon_fano_trees'] = (($flagbytes & 0x0004) ? 3    : 2);
				break;

			case 8:
			case 9:
				switch (($flagbytes & 0x0006) >> 1) {
					case 0:
						$ParsedFlags['compression_speed'] = 'normal';
						break;
					case 1:
						$ParsedFlags['compression_speed'] = 'maximum';
						break;
					case 2:
						$ParsedFlags['compression_speed'] = 'fast';
						break;
					case 3:
						$ParsedFlags['compression_speed'] = 'superfast';
						break;
				}
				break;
		}
		$ParsedFlags['data_descriptor_used']       = (bool) ($flagbytes & 0x0008);

		return $ParsedFlags;
	}


	function ZIPversionOSLookup($index) {
		static $ZIPversionOSLookup = array(
			0  => 'MS-DOS and OS/2 (FAT / VFAT / FAT32 file systems)',
			1  => 'Amiga',
			2  => 'OpenVMS',
			3  => 'Unix',
			4  => 'VM/CMS',
			5  => 'Atari ST',
			6  => 'OS/2 H.P.F.S.',
			7  => 'Macintosh',
			8  => 'Z-System',
			9  => 'CP/M',
			10 => 'Windows NTFS',
			11 => 'MVS',
			12 => 'VSE',
			13 => 'Acorn Risc',
			14 => 'VFAT',
			15 => 'Alternate MVS',
			16 => 'BeOS',
			17 => 'Tandem'
		);

		return (isset($ZIPversionOSLookup[$index]) ? $ZIPversionOSLookup[$index] : '[unknown]');
	}

	function ZIPcompressionMethodLookup($index) {
		static $ZIPcompressionMethodLookup = array(
			0  => 'store',
			1  => 'shrink',
			2  => 'reduce-1',
			3  => 'reduce-2',
			4  => 'reduce-3',
			5  => 'reduce-4',
			6  => 'implode',
			7  => 'tokenize',
			8  => 'deflate',
			9  => 'deflate64',
			10 => 'PKWARE Date Compression Library Imploding'
		);

		return (isset($ZIPcompressionMethodLookup[$index]) ? $ZIPcompressionMethodLookup[$index] : '[unknown]');
	}

	function DOStime2UNIXtime($DOSdate, $DOStime) {
		// wFatDate
		// Specifies the MS-DOS date. The date is a packed 16-bit value with the following format:
		// Bits      Contents
		// 0-4    Day of the month (1-31)
		// 5-8    Month (1 = January, 2 = February, and so on)
		// 9-15   Year offset from 1980 (add 1980 to get actual year)

		$UNIXday    =  ($DOSdate & 0x001F);
		$UNIXmonth  = (($DOSdate & 0x01E0) >> 5);
		$UNIXyear   = (($DOSdate & 0xFE00) >> 9) + 1980;

		// wFatTime
		// Specifies the MS-DOS time. The time is a packed 16-bit value with the following format:
		// Bits   Contents
		// 0-4    Second divided by 2
		// 5-10   Minute (0-59)
		// 11-15  Hour (0-23 on a 24-hour clock)

		$UNIXsecond =  ($DOStime & 0x001F) * 2;
		$UNIXminute = (($DOStime & 0x07E0) >> 5);
		$UNIXhour   = (($DOStime & 0xF800) >> 11);

		return gmmktime($UNIXhour, $UNIXminute, $UNIXsecond, $UNIXmonth, $UNIXday, $UNIXyear);
	}

}


?>