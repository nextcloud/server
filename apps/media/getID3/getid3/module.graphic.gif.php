<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.graphic.gif.php                                      //
// module for analyzing GIF Image files                        //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////


class getid3_gif
{

	function getid3_gif(&$fd, &$ThisFileInfo) {
		$ThisFileInfo['fileformat']                  = 'gif';
		$ThisFileInfo['video']['dataformat']         = 'gif';
		$ThisFileInfo['video']['lossless']           = true;
		$ThisFileInfo['video']['pixel_aspect_ratio'] = (float) 1;

		fseek($fd, $ThisFileInfo['avdataoffset'], SEEK_SET);
		$GIFheader = fread($fd, 13);
		$offset = 0;

		$ThisFileInfo['gif']['header']['raw']['identifier']            =                              substr($GIFheader, $offset, 3);
		$offset += 3;

		if ($ThisFileInfo['gif']['header']['raw']['identifier'] != 'GIF') {
			$ThisFileInfo['error'][] = 'Expecting "GIF" at offset '.$ThisFileInfo['avdataoffset'].', found "'.$ThisFileInfo['gif']['header']['raw']['identifier'].'"';
			unset($ThisFileInfo['fileformat']);
			unset($ThisFileInfo['gif']);
			return false;
		}

		$ThisFileInfo['gif']['header']['raw']['version']               =                              substr($GIFheader, $offset, 3);
		$offset += 3;
		$ThisFileInfo['gif']['header']['raw']['width']                 = getid3_lib::LittleEndian2Int(substr($GIFheader, $offset, 2));
		$offset += 2;
		$ThisFileInfo['gif']['header']['raw']['height']                = getid3_lib::LittleEndian2Int(substr($GIFheader, $offset, 2));
		$offset += 2;
		$ThisFileInfo['gif']['header']['raw']['flags']                 = getid3_lib::LittleEndian2Int(substr($GIFheader, $offset, 1));
		$offset += 1;
		$ThisFileInfo['gif']['header']['raw']['bg_color_index']        = getid3_lib::LittleEndian2Int(substr($GIFheader, $offset, 1));
		$offset += 1;
		$ThisFileInfo['gif']['header']['raw']['aspect_ratio']          = getid3_lib::LittleEndian2Int(substr($GIFheader, $offset, 1));
		$offset += 1;

		$ThisFileInfo['video']['resolution_x']                         = $ThisFileInfo['gif']['header']['raw']['width'];
		$ThisFileInfo['video']['resolution_y']                         = $ThisFileInfo['gif']['header']['raw']['height'];
		$ThisFileInfo['gif']['version']                                = $ThisFileInfo['gif']['header']['raw']['version'];
		$ThisFileInfo['gif']['header']['flags']['global_color_table']  = (bool) ($ThisFileInfo['gif']['header']['raw']['flags'] & 0x80);
		if ($ThisFileInfo['gif']['header']['raw']['flags'] & 0x80) {
			// Number of bits per primary color available to the original image, minus 1
			$ThisFileInfo['gif']['header']['bits_per_pixel']  = 3 * ((($ThisFileInfo['gif']['header']['raw']['flags'] & 0x70) >> 4) + 1);
		} else {
			$ThisFileInfo['gif']['header']['bits_per_pixel']  = 0;
		}
		$ThisFileInfo['gif']['header']['flags']['global_color_sorted'] = (bool) ($ThisFileInfo['gif']['header']['raw']['flags'] & 0x40);
		if ($ThisFileInfo['gif']['header']['flags']['global_color_table']) {
			// the number of bytes contained in the Global Color Table. To determine that
			// actual size of the color table, raise 2 to [the value of the field + 1]
			$ThisFileInfo['gif']['header']['global_color_size'] = pow(2, ($ThisFileInfo['gif']['header']['raw']['flags'] & 0x07) + 1);
			$ThisFileInfo['video']['bits_per_sample']           = ($ThisFileInfo['gif']['header']['raw']['flags'] & 0x07) + 1;
		} else {
			$ThisFileInfo['gif']['header']['global_color_size'] = 0;
		}
		if ($ThisFileInfo['gif']['header']['raw']['aspect_ratio'] != 0) {
			// Aspect Ratio = (Pixel Aspect Ratio + 15) / 64
			$ThisFileInfo['gif']['header']['aspect_ratio'] = ($ThisFileInfo['gif']['header']['raw']['aspect_ratio'] + 15) / 64;
		}

//		if ($ThisFileInfo['gif']['header']['flags']['global_color_table']) {
//			$GIFcolorTable = fread($fd, 3 * $ThisFileInfo['gif']['header']['global_color_size']);
//			$offset = 0;
//			for ($i = 0; $i < $ThisFileInfo['gif']['header']['global_color_size']; $i++) {
//				$red   = getid3_lib::LittleEndian2Int(substr($GIFcolorTable, $offset++, 1));
//				$green = getid3_lib::LittleEndian2Int(substr($GIFcolorTable, $offset++, 1));
//				$blue  = getid3_lib::LittleEndian2Int(substr($GIFcolorTable, $offset++, 1));
//				$ThisFileInfo['gif']['global_color_table'][$i] = (($red << 16) | ($green << 8) | ($blue));
//			}
//		}
//
//		// Image Descriptor
//		while (!feof($fd)) {
//			$NextBlockTest = fread($fd, 1);
//			switch ($NextBlockTest) {
//
//				case ',': // ',' - Image separator character
//
//					$ImageDescriptorData = $NextBlockTest.fread($fd, 9);
//					$ImageDescriptor = array();
//					$ImageDescriptor['image_left']   = getid3_lib::LittleEndian2Int(substr($ImageDescriptorData, 1, 2));
//					$ImageDescriptor['image_top']    = getid3_lib::LittleEndian2Int(substr($ImageDescriptorData, 3, 2));
//					$ImageDescriptor['image_width']  = getid3_lib::LittleEndian2Int(substr($ImageDescriptorData, 5, 2));
//					$ImageDescriptor['image_height'] = getid3_lib::LittleEndian2Int(substr($ImageDescriptorData, 7, 2));
//					$ImageDescriptor['flags_raw']    = getid3_lib::LittleEndian2Int(substr($ImageDescriptorData, 9, 1));
//					$ImageDescriptor['flags']['use_local_color_map'] = (bool) ($ImageDescriptor['flags_raw'] & 0x80);
//					$ImageDescriptor['flags']['image_interlaced']    = (bool) ($ImageDescriptor['flags_raw'] & 0x40);
//					$ThisFileInfo['gif']['image_descriptor'][] = $ImageDescriptor;
//
//					if ($ImageDescriptor['flags']['use_local_color_map']) {
//
//						$ThisFileInfo['warning'][] = 'This version of getID3() cannot parse local color maps for GIFs';
//						return true;
//
//					}
//echo 'Start of raster data: '.ftell($fd).'<BR>';
//					$RasterData = array();
//					$RasterData['code_size']        = getid3_lib::LittleEndian2Int(fread($fd, 1));
//					$RasterData['block_byte_count'] = getid3_lib::LittleEndian2Int(fread($fd, 1));
//					$ThisFileInfo['gif']['raster_data'][count($ThisFileInfo['gif']['image_descriptor']) - 1] = $RasterData;
//
//					$CurrentCodeSize = $RasterData['code_size'] + 1;
//					for ($i = 0; $i < pow(2, $RasterData['code_size']); $i++) {
//						$DefaultDataLookupTable[$i] = chr($i);
//					}
//					$DefaultDataLookupTable[pow(2, $RasterData['code_size']) + 0] = ''; // Clear Code
//					$DefaultDataLookupTable[pow(2, $RasterData['code_size']) + 1] = ''; // End Of Image Code
//
//
//					$NextValue = $this->GetLSBits($fd, $CurrentCodeSize);
//					echo 'Clear Code: '.$NextValue.'<BR>';
//
//					$NextValue = $this->GetLSBits($fd, $CurrentCodeSize);
//					echo 'First Color: '.$NextValue.'<BR>';
//
//					$Prefix = $NextValue;
//$i = 0;
//					while ($i++ < 20) {
//						$NextValue = $this->GetLSBits($fd, $CurrentCodeSize);
//						echo $NextValue.'<BR>';
//					}
//return true;
//					break;
//
//				case '!':
//					// GIF Extension Block
//					$ExtensionBlockData = $NextBlockTest.fread($fd, 2);
//					$ExtensionBlock = array();
//					$ExtensionBlock['function_code']  = getid3_lib::LittleEndian2Int(substr($ExtensionBlockData, 1, 1));
//					$ExtensionBlock['byte_length']    = getid3_lib::LittleEndian2Int(substr($ExtensionBlockData, 2, 1));
//					$ExtensionBlock['data']           = fread($fd, $ExtensionBlock['byte_length']);
//					$ThisFileInfo['gif']['extension_blocks'][] = $ExtensionBlock;
//					break;
//
//				case ';':
//					$ThisFileInfo['gif']['terminator_offset'] = ftell($fd) - 1;
//					// GIF Terminator
//					break;
//
//				default:
//					break;
//
//
//			}
//		}

		return true;
	}


	function GetLSBits($fd, $bits) {
		static $bitbuffer = '';
		while (strlen($bitbuffer) < $bits) {
//echo 'Read another byte: '.ftell($fd).'<BR>';
			$bitbuffer = str_pad(decbin(ord(fread($fd, 1))), 8, '0', STR_PAD_LEFT).$bitbuffer;
		}

		$value = bindec(substr($bitbuffer, 0 - $bits));
		$bitbuffer = substr($bitbuffer, 0, 0 - $bits);

		return $value;
	}

}


?>