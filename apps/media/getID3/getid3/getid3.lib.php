<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
//                                                             //
// getid3.lib.php - part of getID3()                           //
// See readme.txt for more details                             //
//                                                            ///
/////////////////////////////////////////////////////////////////


class getid3_lib
{

	function PrintHexBytes($string, $hex=true, $spaces=true, $htmlsafe=true) {
		$returnstring = '';
		for ($i = 0; $i < strlen($string); $i++) {
			if ($hex) {
				$returnstring .= str_pad(dechex(ord($string{$i})), 2, '0', STR_PAD_LEFT);
			} else {
				$returnstring .= ' '.(ereg("[\x20-\x7E]", $string{$i}) ? $string{$i} : '¤');
			}
			if ($spaces) {
				$returnstring .= ' ';
			}
		}
		if ($htmlsafe) {
			$returnstring = htmlentities($returnstring);
		}
		return $returnstring;
	}

	function SafeStripSlashes($text) {
		if (get_magic_quotes_gpc()) {
			return stripslashes($text);
		}
		return $text;
	}


	function trunc($floatnumber) {
		// truncates a floating-point number at the decimal point
		// returns int (if possible, otherwise float)
		if ($floatnumber >= 1) {
			$truncatednumber = floor($floatnumber);
		} elseif ($floatnumber <= -1) {
			$truncatednumber = ceil($floatnumber);
		} else {
			$truncatednumber = 0;
		}
		if ($truncatednumber <= 1073741824) { // 2^30
			$truncatednumber = (int) $truncatednumber;
		}
		return $truncatednumber;
	}


	function CastAsInt($floatnum) {
		// convert to float if not already
		$floatnum = (float) $floatnum;

		// convert a float to type int, only if possible
		if (getid3_lib::trunc($floatnum) == $floatnum) {
			// it's not floating point
			if ($floatnum <= 2147483647) { // 2^31
				// it's within int range
				$floatnum = (int) $floatnum;
			}
		}
		return $floatnum;
	}


	function DecimalBinary2Float($binarynumerator) {
		$numerator   = getid3_lib::Bin2Dec($binarynumerator);
		$denominator = getid3_lib::Bin2Dec('1'.str_repeat('0', strlen($binarynumerator)));
		return ($numerator / $denominator);
	}


	function NormalizeBinaryPoint($binarypointnumber, $maxbits=52) {
		// http://www.scri.fsu.edu/~jac/MAD3401/Backgrnd/binary.html
		if (strpos($binarypointnumber, '.') === false) {
			$binarypointnumber = '0.'.$binarypointnumber;
		} elseif ($binarypointnumber{0} == '.') {
			$binarypointnumber = '0'.$binarypointnumber;
		}
		$exponent = 0;
		while (($binarypointnumber{0} != '1') || (substr($binarypointnumber, 1, 1) != '.')) {
			if (substr($binarypointnumber, 1, 1) == '.') {
				$exponent--;
				$binarypointnumber = substr($binarypointnumber, 2, 1).'.'.substr($binarypointnumber, 3);
			} else {
				$pointpos = strpos($binarypointnumber, '.');
				$exponent += ($pointpos - 1);
				$binarypointnumber = str_replace('.', '', $binarypointnumber);
				$binarypointnumber = $binarypointnumber{0}.'.'.substr($binarypointnumber, 1);
			}
		}
		$binarypointnumber = str_pad(substr($binarypointnumber, 0, $maxbits + 2), $maxbits + 2, '0', STR_PAD_RIGHT);
		return array('normalized'=>$binarypointnumber, 'exponent'=>(int) $exponent);
	}


	function Float2BinaryDecimal($floatvalue) {
		// http://www.scri.fsu.edu/~jac/MAD3401/Backgrnd/binary.html
		$maxbits = 128; // to how many bits of precision should the calculations be taken?
		$intpart   = getid3_lib::trunc($floatvalue);
		$floatpart = abs($floatvalue - $intpart);
		$pointbitstring = '';
		while (($floatpart != 0) && (strlen($pointbitstring) < $maxbits)) {
			$floatpart *= 2;
			$pointbitstring .= (string) getid3_lib::trunc($floatpart);
			$floatpart -= getid3_lib::trunc($floatpart);
		}
		$binarypointnumber = decbin($intpart).'.'.$pointbitstring;
		return $binarypointnumber;
	}


	function Float2String($floatvalue, $bits) {
		// http://www.scri.fsu.edu/~jac/MAD3401/Backgrnd/ieee-expl.html
		switch ($bits) {
			case 32:
				$exponentbits = 8;
				$fractionbits = 23;
				break;

			case 64:
				$exponentbits = 11;
				$fractionbits = 52;
				break;

			default:
				return false;
				break;
		}
		if ($floatvalue >= 0) {
			$signbit = '0';
		} else {
			$signbit = '1';
		}
		$normalizedbinary  = getid3_lib::NormalizeBinaryPoint(getid3_lib::Float2BinaryDecimal($floatvalue), $fractionbits);
		$biasedexponent    = pow(2, $exponentbits - 1) - 1 + $normalizedbinary['exponent']; // (127 or 1023) +/- exponent
		$exponentbitstring = str_pad(decbin($biasedexponent), $exponentbits, '0', STR_PAD_LEFT);
		$fractionbitstring = str_pad(substr($normalizedbinary['normalized'], 2), $fractionbits, '0', STR_PAD_RIGHT);

		return getid3_lib::BigEndian2String(getid3_lib::Bin2Dec($signbit.$exponentbitstring.$fractionbitstring), $bits % 8, false);
	}


	function LittleEndian2Float($byteword) {
		return getid3_lib::BigEndian2Float(strrev($byteword));
	}


