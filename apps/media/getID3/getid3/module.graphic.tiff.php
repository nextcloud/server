<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.archive.tiff.php                                     //
// module for analyzing TIFF files                             //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////


class getid3_tiff
{

	function getid3_tiff(&$fd, &$ThisFileInfo) {

		fseek($fd, $ThisFileInfo['avdataoffset'], SEEK_SET);
		$TIFFheader = fread($fd, 4);

		switch (substr($TIFFheader, 0, 2)) {
			case 'II':
				$ThisFileInfo['tiff']['byte_order'] = 'Intel';
				break;
			case 'MM':
				$ThisFileInfo['tiff']['byte_order'] = 'Motorola';
				break;
			default:
				$ThisFileInfo['error'][] = 'Invalid TIFF byte order identifier ('.substr($TIFFheader, 0, 2).') at offset '.$ThisFileInfo['avdataoffset'];
				return false;
				break;
		}

		$ThisFileInfo['fileformat']          = 'tiff';
		$ThisFileInfo['video']['dataformat'] = 'tiff';
		$ThisFileInfo['video']['lossless']   = true;
		$ThisFileInfo['tiff']['ifd']         = array();
		$CurrentIFD                          = array();

		$FieldTypeByteLength = array(1=>1, 2=>1, 3=>2, 4=>4, 5=>8);

		$nextIFDoffset = $this->TIFFendian2Int(fread($fd, 4), $ThisFileInfo['tiff']['byte_order']);

		while ($nextIFDoffset > 0) {

			$CurrentIFD['offset'] = $nextIFDoffset;

			fseek($fd, $ThisFileInfo['avdataoffset'] + $nextIFDoffset, SEEK_SET);
			$CurrentIFD['fieldcount'] = $this->TIFFendian2Int(fread($fd, 2), $ThisFileInfo['tiff']['byte_order']);

			for ($i = 0; $i < $CurrentIFD['fieldcount']; $i++) {
				$CurrentIFD['fields'][$i]['raw']['tag']    = $this->TIFFendian2Int(fread($fd, 2), $ThisFileInfo['tiff']['byte_order']);
				$CurrentIFD['fields'][$i]['raw']['type']   = $this->TIFFendian2Int(fread($fd, 2), $ThisFileInfo['tiff']['byte_order']);
				$CurrentIFD['fields'][$i]['raw']['length'] = $this->TIFFendian2Int(fread($fd, 4), $ThisFileInfo['tiff']['byte_order']);
				$CurrentIFD['fields'][$i]['raw']['offset'] =                       fread($fd, 4);

				switch ($CurrentIFD['fields'][$i]['raw']['type']) {
					case 1: // BYTE  An 8-bit unsigned integer.
						if ($CurrentIFD['fields'][$i]['raw']['length'] <= 4) {
							$CurrentIFD['fields'][$i]['value']  = $this->TIFFendian2Int(substr($CurrentIFD['fields'][$i]['raw']['offset'], 0, 1), $ThisFileInfo['tiff']['byte_order']);
						} else {
							$CurrentIFD['fields'][$i]['offset'] = $this->TIFFendian2Int($CurrentIFD['fields'][$i]['raw']['offset'], $ThisFileInfo['tiff']['byte_order']);
						}
						break;

					case 2: // ASCII 8-bit bytes  that store ASCII codes; the last byte must be null.
						if ($CurrentIFD['fields'][$i]['raw']['length'] <= 4) {
							$CurrentIFD['fields'][$i]['value']  = substr($CurrentIFD['fields'][$i]['raw']['offset'], 3);
						} else {
							$CurrentIFD['fields'][$i]['offset'] = $this->TIFFendian2Int($CurrentIFD['fields'][$i]['raw']['offset'], $ThisFileInfo['tiff']['byte_order']);
						}
						break;

					case 3: // SHORT A 16-bit (2-byte) unsigned integer.
						if ($CurrentIFD['fields'][$i]['raw']['length'] <= 2) {
							$CurrentIFD['fields'][$i]['value']  = $this->TIFFendian2Int(substr($CurrentIFD['fields'][$i]['raw']['offset'], 0, 2), $ThisFileInfo['tiff']['byte_order']);
						} else {
							$CurrentIFD['fields'][$i]['offset'] = $this->TIFFendian2Int($CurrentIFD['fields'][$i]['raw']['offset'], $ThisFileInfo['tiff']['byte_order']);
						}
						break;

					case 4: // LONG  A 32-bit (4-byte) unsigned integer.
						if ($CurrentIFD['fields'][$i]['raw']['length'] <= 1) {
							$CurrentIFD['fields'][$i]['value']  = $this->TIFFendian2Int($CurrentIFD['fields'][$i]['raw']['offset'], $ThisFileInfo['tiff']['byte_order']);
						} else {
							$CurrentIFD['fields'][$i]['offset'] = $this->TIFFendian2Int($CurrentIFD['fields'][$i]['raw']['offset'], $ThisFileInfo['tiff']['byte_order']);
						}
						break;

					case 5: // RATIONAL   Two LONG_s:  the first represents the numerator of a fraction, the second the denominator.
						break;
				}
			}

			$ThisFileInfo['tiff']['ifd'][] = $CurrentIFD;
			$CurrentIFD = array();
			$nextIFDoffset = $this->TIFFendian2Int(fread($fd, 4), $ThisFileInfo['tiff']['byte_order']);

		}

		foreach ($ThisFileInfo['tiff']['ifd'] as $IFDid => $IFDarray) {
			foreach ($IFDarray['fields'] as $key => $fieldarray) {
				switch ($fieldarray['raw']['tag']) {
					case 256: // ImageWidth
					case 257: // ImageLength
					case 258: // BitsPerSample
					case 259: // Compression
						if (!isset($fieldarray['value'])) {
							fseek($fd, $fieldarray['offset'], SEEK_SET);
							$ThisFileInfo['tiff']['ifd'][$IFDid]['fields'][$key]['raw']['data'] = fread($fd, $fieldarray['raw']['length'] * $FieldTypeByteLength[$fieldarray['raw']['type']]);

						}
						break;

					case 270: // ImageDescription
					case 271: // Make
					case 272: // Model
					case 305: // Software
					case 306: // DateTime
					case 315: // Artist
					case 316: // HostComputer
						if (isset($fieldarray['value'])) {
							$ThisFileInfo['tiff']['ifd'][$IFDid]['fields'][$key]['raw']['data'] = $fieldarray['value'];
						} else {
							fseek($fd, $fieldarray['offset'], SEEK_SET);
							$ThisFileInfo['tiff']['ifd'][$IFDid]['fields'][$key]['raw']['data'] = fread($fd, $fieldarray['raw']['length'] * $FieldTypeByteLength[$fieldarray['raw']['type']]);

						}
						break;
				}
				switch ($fieldarray['raw']['tag']) {
					case 256: // ImageWidth
						$ThisFileInfo['video']['resolution_x'] = $fieldarray['value'];
						break;

					case 257: // ImageLength
						$ThisFileInfo['video']['resolution_y'] = $fieldarray['value'];
						break;

					case 258: // BitsPerSample
						if (isset($fieldarray['value'])) {
							$ThisFileInfo['video']['bits_per_sample'] = $fieldarray['value'];
						} else {
							$ThisFileInfo['video']['bits_per_sample'] = 0;
							for ($i = 0; $i < $fieldarray['raw']['length']; $i++) {
								$ThisFileInfo['video']['bits_per_sample'] += $this->TIFFendian2Int(substr($ThisFileInfo['tiff']['ifd'][$IFDid]['fields'][$key]['raw']['data'], $i * $FieldTypeByteLength[$fieldarray['raw']['type']], $FieldTypeByteLength[$fieldarray['raw']['type']]), $ThisFileInfo['tiff']['byte_order']);
							}
						}
						break;

					case 259: // Compression
						$ThisFileInfo['video']['codec'] = $this->TIFFcompressionMethod($fieldarray['value']);
						break;

					case 270: // ImageDescription
					case 271: // Make
					case 272: // Model
					case 305: // Software
					case 306: // DateTime
					case 315: // Artist
					case 316: // HostComputer
						@$ThisFileInfo['tiff']['comments'][$this->TIFFcommentName($fieldarray['raw']['tag'])][] = $ThisFileInfo['tiff']['ifd'][$IFDid]['fields'][$key]['raw']['data'];
						break;

					default:
						break;
				}
			}
		}

		return true;
	}