	function BigEndian2Float($byteword) {
		// ANSI/IEEE Standard 754-1985, Standard for Binary Floating Point Arithmetic
		// http://www.psc.edu/general/software/packages/ieee/ieee.html
		// http://www.scri.fsu.edu/~jac/MAD3401/Backgrnd/ieee.html

		$bitword = getid3_lib::BigEndian2Bin($byteword);
		if (!$bitword) {
            return 0;
        }
		$signbit = $bitword{0};

		switch (strlen($byteword) * 8) {
			case 32:
				$exponentbits = 8;
				$fractionbits = 23;
				break;

			case 64:
				$exponentbits = 11;
				$fractionbits = 52;
				break;

			case 80:
				// 80-bit Apple SANE format
				// http://www.mactech.com/articles/mactech/Vol.06/06.01/SANENormalized/
				$exponentstring = substr($bitword, 1, 15);
				$isnormalized = intval($bitword{16});
				$fractionstring = substr($bitword, 17, 63);
				$exponent = pow(2, getid3_lib::Bin2Dec($exponentstring) - 16383);
				$fraction = $isnormalized + getid3_lib::DecimalBinary2Float($fractionstring);
				$floatvalue = $exponent * $fraction;
				if ($signbit == '1') {
					$floatvalue *= -1;
				}
				return $floatvalue;
				break;

			default:
				return false;
				break;
		}
		$exponentstring = substr($bitword, 1, $exponentbits);
		$fractionstring = substr($bitword, $exponentbits + 1, $fractionbits);
		$exponent = getid3_lib::Bin2Dec($exponentstring);
		$fraction = getid3_lib::Bin2Dec($fractionstring);

		if (($exponent == (pow(2, $exponentbits) - 1)) && ($fraction != 0)) {
			// Not a Number
			$floatvalue = false;
		} elseif (($exponent == (pow(2, $exponentbits) - 1)) && ($fraction == 0)) {
			if ($signbit == '1') {
				$floatvalue = '-infinity';
			} else {
				$floatvalue = '+infinity';
			}
		} elseif (($exponent == 0) && ($fraction == 0)) {
			if ($signbit == '1') {
				$floatvalue = -0;
			} else {
				$floatvalue = 0;
			}
			$floatvalue = ($signbit ? 0 : -0);
		} elseif (($exponent == 0) && ($fraction != 0)) {
			// These are 'unnormalized' values
			$floatvalue = pow(2, (-1 * (pow(2, $exponentbits - 1) - 2))) * getid3_lib::DecimalBinary2Float($fractionstring);
			if ($signbit == '1') {
				$floatvalue *= -1;
			}
		} elseif ($exponent != 0) {
			$floatvalue = pow(2, ($exponent - (pow(2, $exponentbits - 1) - 1))) * (1 + getid3_lib::DecimalBinary2Float($fractionstring));
			if ($signbit == '1') {
				$floatvalue *= -1;
			}
		}
		return (float) $floatvalue;
	}


	function BigEndian2Int($byteword, $synchsafe=false, $signed=false) {
		$intvalue = 0;
		$bytewordlen = strlen($byteword);
		for ($i = 0; $i < $bytewordlen; $i++) {
			if ($synchsafe) { // disregard MSB, effectively 7-bit bytes
				$intvalue = $intvalue | (ord($byteword{$i}) & 0x7F) << (($bytewordlen - 1 - $i) * 7);
			} else {
				$intvalue += ord($byteword{$i}) * pow(256, ($bytewordlen - 1 - $i));
			}
		}
		if ($signed && !$synchsafe) {
			// synchsafe ints are not allowed to be signed
			switch ($bytewordlen) {
				case 1:
				case 2:
				case 3:
				case 4:
					$signmaskbit = 0x80 << (8 * ($bytewordlen - 1));
					if ($intvalue & $signmaskbit) {
						$intvalue = 0 - ($intvalue & ($signmaskbit - 1));
					}
					break;

				default:
					die('ERROR: Cannot have signed integers larger than 32-bits in getid3_lib::BigEndian2Int()');
					break;
			}
		}
		return getid3_lib::CastAsInt($intvalue);
	}


	function LittleEndian2Int($byteword, $signed=false) {
		return getid3_lib::BigEndian2Int(strrev($byteword), false, $signed);
	}


	function BigEndian2Bin($byteword) {
		$binvalue = '';
		$bytewordlen = strlen($byteword);
		for ($i = 0; $i < $bytewordlen; $i++) {
			$binvalue .= str_pad(decbin(ord($byteword{$i})), 8, '0', STR_PAD_LEFT);
		}
		return $binvalue;
	}


	function BigEndian2String($number, $minbytes=1, $synchsafe=false, $signed=false) {
		if ($number < 0) {
			return false;
		}
		$maskbyte = (($synchsafe || $signed) ? 0x7F : 0xFF);
		$intstring = '';
		if ($signed) {
			if ($minbytes > 4) {
				die('ERROR: Cannot have signed integers larger than 32-bits in getid3_lib::BigEndian2String()');
			}
			$number = $number & (0x80 << (8 * ($minbytes - 1)));
		}
		while ($number != 0) {
			$quotient = ($number / ($maskbyte + 1));
			$intstring = chr(ceil(($quotient - floor($quotient)) * $maskbyte)).$intstring;
			$number = floor($quotient);
		}
		return str_pad($intstring, $minbytes, "\x00", STR_PAD_LEFT);
	}


	function Dec2Bin($number) {
		while ($number >= 256) {
			$bytes[] = (($number / 256) - (floor($number / 256))) * 256;
			$number = floor($number / 256);
		}
		$bytes[] = $number;
		$binstring = '';
		for ($i = 0; $i < count($bytes); $i++) {
			$binstring = (($i == count($bytes) - 1) ? decbin($bytes[$i]) : str_pad(decbin($bytes[$i]), 8, '0', STR_PAD_LEFT)).$binstring;
		}
		return $binstring;
	}


	function Bin2Dec($binstring, $signed=false) {
		$signmult = 1;
		if ($signed) {
			if ($binstring{0} == '1') {
				$signmult = -1;
			}
			$binstring = substr($binstring, 1);
		}
		$decvalue = 0;
		for ($i = 0; $i < strlen($binstring); $i++) {
			$decvalue += ((int) substr($binstring, strlen($binstring) - $i - 1, 1)) * pow(2, $i);
		}
		return getid3_lib::CastAsInt($decvalue * $signmult);
	}


	function Bin2String($binstring) {
		// return 'hi' for input of '0110100001101001'
		$string = '';
		$binstringreversed = strrev($binstring);
		for ($i = 0; $i < strlen($binstringreversed); $i += 8) {
			$string = chr(getid3_lib::Bin2Dec(strrev(substr($binstringreversed, $i, 8)))).$string;
		}
		return $string;
	}


	function LittleEndian2String($number, $minbytes=1, $synchsafe=false) {
		$intstring = '';
		while ($number > 0) {
			if ($synchsafe) {
				$intstring = $intstring.chr($number & 127);
				$number >>= 7;
			} else {
				$intstring = $intstring.chr($number & 255);
				$number >>= 8;
			}
		}
		return str_pad($intstring, $minbytes, "\x00", STR_PAD_RIGHT);
	}


	function array_merge_clobber($array1, $array2) {
		// written by kcØhireability*com
		// taken from http://www.php.net/manual/en/function.array-merge-recursive.php
		if (!is_array($array1) || !is_array($array2)) {
			return false;
		}
		$newarray = $array1;
		foreach ($array2 as $key => $val) {
			if (is_array($val) && isset($newarray[$key]) && is_array($newarray[$key])) {
				$newarray[$key] = getid3_lib::array_merge_clobber($newarray[$key], $val);
			} else {
				$newarray[$key] = $val;
			}
		}
		return $newarray;
	}


	function array_merge_noclobber($array1, $array2) {
		if (!is_array($array1) || !is_array($array2)) {
			return false;
		}
		$newarray = $array1;
		foreach ($array2 as $key => $val) {
			if (is_array($val) && isset($newarray[$key]) && is_array($newarray[$key])) {
				$newarray[$key] = getid3_lib::array_merge_noclobber($newarray[$key], $val);
			} elseif (!isset($newarray[$key])) {
				$newarray[$key] = $val;
			}
		}
		return $newarray;
	}


	function fileextension($filename, $numextensions=1) {
		if (strstr($filename, '.')) {
			$reversedfilename = strrev($filename);
			$offset = 0;
			for ($i = 0; $i < $numextensions; $i++) {
				$offset = strpos($reversedfilename, '.', $offset + 1);
				if ($offset === false) {
					return '';
				}
			}
			return strrev(substr($reversedfilename, 0, $offset));
		}
		return '';
	}


	function PlaytimeString($playtimeseconds) {
		$sign = (($playtimeseconds < 0) ? '-' : '');
		$playtimeseconds = abs($playtimeseconds);
		$contentseconds = round((($playtimeseconds / 60) - floor($playtimeseconds / 60)) * 60);
		$contentminutes = floor($playtimeseconds / 60);
		if ($contentseconds >= 60) {
			$contentseconds -= 60;
			$contentminutes++;
		}
		return $sign.intval($contentminutes).':'.str_pad($contentseconds, 2, 0, STR_PAD_LEFT);
	}


	function image_type_to_mime_type($imagetypeid) {
		// only available in PHP v4.3.0+
		static $image_type_to_mime_type = array();
		if (empty($image_type_to_mime_type)) {
			$image_type_to_mime_type[1]  = 'image/gif';                     // GIF
			$image_type_to_mime_type[2]  = 'image/jpeg';                    // JPEG
			$image_type_to_mime_type[3]  = 'image/png';                     // PNG
			$image_type_to_mime_type[4]  = 'application/x-shockwave-flash'; // Flash
			$image_type_to_mime_type[5]  = 'image/psd';                     // PSD
			$image_type_to_mime_type[6]  = 'image/bmp';                     // BMP
			$image_type_to_mime_type[7]  = 'image/tiff';                    // TIFF: little-endian (Intel)
			$image_type_to_mime_type[8]  = 'image/tiff';                    // TIFF: big-endian (Motorola)
			//$image_type_to_mime_type[9]  = 'image/jpc';                   // JPC
			//$image_type_to_mime_type[10] = 'image/jp2';                   // JPC
			//$image_type_to_mime_type[11] = 'image/jpx';                   // JPC
			//$image_type_to_mime_type[12] = 'image/jb2';                   // JPC
			$image_type_to_mime_type[13] = 'application/x-shockwave-flash'; // Shockwave
			$image_type_to_mime_type[14] = 'image/iff';                     // IFF
		}
		return (isset($image_type_to_mime_type[$imagetypeid]) ? $image_type_to_mime_type[$imagetypeid] : 'application/octet-stream');
	}


	function DateMac2Unix($macdate) {
		// Macintosh timestamp: seconds since 00:00h January 1, 1904
		// UNIX timestamp:      seconds since 00:00h January 1, 1970
		return getid3_lib::CastAsInt($macdate - 2082844800);
	}


	function FixedPoint8_8($rawdata) {
		return getid3_lib::BigEndian2Int(substr($rawdata, 0, 1)) + (float) (getid3_lib::BigEndian2Int(substr($rawdata, 1, 1)) / pow(2, 8));
	}


	function FixedPoint16_16($rawdata) {
		return getid3_lib::BigEndian2Int(substr($rawdata, 0, 2)) + (float) (getid3_lib::BigEndian2Int(substr($rawdata, 2, 2)) / pow(2, 16));
	}


	function FixedPoint2_30($rawdata) {
		$binarystring = getid3_lib::BigEndian2Bin($rawdata);
		return getid3_lib::Bin2Dec(substr($binarystring, 0, 2)) + (float) (getid3_lib::Bin2Dec(substr($binarystring, 2, 30)) / 1073741824);
	}


	function CreateDeepArray($ArrayPath, $Separator, $Value) {
		// assigns $Value to a nested array path:
		//   $foo = getid3_lib::CreateDeepArray('/path/to/my', '/', 'file.txt')
		// is the same as:
		//   $foo = array('path'=>array('to'=>'array('my'=>array('file.txt'))));
		// or
		//   $foo['path']['to']['my'] = 'file.txt';
		while ($ArrayPath && ($ArrayPath{0} == $Separator)) {
			$ArrayPath = substr($ArrayPath, 1);
		}
		if (($pos = strpos($ArrayPath, $Separator)) !== false) {
			$ReturnedArray[substr($ArrayPath, 0, $pos)] = getid3_lib::CreateDeepArray(substr($ArrayPath, $pos + 1), $Separator, $Value);
		} else {
			$ReturnedArray[$ArrayPath] = $Value;
		}
		return $ReturnedArray;
	}

	function array_max($arraydata, $returnkey=false) {
		$maxvalue = false;
		$maxkey = false;
		foreach ($arraydata as $key => $value) {
			if (!is_array($value)) {
				if ($value > $maxvalue) {
					$maxvalue = $value;
					$maxkey = $key;
				}
			}
		}
		return ($returnkey ? $maxkey : $maxvalue);
	}