	function TIFFendian2Int($bytestring, $byteorder) {
		if ($byteorder == 'Intel') {
			return getid3_lib::LittleEndian2Int($bytestring);
		} elseif ($byteorder == 'Motorola') {
			return getid3_lib::BigEndian2Int($bytestring);
		}
		return false;
	}

	function TIFFcompressionMethod($id) {
		static $TIFFcompressionMethod = array();
		if (empty($TIFFcompressionMethod)) {
			$TIFFcompressionMethod = array(
				1     => 'Uncompressed',
				2     => 'Huffman',
				3     => 'Fax - CCITT 3',
				5     => 'LZW',
				32773 => 'PackBits',
			);
		}
		return (isset($TIFFcompressionMethod[$id]) ? $TIFFcompressionMethod[$id] : 'unknown/invalid ('.$id.')');
	}

	function TIFFcommentName($id) {
		static $TIFFcommentName = array();
		if (empty($TIFFcommentName)) {
			$TIFFcommentName = array(
				270 => 'imagedescription',
				271 => 'make',
				272 => 'model',
				305 => 'software',
				306 => 'datetime',
				315 => 'artist',
				316 => 'hostcomputer',
			);
		}
		return (isset($TIFFcommentName[$id]) ? $TIFFcommentName[$id] : 'unknown/invalid ('.$id.')');
	}

}


?>