	function array_min($arraydata, $returnkey=false) {
		$minvalue = false;
		$minkey = false;
		foreach ($arraydata as $key => $value) {
			if (!is_array($value)) {
				if ($value > $minvalue) {
					$minvalue = $value;
					$minkey = $key;
				}
			}
		}
		return ($returnkey ? $minkey : $minvalue);
	}


	function md5_file($file) {

		// md5_file() exists in PHP 4.2.0+.
		if (function_exists('md5_file')) {
			return md5_file($file);
		}

		if (GETID3_OS_ISWINDOWS) {

			$RequiredFiles = array('cygwin1.dll', 'md5sum.exe');
			foreach ($RequiredFiles as $required_file) {
				if (!is_readable(GETID3_HELPERAPPSDIR.$required_file)) {
					die(implode(' and ', $RequiredFiles).' are required in '.GETID3_HELPERAPPSDIR.' for getid3_lib::md5_file() to function under Windows in PHP < v4.2.0');
				}
			}
			$commandline = GETID3_HELPERAPPSDIR.'md5sum.exe "'.str_replace('/', DIRECTORY_SEPARATOR, $file).'"';
			if (ereg("^[\\]?([0-9a-f]{32})", strtolower(`$commandline`), $r)) {
				return $r[1];
			}

		} else {

			// The following works under UNIX only
			$file = str_replace('`', '\\`', $file);
			if (ereg("^([0-9a-f]{32})[ \t\n\r]", `md5sum "$file"`, $r)) {
				return $r[1];
			}

		}
		return false;
	}


	function sha1_file($file) {

		// sha1_file() exists in PHP 4.3.0+.
		if (function_exists('sha1_file')) {
			return sha1_file($file);
		}

		$file = str_replace('`', '\\`', $file);

		if (GETID3_OS_ISWINDOWS) {

			$RequiredFiles = array('cygwin1.dll', 'sha1sum.exe');
			foreach ($RequiredFiles as $required_file) {
				if (!is_readable(GETID3_HELPERAPPSDIR.$required_file)) {
					die(implode(' and ', $RequiredFiles).' are required in '.GETID3_HELPERAPPSDIR.' for getid3_lib::sha1_file() to function under Windows in PHP < v4.3.0');
				}
			}
			$commandline = GETID3_HELPERAPPSDIR.'sha1sum.exe "'.str_replace('/', DIRECTORY_SEPARATOR, $file).'"';
			if (ereg("^sha1=([0-9a-f]{40})", strtolower(`$commandline`), $r)) {
				return $r[1];
			}

		} else {

			$commandline = 'sha1sum '.escapeshellarg($file).'';
			if (ereg("^([0-9a-f]{40})[ \t\n\r]", strtolower(`$commandline`), $r)) {
				return $r[1];
			}

		}

		return false;
	}


	// Allan Hansen <ahØartemis*dk>
	// getid3_lib::md5_data() - returns md5sum for a file from startuing position to absolute end position
	function hash_data($file, $offset, $end, $algorithm) {
		if ($end >= pow(2, 31)) {
			return false;
		}

		switch ($algorithm) {
			case 'md5':
				$hash_function = 'md5_file';
				$unix_call     = 'md5sum';
				$windows_call  = 'md5sum.exe';
				$hash_length   = 32;
				break;

			case 'sha1':
				$hash_function = 'sha1_file';
				$unix_call     = 'sha1sum';
				$windows_call  = 'sha1sum.exe';
				$hash_length   = 40;
				break;

			default:
				die('Invalid algorithm ('.$algorithm.') in getid3_lib::hash_data()');
				break;
		}
		$size = $end - $offset;
		while (true) {
			if (GETID3_OS_ISWINDOWS) {

				// It seems that sha1sum.exe for Windows only works on physical files, does not accept piped data
				// Fall back to create-temp-file method:
				if ($algorithm == 'sha1') {
					break;
				}

				$RequiredFiles = array('cygwin1.dll', 'head.exe', 'tail.exe', $windows_call);
				foreach ($RequiredFiles as $required_file) {
					if (!is_readable(GETID3_HELPERAPPSDIR.$required_file)) {
						// helper apps not available - fall back to old method
						break;
					}
				}
				$commandline  = GETID3_HELPERAPPSDIR.'head.exe -c '.$end.' "'.escapeshellarg(str_replace('/', DIRECTORY_SEPARATOR, $file)).'" | ';
				$commandline .= GETID3_HELPERAPPSDIR.'tail.exe -c '.$size.' | ';
				$commandline .= GETID3_HELPERAPPSDIR.$windows_call;

			} else {

				$commandline  = 'head -c'.$end.' '.escapeshellarg($file).' | ';
				$commandline .= 'tail -c'.$size.' | ';
				$commandline .= $unix_call;

			}
			if ((bool) ini_get('safe_mode')) {
				$ThisFileInfo['warning'][] = 'PHP running in Safe Mode - backtick operator not available, using slower non-system-call '.$algorithm.' algorithm';
				break;
			}
			return substr(`$commandline`, 0, $hash_length);
		}

		// try to create a temporary file in the system temp directory - invalid dirname should force to system temp dir
		if (($data_filename = tempnam('*', 'getID3')) === false) {
			// can't find anywhere to create a temp file, just die
			return false;
		}

		// Init
		$result = false;

		// copy parts of file
		if ($fp = @fopen($file, 'rb')) {

			if ($fp_data = @fopen($data_filename, 'wb')) {

				fseek($fp, $offset, SEEK_SET);
				$byteslefttowrite = $end - $offset;
				while (($byteslefttowrite > 0) && ($buffer = fread($fp, GETID3_FREAD_BUFFER_SIZE))) {
					$byteswritten = fwrite($fp_data, $buffer, $byteslefttowrite);
					$byteslefttowrite -= $byteswritten;
				}
				fclose($fp_data);
				$result = getid3_lib::$hash_function($data_filename);

			}
			fclose($fp);
		}
		unlink($data_filename);
		return $result;
	}


	function iconv_fallback_int_utf8($charval) {
		if ($charval < 128) {
			// 0bbbbbbb
			$newcharstring = chr($charval);
		} elseif ($charval < 2048) {
			// 110bbbbb 10bbbbbb
			$newcharstring  = chr(($charval >> 6) | 0xC0);
			$newcharstring .= chr(($charval & 0x3F) | 0x80);
		} elseif ($charval < 65536) {
			// 1110bbbb 10bbbbbb 10bbbbbb
			$newcharstring  = chr(($charval >> 12) | 0xE0);
			$newcharstring .= chr(($charval >>  6) | 0xC0);
			$newcharstring .= chr(($charval & 0x3F) | 0x80);
		} else {
			// 11110bbb 10bbbbbb 10bbbbbb 10bbbbbb
			$newcharstring  = chr(($charval >> 18) | 0xF0);
			$newcharstring .= chr(($charval >> 12) | 0xC0);
			$newcharstring .= chr(($charval >>  6) | 0xC0);
			$newcharstring .= chr(($charval & 0x3F) | 0x80);
		}
		return $newcharstring;
	}

	// ISO-8859-1 => UTF-8
	function iconv_fallback_iso88591_utf8($string, $bom=false) {
		if (function_exists('utf8_encode')) {
			return utf8_encode($string);
		}
		// utf8_encode() unavailable, use getID3()'s iconv_fallback() conversions (possibly PHP is compiled without XML support)
		$newcharstring = '';
		if ($bom) {
			$newcharstring .= "\xEF\xBB\xBF";
		}
		for ($i = 0; $i < strlen($string); $i++) {
			$charval = ord($string{$i});
			$newcharstring .= getid3_lib::iconv_fallback_int_utf8($charval);
		}
		return $newcharstring;
	}

	// ISO-8859-1 => UTF-16BE
	function iconv_fallback_iso88591_utf16be($string, $bom=false) {
		$newcharstring = '';
		if ($bom) {
			$newcharstring .= "\xFE\xFF";
		}
		for ($i = 0; $i < strlen($string); $i++) {
			$newcharstring .= "\x00".$string{$i};
		}
		return $newcharstring;
	}

	// ISO-8859-1 => UTF-16LE
	function iconv_fallback_iso88591_utf16le($string, $bom=false) {
		$newcharstring = '';
		if ($bom) {
			$newcharstring .= "\xFF\xFE";
		}
		for ($i = 0; $i < strlen($string); $i++) {
			$newcharstring .= $string{$i}."\x00";
		}
		return $newcharstring;
	}

	// ISO-8859-1 => UTF-16LE (BOM)
	function iconv_fallback_iso88591_utf16($string) {
		return getid3_lib::iconv_fallback_iso88591_utf16le($string, true);
	}

	// UTF-8 => ISO-8859-1
	function iconv_fallback_utf8_iso88591($string) {
		if (function_exists('utf8_decode')) {
			return utf8_decode($string);
		}
		// utf8_decode() unavailable, use getID3()'s iconv_fallback() conversions (possibly PHP is compiled without XML support)
		$newcharstring = '';
		$offset = 0;
		$stringlength = strlen($string);
		while ($offset < $stringlength) {
			if ((ord($string{$offset}) | 0x07) == 0xF7) {
				// 11110bbb 10bbbbbb 10bbbbbb 10bbbbbb
				$charval = ((ord($string{($offset + 0)}) & 0x07) << 18) &
				           ((ord($string{($offset + 1)}) & 0x3F) << 12) &
				           ((ord($string{($offset + 2)}) & 0x3F) <<  6) &
				            (ord($string{($offset + 3)}) & 0x3F);
				$offset += 4;
			} elseif ((ord($string{$offset}) | 0x0F) == 0xEF) {
				// 1110bbbb 10bbbbbb 10bbbbbb
				$charval = ((ord($string{($offset + 0)}) & 0x0F) << 12) &
				           ((ord($string{($offset + 1)}) & 0x3F) <<  6) &
				            (ord($string{($offset + 2)}) & 0x3F);
				$offset += 3;
			} elseif ((ord($string{$offset}) | 0x1F) == 0xDF) {
				// 110bbbbb 10bbbbbb
				$charval = ((ord($string{($offset + 0)}) & 0x1F) <<  6) &
				            (ord($string{($offset + 1)}) & 0x3F);
				$offset += 2;
			} elseif ((ord($string{$offset}) | 0x7F) == 0x7F) {
				// 0bbbbbbb
				$charval = ord($string{$offset});
				$offset += 1;
			} else {
				// error? throw some kind of warning here?
				$charval = false;
				$offset += 1;
			}
			if ($charval !== false) {
				$newcharstring .= (($charval < 256) ? chr($charval) : '?');
			}
		}
		return $newcharstring;
	}

	// UTF-8 => UTF-16BE
	function iconv_fallback_utf8_utf16be($string, $bom=false) {
		$newcharstring = '';
		if ($bom) {
			$newcharstring .= "\xFE\xFF";
		}
		$offset = 0;
		$stringlength = strlen($string);
		while ($offset < $stringlength) {
			if ((ord($string{$offset}) | 0x07) == 0xF7) {
				// 11110bbb 10bbbbbb 10bbbbbb 10bbbbbb
				$charval = ((ord($string{($offset + 0)}) & 0x07) << 18) &
				           ((ord($string{($offset + 1)}) & 0x3F) << 12) &
				           ((ord($string{($offset + 2)}) & 0x3F) <<  6) &
				            (ord($string{($offset + 3)}) & 0x3F);
				$offset += 4;
			} elseif ((ord($string{$offset}) | 0x0F) == 0xEF) {
				// 1110bbbb 10bbbbbb 10bbbbbb
				$charval = ((ord($string{($offset + 0)}) & 0x0F) << 12) &
				           ((ord($string{($offset + 1)}) & 0x3F) <<  6) &
				            (ord($string{($offset + 2)}) & 0x3F);
				$offset += 3;
			} elseif ((ord($string{$offset}) | 0x1F) == 0xDF) {
				// 110bbbbb 10bbbbbb
				$charval = ((ord($string{($offset + 0)}) & 0x1F) <<  6) &
				            (ord($string{($offset + 1)}) & 0x3F);
				$offset += 2;
			} elseif ((ord($string{$offset}) | 0x7F) == 0x7F) {
				// 0bbbbbbb
				$charval = ord($string{$offset});
				$offset += 1;
			} else {
				// error? throw some kind of warning here?
				$charval = false;
				$offset += 1;
			}
			if ($charval !== false) {
				$newcharstring .= (($charval < 65536) ? getid3_lib::BigEndian2String($charval, 2) : "\x00".'?');
			}
		}
		return $newcharstring;
	}

	// UTF-8 => UTF-16LE
	function iconv_fallback_utf8_utf16le($string, $bom=false) {
		$newcharstring = '';
		if ($bom) {
			$newcharstring .= "\xFF\xFE";
		}
		$offset = 0;
		$stringlength = strlen($string);
		while ($offset < $stringlength) {
			if ((ord($string{$offset}) | 0x07) == 0xF7) {
				// 11110bbb 10bbbbbb 10bbbbbb 10bbbbbb
				$charval = ((ord($string{($offset + 0)}) & 0x07) << 18) &
				           ((ord($string{($offset + 1)}) & 0x3F) << 12) &
				           ((ord($string{($offset + 2)}) & 0x3F) <<  6) &
				            (ord($string{($offset + 3)}) & 0x3F);
				$offset += 4;
			} elseif ((ord($string{$offset}) | 0x0F) == 0xEF) {
				// 1110bbbb 10bbbbbb 10bbbbbb
				$charval = ((ord($string{($offset + 0)}) & 0x0F) << 12) &
				           ((ord($string{($offset + 1)}) & 0x3F) <<  6) &
				            (ord($string{($offset + 2)}) & 0x3F);
				$offset += 3;
			} elseif ((ord($string{$offset}) | 0x1F) == 0xDF) {
				// 110bbbbb 10bbbbbb
				$charval = ((ord($string{($offset + 0)}) & 0x1F) <<  6) &
				            (ord($string{($offset + 1)}) & 0x3F);
				$offset += 2;
			} elseif ((ord($string{$offset}) | 0x7F) == 0x7F) {
				// 0bbbbbbb
				$charval = ord($string{$offset});
				$offset += 1;
			} else {
				// error? maybe throw some warning here?
				$charval = false;
				$offset += 1;
			}
			if ($charval !== false) {
				$newcharstring .= (($charval < 65536) ? getid3_lib::LittleEndian2String($charval, 2) : '?'."\x00");
			}
		}
		return $newcharstring;
	}

	// UTF-8 => UTF-16LE (BOM)
	function iconv_fallback_utf8_utf16($string) {
		return getid3_lib::iconv_fallback_utf8_utf16le($string, true);
	}

	// UTF-16BE => UTF-8
	function iconv_fallback_utf16be_utf8($string) {
		if (substr($string, 0, 2) == "\xFE\xFF") {
			// strip BOM
			$string = substr($string, 2);
		}
		$newcharstring = '';
		for ($i = 0; $i < strlen($string); $i += 2) {
			$charval = getid3_lib::BigEndian2Int(substr($string, $i, 2));
			$newcharstring .= getid3_lib::iconv_fallback_int_utf8($charval);
		}
		return $newcharstring;
	}

	// UTF-16LE => UTF-8
	function iconv_fallback_utf16le_utf8($string) {
		if (substr($string, 0, 2) == "\xFF\xFE") {
			// strip BOM
			$string = substr($string, 2);
		}
		$newcharstring = '';
		for ($i = 0; $i < strlen($string); $i += 2) {
			$charval = getid3_lib::LittleEndian2Int(substr($string, $i, 2));
			$newcharstring .= getid3_lib::iconv_fallback_int_utf8($charval);
		}
		return $newcharstring;
	}

	// UTF-16BE => ISO-8859-1
	function iconv_fallback_utf16be_iso88591($string) {
		if (substr($string, 0, 2) == "\xFE\xFF") {
			// strip BOM
			$string = substr($string, 2);
		}
		$newcharstring = '';
		for ($i = 0; $i < strlen($string); $i += 2) {
			$charval = getid3_lib::BigEndian2Int(substr($string, $i, 2));
			$newcharstring .= (($charval < 256) ? chr($charval) : '?');
		}
		return $newcharstring;
	}

	// UTF-16LE => ISO-8859-1
	function iconv_fallback_utf16le_iso88591($string) {
		if (substr($string, 0, 2) == "\xFF\xFE") {
			// strip BOM
			$string = substr($string, 2);
		}
		$newcharstring = '';
		for ($i = 0; $i < strlen($string); $i += 2) {
			$charval = getid3_lib::LittleEndian2Int(substr($string, $i, 2));
			$newcharstring .= (($charval < 256) ? chr($charval) : '?');
		}
		return $newcharstring;
	}

	// UTF-16 (BOM) => ISO-8859-1
	function iconv_fallback_utf16_iso88591($string) {
		$bom = substr($string, 0, 2);
		if ($bom == "\xFE\xFF") {
			return getid3_lib::iconv_fallback_utf16be_iso88591(substr($string, 2));
		} elseif ($bom == "\xFF\xFE") {
			return getid3_lib::iconv_fallback_utf16le_iso88591(substr($string, 2));
		}
		return $string;
	}

	// UTF-16 (BOM) => UTF-8
	function iconv_fallback_utf16_utf8($string) {
		$bom = substr($string, 0, 2);
		if ($bom == "\xFE\xFF") {
			return getid3_lib::iconv_fallback_utf16be_utf8(substr($string, 2));
		} elseif ($bom == "\xFF\xFE") {
			return getid3_lib::iconv_fallback_utf16le_utf8(substr($string, 2));
		}
		return $string;
	}

	function iconv_fallback($in_charset, $out_charset, $string) {

		if ($in_charset == $out_charset) {
			return $string;
		}

		// iconv() availble
		if (function_exists('iconv')) {

		    if ($converted_string = @iconv($in_charset, $out_charset.'//TRANSLIT', $string)) {
    			switch ($out_charset) {
    				case 'ISO-8859-1':
    					$converted_string = rtrim($converted_string, "\x00");
    					break;
    			}
    			return $converted_string;
    		}

    		// iconv() may sometimes fail with "illegal character in input string" error message
    		// and return an empty string, but returning the unconverted string is more useful
    		return $string;
    	}


        // iconv() not available
		static $ConversionFunctionList = array();
		if (empty($ConversionFunctionList)) {
			$ConversionFunctionList['ISO-8859-1']['UTF-8']    = 'iconv_fallback_iso88591_utf8';
			$ConversionFunctionList['ISO-8859-1']['UTF-16']   = 'iconv_fallback_iso88591_utf16';
			$ConversionFunctionList['ISO-8859-1']['UTF-16BE'] = 'iconv_fallback_iso88591_utf16be';
			$ConversionFunctionList['ISO-8859-1']['UTF-16LE'] = 'iconv_fallback_iso88591_utf16le';
			$ConversionFunctionList['UTF-8']['ISO-8859-1']    = 'iconv_fallback_utf8_iso88591';
			$ConversionFunctionList['UTF-8']['UTF-16']        = 'iconv_fallback_utf8_utf16';
			$ConversionFunctionList['UTF-8']['UTF-16BE']      = 'iconv_fallback_utf8_utf16be';
			$ConversionFunctionList['UTF-8']['UTF-16LE']      = 'iconv_fallback_utf8_utf16le';
			$ConversionFunctionList['UTF-16']['ISO-8859-1']   = 'iconv_fallback_utf16_iso88591';
			$ConversionFunctionList['UTF-16']['UTF-8']        = 'iconv_fallback_utf16_utf8';
			$ConversionFunctionList['UTF-16LE']['ISO-8859-1'] = 'iconv_fallback_utf16le_iso88591';
			$ConversionFunctionList['UTF-16LE']['UTF-8']      = 'iconv_fallback_utf16le_utf8';
			$ConversionFunctionList['UTF-16BE']['ISO-8859-1'] = 'iconv_fallback_utf16be_iso88591';
			$ConversionFunctionList['UTF-16BE']['UTF-8']      = 'iconv_fallback_utf16be_utf8';
		}
		if (isset($ConversionFunctionList[strtoupper($in_charset)][strtoupper($out_charset)])) {
			$ConversionFunction = $ConversionFunctionList[strtoupper($in_charset)][strtoupper($out_charset)];
			return getid3_lib::$ConversionFunction($string);
		}
		die('PHP does not have iconv() support - cannot convert from '.$in_charset.' to '.$out_charset);
	}


	function MultiByteCharString2HTML($string, $charset='ISO-8859-1') {
		$HTMLstring = '';

		switch ($charset) {
			case 'ISO-8859-1':
			case 'ISO8859-1':
			case 'ISO-8859-15':
			case 'ISO8859-15':
			case 'cp866':
			case 'ibm866':
			case '866':
			case 'cp1251':
			case 'Windows-1251':
			case 'win-1251':
			case '1251':
			case 'cp1252':
			case 'Windows-1252':
			case '1252':
			case 'KOI8-R':
			case 'koi8-ru':
			case 'koi8r':
			case 'BIG5':
			case '950':
			case 'GB2312':
			case '936':
			case 'BIG5-HKSCS':
			case 'Shift_JIS':
			case 'SJIS':
			case '932':
			case 'EUC-JP':
			case 'EUCJP':
				$HTMLstring = htmlentities($string, ENT_COMPAT, $charset);
				break;

			case 'UTF-8':
				$strlen = strlen($string);
				for ($i = 0; $i < $strlen; $i++) {
					$char_ord_val = ord($string{$i});
					$charval = 0;
					if ($char_ord_val < 0x80) {
						$charval = $char_ord_val;
					} elseif ((($char_ord_val & 0xF0) >> 4) == 0x0F  &&  $i+3 < $strlen) {
						$charval  = (($char_ord_val & 0x07) << 18);
						$charval += ((ord($string{++$i}) & 0x3F) << 12);
						$charval += ((ord($string{++$i}) & 0x3F) << 6);
						$charval +=  (ord($string{++$i}) & 0x3F);
					} elseif ((($char_ord_val & 0xE0) >> 5) == 0x07  &&  $i+2 < $strlen) {
						$charval  = (($char_ord_val & 0x0F) << 12);
						$charval += ((ord($string{++$i}) & 0x3F) << 6);
						$charval +=  (ord($string{++$i}) & 0x3F);
					} elseif ((($char_ord_val & 0xC0) >> 6) == 0x03  &&  $i+1 < $strlen) {
						$charval  = (($char_ord_val & 0x1F) << 6);
						$charval += (ord($string{++$i}) & 0x3F);
					}
					if (($charval >= 32) && ($charval <= 127)) {
						$HTMLstring .= htmlentities(chr($charval));
					} else {
						$HTMLstring .= '&#'.$charval.';';
					}
				}
				break;

			case 'UTF-16LE':
				for ($i = 0; $i < strlen($string); $i += 2) {
					$charval = getid3_lib::LittleEndian2Int(substr($string, $i, 2));
					if (($charval >= 32) && ($charval <= 127)) {
						$HTMLstring .= chr($charval);
					} else {
						$HTMLstring .= '&#'.$charval.';';
					}
				}
				break;

			case 'UTF-16BE':
				for ($i = 0; $i < strlen($string); $i += 2) {
					$charval = getid3_lib::BigEndian2Int(substr($string, $i, 2));
					if (($charval >= 32) && ($charval <= 127)) {
						$HTMLstring .= chr($charval);
					} else {
						$HTMLstring .= '&#'.$charval.';';
					}
				}
				break;

			default:
				$HTMLstring = 'ERROR: Character set "'.$charset.'" not supported in MultiByteCharString2HTML()';
				break;
		}
		return $HTMLstring;
	}



	function RGADnameLookup($namecode) {
		static $RGADname = array();
		if (empty($RGADname)) {
			$RGADname[0] = 'not set';
			$RGADname[1] = 'Track Gain Adjustment';
			$RGADname[2] = 'Album Gain Adjustment';
		}

		return (isset($RGADname[$namecode]) ? $RGADname[$namecode] : '');
	}


	function RGADoriginatorLookup($originatorcode) {
		static $RGADoriginator = array();
		if (empty($RGADoriginator)) {
			$RGADoriginator[0] = 'unspecified';
			$RGADoriginator[1] = 'pre-set by artist/producer/mastering engineer';
			$RGADoriginator[2] = 'set by user';
			$RGADoriginator[3] = 'determined automatically';
		}

		return (isset($RGADoriginator[$originatorcode]) ? $RGADoriginator[$originatorcode] : '');
	}


	function RGADadjustmentLookup($rawadjustment, $signbit) {
		$adjustment = $rawadjustment / 10;
		if ($signbit == 1) {
			$adjustment *= -1;
		}
		return (float) $adjustment;
	}


	function RGADgainString($namecode, $originatorcode, $replaygain) {
		if ($replaygain < 0) {
			$signbit = '1';
		} else {
			$signbit = '0';
		}
		$storedreplaygain = intval(round($replaygain * 10));
		$gainstring  = str_pad(decbin($namecode), 3, '0', STR_PAD_LEFT);
		$gainstring .= str_pad(decbin($originatorcode), 3, '0', STR_PAD_LEFT);
		$gainstring .= $signbit;
		$gainstring .= str_pad(decbin($storedreplaygain), 9, '0', STR_PAD_LEFT);

		return $gainstring;
	}

	function RGADamplitude2dB($amplitude) {
		return 20 * log10($amplitude);
	}


	function GetDataImageSize($imgData, &$imageinfo) {
		$GetDataImageSize = false;
		if ($tempfilename = tempnam('*', 'getID3')) {
			if ($tmp = @fopen($tempfilename, 'wb')) {
				fwrite($tmp, $imgData);
				fclose($tmp);
				$GetDataImageSize = @GetImageSize($tempfilename, $imageinfo);
			}
			unlink($tempfilename);
		}
		return $GetDataImageSize;
	}

	function ImageTypesLookup($imagetypeid) {
		static $ImageTypesLookup = array();
		if (empty($ImageTypesLookup)) {
			$ImageTypesLookup[1]  = 'gif';
			$ImageTypesLookup[2]  = 'jpeg';
			$ImageTypesLookup[3]  = 'png';
			$ImageTypesLookup[4]  = 'swf';
			$ImageTypesLookup[5]  = 'psd';
			$ImageTypesLookup[6]  = 'bmp';
			$ImageTypesLookup[7]  = 'tiff (little-endian)';
			$ImageTypesLookup[8]  = 'tiff (big-endian)';
			$ImageTypesLookup[9]  = 'jpc';
			$ImageTypesLookup[10] = 'jp2';
			$ImageTypesLookup[11] = 'jpx';
			$ImageTypesLookup[12] = 'jb2';
			$ImageTypesLookup[13] = 'swc';
			$ImageTypesLookup[14] = 'iff';
		}
		return (isset($ImageTypesLookup[$imagetypeid]) ? $ImageTypesLookup[$imagetypeid] : '');
	}

	function CopyTagsToComments(&$ThisFileInfo) {

		// Copy all entries from ['tags'] into common ['comments']
		if (!empty($ThisFileInfo['tags'])) {
			foreach ($ThisFileInfo['tags'] as $tagtype => $tagarray) {
				foreach ($tagarray as $tagname => $tagdata) {
					foreach ($tagdata as $key => $value) {
						if (!empty($value)) {
							if (empty($ThisFileInfo['comments'][$tagname])) {

								// fall through and append value

							} elseif ($tagtype == 'id3v1') {

								$newvaluelength = strlen(trim($value));
								foreach ($ThisFileInfo['comments'][$tagname] as $existingkey => $existingvalue) {
									$oldvaluelength = strlen(trim($existingvalue));
									if (($newvaluelength <= $oldvaluelength) && (substr($existingvalue, 0, $newvaluelength) == trim($value))) {
										// new value is identical but shorter-than (or equal-length to) one already in comments - skip
										break 2;
									}
								}

							} else {

								$newvaluelength = strlen(trim($value));
								foreach ($ThisFileInfo['comments'][$tagname] as $existingkey => $existingvalue) {
									$oldvaluelength = strlen(trim($existingvalue));
									if (($newvaluelength > $oldvaluelength) && (substr(trim($value), 0, strlen($existingvalue)) == $existingvalue)) {
										$ThisFileInfo['comments'][$tagname][$existingkey] = trim($value);
										break 2;
									}
								}

							}
							if (empty($ThisFileInfo['comments'][$tagname]) || !in_array(trim($value), $ThisFileInfo['comments'][$tagname])) {
								$ThisFileInfo['comments'][$tagname][] = trim($value);
							}
						}
					}
				}
			}

			// Copy to ['comments_html']
    		foreach ($ThisFileInfo['comments'] as $field => $values) {
    		    foreach ($values as $index => $value) {
    		        $ThisFileInfo['comments_html'][$field][$index] = str_replace('&#0;', '', getid3_lib::MultiByteCharString2HTML($value, $ThisFileInfo['encoding']));
    		    }
            }
		}
	}


	function EmbeddedLookup($key, $begin, $end, $file, $name) {

		// Cached
		static $cache;
		if (isset($cache[$file][$name])) {
			return @$cache[$file][$name][$key];
		}

		// Init
		$keylength  = strlen($key);
		$line_count = $end - $begin - 7;

		// Open php file
		$fp = fopen($file, 'r');

		// Discard $begin lines
		for ($i = 0; $i < ($begin + 3); $i++) {
			fgets($fp, 1024);
		}

		// Loop thru line
		while (0 < $line_count--) {

			// Read line
			$line = ltrim(fgets($fp, 1024), "\t ");

			// METHOD A: only cache the matching key - less memory but slower on next lookup of not-previously-looked-up key
			//$keycheck = substr($line, 0, $keylength);
			//if ($key == $keycheck)  {
			//	$cache[$file][$name][$keycheck] = substr($line, $keylength + 1);
			//	break;
			//}

			// METHOD B: cache all keys in this lookup - more memory but faster on next lookup of not-previously-looked-up key
			//$cache[$file][$name][substr($line, 0, $keylength)] = trim(substr($line, $keylength + 1));
			@list($ThisKey, $ThisValue) = explode("\t", $line, 2);
			$cache[$file][$name][$ThisKey] = trim($ThisValue);
		}

		// Close and return
		fclose($fp);
		return @$cache[$file][$name][$key];
	}

	function IncludeDependency($filename, $sourcefile, $DieOnFailure=false) {
		global $GETID3_ERRORARRAY;

		if (file_exists($filename)) {
			if (@include_once($filename)) {
				return true;
			} else {
				$diemessage = basename($sourcefile).' depends on '.$filename.', which has errors';
			}
		} else {
			$diemessage = basename($sourcefile).' depends on '.$filename.', which is missing';
		}
		if ($DieOnFailure) {
			die($diemessage);
		} else {
			$GETID3_ERRORARRAY[] = $diemessage;
		}
		return false;
	}

}

?>