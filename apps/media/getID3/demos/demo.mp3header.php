<?php

if (!function_exists('PrintHexBytes')) {
	function PrintHexBytes($string) {
		$returnstring = '';
		for ($i = 0; $i < strlen($string); $i++) {
			$returnstring .= str_pad(dechex(ord(substr($string, $i, 1))), 2, '0', STR_PAD_LEFT).' ';
		}
		return $returnstring;
	}
}

if (!function_exists('PrintTextBytes')) {
	function PrintTextBytes($string) {
		$returnstring = '';
		for ($i = 0; $i < strlen($string); $i++) {
			if (ord(substr($string, $i, 1)) <= 31) {
				$returnstring .= '   ';
			} else {
				$returnstring .= ' '.substr($string, $i, 1).' ';
			}
		}
		return $returnstring;
	}
}

if (!function_exists('FixDBFields')) {
	function FixDBFields($text) {
		return mysql_escape_string($text);
	}
}

if (!function_exists('FixTextFields')) {
	function FixTextFields($text) {
		$text = SafeStripSlashes($text);
		$text = htmlentities($text, ENT_QUOTES);
		return $text;
	}
}

if (!function_exists('SafeStripSlashes')) {
	function SafeStripSlashes($text) {
		if (get_magic_quotes_gpc()) {
			return stripslashes($text);
		}
		return $text;
	}
}


if (!function_exists('table_var_dump')) {
	function table_var_dump($variable) {
		$returnstring = '';
		switch (gettype($variable)) {
			case 'array':
				$returnstring .= '<TABLE BORDER="1" CELLSPACING="0" CELLPADDING="2">';
				foreach ($variable as $key => $value) {
					$returnstring .= '<TR><TD VALIGN="TOP"><B>'.str_replace(chr(0), ' ', $key).'</B></TD>';
					$returnstring .= '<TD VALIGN="TOP">'.gettype($value);
					if (is_array($value)) {
						$returnstring .= '&nbsp;('.count($value).')';
					} elseif (is_string($value)) {
						$returnstring .= '&nbsp;('.strlen($value).')';
					}
					if (($key == 'data') && isset($variable['image_mime']) && isset($variable['dataoffset'])) {
						require_once(GETID3_INCLUDEPATH.'getid3.getimagesize.php');
						$imageinfo = array();
						$imagechunkcheck = GetDataImageSize($value, $imageinfo);
						$DumpedImageSRC = (!empty($_REQUEST['filename']) ? $_REQUEST['filename'] : '.getid3').'.'.$variable['dataoffset'].'.'.ImageTypesLookup($imagechunkcheck[2]);
						if ($tempimagefile = fopen($DumpedImageSRC, 'wb')) {
							fwrite($tempimagefile, $value);
							fclose($tempimagefile);
						}
						$returnstring .= '</TD><TD><IMG SRC="'.$DumpedImageSRC.'" WIDTH="'.$imagechunkcheck[0].'" HEIGHT="'.$imagechunkcheck[1].'"></TD></TR>';
					} else {
						$returnstring .= '</TD><TD>'.table_var_dump($value).'</TD></TR>';
					}
				}
				$returnstring .= '</TABLE>';
				break;

			case 'boolean':
				$returnstring .= ($variable ? 'TRUE' : 'FALSE');
				break;

			case 'integer':
			case 'double':
			case 'float':
				$returnstring .= $variable;
				break;

			case 'object':
			case 'null':
				$returnstring .= string_var_dump($variable);
				break;

			case 'string':
				$variable = str_replace(chr(0), ' ', $variable);
				$varlen = strlen($variable);
				for ($i = 0; $i < $varlen; $i++) {
					if (ereg('['.chr(0x0A).chr(0x0D).' -;0-9A-Za-z]', $variable{$i})) {
						$returnstring .= $variable{$i};
					} else {
						$returnstring .= '&#'.str_pad(ord($variable{$i}), 3, '0', STR_PAD_LEFT).';';
					}
				}
				$returnstring = nl2br($returnstring);
				break;

			default:
				require_once(GETID3_INCLUDEPATH.'getid3.getimagesize.php');
				$imageinfo = array();
				$imagechunkcheck = GetDataImageSize(substr($variable, 0, FREAD_BUFFER_SIZE), $imageinfo);

				if (($imagechunkcheck[2] >= 1) && ($imagechunkcheck[2] <= 3)) {
					$returnstring .= '<TABLE BORDER="1" CELLSPACING="0" CELLPADDING="2">';
					$returnstring .= '<TR><TD><B>type</B></TD><TD>'.ImageTypesLookup($imagechunkcheck[2]).'</TD></TR>';
					$returnstring .= '<TR><TD><B>width</B></TD><TD>'.number_format($imagechunkcheck[0]).' px</TD></TR>';
					$returnstring .= '<TR><TD><B>height</B></TD><TD>'.number_format($imagechunkcheck[1]).' px</TD></TR>';
					$returnstring .= '<TR><TD><B>size</B></TD><TD>'.number_format(strlen($variable)).' bytes</TD></TR></TABLE>';
				} else {
					$returnstring .= nl2br(htmlspecialchars(str_replace(chr(0), ' ', $variable)));
				}
				break;
		}
		return $returnstring;
	}
}

if (!function_exists('string_var_dump')) {
	function string_var_dump($variable) {
		ob_start();
		var_dump($variable);
		$dumpedvariable = ob_get_contents();
		ob_end_clean();
		return $dumpedvariable;
	}
}

if (!function_exists('fileextension')) {
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
}

if (!function_exists('RemoveAccents')) {
	function RemoveAccents($string) {
		// return strtr($string, 'ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ', 'SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy');
		// Revised version by marksteward@hotmail.com
		return strtr(strtr($string, 'ŠŽšžŸÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÑÒÓÔÕÖØÙÚÛÜÝàáâãäåçèéêëìíîïñòóôõöøùúûüýÿ', 'SZszYAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy'), array('Þ' => 'TH', 'þ' => 'th', 'Ð' => 'DH', 'ð' => 'dh', 'ß' => 'ss', 'Œ' => 'OE', 'œ' => 'oe', 'Æ' => 'AE', 'æ' => 'ae', 'µ' => 'u'));
	}
}

if (!function_exists('MoreNaturalSort')) {
	function MoreNaturalSort($ar1, $ar2) {
		if ($ar1 === $ar2) {
			return 0;
		}
		$len1     = strlen($ar1);
		$len2     = strlen($ar2);
		$shortest = min($len1, $len2);
		if (substr($ar1, 0, $shortest) === substr($ar2, 0, $shortest)) {
			// the shorter argument is the beginning of the longer one, like "str" and "string"
			if ($len1 < $len2) {
				return -1;
			} elseif ($len1 > $len2) {
				return 1;
			}
			return 0;
		}
		$ar1 = RemoveAccents(strtolower(trim($ar1)));
		$ar2 = RemoveAccents(strtolower(trim($ar2)));
		$translatearray = array('\''=>'', '"'=>'', '_'=>' ', '('=>'', ')'=>'', '-'=>' ', '  '=>' ', '.'=>'', ','=>'');
		foreach ($translatearray as $key => $val) {
			$ar1 = str_replace($key, $val, $ar1);
			$ar2 = str_replace($key, $val, $ar2);
		}

		if ($ar1 < $ar2) {
			return -1;
		} elseif ($ar1 > $ar2) {
			return 1;
		}
		return 0;
	}
}

if (!function_exists('trunc')) {
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
		if ($truncatednumber <= pow(2, 30)) {
			$truncatednumber = (int) $truncatednumber;
		}
		return $truncatednumber;
	}
}

if (!function_exists('CastAsInt')) {
	function CastAsInt($floatnum) {
		// convert to float if not already
		$floatnum = (float) $floatnum;

		// convert a float to type int, only if possible
		if (trunc($floatnum) == $floatnum) {
			// it's not floating point
			if ($floatnum <= pow(2, 30)) {
				// it's within int range
				$floatnum = (int) $floatnum;
			}
		}
		return $floatnum;
	}
}

if (!function_exists('getmicrotime')) {
	function getmicrotime() {
		list($usec, $sec) = explode(' ', microtime());
		return ((float) $usec + (float) $sec);
	}
}

if (!function_exists('DecimalBinary2Float')) {
	function DecimalBinary2Float($binarynumerator) {
		$numerator   = Bin2Dec($binarynumerator);
		$denominator = Bin2Dec(str_repeat('1', strlen($binarynumerator)));
		return ($numerator / $denominator);
	}
}

if (!function_exists('NormalizeBinaryPoint')) {
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
}

if (!function_exists('Float2BinaryDecimal')) {
	function Float2BinaryDecimal($floatvalue) {
		// http://www.scri.fsu.edu/~jac/MAD3401/Backgrnd/binary.html
		$maxbits = 128; // to how many bits of precision should the calculations be taken?
		$intpart   = trunc($floatvalue);
		$floatpart = abs($floatvalue - $intpart);
		$pointbitstring = '';
		while (($floatpart != 0) && (strlen($pointbitstring) < $maxbits)) {
			$floatpart *= 2;
			$pointbitstring .= (string) trunc($floatpart);
			$floatpart -= trunc($floatpart);
		}
		$binarypointnumber = decbin($intpart).'.'.$pointbitstring;
		return $binarypointnumber;
	}
}

if (!function_exists('Float2String')) {
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
		$normalizedbinary  = NormalizeBinaryPoint(Float2BinaryDecimal($floatvalue), $fractionbits);
		$biasedexponent    = pow(2, $exponentbits - 1) - 1 + $normalizedbinary['exponent']; // (127 or 1023) +/- exponent
		$exponentbitstring = str_pad(decbin($biasedexponent), $exponentbits, '0', STR_PAD_LEFT);
		$fractionbitstring = str_pad(substr($normalizedbinary['normalized'], 2), $fractionbits, '0', STR_PAD_RIGHT);

		return BigEndian2String(Bin2Dec($signbit.$exponentbitstring.$fractionbitstring), $bits % 8, false);
	}
}

if (!function_exists('LittleEndian2Float')) {
	function LittleEndian2Float($byteword) {
		return BigEndian2Float(strrev($byteword));
	}
}

if (!function_exists('BigEndian2Float')) {
	function BigEndian2Float($byteword) {
		// ANSI/IEEE Standard 754-1985, Standard for Binary Floating Point Arithmetic
		// http://www.psc.edu/general/software/packages/ieee/ieee.html
		// http://www.scri.fsu.edu/~jac/MAD3401/Backgrnd/ieee.html

		$bitword = BigEndian2Bin($byteword);
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
				$exponentbits = 16;
				$fractionbits = 64;
				break;

			default:
				return false;
				break;
		}
		$exponentstring = substr($bitword, 1, $exponentbits - 1);
		$fractionstring = substr($bitword, $exponentbits, $fractionbits);
		$exponent = Bin2Dec($exponentstring);
		$fraction = Bin2Dec($fractionstring);

		if (($exponentbits == 16) && ($fractionbits == 64)) {
			// 80-bit
			// As used in Apple AIFF for sample_rate
			// A bit of a hack, but it works ;)
			return pow(2, ($exponent  - 16382)) * DecimalBinary2Float($fractionstring);
		}


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
			$floatvalue = pow(2, (-1 * (pow(2, $exponentbits - 1) - 2))) * DecimalBinary2Float($fractionstring);
			if ($signbit == '1') {
				$floatvalue *= -1;
			}
		} elseif ($exponent != 0) {
			$floatvalue = pow(2, ($exponent - (pow(2, $exponentbits - 1) - 1))) * (1 + DecimalBinary2Float($fractionstring));
			if ($signbit == '1') {
				$floatvalue *= -1;
			}
		}
		return (float) $floatvalue;
	}
}

if (!function_exists('BigEndian2Int')) {
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
					die('ERROR: Cannot have signed integers larger than 32-bits in BigEndian2Int()');
					break;
			}
		}
		return CastAsInt($intvalue);
	}
}

if (!function_exists('LittleEndian2Int')) {
	function LittleEndian2Int($byteword, $signed=false) {
		return BigEndian2Int(strrev($byteword), false, $signed);
	}
}

if (!function_exists('BigEndian2Bin')) {
	function BigEndian2Bin($byteword) {
		$binvalue = '';
		$bytewordlen = strlen($byteword);
		for ($i = 0; $i < $bytewordlen; $i++) {
			$binvalue .= str_pad(decbin(ord($byteword{$i})), 8, '0', STR_PAD_LEFT);
		}
		return $binvalue;
	}
}

if (!function_exists('BigEndian2String')) {
	function BigEndian2String($number, $minbytes=1, $synchsafe=false, $signed=false) {
		if ($number < 0) {
			return false;
		}
		$maskbyte = (($synchsafe || $signed) ? 0x7F : 0xFF);
		$intstring = '';
		if ($signed) {
			if ($minbytes > 4) {
				die('ERROR: Cannot have signed integers larger than 32-bits in BigEndian2String()');
			}
			$number = $number & (0x80 << (8 * ($minbytes - 1)));
		}
		while ($number != 0) {
			$quotient = ($number / ($maskbyte + 1));
			$intstring = chr(ceil(($quotient - floor($quotient)) * $maskbyte)).$intstring;
			$number = floor($quotient);
		}
		return str_pad($intstring, $minbytes, chr(0), STR_PAD_LEFT);
	}
}

if (!function_exists('Dec2Bin')) {
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
}

if (!function_exists('Bin2Dec')) {
	function Bin2Dec($binstring) {
		$decvalue = 0;
		for ($i = 0; $i < strlen($binstring); $i++) {
			$decvalue += ((int) substr($binstring, strlen($binstring) - $i - 1, 1)) * pow(2, $i);
		}
		return CastAsInt($decvalue);
	}
}

if (!function_exists('Bin2String')) {
	function Bin2String($binstring) {
		// return 'hi' for input of '0110100001101001'
		$string = '';
		$binstringreversed = strrev($binstring);
		for ($i = 0; $i < strlen($binstringreversed); $i += 8) {
			$string = chr(Bin2Dec(strrev(substr($binstringreversed, $i, 8)))).$string;
		}
		return $string;
	}
}

if (!function_exists('LittleEndian2String')) {
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
		return str_pad($intstring, $minbytes, chr(0), STR_PAD_RIGHT);
	}
}

if (!function_exists('Bool2IntString')) {
	function Bool2IntString($intvalue) {
		return ($intvalue ? '1' : '0');
	}
}

if (!function_exists('IntString2Bool')) {
	function IntString2Bool($char) {
		if ($char == '1') {
			return true;
		} elseif ($char == '0') {
			return false;
		}
		return null;
	}
}

if (!function_exists('InverseBoolean')) {
	function InverseBoolean($value) {
		return ($value ? false : true);
	}
}

if (!function_exists('DeUnSynchronise')) {
	function DeUnSynchronise($data) {
		return str_replace(chr(0xFF).chr(0x00), chr(0xFF), $data);
	}
}

if (!function_exists('Unsynchronise')) {
	function Unsynchronise($data) {
		// Whenever a false synchronisation is found within the tag, one zeroed
		// byte is inserted after the first false synchronisation byte. The
		// format of a correct sync that should be altered by ID3 encoders is as
		// follows:
		//      %11111111 111xxxxx
		// And should be replaced with:
		//      %11111111 00000000 111xxxxx
		// This has the side effect that all $FF 00 combinations have to be
		// altered, so they won't be affected by the decoding process. Therefore
		// all the $FF 00 combinations have to be replaced with the $FF 00 00
		// combination during the unsynchronisation.

		$data = str_replace(chr(0xFF).chr(0x00), chr(0xFF).chr(0x00).chr(0x00), $data);
		$unsyncheddata = '';
		for ($i = 0; $i < strlen($data); $i++) {
			$thischar = $data{$i};
			$unsyncheddata .= $thischar;
			if ($thischar == chr(255)) {
				$nextchar = ord(substr($data, $i + 1, 1));
				if (($nextchar | 0xE0) == 0xE0) {
					// previous byte = 11111111, this byte = 111?????
					$unsyncheddata .= chr(0);
				}
			}
		}
		return $unsyncheddata;
	}
}

if (!function_exists('is_hash')) {
	function is_hash($var) {
		// written by dev-null@christophe.vg
		// taken from http://www.php.net/manual/en/function.array-merge-recursive.php
		if (is_array($var)) {
			$keys = array_keys($var);
			$all_num = true;
			for ($i = 0; $i < count($keys); $i++) {
				if (is_string($keys[$i])) {
					return true;
				}
			}
		}
		return false;
	}
}

if (!function_exists('array_join_merge')) {
	function array_join_merge($arr1, $arr2) {
		// written by dev-null@christophe.vg
		// taken from http://www.php.net/manual/en/function.array-merge-recursive.php
		if (is_array($arr1) && is_array($arr2)) {
			// the same -> merge
			$new_array = array();

			if (is_hash($arr1) && is_hash($arr2)) {
				// hashes -> merge based on keys
				$keys = array_merge(array_keys($arr1), array_keys($arr2));
				foreach ($keys as $key) {
					$new_array[$key] = array_join_merge(@$arr1[$key], @$arr2[$key]);
				}
			} else {
				// two real arrays -> merge
				$new_array = array_reverse(array_unique(array_reverse(array_merge($arr1,$arr2))));
			}
			return $new_array;
		} else {
			// not the same ... take new one if defined, else the old one stays
			return $arr2 ? $arr2 : $arr1;
		}
	}
}

if (!function_exists('array_merge_clobber')) {
	function array_merge_clobber($array1, $array2) {
		// written by kc@hireability.com
		// taken from http://www.php.net/manual/en/function.array-merge-recursive.php
		if (!is_array($array1) || !is_array($array2)) {
			return false;
		}
		$newarray = $array1;
		foreach ($array2 as $key => $val) {
			if (is_array($val) && isset($newarray[$key]) && is_array($newarray[$key])) {
				$newarray[$key] = array_merge_clobber($newarray[$key], $val);
			} else {
				$newarray[$key] = $val;
			}
		}
		return $newarray;
	}
}

if (!function_exists('array_merge_noclobber')) {
	function array_merge_noclobber($array1, $array2) {
		if (!is_array($array1) || !is_array($array2)) {
			return false;
		}
		$newarray = $array1;
		foreach ($array2 as $key => $val) {
			if (is_array($val) && isset($newarray[$key]) && is_array($newarray[$key])) {
				$newarray[$key] = array_merge_noclobber($newarray[$key], $val);
			} elseif (!isset($newarray[$key])) {
				$newarray[$key] = $val;
			}
		}
		return $newarray;
	}
}

if (!function_exists('RoughTranslateUnicodeToASCII')) {
	function RoughTranslateUnicodeToASCII($rawdata, $frame_textencoding) {
		// rough translation of data for application that can't handle Unicode data

		$tempstring = '';
		switch ($frame_textencoding) {
			case 0: // ISO-8859-1. Terminated with $00.
				$asciidata = $rawdata;
				break;

			case 1: // UTF-16 encoded Unicode with BOM. Terminated with $00 00.
				$asciidata = $rawdata;
				if (substr($asciidata, 0, 2) == chr(0xFF).chr(0xFE)) {
					// remove BOM, only if present (it should be, but...)
					$asciidata = substr($asciidata, 2);
				}
				if (substr($asciidata, strlen($asciidata) - 2, 2) == chr(0).chr(0)) {
					$asciidata = substr($asciidata, 0, strlen($asciidata) - 2); // remove terminator, only if present (it should be, but...)
				}
				for ($i = 0; $i < strlen($asciidata); $i += 2) {
					if ((ord($asciidata{$i}) <= 0x7F) || (ord($asciidata{$i}) >= 0xA0)) {
						$tempstring .= $asciidata{$i};
					} else {
						$tempstring .= '?';
					}
				}
				$asciidata = $tempstring;
				break;

			case 2: // UTF-16BE encoded Unicode without BOM. Terminated with $00 00.
				$asciidata = $rawdata;
				if (substr($asciidata, strlen($asciidata) - 2, 2) == chr(0).chr(0)) {
					$asciidata = substr($asciidata, 0, strlen($asciidata) - 2); // remove terminator, only if present (it should be, but...)
				}
				for ($i = 0; $i < strlen($asciidata); $i += 2) {
					if ((ord($asciidata{$i}) <= 0x7F) || (ord($asciidata{$i}) >= 0xA0)) {
						$tempstring .= $asciidata{$i};
					} else {
						$tempstring .= '?';
					}
				}
				$asciidata = $tempstring;
				break;

			case 3: // UTF-8 encoded Unicode. Terminated with $00.
				$asciidata = utf8_decode($rawdata);
				break;

			case 255: // Unicode, Big-Endian. Terminated with $00 00.
				$asciidata = $rawdata;
				if (substr($asciidata, strlen($asciidata) - 2, 2) == chr(0).chr(0)) {
					$asciidata = substr($asciidata, 0, strlen($asciidata) - 2); // remove terminator, only if present (it should be, but...)
				}
				for ($i = 0; ($i + 1) < strlen($asciidata); $i += 2) {
					if ((ord($asciidata{($i + 1)}) <= 0x7F) || (ord($asciidata{($i + 1)}) >= 0xA0)) {
						$tempstring .= $asciidata{($i + 1)};
					} else {
						$tempstring .= '?';
					}
				}
				$asciidata = $tempstring;
				break;


			default:
				// shouldn't happen, but in case $frame_textencoding is not 1 <= $frame_textencoding <= 4
				// just pass the data through unchanged.
				$asciidata = $rawdata;
				break;
		}
		if (substr($asciidata, strlen($asciidata) - 1, 1) == chr(0)) {
			// remove null terminator, if present
			$asciidata = NoNullString($asciidata);
		}
		return $asciidata;
		// return str_replace(chr(0), '', $asciidata); // just in case any nulls slipped through
	}
}

if (!function_exists('PlaytimeString')) {
	function PlaytimeString($playtimeseconds) {
		$contentseconds = round((($playtimeseconds / 60) - floor($playtimeseconds / 60)) * 60);
		$contentminutes = floor($playtimeseconds / 60);
		if ($contentseconds >= 60) {
			$contentseconds -= 60;
			$contentminutes++;
		}
		return number_format($contentminutes).':'.str_pad($contentseconds, 2, 0, STR_PAD_LEFT);
	}
}

if (!function_exists('CloseMatch')) {
	function CloseMatch($value1, $value2, $tolerance) {
		return (abs($value1 - $value2) <= $tolerance);
	}
}

if (!function_exists('ID3v1matchesID3v2')) {
	function ID3v1matchesID3v2($id3v1, $id3v2) {

		$requiredindices = array('title', 'artist', 'album', 'year', 'genre', 'comment');
		foreach ($requiredindices as $requiredindex) {
			if (!isset($id3v1["$requiredindex"])) {
				$id3v1["$requiredindex"] = '';
			}
			if (!isset($id3v2["$requiredindex"])) {
				$id3v2["$requiredindex"] = '';
			}
		}

		if (trim($id3v1['title']) != trim(substr($id3v2['title'], 0, 30))) {
			return false;
		}
		if (trim($id3v1['artist']) != trim(substr($id3v2['artist'], 0, 30))) {
			return false;
		}
		if (trim($id3v1['album']) != trim(substr($id3v2['album'], 0, 30))) {
			return false;
		}
		if (trim($id3v1['year']) != trim(substr($id3v2['year'], 0, 4))) {
			return false;
		}
		if (trim($id3v1['genre']) != trim($id3v2['genre'])) {
			return false;
		}
		if (isset($id3v1['track'])) {
			if (!isset($id3v1['track']) || (trim($id3v1['track']) != trim($id3v2['track']))) {
				return false;
			}
			if (trim($id3v1['comment']) != trim(substr($id3v2['comment'], 0, 28))) {
				return false;
			}
		} else {
			if (trim($id3v1['comment']) != trim(substr($id3v2['comment'], 0, 30))) {
				return false;
			}
		}
		return true;
	}
}

if (!function_exists('FILETIMEtoUNIXtime')) {
	function FILETIMEtoUNIXtime($FILETIME, $round=true) {
		// FILETIME is a 64-bit unsigned integer representing
		// the number of 100-nanosecond intervals since January 1, 1601
		// UNIX timestamp is number of seconds since January 1, 1970
		// 116444736000000000 = 10000000 * 60 * 60 * 24 * 365 * 369 + 89 leap days
		if ($round) {
			return round(($FILETIME - 116444736000000000) / 10000000);
		}
		return ($FILETIME - 116444736000000000) / 10000000;
	}
}

if (!function_exists('GUIDtoBytestring')) {
	function GUIDtoBytestring($GUIDstring) {
		// Microsoft defines these 16-byte (128-bit) GUIDs in the strangest way:
		// first 4 bytes are in little-endian order
		// next 2 bytes are appended in little-endian order
		// next 2 bytes are appended in little-endian order
		// next 2 bytes are appended in big-endian order
		// next 6 bytes are appended in big-endian order

		// AaBbCcDd-EeFf-GgHh-IiJj-KkLlMmNnOoPp is stored as this 16-byte string:
		// $Dd $Cc $Bb $Aa $Ff $Ee $Hh $Gg $Ii $Jj $Kk $Ll $Mm $Nn $Oo $Pp

		$hexbytecharstring  = chr(hexdec(substr($GUIDstring,  6, 2)));
		$hexbytecharstring .= chr(hexdec(substr($GUIDstring,  4, 2)));
		$hexbytecharstring .= chr(hexdec(substr($GUIDstring,  2, 2)));
		$hexbytecharstring .= chr(hexdec(substr($GUIDstring,  0, 2)));

		$hexbytecharstring .= chr(hexdec(substr($GUIDstring, 11, 2)));
		$hexbytecharstring .= chr(hexdec(substr($GUIDstring,  9, 2)));

		$hexbytecharstring .= chr(hexdec(substr($GUIDstring, 16, 2)));
		$hexbytecharstring .= chr(hexdec(substr($GUIDstring, 14, 2)));

		$hexbytecharstring .= chr(hexdec(substr($GUIDstring, 19, 2)));
		$hexbytecharstring .= chr(hexdec(substr($GUIDstring, 21, 2)));

		$hexbytecharstring .= chr(hexdec(substr($GUIDstring, 24, 2)));
		$hexbytecharstring .= chr(hexdec(substr($GUIDstring, 26, 2)));
		$hexbytecharstring .= chr(hexdec(substr($GUIDstring, 28, 2)));
		$hexbytecharstring .= chr(hexdec(substr($GUIDstring, 30, 2)));
		$hexbytecharstring .= chr(hexdec(substr($GUIDstring, 32, 2)));
		$hexbytecharstring .= chr(hexdec(substr($GUIDstring, 34, 2)));

		return $hexbytecharstring;
	}
}

if (!function_exists('BytestringToGUID')) {
	function BytestringToGUID($Bytestring) {
		$GUIDstring  = str_pad(dechex(ord($Bytestring{3})),  2, '0', STR_PAD_LEFT);
		$GUIDstring .= str_pad(dechex(ord($Bytestring{2})),  2, '0', STR_PAD_LEFT);
		$GUIDstring .= str_pad(dechex(ord($Bytestring{1})),  2, '0', STR_PAD_LEFT);
		$GUIDstring .= str_pad(dechex(ord($Bytestring{0})),  2, '0', STR_PAD_LEFT);
		$GUIDstring .= '-';
		$GUIDstring .= str_pad(dechex(ord($Bytestring{5})),  2, '0', STR_PAD_LEFT);
		$GUIDstring .= str_pad(dechex(ord($Bytestring{4})),  2, '0', STR_PAD_LEFT);
		$GUIDstring .= '-';
		$GUIDstring .= str_pad(dechex(ord($Bytestring{7})),  2, '0', STR_PAD_LEFT);
		$GUIDstring .= str_pad(dechex(ord($Bytestring{6})),  2, '0', STR_PAD_LEFT);
		$GUIDstring .= '-';
		$GUIDstring .= str_pad(dechex(ord($Bytestring{8})),  2, '0', STR_PAD_LEFT);
		$GUIDstring .= str_pad(dechex(ord($Bytestring{9})),  2, '0', STR_PAD_LEFT);
		$GUIDstring .= '-';
		$GUIDstring .= str_pad(dechex(ord($Bytestring{10})), 2, '0', STR_PAD_LEFT);
		$GUIDstring .= str_pad(dechex(ord($Bytestring{11})), 2, '0', STR_PAD_LEFT);
		$GUIDstring .= str_pad(dechex(ord($Bytestring{12})), 2, '0', STR_PAD_LEFT);
		$GUIDstring .= str_pad(dechex(ord($Bytestring{13})), 2, '0', STR_PAD_LEFT);
		$GUIDstring .= str_pad(dechex(ord($Bytestring{14})), 2, '0', STR_PAD_LEFT);
		$GUIDstring .= str_pad(dechex(ord($Bytestring{15})), 2, '0', STR_PAD_LEFT);

		return strtoupper($GUIDstring);
	}
}

if (!function_exists('BitrateColor')) {
	function BitrateColor($bitrate) {
		$bitrate /= 3; // scale from 1-768kbps to 1-256kbps
		$bitrate--;    // scale from 1-256kbps to 0-255kbps
		$bitrate = max($bitrate, 0);
		$bitrate = min($bitrate, 255);
		//$bitrate = max($bitrate, 32);
		//$bitrate = min($bitrate, 143);
		//$bitrate = ($bitrate * 2) - 32;

		$Rcomponent = max(255 - ($bitrate * 2), 0);
		$Gcomponent = max(($bitrate * 2) - 255, 0);
		if ($bitrate > 127) {
			$Bcomponent = max((255 - $bitrate) * 2, 0);
		} else {
			$Bcomponent = max($bitrate * 2, 0);
		}
		return str_pad(dechex($Rcomponent), 2, '0', STR_PAD_LEFT).str_pad(dechex($Gcomponent), 2, '0', STR_PAD_LEFT).str_pad(dechex($Bcomponent), 2, '0', STR_PAD_LEFT);
	}
}

if (!function_exists('BitrateText')) {
	function BitrateText($bitrate) {
		return '<SPAN STYLE="color: #'.BitrateColor($bitrate).'">'.round($bitrate).' kbps</SPAN>';
	}
}

if (!function_exists('image_type_to_mime_type')) {
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
}

if (!function_exists('utf8_decode')) {
	// PHP has this function built-in if it's configured with the --with-xml option
	// This version of the function is only provided in case XML isn't installed
	function utf8_decode($utf8text) {
		// http://www.php.net/manual/en/function.utf8-encode.php
		// bytes  bits  representation
		//   1     7    0bbbbbbb
		//   2     11   110bbbbb 10bbbbbb
		//   3     16   1110bbbb 10bbbbbb 10bbbbbb
		//   4     21   11110bbb 10bbbbbb 10bbbbbb 10bbbbbb

		$utf8length = strlen($utf8text);
		$decodedtext = '';
		for ($i = 0; $i < $utf8length; $i++) {
			if ((ord($utf8text{$i}) & 0x80) == 0) {
				$decodedtext .= $utf8text{$i};
			} elseif ((ord($utf8text{$i}) & 0xF0) == 0xF0) {
				$decodedtext .= '?';
				$i += 3;
			} elseif ((ord($utf8text{$i}) & 0xE0) == 0xE0) {
				$decodedtext .= '?';
				$i += 2;
			} elseif ((ord($utf8text{$i}) & 0xC0) == 0xC0) {
				//   2     11   110bbbbb 10bbbbbb
				$decodedchar = Bin2Dec(substr(Dec2Bin(ord($utf8text{$i})), 3, 5).substr(Dec2Bin(ord($utf8text{($i + 1)})), 2, 6));
				if ($decodedchar <= 255) {
					$decodedtext .= chr($decodedchar);
				} else {
					$decodedtext .= '?';
				}
				$i += 1;
			}
		}
		return $decodedtext;
	}
}

if (!function_exists('DateMac2Unix')) {
	function DateMac2Unix($macdate) {
		// Macintosh timestamp: seconds since 00:00h January 1, 1904
		// UNIX timestamp:      seconds since 00:00h January 1, 1970
		return CastAsInt($macdate - 2082844800);
	}
}


if (!function_exists('FixedPoint8_8')) {
	function FixedPoint8_8($rawdata) {
		return BigEndian2Int(substr($rawdata, 0, 1)) + (float) (BigEndian2Int(substr($rawdata, 1, 1)) / pow(2, 8));
	}
}


if (!function_exists('FixedPoint16_16')) {
	function FixedPoint16_16($rawdata) {
		return BigEndian2Int(substr($rawdata, 0, 2)) + (float) (BigEndian2Int(substr($rawdata, 2, 2)) / pow(2, 16));
	}
}


if (!function_exists('FixedPoint2_30')) {
	function FixedPoint2_30($rawdata) {
		$binarystring = BigEndian2Bin($rawdata);
		return Bin2Dec(substr($binarystring, 0, 2)) + (float) (Bin2Dec(substr($binarystring, 2, 30)) / pow(2, 30));
	}
}


if (!function_exists('Pascal2String')) {
	function Pascal2String($pascalstring) {
		// Pascal strings have 1 byte at the beginning saying how many chars are in the string
		return substr($pascalstring, 1);
	}
}

if (!function_exists('NoNullString')) {
	function NoNullString($nullterminatedstring) {
		// remove the single null terminator on null terminated strings
		if (substr($nullterminatedstring, strlen($nullterminatedstring) - 1, 1) === chr(0)) {
			return substr($nullterminatedstring, 0, strlen($nullterminatedstring) - 1);
		}
		return $nullterminatedstring;
	}
}

if (!function_exists('FileSizeNiceDisplay')) {
	function FileSizeNiceDisplay($filesize, $precision=2) {
		if ($filesize < 1000) {
			$sizeunit  = 'bytes';
			$precision = 0;
		} else {
			$filesize /= 1024;
			$sizeunit = 'kB';
		}
		if ($filesize >= 1000) {
			$filesize /= 1024;
			$sizeunit = 'MB';
		}
		if ($filesize >= 1000) {
			$filesize /= 1024;
			$sizeunit = 'GB';
		}
		return number_format($filesize, $precision).' '.$sizeunit;
	}
}

if (!function_exists('DOStime2UNIXtime')) {
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

		return mktime($UNIXhour, $UNIXminute, $UNIXsecond, $UNIXmonth, $UNIXday, $UNIXyear);
	}
}

if (!function_exists('CreateDeepArray')) {
	function CreateDeepArray($ArrayPath, $Separator, $Value) {
		// assigns $Value to a nested array path:
		//   $foo = CreateDeepArray('/path/to/my', '/', 'file.txt')
		// is the same as:
		//   $foo = array('path'=>array('to'=>'array('my'=>array('file.txt'))));
		// or
		//   $foo['path']['to']['my'] = 'file.txt';
		while ($ArrayPath{0} == $Separator) {
			$ArrayPath = substr($ArrayPath, 1);
		}
		if (($pos = strpos($ArrayPath, $Separator)) !== false) {
			$ReturnedArray[substr($ArrayPath, 0, $pos)] = CreateDeepArray(substr($ArrayPath, $pos + 1), $Separator, $Value);
		} else {
			$ReturnedArray["$ArrayPath"] = $Value;
		}
		return $ReturnedArray;
	}
}

if (!function_exists('md5_file')) {
	// Allan Hansen <ah@artemis.dk>
	// md5_file() exists in PHP 4.2.0.
	// The following works under UNIX only, but dies on windows
	function md5_file($file) {
		if (substr(php_uname(), 0, 7) == 'Windows') {
			die('PHP 4.2.0 or newer required for md5_file()');
		}

		$file = str_replace('`', '\\`', $file);
		if (ereg("^([0-9a-f]{32})[ \t\n\r]", `md5sum "$file"`, $r)) {
			return $r[1];
		}
		return false;
	}
}

if (!function_exists('md5_data')) {
	// Allan Hansen <ah@artemis.dk>
	// md5_data() - returns md5sum for a file from startuing position to absolute end position

	function md5_data($file, $offset, $end, $invertsign=false) {
		// first try and create a temporary file in the same directory as the file being scanned
		if (($dataMD5filename = tempnam(dirname($file), eregi_replace('[^[:alnum:]]', '', basename($file)))) === false) {
			// if that fails, create a temporary file in the system temp directory
			if (($dataMD5filename = tempnam('/tmp', 'getID3')) === false) {
				// if that fails, create a temporary file in the current directory
				if (($dataMD5filename = tempnam('.', eregi_replace('[^[:alnum:]]', '', basename($file)))) === false) {
					// can't find anywhere to create a temp file, just die
					return false;
				}
			}
		}
		$md5 = false;
		set_time_limit(max(filesize($file) / 1000000, 30));

		// copy parts of file
		if ($fp = @fopen($file, 'rb')) {

			if ($MD5fp = @fopen($dataMD5filename, 'wb')) {

				if ($invertsign) {
					// Load conversion lookup strings for 8-bit unsigned->signed conversion below
	                $from = '';
	                $to   = '';
					for ($i = 0; $i < 128; $i++) {
						$from .= chr($i);
						$to   .= chr($i + 128);
					}
					for ($i = 128; $i < 256; $i++) {
						$from .= chr($i);
						$to   .= chr($i - 128);
					}
				}

				fseek($fp, $offset, SEEK_SET);
				$byteslefttowrite = $end - $offset;
				while (($byteslefttowrite > 0) && ($buffer = fread($fp, FREAD_BUFFER_SIZE))) {
					if ($invertsign) {
						// Possibly FLAC-specific (?)
						// FLAC calculates the MD5sum of the source data of 8-bit files
						// not on the actual byte values in the source file, but of those
						// values converted from unsigned to signed, or more specifcally,
						// with the MSB inverted. ex: 01 -> 81; F5 -> 75; etc

						// Therefore, 8-bit WAV data has to be converted before getting the
						// md5_data value so as to match the FLAC value

						// Flip the MSB for each byte in the buffer before copying
						$buffer = strtr($buffer, $from, $to);
					}
					$byteswritten = fwrite($MD5fp, $buffer, $byteslefttowrite);
					$byteslefttowrite -= $byteswritten;
				}
				fclose($MD5fp);
				$md5 = md5_file($dataMD5filename);

			}
			fclose($fp);

		}
		unlink($dataMD5filename);
		return $md5;
	}
}

if (!function_exists('TwosCompliment2Decimal')) {
	function TwosCompliment2Decimal($BinaryValue) {
		// http://sandbox.mc.edu/~bennet/cs110/tc/tctod.html
		// First check if the number is negative or positive by looking at the sign bit.
		// If it is positive, simply convert it to decimal.
		// If it is negative, make it positive by inverting the bits and adding one.
		// Then, convert the result to decimal.
		// The negative of this number is the value of the original binary.

		if ($BinaryValue & 0x80) {

			// negative number
			return (0 - ((~$BinaryValue & 0xFF) + 1));

		} else {

			// positive number
			return $BinaryValue;

		}

	}
}

if (!function_exists('LastArrayElement')) {
	function LastArrayElement($MyArray) {
		if (!is_array($MyArray)) {
			return false;
		}
		if (empty($MyArray)) {
			return null;
		}
		foreach ($MyArray as $key => $value) {
		}
		return $value;
	}
}

if (!function_exists('safe_inc')) {
	function safe_inc(&$variable, $increment=1) {
		if (isset($variable)) {
			$variable += $increment;
		} else {
			$variable = $increment;
		}
		return true;
	}
}

if (!function_exists('CalculateCompressionRatioVideo')) {
	function CalculateCompressionRatioVideo(&$ThisFileInfo) {
		if (empty($ThisFileInfo['video'])) {
			return false;
		}
		if (empty($ThisFileInfo['video']['resolution_x']) || empty($ThisFileInfo['video']['resolution_y'])) {
			return false;
		}
		if (empty($ThisFileInfo['video']['bits_per_sample'])) {
			return false;
		}

		switch ($ThisFileInfo['video']['dataformat']) {
			case 'bmp':
			case 'gif':
			case 'jpeg':
			case 'jpg':
			case 'png':
			case 'tiff':
				$FrameRate = 1;
				$PlaytimeSeconds = 1;
				$BitrateCompressed = $ThisFileInfo['filesize'] * 8;
				break;

			default:
				if (!empty($ThisFileInfo['video']['frame_rate'])) {
					$FrameRate = $ThisFileInfo['video']['frame_rate'];
				} else {
					return false;
				}
				if (!empty($ThisFileInfo['playtime_seconds'])) {
					$PlaytimeSeconds = $ThisFileInfo['playtime_seconds'];
				} else {
					return false;
				}
				if (!empty($ThisFileInfo['video']['bitrate'])) {
					$BitrateCompressed = $ThisFileInfo['video']['bitrate'];
				} else {
					return false;
				}
				break;
		}
		$BitrateUncompressed = $ThisFileInfo['video']['resolution_x'] * $ThisFileInfo['video']['resolution_y'] * $ThisFileInfo['video']['bits_per_sample'] * $FrameRate;

		$ThisFileInfo['video']['compression_ratio'] = $BitrateCompressed / $BitrateUncompressed;
		return true;
	}
}

if (!function_exists('CalculateCompressionRatioAudio')) {
	function CalculateCompressionRatioAudio(&$ThisFileInfo) {
		if (empty($ThisFileInfo['audio']['bitrate']) || empty($ThisFileInfo['audio']['channels']) || empty($ThisFileInfo['audio']['sample_rate']) || empty($ThisFileInfo['audio']['bits_per_sample'])) {
			return false;
		}
		$ThisFileInfo['audio']['compression_ratio'] = $ThisFileInfo['audio']['bitrate'] / ($ThisFileInfo['audio']['channels'] * $ThisFileInfo['audio']['sample_rate'] * $ThisFileInfo['audio']['bits_per_sample']);
		return true;
	}
}

if (!function_exists('IsValidMIMEstring')) {
	function IsValidMIMEstring($mimestring) {
	    if ((strlen($mimestring) >= 3) && (strpos($mimestring, '/') > 0) && (strpos($mimestring, '/') < (strlen($mimestring) - 1))) {
			return true;
	    }
	    return false;
	}
}

if (!function_exists('IsWithinBitRange')) {
	function IsWithinBitRange($number, $maxbits, $signed=false) {
	    if ($signed) {
			if (($number > (0 - pow(2, $maxbits - 1))) && ($number <= pow(2, $maxbits - 1))) {
				return true;
			}
	    } else {
			if (($number >= 0) && ($number <= pow(2, $maxbits))) {
				return true;
			}
	    }
	    return false;
	}
}

if (!function_exists('safe_parse_url')) {
	function safe_parse_url($url) {
	    $parts = @parse_url($url);
	    $parts['scheme'] = (isset($parts['scheme']) ? $parts['scheme'] : '');
	    $parts['host']   = (isset($parts['host'])   ? $parts['host']   : '');
	    $parts['user']   = (isset($parts['user'])   ? $parts['user']   : '');
	    $parts['pass']   = (isset($parts['pass'])   ? $parts['pass']   : '');
	    $parts['path']   = (isset($parts['path'])   ? $parts['path']   : '');
	    $parts['query']  = (isset($parts['query'])  ? $parts['query']  : '');
	    return $parts;
	}
}

if (!function_exists('IsValidURL')) {
	function IsValidURL($url, $allowUserPass=false) {
	    if ($url == '') {
			return false;
	    }
	    if ($allowUserPass !== true) {
			if (strstr($url, '@')) {
				// in the format http://user:pass@example.com  or http://user@example.com
				// but could easily be somebody incorrectly entering an email address in place of a URL
				return false;
			}
	    }
	    if ($parts = safe_parse_url($url)) {
			if (($parts['scheme'] != 'http') && ($parts['scheme'] != 'https') && ($parts['scheme'] != 'ftp') && ($parts['scheme'] != 'gopher')) {
				return false;
			} elseif (!eregi("^[[:alnum:]]([-.]?[0-9a-z])*\.[a-z]{2,3}$", $parts['host'], $regs) && !IsValidDottedIP($parts['host'])) {
				return false;
			} elseif (!eregi("^([[:alnum:]-]|[\_])*$", $parts['user'], $regs)) {
				return false;
			} elseif (!eregi("^([[:alnum:]-]|[\_])*$", $parts['pass'], $regs)) {
				return false;
			} elseif (!eregi("^[[:alnum:]/_\.@~-]*$", $parts['path'], $regs)) {
				return false;
			} elseif (!eregi("^[[:alnum:]?&=+:;_()%#/,\.-]*$", $parts['query'], $regs)) {
				return false;
			} else {
				return true;
			}
		}
		return false;
	}
}

echo '<FORM ACTION="'.$_SERVER['PHP_SELF'].'" METHOD="POST">';
echo 'Enter 4 hex bytes of MPEG-audio header (ie <I>FF FA 92 44</I>)<BR>';
echo '<INPUT TYPE="TEXT" NAME="HeaderHexBytes" VALUE="'.(isset($_POST['HeaderHexBytes']) ? strtoupper($_POST['HeaderHexBytes']) : '').'" SIZE="11" MAXLENGTH="11">';
echo '<INPUT TYPE="SUBMIT" NAME="Analyze" VALUE="Analyze"></FORM>';
echo '<HR>';

echo '<FORM ACTION="'.$_SERVER['PHP_SELF'].'" METHOD="POST">';
echo 'Generate a MPEG-audio 4-byte header from these values:<BR>';
echo '<TABLE BORDER="0">';

$MPEGgenerateValues = array(
								'version'=>array('1', '2', '2.5'),
								'layer'=>array('I', 'II', 'III'),
								'protection'=>array('Y', 'N'),
								'bitrate'=>array('free', '8', '16', '24', '32', '40', '48', '56', '64', '80', '96', '112', '128', '144', '160', '176', '192', '224', '256', '288', '320', '352', '384', '416', '448'),
								'frequency'=>array('8000', '11025', '12000', '16000', '22050', '24000', '32000', '44100', '48000'),
								'padding'=>array('Y', 'N'),
								'private'=>array('Y', 'N'),
								'channelmode'=>array('stereo', 'joint stereo', 'dual channel', 'mono'),
								'modeextension'=>array('none', 'IS', 'MS', 'IS+MS', '4-31', '8-31', '12-31', '16-31'),
								'copyright'=>array('Y', 'N'),
								'original'=>array('Y', 'N'),
								'emphasis'=>array('none', '50/15ms', 'CCIT J.17')
							);

foreach ($MPEGgenerateValues as $name => $dataarray) {
    echo '<TR><TH>'.$name.':</TH><TD><SELECT NAME="'.$name.'">';
    foreach ($dataarray as $key => $value) {
		echo '<OPTION'.((isset($_POST["$name"]) && ($_POST["$name"] == $value)) ? ' SELECTED' : '').'>'.$value.'</OPTION>';
    }
    echo '</SELECT></TD></TR>';
}

if (isset($_POST['bitrate'])) {
	echo '<TR><TH>Frame Length:</TH><TD>'.(int) MPEGaudioFrameLength($_POST['bitrate'], $_POST['version'], $_POST['layer'], (($_POST['padding'] == 'Y') ? '1' : '0'), $_POST['frequency']).'</TD></TR>';
}
echo '</TABLE>';
echo '<INPUT TYPE="SUBMIT" NAME="Generate" VALUE="Generate"></FORM>';
echo '<HR>';


if (isset($_POST['Analyze']) && $_POST['HeaderHexBytes']) {

    $headerbytearray = explode(' ', $_POST['HeaderHexBytes']);
    if (count($headerbytearray) != 4) {
		die('Invalid byte pattern');
    }
    $headerstring = '';
    foreach ($headerbytearray as $textbyte) {
		$headerstring .= chr(hexdec($textbyte));
    }

    $MP3fileInfo['error'] = '';

    $MPEGheaderRawArray = MPEGaudioHeaderDecode(substr($headerstring, 0, 4));

    if (MPEGaudioHeaderValid($MPEGheaderRawArray, true)) {

		$MP3fileInfo['raw'] = $MPEGheaderRawArray;

		$MP3fileInfo['version']              = MPEGaudioVersionLookup($MP3fileInfo['raw']['version']);
		$MP3fileInfo['layer']                = MPEGaudioLayerLookup($MP3fileInfo['raw']['layer']);
		$MP3fileInfo['protection']           = MPEGaudioCRCLookup($MP3fileInfo['raw']['protection']);
		$MP3fileInfo['bitrate']              = MPEGaudioBitrateLookup($MP3fileInfo['version'], $MP3fileInfo['layer'], $MP3fileInfo['raw']['bitrate']);
		$MP3fileInfo['frequency']            = MPEGaudioFrequencyLookup($MP3fileInfo['version'], $MP3fileInfo['raw']['sample_rate']);
		$MP3fileInfo['padding']              = (bool) $MP3fileInfo['raw']['padding'];
		$MP3fileInfo['private']              = (bool) $MP3fileInfo['raw']['private'];
		$MP3fileInfo['channelmode']          = MPEGaudioChannelModeLookup($MP3fileInfo['raw']['channelmode']);
		$MP3fileInfo['channels']             = (($MP3fileInfo['channelmode'] == 'mono') ? 1 : 2);
		$MP3fileInfo['modeextension']        = MPEGaudioModeExtensionLookup($MP3fileInfo['layer'], $MP3fileInfo['raw']['modeextension']);
		$MP3fileInfo['copyright']            = (bool) $MP3fileInfo['raw']['copyright'];
		$MP3fileInfo['original']             = (bool) $MP3fileInfo['raw']['original'];
		$MP3fileInfo['emphasis']             = MPEGaudioEmphasisLookup($MP3fileInfo['raw']['emphasis']);

		if ($MP3fileInfo['protection']) {
			$MP3fileInfo['crc'] = BigEndian2Int(substr($headerstring, 4, 2));
		}

		if ($MP3fileInfo['frequency'] > 0) {
			$MP3fileInfo['framelength'] = MPEGaudioFrameLength($MP3fileInfo['bitrate'], $MP3fileInfo['version'], $MP3fileInfo['layer'], (int) $MP3fileInfo['padding'], $MP3fileInfo['frequency']);
		}
		if ($MP3fileInfo['bitrate'] != 'free') {
			$MP3fileInfo['bitrate'] *= 1000;
		}

    } else {

		$MP3fileInfo['error'] .= "\n".'Invalid MPEG audio header';

    }

    if (!$MP3fileInfo['error']) {
		unset($MP3fileInfo['error']);
    }

    echo table_var_dump($MP3fileInfo);

} elseif (isset($_POST['Generate'])) {

    // AAAA AAAA  AAAB BCCD  EEEE FFGH  IIJJ KLMM

    $headerbitstream  = '11111111111';                               // A - Frame sync (all bits set)

    $MPEGversionLookup = array('2.5'=>'00', '2'=>'10', '1'=>'11');
    $headerbitstream .= $MPEGversionLookup[$_POST['version']];       // B - MPEG Audio version ID

    $MPEGlayerLookup = array('III'=>'01', 'II'=>'10', 'I'=>'11');
    $headerbitstream .= $MPEGlayerLookup[$_POST['layer']];           // C - Layer description

    $headerbitstream .= (($_POST['protection'] == 'Y') ? '0' : '1'); // D - Protection bit

    $MPEGaudioBitrateLookup['1']['I']     = array('free'=>'0000', '32'=>'0001', '64'=>'0010', '96'=>'0011', '128'=>'0100', '160'=>'0101', '192'=>'0110', '224'=>'0111', '256'=>'1000', '288'=>'1001', '320'=>'1010', '352'=>'1011', '384'=>'1100', '416'=>'1101', '448'=>'1110');
    $MPEGaudioBitrateLookup['1']['II']    = array('free'=>'0000', '32'=>'0001', '48'=>'0010', '56'=>'0011',  '64'=>'0100',  '80'=>'0101',  '96'=>'0110', '112'=>'0111', '128'=>'1000', '160'=>'1001', '192'=>'1010', '224'=>'1011', '256'=>'1100', '320'=>'1101', '384'=>'1110');
    $MPEGaudioBitrateLookup['1']['III']   = array('free'=>'0000', '32'=>'0001', '40'=>'0010', '48'=>'0011',  '56'=>'0100',  '64'=>'0101',  '80'=>'0110',  '96'=>'0111', '112'=>'1000', '128'=>'1001', '160'=>'1010', '192'=>'1011', '224'=>'1100', '256'=>'1101', '320'=>'1110');
    $MPEGaudioBitrateLookup['2']['I']     = array('free'=>'0000', '32'=>'0001', '48'=>'0010', '56'=>'0011',  '64'=>'0100',  '80'=>'0101',  '96'=>'0110', '112'=>'0111', '128'=>'1000', '144'=>'1001', '160'=>'1010', '176'=>'1011', '192'=>'1100', '224'=>'1101', '256'=>'1110');
    $MPEGaudioBitrateLookup['2']['II']    = array('free'=>'0000',  '8'=>'0001', '16'=>'0010', '24'=>'0011',  '32'=>'0100',  '40'=>'0101',  '48'=>'0110',  '56'=>'0111',  '64'=>'1000',  '80'=>'1001',  '96'=>'1010', '112'=>'1011', '128'=>'1100', '144'=>'1101', '160'=>'1110');
    $MPEGaudioBitrateLookup['2']['III']   = $MPEGaudioBitrateLookup['2']['II'];
    $MPEGaudioBitrateLookup['2.5']['I']   = $MPEGaudioBitrateLookup['2']['I'];
    $MPEGaudioBitrateLookup['2.5']['II']  = $MPEGaudioBitrateLookup['2']['II'];
    $MPEGaudioBitrateLookup['2.5']['III'] = $MPEGaudioBitrateLookup['2']['II'];
    if (isset($MPEGaudioBitrateLookup[$_POST['version']][$_POST['layer']][$_POST['bitrate']])) {
		$headerbitstream .= $MPEGaudioBitrateLookup[$_POST['version']][$_POST['layer']][$_POST['bitrate']]; // E - Bitrate index
    } else {
		die('Invalid <B>Bitrate</B>');
    }

    $MPEGaudioFrequencyLookup['1']   = array('44100'=>'00', '48000'=>'01', '32000'=>'10');
    $MPEGaudioFrequencyLookup['2']   = array('22050'=>'00', '24000'=>'01', '16000'=>'10');
    $MPEGaudioFrequencyLookup['2.5'] = array('11025'=>'00', '12000'=>'01', '8000'=>'10');
    if (isset($MPEGaudioFrequencyLookup[$_POST['version']][$_POST['frequency']])) {
		$headerbitstream .= $MPEGaudioFrequencyLookup[$_POST['version']][$_POST['frequency']];  // F - Sampling rate frequency index
    } else {
		die('Invalid <B>Frequency</B>');
    }

    $headerbitstream .= (($_POST['padding'] == 'Y') ? '1' : '0');            // G - Padding bit

    $headerbitstream .= (($_POST['private'] == 'Y') ? '1' : '0');            // H - Private bit

    $MPEGaudioChannelModeLookup = array('stereo'=>'00', 'joint stereo'=>'01', 'dual channel'=>'10', 'mono'=>'11');
    $headerbitstream .= $MPEGaudioChannelModeLookup[$_POST['channelmode']];  // I - Channel Mode

    $MPEGaudioModeExtensionLookup['I']   = array('4-31'=>'00', '8-31'=>'01', '12-31'=>'10', '16-31'=>'11');
    $MPEGaudioModeExtensionLookup['II']  = $MPEGaudioModeExtensionLookup['I'];
    $MPEGaudioModeExtensionLookup['III'] = array('none'=>'00',   'IS'=>'01',    'MS'=>'10', 'IS+MS'=>'11');
    if ($_POST['channelmode'] != 'joint stereo') {
		$headerbitstream .= '00';
    } elseif (isset($MPEGaudioModeExtensionLookup[$_POST['layer']][$_POST['modeextension']])) {
		$headerbitstream .= $MPEGaudioModeExtensionLookup[$_POST['layer']][$_POST['modeextension']];  // J - Mode extension (Only if Joint stereo)
    } else {
		die('Invalid <B>Mode Extension</B>');
    }

    $headerbitstream .= (($_POST['copyright'] == 'Y') ? '1' : '0');          // K - Copyright

    $headerbitstream .= (($_POST['original']  == 'Y') ? '1' : '0');          // L - Original

    $MPEGaudioEmphasisLookup = array('none'=>'00', '50/15ms'=>'01', 'CCIT J.17'=>'11');
    if (isset($MPEGaudioEmphasisLookup[$_POST['emphasis']])) {
		$headerbitstream .= $MPEGaudioEmphasisLookup[$_POST['emphasis']];    // M - Emphasis
    } else {
		die('Invalid <B>Emphasis</B>');
    }

    echo strtoupper(str_pad(dechex(bindec(substr($headerbitstream,  0, 8))), 2, '0', STR_PAD_LEFT)).' ';
    echo strtoupper(str_pad(dechex(bindec(substr($headerbitstream,  8, 8))), 2, '0', STR_PAD_LEFT)).' ';
    echo strtoupper(str_pad(dechex(bindec(substr($headerbitstream, 16, 8))), 2, '0', STR_PAD_LEFT)).' ';
    echo strtoupper(str_pad(dechex(bindec(substr($headerbitstream, 24, 8))), 2, '0', STR_PAD_LEFT)).'<BR>';

}

function MPEGaudioVersionLookup($rawversion) {
	$MPEGaudioVersionLookup = array('2.5', FALSE, '2', '1');
	return (isset($MPEGaudioVersionLookup["$rawversion"]) ? $MPEGaudioVersionLookup["$rawversion"] : FALSE);
}

function MPEGaudioLayerLookup($rawlayer) {
	$MPEGaudioLayerLookup = array(FALSE, 'III', 'II', 'I');
	return (isset($MPEGaudioLayerLookup["$rawlayer"]) ? $MPEGaudioLayerLookup["$rawlayer"] : FALSE);
}

function MPEGaudioBitrateLookup($version, $layer, $rawbitrate) {
	static $MPEGaudioBitrateLookup;
	if (empty($MPEGaudioBitrateLookup)) {
		$MPEGaudioBitrateLookup = MPEGaudioBitrateArray();
	}
	return (isset($MPEGaudioBitrateLookup["$version"]["$layer"]["$rawbitrate"]) ? $MPEGaudioBitrateLookup["$version"]["$layer"]["$rawbitrate"] : FALSE);
}

function MPEGaudioFrequencyLookup($version, $rawfrequency) {
	static $MPEGaudioFrequencyLookup;
	if (empty($MPEGaudioFrequencyLookup)) {
		$MPEGaudioFrequencyLookup = MPEGaudioFrequencyArray();
	}
	return (isset($MPEGaudioFrequencyLookup["$version"]["$rawfrequency"]) ? $MPEGaudioFrequencyLookup["$version"]["$rawfrequency"] : FALSE);
}

function MPEGaudioChannelModeLookup($rawchannelmode) {
	$MPEGaudioChannelModeLookup = array('stereo', 'joint stereo', 'dual channel', 'mono');
	return (isset($MPEGaudioChannelModeLookup["$rawchannelmode"]) ? $MPEGaudioChannelModeLookup["$rawchannelmode"] : FALSE);
}

function MPEGaudioModeExtensionLookup($layer, $rawmodeextension) {
	$MPEGaudioModeExtensionLookup['I']   = array('4-31', '8-31', '12-31', '16-31');
	$MPEGaudioModeExtensionLookup['II']  = array('4-31', '8-31', '12-31', '16-31');
	$MPEGaudioModeExtensionLookup['III'] = array('', 'IS', 'MS', 'IS+MS');
	return (isset($MPEGaudioModeExtensionLookup["$layer"]["$rawmodeextension"]) ? $MPEGaudioModeExtensionLookup["$layer"]["$rawmodeextension"] : FALSE);
}

function MPEGaudioEmphasisLookup($rawemphasis) {
	$MPEGaudioEmphasisLookup = array('none', '50/15ms', FALSE, 'CCIT J.17');
	return (isset($MPEGaudioEmphasisLookup["$rawemphasis"]) ? $MPEGaudioEmphasisLookup["$rawemphasis"] : FALSE);
}

function MPEGaudioCRCLookup($CRCbit) {
	// inverse boolean cast :)
	if ($CRCbit == '0') {
		return TRUE;
	} else {
		return FALSE;
	}
}

/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                ///
//            or http://www.getid3.org                        ///
/////////////////////////////////////////////////////////////////
//                                                             //
// getid3.mp3.php - part of getID3()                           //
// See getid3.readme.txt for more details                      //
//                                                             //
/////////////////////////////////////////////////////////////////

// number of frames to scan to determine if MPEG-audio sequence is valid
// Lower this number to 5-20 for faster scanning
// Increase this number to 50+ for most accurate detection of valid VBR/CBR
// mpeg-audio streams
define('MPEG_VALID_CHECK_FRAMES', 35);

function getMP3headerFilepointer(&$fd, &$ThisFileInfo) {

	getOnlyMPEGaudioInfo($fd, $ThisFileInfo, $ThisFileInfo['avdataoffset']);

	if (isset($ThisFileInfo['mpeg']['audio']['bitrate_mode'])) {
		$ThisFileInfo['audio']['bitrate_mode'] = strtolower($ThisFileInfo['mpeg']['audio']['bitrate_mode']);
	}

	if (((isset($ThisFileInfo['id3v2']) && ($ThisFileInfo['avdataoffset'] > $ThisFileInfo['id3v2']['headerlength'])) || (!isset($ThisFileInfo['id3v2']) && ($ThisFileInfo['avdataoffset'] > 0)))) {

		$ThisFileInfo['warning'] .= "\n".'Unknown data before synch ';
		if (isset($ThisFileInfo['id3v2']['headerlength'])) {
			$ThisFileInfo['warning'] .= '(ID3v2 header ends at '.$ThisFileInfo['id3v2']['headerlength'].', then '.($ThisFileInfo['avdataoffset'] - $ThisFileInfo['id3v2']['headerlength']).' bytes garbage, ';
		} else {
			$ThisFileInfo['warning'] .= '(should be at beginning of file, ';
		}
		$ThisFileInfo['warning'] .= 'synch detected at '.$ThisFileInfo['avdataoffset'].')';
		if ($ThisFileInfo['audio']['bitrate_mode'] == 'cbr') {
			if (!empty($ThisFileInfo['id3v2']['headerlength']) && (($ThisFileInfo['avdataoffset'] - $ThisFileInfo['id3v2']['headerlength']) == $ThisFileInfo['mpeg']['audio']['framelength'])) {
				$ThisFileInfo['warning'] .= '. This is a known problem with some versions of LAME (3.91, 3.92) DLL in CBR mode.';
				$ThisFileInfo['audio']['codec'] = 'LAME';
			} elseif (empty($ThisFileInfo['id3v2']['headerlength']) && ($ThisFileInfo['avdataoffset'] == $ThisFileInfo['mpeg']['audio']['framelength'])) {
				$ThisFileInfo['warning'] .= '. This is a known problem with some versions of LAME (3.91, 3.92) DLL in CBR mode.';
				$ThisFileInfo['audio']['codec'] = 'LAME';
			}
		}

	}

	if (isset($ThisFileInfo['mpeg']['audio']['layer']) && ($ThisFileInfo['mpeg']['audio']['layer'] == 'II')) {
		$ThisFileInfo['audio']['dataformat'] = 'mp2';
	} elseif (isset($ThisFileInfo['mpeg']['audio']['layer']) && ($ThisFileInfo['mpeg']['audio']['layer'] == 'I')) {
		$ThisFileInfo['audio']['dataformat'] = 'mp1';
	}
	if ($ThisFileInfo['fileformat'] == 'mp3') {
		switch ($ThisFileInfo['audio']['dataformat']) {
			case 'mp1':
			case 'mp2':
			case 'mp3':
				$ThisFileInfo['fileformat'] = $ThisFileInfo['audio']['dataformat'];
				break;

			default:
				$ThisFileInfo['warning'] .= "\n".'Expecting [audio][dataformat] to be mp1/mp2/mp3 when fileformat == mp3, [audio][dataformat] actually "'.$ThisFileInfo['audio']['dataformat'].'"';
				break;
		}
	}

	if (empty($ThisFileInfo['fileformat'])) {
		$ThisFileInfo['error'] .= "\n".'Synch not found';
		unset($ThisFileInfo['fileformat']);
		unset($ThisFileInfo['audio']['bitrate_mode']);
		unset($ThisFileInfo['avdataoffset']);
		unset($ThisFileInfo['avdataend']);
		return false;
	}

	$ThisFileInfo['mime_type']         = 'audio/mpeg';
	$ThisFileInfo['audio']['lossless'] = false;

	// Calculate playtime
	if (!isset($ThisFileInfo['playtime_seconds']) && isset($ThisFileInfo['audio']['bitrate']) && ($ThisFileInfo['audio']['bitrate'] > 0)) {
		$ThisFileInfo['playtime_seconds'] = ($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']) * 8 / $ThisFileInfo['audio']['bitrate'];
	}

	if (isset($ThisFileInfo['mpeg']['audio']['LAME'])) {
		$ThisFileInfo['audio']['codec'] = 'LAME';
		if (!empty($ThisFileInfo['mpeg']['audio']['LAME']['long_version'])) {
			$ThisFileInfo['audio']['encoder'] = trim($ThisFileInfo['mpeg']['audio']['LAME']['long_version']);
		}
	}

	return true;
}


function decodeMPEGaudioHeader($fd, $offset, &$ThisFileInfo, $recursivesearch=true, $ScanAsCBR=false, $FastMPEGheaderScan=false) {

	static $MPEGaudioVersionLookup;
	static $MPEGaudioLayerLookup;
	static $MPEGaudioBitrateLookup;
	static $MPEGaudioFrequencyLookup;
	static $MPEGaudioChannelModeLookup;
	static $MPEGaudioModeExtensionLookup;
	static $MPEGaudioEmphasisLookup;
	if (empty($MPEGaudioVersionLookup)) {
		$MPEGaudioVersionLookup       = MPEGaudioVersionArray();
		$MPEGaudioLayerLookup         = MPEGaudioLayerArray();
		$MPEGaudioBitrateLookup       = MPEGaudioBitrateArray();
		$MPEGaudioFrequencyLookup     = MPEGaudioFrequencyArray();
		$MPEGaudioChannelModeLookup   = MPEGaudioChannelModeArray();
		$MPEGaudioModeExtensionLookup = MPEGaudioModeExtensionArray();
		$MPEGaudioEmphasisLookup      = MPEGaudioEmphasisArray();
	}

	if ($offset >= $ThisFileInfo['avdataend']) {
		$ThisFileInfo['error'] .= "\n".'end of file encounter looking for MPEG synch';
		return false;
	}
	fseek($fd, $offset, SEEK_SET);
	$headerstring = fread($fd, 1441); // worse-case max length = 32kHz @ 320kbps layer 3 = 1441 bytes/frame

	// MP3 audio frame structure:
	// $aa $aa $aa $aa [$bb $bb] $cc...
	// where $aa..$aa is the four-byte mpeg-audio header (below)
	// $bb $bb is the optional 2-byte CRC
	// and $cc... is the audio data

	$head4 = substr($headerstring, 0, 4);

	static $MPEGaudioHeaderDecodeCache = array();
	if (isset($MPEGaudioHeaderDecodeCache[$head4])) {
		$MPEGheaderRawArray = $MPEGaudioHeaderDecodeCache[$head4];
	} else {
		$MPEGheaderRawArray = MPEGaudioHeaderDecode($head4);
		$MPEGaudioHeaderDecodeCache[$head4] = $MPEGheaderRawArray;
	}

	static $MPEGaudioHeaderValidCache = array();

	// Not in cache
	if (!isset($MPEGaudioHeaderValidCache[$head4])) {
		$MPEGaudioHeaderValidCache[$head4] = MPEGaudioHeaderValid($MPEGheaderRawArray);
	}

	if ($MPEGaudioHeaderValidCache[$head4]) {
		$ThisFileInfo['mpeg']['audio']['raw'] = $MPEGheaderRawArray;
	} else {
		$ThisFileInfo['error'] .= "\n".'Invalid MPEG audio header at offset '.$offset;
		return false;
	}

	if (!$FastMPEGheaderScan) {

		$ThisFileInfo['mpeg']['audio']['version']       = $MPEGaudioVersionLookup[$ThisFileInfo['mpeg']['audio']['raw']['version']];
		$ThisFileInfo['mpeg']['audio']['layer']         = $MPEGaudioLayerLookup[$ThisFileInfo['mpeg']['audio']['raw']['layer']];

		$ThisFileInfo['mpeg']['audio']['channelmode']   = $MPEGaudioChannelModeLookup[$ThisFileInfo['mpeg']['audio']['raw']['channelmode']];
		$ThisFileInfo['mpeg']['audio']['channels']      = (($ThisFileInfo['mpeg']['audio']['channelmode'] == 'mono') ? 1 : 2);
		$ThisFileInfo['mpeg']['audio']['sample_rate']   = $MPEGaudioFrequencyLookup[$ThisFileInfo['mpeg']['audio']['version']][$ThisFileInfo['mpeg']['audio']['raw']['sample_rate']];
		$ThisFileInfo['mpeg']['audio']['protection']    = !$ThisFileInfo['mpeg']['audio']['raw']['protection'];
		$ThisFileInfo['mpeg']['audio']['private']       = (bool) $ThisFileInfo['mpeg']['audio']['raw']['private'];
		$ThisFileInfo['mpeg']['audio']['modeextension'] = $MPEGaudioModeExtensionLookup[$ThisFileInfo['mpeg']['audio']['layer']][$ThisFileInfo['mpeg']['audio']['raw']['modeextension']];
		$ThisFileInfo['mpeg']['audio']['copyright']     = (bool) $ThisFileInfo['mpeg']['audio']['raw']['copyright'];
		$ThisFileInfo['mpeg']['audio']['original']      = (bool) $ThisFileInfo['mpeg']['audio']['raw']['original'];
		$ThisFileInfo['mpeg']['audio']['emphasis']      = $MPEGaudioEmphasisLookup[$ThisFileInfo['mpeg']['audio']['raw']['emphasis']];

		$ThisFileInfo['audio']['channels']    = $ThisFileInfo['mpeg']['audio']['channels'];
		$ThisFileInfo['audio']['sample_rate'] = $ThisFileInfo['mpeg']['audio']['sample_rate'];

		if ($ThisFileInfo['mpeg']['audio']['protection']) {
			$ThisFileInfo['mpeg']['audio']['crc'] = BigEndian2Int(substr($headerstring, 4, 2));
		}

	}

	if ($ThisFileInfo['mpeg']['audio']['raw']['bitrate'] == 15) {
		// http://www.hydrogenaudio.org/?act=ST&f=16&t=9682&st=0
		$ThisFileInfo['warning'] .= "\n".'Invalid bitrate index (15), this is a known bug in free-format MP3s encoded by LAME v3.90 - 3.93.1';
		$ThisFileInfo['mpeg']['audio']['raw']['bitrate'] = 0;
	}
	$ThisFileInfo['mpeg']['audio']['padding'] = (bool) $ThisFileInfo['mpeg']['audio']['raw']['padding'];
	$ThisFileInfo['mpeg']['audio']['bitrate'] = $MPEGaudioBitrateLookup[$ThisFileInfo['mpeg']['audio']['version']][$ThisFileInfo['mpeg']['audio']['layer']][$ThisFileInfo['mpeg']['audio']['raw']['bitrate']];

	if (($ThisFileInfo['mpeg']['audio']['bitrate'] == 'free') && ($offset == $ThisFileInfo['avdataoffset'])) {
		// only skip multiple frame check if free-format bitstream found at beginning of file
		// otherwise is quite possibly simply corrupted data
		$recursivesearch = false;
	}

	// For Layer II there are some combinations of bitrate and mode which are not allowed.
	if (!$FastMPEGheaderScan && ($ThisFileInfo['mpeg']['audio']['layer'] == 'II')) {

		$ThisFileInfo['audio']['dataformat'] = 'mp2';
		switch ($ThisFileInfo['mpeg']['audio']['channelmode']) {

			case 'mono':
				if (($ThisFileInfo['mpeg']['audio']['bitrate'] == 'free') || ($ThisFileInfo['mpeg']['audio']['bitrate'] <= 192)) {
					// these are ok
				} else {
					$ThisFileInfo['error'] .= "\n".$ThisFileInfo['mpeg']['audio']['bitrate'].'kbps not allowed in Layer II, '.$ThisFileInfo['mpeg']['audio']['channelmode'].'.';
					return false;
				}
				break;

			case 'stereo':
			case 'joint stereo':
			case 'dual channel':
				if (($ThisFileInfo['mpeg']['audio']['bitrate'] == 'free') || ($ThisFileInfo['mpeg']['audio']['bitrate'] == 64) || ($ThisFileInfo['mpeg']['audio']['bitrate'] >= 96)) {
					// these are ok
				} else {
					$ThisFileInfo['error'] .= "\n".$ThisFileInfo['mpeg']['audio']['bitrate'].'kbps not allowed in Layer II, '.$ThisFileInfo['mpeg']['audio']['channelmode'].'.';
					return false;
				}
				break;

		}

	}


	if ($ThisFileInfo['audio']['sample_rate'] > 0) {
		$ThisFileInfo['mpeg']['audio']['framelength'] = MPEGaudioFrameLength($ThisFileInfo['mpeg']['audio']['bitrate'], $ThisFileInfo['mpeg']['audio']['version'], $ThisFileInfo['mpeg']['audio']['layer'], (int) $ThisFileInfo['mpeg']['audio']['padding'], $ThisFileInfo['audio']['sample_rate']);
	}

	if ($ThisFileInfo['mpeg']['audio']['bitrate'] != 'free') {

		$ThisFileInfo['audio']['bitrate'] = 1000 * $ThisFileInfo['mpeg']['audio']['bitrate'];

		if (isset($ThisFileInfo['mpeg']['audio']['framelength'])) {
			$nextframetestoffset = $offset + $ThisFileInfo['mpeg']['audio']['framelength'];
		} else {
			$ThisFileInfo['error'] .= "\n".'Frame at offset('.$offset.') is has an invalid frame length.';
			return false;
		}

	}

	$ExpectedNumberOfAudioBytes = 0;

	////////////////////////////////////////////////////////////////////////////////////
	// Variable-bitrate headers

	if (substr($headerstring, 4 + 32, 4) == 'VBRI') {
		// Fraunhofer VBR header is hardcoded 'VBRI' at offset 0x24 (36)
		// specs taken from http://minnie.tuhs.org/pipermail/mp3encoder/2001-January/001800.html

		$ThisFileInfo['mpeg']['audio']['bitrate_mode'] = 'vbr';
		$ThisFileInfo['mpeg']['audio']['VBR_method']   = 'Fraunhofer';
		$ThisFileInfo['audio']['codec']                = 'Fraunhofer';

		$SideInfoData = substr($headerstring, 4 + 2, 32);

		$FraunhoferVBROffset = 36;

		$ThisFileInfo['mpeg']['audio']['VBR_encoder_version']     = BigEndian2Int(substr($headerstring, $FraunhoferVBROffset +  4, 2));
		$ThisFileInfo['mpeg']['audio']['VBR_encoder_delay']       = BigEndian2Int(substr($headerstring, $FraunhoferVBROffset +  6, 2));
		$ThisFileInfo['mpeg']['audio']['VBR_quality']             = BigEndian2Int(substr($headerstring, $FraunhoferVBROffset +  8, 2));
		$ThisFileInfo['mpeg']['audio']['VBR_bytes']               = BigEndian2Int(substr($headerstring, $FraunhoferVBROffset + 10, 4));
		$ThisFileInfo['mpeg']['audio']['VBR_frames']              = BigEndian2Int(substr($headerstring, $FraunhoferVBROffset + 14, 4));
		$ThisFileInfo['mpeg']['audio']['VBR_seek_offsets']        = BigEndian2Int(substr($headerstring, $FraunhoferVBROffset + 18, 2));
		//$ThisFileInfo['mpeg']['audio']['reserved']              = BigEndian2Int(substr($headerstring, $FraunhoferVBROffset + 20, 4)); // hardcoded $00 $01 $00 $02  - purpose unknown
		$ThisFileInfo['mpeg']['audio']['VBR_seek_offsets_stride'] = BigEndian2Int(substr($headerstring, $FraunhoferVBROffset + 24, 2));

		$ExpectedNumberOfAudioBytes = $ThisFileInfo['mpeg']['audio']['VBR_bytes'];

		$previousbyteoffset = $offset;
		for ($i = 0; $i < $ThisFileInfo['mpeg']['audio']['VBR_seek_offsets']; $i++) {
			$Fraunhofer_OffsetN = BigEndian2Int(substr($headerstring, $FraunhoferVBROffset, 2));
			$FraunhoferVBROffset += 2;
			$ThisFileInfo['mpeg']['audio']['VBR_offsets_relative'][$i] = $Fraunhofer_OffsetN;
			$ThisFileInfo['mpeg']['audio']['VBR_offsets_absolute'][$i] = $Fraunhofer_OffsetN + $previousbyteoffset;
			$previousbyteoffset += $Fraunhofer_OffsetN;
		}


	} else {

		// Xing VBR header is hardcoded 'Xing' at a offset 0x0D (13), 0x15 (21) or 0x24 (36)
		// depending on MPEG layer and number of channels

		if ($ThisFileInfo['mpeg']['audio']['version'] == '1') {
			if ($ThisFileInfo['mpeg']['audio']['channelmode'] == 'mono') {
				// MPEG-1 (mono)
				$VBRidOffset  = 4 + 17; // 0x15
				$SideInfoData = substr($headerstring, 4 + 2, 17);
			} else {
				// MPEG-1 (stereo, joint-stereo, dual-channel)
				$VBRidOffset = 4 + 32; // 0x24
				$SideInfoData = substr($headerstring, 4 + 2, 32);
			}
		} else { // 2 or 2.5
			if ($ThisFileInfo['mpeg']['audio']['channelmode'] == 'mono') {
				// MPEG-2, MPEG-2.5 (mono)
				$VBRidOffset = 4 + 9;  // 0x0D
				$SideInfoData = substr($headerstring, 4 + 2, 9);
			} else {
				// MPEG-2, MPEG-2.5 (stereo, joint-stereo, dual-channel)
				$VBRidOffset = 4 + 17; // 0x15
				$SideInfoData = substr($headerstring, 4 + 2, 17);
			}
		}

		if ((substr($headerstring, $VBRidOffset, strlen('Xing')) == 'Xing') || (substr($headerstring, $VBRidOffset, strlen('Info')) == 'Info')) {
			// 'Xing' is traditional Xing VBR frame
			// 'Info' is LAME-encoded CBR (This was done to avoid CBR files to be recognized as traditional Xing VBR files by some decoders.)

			$ThisFileInfo['mpeg']['audio']['bitrate_mode'] = 'vbr';
			$ThisFileInfo['mpeg']['audio']['VBR_method']   = 'Xing';

			$ThisFileInfo['mpeg']['audio']['xing_flags_raw'] = BigEndian2Int(substr($headerstring, $VBRidOffset + 4, 4));

			$ThisFileInfo['mpeg']['audio']['xing_flags']['frames']    = (bool) ($ThisFileInfo['mpeg']['audio']['xing_flags_raw'] & 0x00000001);
			$ThisFileInfo['mpeg']['audio']['xing_flags']['bytes']     = (bool) ($ThisFileInfo['mpeg']['audio']['xing_flags_raw'] & 0x00000002);
			$ThisFileInfo['mpeg']['audio']['xing_flags']['toc']       = (bool) ($ThisFileInfo['mpeg']['audio']['xing_flags_raw'] & 0x00000004);
			$ThisFileInfo['mpeg']['audio']['xing_flags']['vbr_scale'] = (bool) ($ThisFileInfo['mpeg']['audio']['xing_flags_raw'] & 0x00000008);

			if ($ThisFileInfo['mpeg']['audio']['xing_flags']['frames']) {
				$ThisFileInfo['mpeg']['audio']['VBR_frames'] = BigEndian2Int(substr($headerstring, $VBRidOffset +  8, 4));
			}
			if ($ThisFileInfo['mpeg']['audio']['xing_flags']['bytes']) {
				$ThisFileInfo['mpeg']['audio']['VBR_bytes']  = BigEndian2Int(substr($headerstring, $VBRidOffset + 12, 4));
			}

			if (($ThisFileInfo['mpeg']['audio']['bitrate'] == 'free') && !empty($ThisFileInfo['mpeg']['audio']['VBR_frames']) && !empty($ThisFileInfo['mpeg']['audio']['VBR_bytes'])) {
				$framelengthfloat = $ThisFileInfo['mpeg']['audio']['VBR_bytes'] / $ThisFileInfo['mpeg']['audio']['VBR_frames'];
				if ($ThisFileInfo['mpeg']['audio']['layer'] == 'I') {
					// BitRate = (((FrameLengthInBytes / 4) - Padding) * SampleRate) / 12
					$ThisFileInfo['audio']['bitrate'] = ((($framelengthfloat / 4) - intval($ThisFileInfo['mpeg']['audio']['padding'])) * $ThisFileInfo['mpeg']['audio']['sample_rate']) / 12;
				} else {
					// Bitrate = ((FrameLengthInBytes - Padding) * SampleRate) / 144
					$ThisFileInfo['audio']['bitrate'] = (($framelengthfloat - intval($ThisFileInfo['mpeg']['audio']['padding'])) * $ThisFileInfo['mpeg']['audio']['sample_rate']) / 144;
				}
				$ThisFileInfo['mpeg']['audio']['framelength'] = floor($framelengthfloat);
			}

			if ($ThisFileInfo['mpeg']['audio']['xing_flags']['toc']) {
				$LAMEtocData = substr($headerstring, $VBRidOffset + 16, 100);
				for ($i = 0; $i < 100; $i++) {
					$ThisFileInfo['mpeg']['audio']['toc'][$i] = ord($LAMEtocData{$i});
				}
			}
			if ($ThisFileInfo['mpeg']['audio']['xing_flags']['vbr_scale']) {
				$ThisFileInfo['mpeg']['audio']['VBR_scale'] = BigEndian2Int(substr($headerstring, $VBRidOffset + 116, 4));
			}

			// http://gabriel.mp3-tech.org/mp3infotag.html
			if (substr($headerstring, $VBRidOffset + 120, 4) == 'LAME') {
				$ThisFileInfo['mpeg']['audio']['LAME']['long_version']  = substr($headerstring, $VBRidOffset + 120, 20);
				$ThisFileInfo['mpeg']['audio']['LAME']['short_version'] = substr($ThisFileInfo['mpeg']['audio']['LAME']['long_version'], 0, 9);
				$ThisFileInfo['mpeg']['audio']['LAME']['long_version']  = rtrim($ThisFileInfo['mpeg']['audio']['LAME']['long_version'], "\x55\xAA");

				if ($ThisFileInfo['mpeg']['audio']['LAME']['short_version'] >= 'LAME3.90.') {

					// It the LAME tag was only introduced in LAME v3.90
					// http://www.hydrogenaudio.org/?act=ST&f=15&t=9933

					// Offsets of various bytes in http://gabriel.mp3-tech.org/mp3infotag.html
					// are assuming a 'Xing' identifier offset of 0x24, which is the case for
					// MPEG-1 non-mono, but not for other combinations
					$LAMEtagOffsetContant = $VBRidOffset - 0x24;

					// byte $9B  VBR Quality
					// This field is there to indicate a quality level, although the scale was not precised in the original Xing specifications.
					// Actually overwrites original Xing bytes
					unset($ThisFileInfo['mpeg']['audio']['VBR_scale']);
					$ThisFileInfo['mpeg']['audio']['LAME']['vbr_quality'] = BigEndian2Int(substr($headerstring, $LAMEtagOffsetContant + 0x9B, 1));

					// bytes $9C-$A4  Encoder short VersionString
					$ThisFileInfo['mpeg']['audio']['LAME']['short_version'] = substr($headerstring, $LAMEtagOffsetContant + 0x9C, 9);
					$ThisFileInfo['mpeg']['audio']['LAME']['long_version']  = $ThisFileInfo['mpeg']['audio']['LAME']['short_version'];

					// byte $A5  Info Tag revision + VBR method
					$LAMEtagRevisionVBRmethod = BigEndian2Int(substr($headerstring, $LAMEtagOffsetContant + 0xA5, 1));

					$ThisFileInfo['mpeg']['audio']['LAME']['tag_revision']      = ($LAMEtagRevisionVBRmethod & 0xF0) >> 4;
					$ThisFileInfo['mpeg']['audio']['LAME']['raw']['vbr_method'] =  $LAMEtagRevisionVBRmethod & 0x0F;
					$ThisFileInfo['mpeg']['audio']['LAME']['vbr_method']        = LAMEvbrMethodLookup($ThisFileInfo['mpeg']['audio']['LAME']['raw']['vbr_method']);

					// byte $A6  Lowpass filter value
					$ThisFileInfo['mpeg']['audio']['LAME']['lowpass_frequency'] = BigEndian2Int(substr($headerstring, $LAMEtagOffsetContant + 0xA6, 1)) * 100;

					// bytes $A7-$AE  Replay Gain
					// http://privatewww.essex.ac.uk/~djmrob/replaygain/rg_data_format.html
					// bytes $A7-$AA : 32 bit floating point "Peak signal amplitude"
					$ThisFileInfo['mpeg']['audio']['LAME']['RGAD']['peak_amplitude'] = BigEndian2Float(substr($headerstring, $LAMEtagOffsetContant + 0xA7, 4));
					$ThisFileInfo['mpeg']['audio']['LAME']['raw']['RGAD_radio']      =   BigEndian2Int(substr($headerstring, $LAMEtagOffsetContant + 0xAB, 2));
					$ThisFileInfo['mpeg']['audio']['LAME']['raw']['RGAD_audiophile'] =   BigEndian2Int(substr($headerstring, $LAMEtagOffsetContant + 0xAD, 2));

					if ($ThisFileInfo['mpeg']['audio']['LAME']['RGAD']['peak_amplitude'] == 0) {
						$ThisFileInfo['mpeg']['audio']['LAME']['RGAD']['peak_amplitude'] = false;
					}

					if ($ThisFileInfo['mpeg']['audio']['LAME']['raw']['RGAD_radio'] != 0) {
						require_once(GETID3_INCLUDEPATH.'getid3.rgad.php');

						$ThisFileInfo['mpeg']['audio']['LAME']['RGAD']['radio']['raw']['name']        = ($ThisFileInfo['mpeg']['audio']['LAME']['raw']['RGAD_radio'] & 0xE000) >> 13;
						$ThisFileInfo['mpeg']['audio']['LAME']['RGAD']['radio']['raw']['originator']  = ($ThisFileInfo['mpeg']['audio']['LAME']['raw']['RGAD_radio'] & 0x1C00) >> 10;
						$ThisFileInfo['mpeg']['audio']['LAME']['RGAD']['radio']['raw']['sign_bit']    = ($ThisFileInfo['mpeg']['audio']['LAME']['raw']['RGAD_radio'] & 0x0200) >> 9;
						$ThisFileInfo['mpeg']['audio']['LAME']['RGAD']['radio']['raw']['gain_adjust'] =  $ThisFileInfo['mpeg']['audio']['LAME']['raw']['RGAD_radio'] & 0x01FF;
						$ThisFileInfo['mpeg']['audio']['LAME']['RGAD']['radio']['name']       = RGADnameLookup($ThisFileInfo['mpeg']['audio']['LAME']['RGAD']['radio']['raw']['name']);
						$ThisFileInfo['mpeg']['audio']['LAME']['RGAD']['radio']['originator'] = RGADoriginatorLookup($ThisFileInfo['mpeg']['audio']['LAME']['RGAD']['radio']['raw']['originator']);
						$ThisFileInfo['mpeg']['audio']['LAME']['RGAD']['radio']['gain_db']    = RGADadjustmentLookup($ThisFileInfo['mpeg']['audio']['LAME']['RGAD']['radio']['raw']['gain_adjust'], $ThisFileInfo['mpeg']['audio']['LAME']['RGAD']['radio']['raw']['sign_bit']);

						if ($ThisFileInfo['mpeg']['audio']['LAME']['RGAD']['peak_amplitude'] !== false) {
							$ThisFileInfo['replay_gain']['radio']['peak']   = $ThisFileInfo['mpeg']['audio']['LAME']['RGAD']['peak_amplitude'];
						}
						$ThisFileInfo['replay_gain']['radio']['originator'] = $ThisFileInfo['mpeg']['audio']['LAME']['RGAD']['radio']['originator'];
						$ThisFileInfo['replay_gain']['radio']['adjustment'] = $ThisFileInfo['mpeg']['audio']['LAME']['RGAD']['radio']['gain_db'];
					}
					if ($ThisFileInfo['mpeg']['audio']['LAME']['raw']['RGAD_audiophile'] != 0) {
						require_once(GETID3_INCLUDEPATH.'getid3.rgad.php');

						$ThisFileInfo['mpeg']['audio']['LAME']['RGAD']['audiophile']['raw']['name']        = ($ThisFileInfo['mpeg']['audio']['LAME']['raw']['RGAD_audiophile'] & 0xE000) >> 13;
						$ThisFileInfo['mpeg']['audio']['LAME']['RGAD']['audiophile']['raw']['originator']  = ($ThisFileInfo['mpeg']['audio']['LAME']['raw']['RGAD_audiophile'] & 0x1C00) >> 10;
						$ThisFileInfo['mpeg']['audio']['LAME']['RGAD']['audiophile']['raw']['sign_bit']    = ($ThisFileInfo['mpeg']['audio']['LAME']['raw']['RGAD_audiophile'] & 0x0200) >> 9;
						$ThisFileInfo['mpeg']['audio']['LAME']['RGAD']['audiophile']['raw']['gain_adjust'] = $ThisFileInfo['mpeg']['audio']['LAME']['raw']['RGAD_audiophile'] & 0x01FF;
						$ThisFileInfo['mpeg']['audio']['LAME']['RGAD']['audiophile']['name']       = RGADnameLookup($ThisFileInfo['mpeg']['audio']['LAME']['RGAD']['audiophile']['raw']['name']);
						$ThisFileInfo['mpeg']['audio']['LAME']['RGAD']['audiophile']['originator'] = RGADoriginatorLookup($ThisFileInfo['mpeg']['audio']['LAME']['RGAD']['audiophile']['raw']['originator']);
						$ThisFileInfo['mpeg']['audio']['LAME']['RGAD']['audiophile']['gain_db']    = RGADadjustmentLookup($ThisFileInfo['mpeg']['audio']['LAME']['RGAD']['audiophile']['raw']['gain_adjust'], $ThisFileInfo['mpeg']['audio']['LAME']['RGAD']['audiophile']['raw']['sign_bit']);

						if ($ThisFileInfo['mpeg']['audio']['LAME']['RGAD']['peak_amplitude'] !== false) {
							$ThisFileInfo['replay_gain']['audiophile']['peak']   = $ThisFileInfo['mpeg']['audio']['LAME']['RGAD']['peak_amplitude'];
						}
						$ThisFileInfo['replay_gain']['audiophile']['originator'] = $ThisFileInfo['mpeg']['audio']['LAME']['RGAD']['audiophile']['originator'];
						$ThisFileInfo['replay_gain']['audiophile']['adjustment'] = $ThisFileInfo['mpeg']['audio']['LAME']['RGAD']['audiophile']['gain_db'];
					}


					// byte $AF  Encoding flags + ATH Type
					$EncodingFlagsATHtype = BigEndian2Int(substr($headerstring, $LAMEtagOffsetContant + 0xAF, 1));
					$ThisFileInfo['mpeg']['audio']['LAME']['encoding_flags']['nspsytune']   = (bool) ($EncodingFlagsATHtype & 0x10);
					$ThisFileInfo['mpeg']['audio']['LAME']['encoding_flags']['nssafejoint'] = (bool) ($EncodingFlagsATHtype & 0x20);
					$ThisFileInfo['mpeg']['audio']['LAME']['encoding_flags']['nogap_next']  = (bool) ($EncodingFlagsATHtype & 0x40);
					$ThisFileInfo['mpeg']['audio']['LAME']['encoding_flags']['nogap_prev']  = (bool) ($EncodingFlagsATHtype & 0x80);
					$ThisFileInfo['mpeg']['audio']['LAME']['ath_type']                      =         $EncodingFlagsATHtype & 0x0F;

					// byte $B0  if ABR {specified bitrate} else {minimal bitrate}
					$ABRbitrateMinBitrate = BigEndian2Int(substr($headerstring, $LAMEtagOffsetContant + 0xB0, 1));
					if ($ThisFileInfo['mpeg']['audio']['LAME']['raw']['vbr_method'] == 2) { // Average BitRate (ABR)
						$ThisFileInfo['mpeg']['audio']['LAME']['bitrate_abr'] = $ABRbitrateMinBitrate;
					} elseif ($ABRbitrateMinBitrate > 0) { // Variable BitRate (VBR) - minimum bitrate
						$ThisFileInfo['mpeg']['audio']['LAME']['bitrate_min'] = $ABRbitrateMinBitrate;
					}

					// bytes $B1-$B3  Encoder delays
					$EncoderDelays = BigEndian2Int(substr($headerstring, $LAMEtagOffsetContant + 0xB1, 3));
					$ThisFileInfo['mpeg']['audio']['LAME']['encoder_delay'] = ($EncoderDelays & 0xFFF000) >> 12;
					$ThisFileInfo['mpeg']['audio']['LAME']['end_padding']   =  $EncoderDelays & 0x000FFF;

					// byte $B4  Misc
					$MiscByte = BigEndian2Int(substr($headerstring, $LAMEtagOffsetContant + 0xB4, 1));
					$ThisFileInfo['mpeg']['audio']['LAME']['raw']['noise_shaping']       = ($MiscByte & 0x03);
					$ThisFileInfo['mpeg']['audio']['LAME']['raw']['stereo_mode']         = ($MiscByte & 0x1C) >> 2;
					$ThisFileInfo['mpeg']['audio']['LAME']['raw']['not_optimal_quality'] = ($MiscByte & 0x20) >> 5;
					$ThisFileInfo['mpeg']['audio']['LAME']['raw']['source_sample_freq']  = ($MiscByte & 0xC0) >> 6;
					$ThisFileInfo['mpeg']['audio']['LAME']['noise_shaping']       = $ThisFileInfo['mpeg']['audio']['LAME']['raw']['noise_shaping'];
					$ThisFileInfo['mpeg']['audio']['LAME']['stereo_mode']         = LAMEmiscStereoModeLookup($ThisFileInfo['mpeg']['audio']['LAME']['raw']['stereo_mode']);
					$ThisFileInfo['mpeg']['audio']['LAME']['not_optimal_quality'] = (bool) $ThisFileInfo['mpeg']['audio']['LAME']['raw']['not_optimal_quality'];
					$ThisFileInfo['mpeg']['audio']['LAME']['source_sample_freq']  = LAMEmiscSourceSampleFrequencyLookup($ThisFileInfo['mpeg']['audio']['LAME']['raw']['source_sample_freq']);

					// byte $B5  MP3 Gain
					$ThisFileInfo['mpeg']['audio']['LAME']['raw']['mp3_gain'] = BigEndian2Int(substr($headerstring, $LAMEtagOffsetContant + 0xB5, 1), false, true);
					$ThisFileInfo['mpeg']['audio']['LAME']['mp3_gain_db']     = 1.5 * $ThisFileInfo['mpeg']['audio']['LAME']['raw']['mp3_gain'];
					$ThisFileInfo['mpeg']['audio']['LAME']['mp3_gain_factor'] = pow(2, ($ThisFileInfo['mpeg']['audio']['LAME']['mp3_gain_db'] / 6));

					// bytes $B6-$B7  Preset and surround info
					$PresetSurroundBytes = BigEndian2Int(substr($headerstring, $LAMEtagOffsetContant + 0xB6, 2));
					// Reserved                                                    = ($PresetSurroundBytes & 0xC000);
					$ThisFileInfo['mpeg']['audio']['LAME']['raw']['surround_info'] = ($PresetSurroundBytes & 0x3800);
					$ThisFileInfo['mpeg']['audio']['LAME']['surround_info']        = LAMEsurroundInfoLookup($ThisFileInfo['mpeg']['audio']['LAME']['raw']['surround_info']);
					$ThisFileInfo['mpeg']['audio']['LAME']['preset_used_id']       = ($PresetSurroundBytes & 0x07FF);

					// bytes $B8-$BB  MusicLength
					$ThisFileInfo['mpeg']['audio']['LAME']['audio_bytes'] = BigEndian2Int(substr($headerstring, $LAMEtagOffsetContant + 0xB8, 4));
					$ExpectedNumberOfAudioBytes = (($ThisFileInfo['mpeg']['audio']['LAME']['audio_bytes'] > 0) ? $ThisFileInfo['mpeg']['audio']['LAME']['audio_bytes'] : $ThisFileInfo['mpeg']['audio']['VBR_bytes']);

					// bytes $BC-$BD  MusicCRC
					$ThisFileInfo['mpeg']['audio']['LAME']['music_crc']    = BigEndian2Int(substr($headerstring, $LAMEtagOffsetContant + 0xBC, 2));

					// bytes $BE-$BF  CRC-16 of Info Tag
					$ThisFileInfo['mpeg']['audio']['LAME']['lame_tag_crc'] = BigEndian2Int(substr($headerstring, $LAMEtagOffsetContant + 0xBE, 2));


					// LAME CBR
					if ($ThisFileInfo['mpeg']['audio']['LAME']['raw']['vbr_method'] == 1) {

						$ThisFileInfo['mpeg']['audio']['bitrate_mode'] = 'cbr';
						if (empty($ThisFileInfo['mpeg']['audio']['bitrate']) || ($ThisFileInfo['mpeg']['audio']['LAME']['bitrate_min'] != 255)) {
							$ThisFileInfo['mpeg']['audio']['bitrate'] = $ThisFileInfo['mpeg']['audio']['LAME']['bitrate_min'];
						}

					}

				}
			}

		} else {

			// not Fraunhofer or Xing VBR methods, most likely CBR (but could be VBR with no header)
			$ThisFileInfo['mpeg']['audio']['bitrate_mode'] = 'cbr';
			if ($recursivesearch) {
				$ThisFileInfo['mpeg']['audio']['bitrate_mode'] = 'vbr';
				if (RecursiveFrameScanning($fd, $ThisFileInfo, $offset, $nextframetestoffset, true)) {
					$recursivesearch = false;
					$ThisFileInfo['mpeg']['audio']['bitrate_mode'] = 'cbr';
				}
				if ($ThisFileInfo['mpeg']['audio']['bitrate_mode'] == 'vbr') {
					$ThisFileInfo['warning'] .= "\n".'VBR file with no VBR header. Bitrate values calculated from actual frame bitrates.';
				}
			}

		}

	}

	if (($ExpectedNumberOfAudioBytes > 0) && ($ExpectedNumberOfAudioBytes != ($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']))) {
		if (($ExpectedNumberOfAudioBytes - ($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset'])) == 1) {
			$ThisFileInfo['warning'] .= "\n".'Last byte of data truncated (this is a known bug in Meracl ID3 Tag Writer before v1.3.5)';
		} elseif ($ExpectedNumberOfAudioBytes > ($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset'])) {
			$ThisFileInfo['warning'] .= "\n".'Probable truncated file: expecting '.$ExpectedNumberOfAudioBytes.' bytes of audio data, only found '.($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']).' (short by '.($ExpectedNumberOfAudioBytes - ($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset'])).' bytes)';
		} else {
			$ThisFileInfo['warning'] .= "\n".'Too much data in file: expecting '.$ExpectedNumberOfAudioBytes.' bytes of audio data, found '.($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']).' ('.(($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']) - $ExpectedNumberOfAudioBytes).' bytes too many)';
		}
	}

	if (($ThisFileInfo['mpeg']['audio']['bitrate'] == 'free') && empty($ThisFileInfo['audio']['bitrate'])) {
		if (($offset == $ThisFileInfo['avdataoffset']) && empty($ThisFileInfo['mpeg']['audio']['VBR_frames'])) {
			$framebytelength = FreeFormatFrameLength($fd, $offset, $ThisFileInfo, true);
			if ($framebytelength > 0) {
				$ThisFileInfo['mpeg']['audio']['framelength'] = $framebytelength;
				if ($ThisFileInfo['mpeg']['audio']['layer'] == 'I') {
					// BitRate = (((FrameLengthInBytes / 4) - Padding) * SampleRate) / 12
					$ThisFileInfo['audio']['bitrate'] = ((($framebytelength / 4) - intval($ThisFileInfo['mpeg']['audio']['padding'])) * $ThisFileInfo['mpeg']['audio']['sample_rate']) / 12;
				} else {
					// Bitrate = ((FrameLengthInBytes - Padding) * SampleRate) / 144
					$ThisFileInfo['audio']['bitrate'] = (($framebytelength - intval($ThisFileInfo['mpeg']['audio']['padding'])) * $ThisFileInfo['mpeg']['audio']['sample_rate']) / 144;
				}
			} else {
				$ThisFileInfo['error'] .= "\n".'Error calculating frame length of free-format MP3 without Xing/LAME header';
			}
		}
	}

	if (($ThisFileInfo['mpeg']['audio']['bitrate_mode'] == 'vbr') && isset($ThisFileInfo['mpeg']['audio']['VBR_frames']) && ($ThisFileInfo['mpeg']['audio']['VBR_frames'] > 1)) {
		$ThisFileInfo['mpeg']['audio']['VBR_frames']--; // don't count the Xing / VBRI frame
		if (($ThisFileInfo['mpeg']['audio']['version'] == '1') && ($ThisFileInfo['mpeg']['audio']['layer'] == 'I')) {
			$ThisFileInfo['mpeg']['audio']['VBR_bitrate'] = ((($ThisFileInfo['mpeg']['audio']['VBR_bytes'] / $ThisFileInfo['mpeg']['audio']['VBR_frames']) * 8) * ($ThisFileInfo['audio']['sample_rate'] / 384)) / 1000;
		} elseif ((($ThisFileInfo['mpeg']['audio']['version'] == '2') || ($ThisFileInfo['mpeg']['audio']['version'] == '2.5')) && ($ThisFileInfo['mpeg']['audio']['layer'] == 'III')) {
			$ThisFileInfo['mpeg']['audio']['VBR_bitrate'] = ((($ThisFileInfo['mpeg']['audio']['VBR_bytes'] / $ThisFileInfo['mpeg']['audio']['VBR_frames']) * 8) * ($ThisFileInfo['audio']['sample_rate'] / 576)) / 1000;
		} else {
			$ThisFileInfo['mpeg']['audio']['VBR_bitrate'] = ((($ThisFileInfo['mpeg']['audio']['VBR_bytes'] / $ThisFileInfo['mpeg']['audio']['VBR_frames']) * 8) * ($ThisFileInfo['audio']['sample_rate'] / 1152)) / 1000;
		}
		if ($ThisFileInfo['mpeg']['audio']['VBR_bitrate'] > 0) {
			$ThisFileInfo['audio']['bitrate']         = 1000 * $ThisFileInfo['mpeg']['audio']['VBR_bitrate'];
			$ThisFileInfo['mpeg']['audio']['bitrate'] = $ThisFileInfo['mpeg']['audio']['VBR_bitrate']; // to avoid confusion
		}
	}

	// End variable-bitrate headers
	////////////////////////////////////////////////////////////////////////////////////

	if ($recursivesearch) {

		if (!RecursiveFrameScanning($fd, $ThisFileInfo, $offset, $nextframetestoffset, $ScanAsCBR)) {
			return false;
		}

	}


	//if (false) {
	//	// experimental side info parsing section - not returning anything useful yet
    //
	//	$SideInfoBitstream = BigEndian2Bin($SideInfoData);
	//	$SideInfoOffset = 0;
    //
	//	if ($ThisFileInfo['mpeg']['audio']['version'] == '1') {
	//		if ($ThisFileInfo['mpeg']['audio']['channelmode'] == 'mono') {
	//			// MPEG-1 (mono)
	//			$ThisFileInfo['mpeg']['audio']['side_info']['main_data_begin'] = substr($SideInfoBitstream, $SideInfoOffset, 9);
	//			$SideInfoOffset += 9;
	//			$SideInfoOffset += 5;
	//		} else {
	//			// MPEG-1 (stereo, joint-stereo, dual-channel)
	//			$ThisFileInfo['mpeg']['audio']['side_info']['main_data_begin'] = substr($SideInfoBitstream, $SideInfoOffset, 9);
	//			$SideInfoOffset += 9;
	//			$SideInfoOffset += 3;
	//		}
	//	} else { // 2 or 2.5
	//		if ($ThisFileInfo['mpeg']['audio']['channelmode'] == 'mono') {
	//			// MPEG-2, MPEG-2.5 (mono)
	//			$ThisFileInfo['mpeg']['audio']['side_info']['main_data_begin'] = substr($SideInfoBitstream, $SideInfoOffset, 8);
	//			$SideInfoOffset += 8;
	//			$SideInfoOffset += 1;
	//		} else {
	//			// MPEG-2, MPEG-2.5 (stereo, joint-stereo, dual-channel)
	//			$ThisFileInfo['mpeg']['audio']['side_info']['main_data_begin'] = substr($SideInfoBitstream, $SideInfoOffset, 8);
	//			$SideInfoOffset += 8;
	//			$SideInfoOffset += 2;
	//		}
	//	}
    //
	//	if ($ThisFileInfo['mpeg']['audio']['version'] == '1') {
	//		for ($channel = 0; $channel < $ThisFileInfo['audio']['channels']; $channel++) {
	//			for ($scfsi_band = 0; $scfsi_band < 4; $scfsi_band++) {
	//				$ThisFileInfo['mpeg']['audio']['scfsi'][$channel][$scfsi_band] = substr($SideInfoBitstream, $SideInfoOffset, 1);
	//				$SideInfoOffset += 2;
	//			}
	//		}
	//	}
	//	for ($granule = 0; $granule < (($ThisFileInfo['mpeg']['audio']['version'] == '1') ? 2 : 1); $granule++) {
	//		for ($channel = 0; $channel < $ThisFileInfo['audio']['channels']; $channel++) {
	//			$ThisFileInfo['mpeg']['audio']['part2_3_length'][$granule][$channel] = substr($SideInfoBitstream, $SideInfoOffset, 12);
	//			$SideInfoOffset += 12;
	//			$ThisFileInfo['mpeg']['audio']['big_values'][$granule][$channel] = substr($SideInfoBitstream, $SideInfoOffset, 9);
	//			$SideInfoOffset += 9;
	//			$ThisFileInfo['mpeg']['audio']['global_gain'][$granule][$channel] = substr($SideInfoBitstream, $SideInfoOffset, 8);
	//			$SideInfoOffset += 8;
	//			if ($ThisFileInfo['mpeg']['audio']['version'] == '1') {
	//				$ThisFileInfo['mpeg']['audio']['scalefac_compress'][$granule][$channel] = substr($SideInfoBitstream, $SideInfoOffset, 4);
	//				$SideInfoOffset += 4;
	//			} else {
	//				$ThisFileInfo['mpeg']['audio']['scalefac_compress'][$granule][$channel] = substr($SideInfoBitstream, $SideInfoOffset, 9);
	//				$SideInfoOffset += 9;
	//			}
	//			$ThisFileInfo['mpeg']['audio']['window_switching_flag'][$granule][$channel] = substr($SideInfoBitstream, $SideInfoOffset, 1);
	//			$SideInfoOffset += 1;
    //
	//			if ($ThisFileInfo['mpeg']['audio']['window_switching_flag'][$granule][$channel] == '1') {
    //
	//				$ThisFileInfo['mpeg']['audio']['block_type'][$granule][$channel] = substr($SideInfoBitstream, $SideInfoOffset, 2);
	//				$SideInfoOffset += 2;
	//				$ThisFileInfo['mpeg']['audio']['mixed_block_flag'][$granule][$channel] = substr($SideInfoBitstream, $SideInfoOffset, 1);
	//				$SideInfoOffset += 1;
    //
	//				for ($region = 0; $region < 2; $region++) {
	//					$ThisFileInfo['mpeg']['audio']['table_select'][$granule][$channel][$region] = substr($SideInfoBitstream, $SideInfoOffset, 5);
	//					$SideInfoOffset += 5;
	//				}
	//				$ThisFileInfo['mpeg']['audio']['table_select'][$granule][$channel][2] = 0;
    //
	//				for ($window = 0; $window < 3; $window++) {
	//					$ThisFileInfo['mpeg']['audio']['subblock_gain'][$granule][$channel][$window] = substr($SideInfoBitstream, $SideInfoOffset, 3);
	//					$SideInfoOffset += 3;
	//				}
    //
	//			} else {
    //
	//				for ($region = 0; $region < 3; $region++) {
	//					$ThisFileInfo['mpeg']['audio']['table_select'][$granule][$channel][$region] = substr($SideInfoBitstream, $SideInfoOffset, 5);
	//					$SideInfoOffset += 5;
	//				}
    //
	//				$ThisFileInfo['mpeg']['audio']['region0_count'][$granule][$channel] = substr($SideInfoBitstream, $SideInfoOffset, 4);
	//				$SideInfoOffset += 4;
	//				$ThisFileInfo['mpeg']['audio']['region1_count'][$granule][$channel] = substr($SideInfoBitstream, $SideInfoOffset, 3);
	//				$SideInfoOffset += 3;
	//				$ThisFileInfo['mpeg']['audio']['block_type'][$granule][$channel] = 0;
	//			}
    //
	//			if ($ThisFileInfo['mpeg']['audio']['version'] == '1') {
	//				$ThisFileInfo['mpeg']['audio']['preflag'][$granule][$channel] = substr($SideInfoBitstream, $SideInfoOffset, 1);
	//				$SideInfoOffset += 1;
	//			}
	//			$ThisFileInfo['mpeg']['audio']['scalefac_scale'][$granule][$channel] = substr($SideInfoBitstream, $SideInfoOffset, 1);
	//			$SideInfoOffset += 1;
	//			$ThisFileInfo['mpeg']['audio']['count1table_select'][$granule][$channel] = substr($SideInfoBitstream, $SideInfoOffset, 1);
	//			$SideInfoOffset += 1;
	//		}
	//	}
	//}

	return true;
}

function RecursiveFrameScanning(&$fd, &$ThisFileInfo, &$offset, &$nextframetestoffset, $ScanAsCBR) {
	for ($i = 0; $i < MPEG_VALID_CHECK_FRAMES; $i++) {
		// check next MPEG_VALID_CHECK_FRAMES frames for validity, to make sure we haven't run across a false synch
		if (($nextframetestoffset + 4) >= $ThisFileInfo['avdataend']) {
			// end of file
			return true;
		}

		$nextframetestarray = array('error'=>'', 'warning'=>'', 'avdataend'=>$ThisFileInfo['avdataend'], 'avdataoffset'=>$ThisFileInfo['avdataoffset']);
		if (decodeMPEGaudioHeader($fd, $nextframetestoffset, $nextframetestarray, false)) {
			if ($ScanAsCBR) {
				// force CBR mode, used for trying to pick out invalid audio streams with
				// valid(?) VBR headers, or VBR streams with no VBR header
				if (!isset($nextframetestarray['mpeg']['audio']['bitrate']) || !isset($ThisFileInfo['mpeg']['audio']['bitrate']) || ($nextframetestarray['mpeg']['audio']['bitrate'] != $ThisFileInfo['mpeg']['audio']['bitrate'])) {
					return false;
				}
			}


			// next frame is OK, get ready to check the one after that
			if (isset($nextframetestarray['mpeg']['audio']['framelength']) && ($nextframetestarray['mpeg']['audio']['framelength'] > 0)) {
				$nextframetestoffset += $nextframetestarray['mpeg']['audio']['framelength'];
			} else {
				$ThisFileInfo['error'] .= "\n".'Frame at offset ('.$offset.') is has an invalid frame length.';
				return false;
			}

		} else {

			// next frame is not valid, note the error and fail, so scanning can contiue for a valid frame sequence
			$ThisFileInfo['error'] .= "\n".'Frame at offset ('.$offset.') is valid, but the next one at ('.$nextframetestoffset.') is not.';

			return false;
		}
	}
	return true;
}

function FreeFormatFrameLength($fd, $offset, &$ThisFileInfo, $deepscan=false) {
	fseek($fd, $offset, SEEK_SET);
	$MPEGaudioData = fread($fd, 32768);

	$SyncPattern1 = substr($MPEGaudioData, 0, 4);
	// may be different pattern due to padding
	$SyncPattern2 = $SyncPattern1{0}.$SyncPattern1{1}.chr(ord($SyncPattern1{2}) | 0x02).$SyncPattern1{3};
	if ($SyncPattern2 === $SyncPattern1) {
		$SyncPattern2 = $SyncPattern1{0}.$SyncPattern1{1}.chr(ord($SyncPattern1{2}) & 0xFD).$SyncPattern1{3};
	}

	$framelength = false;
	$framelength1 = strpos($MPEGaudioData, $SyncPattern1, 4);
	$framelength2 = strpos($MPEGaudioData, $SyncPattern2, 4);
	if ($framelength1 > 4) {
		$framelength = $framelength1;
	}
	if (($framelength2 > 4) && ($framelength2 < $framelength1)) {
		$framelength = $framelength2;
	}
	if (!$framelength) {

		// LAME 3.88 has a different value for modeextension on the first frame vs the rest
		$framelength1 = strpos($MPEGaudioData, substr($SyncPattern1, 0, 3), 4);
		$framelength2 = strpos($MPEGaudioData, substr($SyncPattern2, 0, 3), 4);

		if ($framelength1 > 4) {
			$framelength = $framelength1;
		}
		if (($framelength2 > 4) && ($framelength2 < $framelength1)) {
			$framelength = $framelength2;
		}
		if (!$framelength) {
			$ThisFileInfo['error'] .= "\n".'Cannot find next free-format synch pattern ('.PrintHexBytes($SyncPattern1).' or '.PrintHexBytes($SyncPattern2).') after offset '.$offset;
			return false;
		} else {
			$ThisFileInfo['warning'] .= "\n".'ModeExtension varies between first frame and other frames (known free-format issue in LAME 3.88)';
			$ThisFileInfo['audio']['codec']   = 'LAME';
			$ThisFileInfo['audio']['encoder'] = 'LAME3.88';
			$SyncPattern1 = substr($SyncPattern1, 0, 3);
			$SyncPattern2 = substr($SyncPattern2, 0, 3);
		}
	}

	if ($deepscan) {

		$ActualFrameLengthValues = array();
		$nextoffset = $offset + $framelength;
		while ($nextoffset < ($ThisFileInfo['avdataend'] - 6)) {
			fseek($fd, $nextoffset - 1, SEEK_SET);
			$NextSyncPattern = fread($fd, 6);
			if ((substr($NextSyncPattern, 1, strlen($SyncPattern1)) == $SyncPattern1) || (substr($NextSyncPattern, 1, strlen($SyncPattern2)) == $SyncPattern2)) {
				// good - found where expected
				$ActualFrameLengthValues[] = $framelength;
			} elseif ((substr($NextSyncPattern, 0, strlen($SyncPattern1)) == $SyncPattern1) || (substr($NextSyncPattern, 0, strlen($SyncPattern2)) == $SyncPattern2)) {
				// ok - found one byte earlier than expected (last frame wasn't padded, first frame was)
				$ActualFrameLengthValues[] = ($framelength - 1);
				$nextoffset--;
			} elseif ((substr($NextSyncPattern, 2, strlen($SyncPattern1)) == $SyncPattern1) || (substr($NextSyncPattern, 2, strlen($SyncPattern2)) == $SyncPattern2)) {
				// ok - found one byte later than expected (last frame was padded, first frame wasn't)
				$ActualFrameLengthValues[] = ($framelength + 1);
				$nextoffset++;
			} else {
				$ThisFileInfo['error'] .= "\n".'Did not find expected free-format sync pattern at offset '.$nextoffset;
				return false;
			}
			$nextoffset += $framelength;
		}
		if (count($ActualFrameLengthValues) > 0) {
			$framelength = round(array_sum($ActualFrameLengthValues) / count($ActualFrameLengthValues));
		}
	}
	return $framelength;
}


function getOnlyMPEGaudioInfo($fd, &$ThisFileInfo, $avdataoffset, $BitrateHistogram=false) {
	// looks for synch, decodes MPEG audio header

	fseek($fd, $avdataoffset, SEEK_SET);
	$header = '';
	$SynchSeekOffset = 0;

	if (!defined('CONST_FF')) {
		define('CONST_FF', chr(0xFF));
		define('CONST_E0', chr(0xE0));
	}

	static $MPEGaudioVersionLookup;
	static $MPEGaudioLayerLookup;
	static $MPEGaudioBitrateLookup;
	if (empty($MPEGaudioVersionLookup)) {
		$MPEGaudioVersionLookup = MPEGaudioVersionArray();
		$MPEGaudioLayerLookup   = MPEGaudioLayerArray();
		$MPEGaudioBitrateLookup = MPEGaudioBitrateArray();

	}

	$header_len = strlen($header) - round(FREAD_BUFFER_SIZE / 2);
	while (true) {

		if (($SynchSeekOffset > $header_len) && (($avdataoffset + $SynchSeekOffset)  < $ThisFileInfo['avdataend']) && !feof($fd)) {

			if ($SynchSeekOffset > 131072) {
				// if a synch's not found within the first 128k bytes, then give up
				$ThisFileInfo['error'] .= "\n".'could not find valid MPEG synch within the first 131072 bytes';
				if (isset($ThisFileInfo['audio']['bitrate'])) {
					unset($ThisFileInfo['audio']['bitrate']);
				}
				if (isset($ThisFileInfo['mpeg']['audio'])) {
					unset($ThisFileInfo['mpeg']['audio']);
				}
				if (isset($ThisFileInfo['mpeg']) && (!is_array($ThisFileInfo['mpeg']) || (count($ThisFileInfo['mpeg']) == 0))) {
					unset($ThisFileInfo['mpeg']);
				}
				return false;

			} elseif ($header .= fread($fd, FREAD_BUFFER_SIZE)) {

				// great
				$header_len = strlen($header) - round(FREAD_BUFFER_SIZE / 2);

			} else {

				$ThisFileInfo['error'] .= "\n".'could not find valid MPEG synch before end of file';
				if (isset($ThisFileInfo['audio']['bitrate'])) {
					unset($ThisFileInfo['audio']['bitrate']);
				}
				if (isset($ThisFileInfo['mpeg']['audio'])) {
					unset($ThisFileInfo['mpeg']['audio']);
				}
				if (isset($ThisFileInfo['mpeg']) && (!is_array($ThisFileInfo['mpeg']) || (count($ThisFileInfo['mpeg']) == 0))) {
					unset($ThisFileInfo['mpeg']);
				}
				return false;

			}
		}

		if (($SynchSeekOffset + 1) >= strlen($header)) {
			$ThisFileInfo['error'] .= "\n".'could not find valid MPEG synch before end of file';
			return false;
		}

		if (($header{$SynchSeekOffset} == CONST_FF) && ($header{($SynchSeekOffset + 1)} > CONST_E0)) { // synch detected

			if (!isset($FirstFrameThisfileInfo) && !isset($ThisFileInfo['mpeg']['audio'])) {
				$FirstFrameThisfileInfo = $ThisFileInfo;
				$FirstFrameAVDataOffset = $avdataoffset + $SynchSeekOffset;
				if (!decodeMPEGaudioHeader($fd, $avdataoffset + $SynchSeekOffset, $FirstFrameThisfileInfo, false)) {
					// if this is the first valid MPEG-audio frame, save it in case it's a VBR header frame and there's
					// garbage between this frame and a valid sequence of MPEG-audio frames, to be restored below
					unset($FirstFrameThisfileInfo);
				}
			}
			$dummy = $ThisFileInfo; // only overwrite real data if valid header found

			if (decodeMPEGaudioHeader($fd, $avdataoffset + $SynchSeekOffset, $dummy, true)) {

				$ThisFileInfo = $dummy;
				$ThisFileInfo['avdataoffset'] = $avdataoffset + $SynchSeekOffset;
				switch ($ThisFileInfo['fileformat']) {
					case '':
					case 'id3':
					case 'ape':
					case 'mp3':
						$ThisFileInfo['fileformat']               = 'mp3';
						$ThisFileInfo['audio']['dataformat']      = 'mp3';
				}
				if (isset($FirstFrameThisfileInfo['mpeg']['audio']['bitrate_mode']) && ($FirstFrameThisfileInfo['mpeg']['audio']['bitrate_mode'] == 'vbr')) {
					if (!CloseMatch($ThisFileInfo['audio']['bitrate'], $FirstFrameThisfileInfo['audio']['bitrate'], 1)) {
						// If there is garbage data between a valid VBR header frame and a sequence
						// of valid MPEG-audio frames the VBR data is no longer discarded.
						$ThisFileInfo = $FirstFrameThisfileInfo;
						$ThisFileInfo['avdataoffset']        = $FirstFrameAVDataOffset;
						$ThisFileInfo['fileformat']          = 'mp3';
						$ThisFileInfo['audio']['dataformat'] = 'mp3';
						$dummy                               = $ThisFileInfo;
						unset($dummy['mpeg']['audio']);
						$GarbageOffsetStart = $FirstFrameAVDataOffset + $FirstFrameThisfileInfo['mpeg']['audio']['framelength'];
						$GarbageOffsetEnd   = $avdataoffset + $SynchSeekOffset;
						if (decodeMPEGaudioHeader($fd, $GarbageOffsetEnd, $dummy, true, true)) {

							$ThisFileInfo = $dummy;
							$ThisFileInfo['avdataoffset'] = $GarbageOffsetEnd;
							$ThisFileInfo['warning'] .= "\n".'apparently-valid VBR header not used because could not find '.MPEG_VALID_CHECK_FRAMES.' consecutive MPEG-audio frames immediately after VBR header (garbage data for '.($GarbageOffsetEnd - $GarbageOffsetStart).' bytes between '.$GarbageOffsetStart.' and '.$GarbageOffsetEnd.'), but did find valid CBR stream starting at '.$GarbageOffsetEnd;

						} else {

							$ThisFileInfo['warning'] .= "\n".'using data from VBR header even though could not find '.MPEG_VALID_CHECK_FRAMES.' consecutive MPEG-audio frames immediately after VBR header (garbage data for '.($GarbageOffsetEnd - $GarbageOffsetStart).' bytes between '.$GarbageOffsetStart.' and '.$GarbageOffsetEnd.')';

						}
					}
				}

				if (isset($ThisFileInfo['mpeg']['audio']['bitrate_mode']) && ($ThisFileInfo['mpeg']['audio']['bitrate_mode'] == 'vbr') && !isset($ThisFileInfo['mpeg']['audio']['VBR_method'])) {
					// VBR file with no VBR header
					$BitrateHistogram = true;
				}

				if ($BitrateHistogram) {

					$ThisFileInfo['mpeg']['audio']['stereo_distribution']  = array('stereo'=>0, 'joint stereo'=>0, 'dual channel'=>0, 'mono'=>0);
					$ThisFileInfo['mpeg']['audio']['version_distribution'] = array('1'=>0, '2'=>0, '2.5'=>0);

					if ($ThisFileInfo['mpeg']['audio']['version'] == '1') {
						if ($ThisFileInfo['mpeg']['audio']['layer'] == 'III') {
							$ThisFileInfo['mpeg']['audio']['bitrate_distribution'] = array('free'=>0, 32=>0, 40=>0, 48=>0, 56=>0, 64=>0, 80=>0, 96=>0, 112=>0, 128=>0, 160=>0, 192=>0, 224=>0, 256=>0, 320=>0);
						} elseif ($ThisFileInfo['mpeg']['audio']['layer'] == 'II') {
							$ThisFileInfo['mpeg']['audio']['bitrate_distribution'] = array('free'=>0, 32=>0, 48=>0, 56=>0, 64=>0, 80=>0, 96=>0, 112=>0, 128=>0, 160=>0, 192=>0, 224=>0, 256=>0, 320=>0, 384=>0);
						} elseif ($ThisFileInfo['mpeg']['audio']['layer'] == 'I') {
							$ThisFileInfo['mpeg']['audio']['bitrate_distribution'] = array('free'=>0, 32=>0, 64=>0, 96=>0, 128=>0, 160=>0, 192=>0, 224=>0, 256=>0, 288=>0, 320=>0, 352=>0, 384=>0, 416=>0, 448=>0);
						}
					} elseif ($ThisFileInfo['mpeg']['audio']['layer'] == 'I') {
						$ThisFileInfo['mpeg']['audio']['bitrate_distribution'] = array('free'=>0, 32=>0, 48=>0, 56=>0, 64=>0, 80=>0, 96=>0, 112=>0, 128=>0, 144=>0, 160=>0, 176=>0, 192=>0, 224=>0, 256=>0);
					} else {
						$ThisFileInfo['mpeg']['audio']['bitrate_distribution'] = array('free'=>0, 8=>0, 16=>0, 24=>0, 32=>0, 40=>0, 48=>0, 56=>0, 64=>0, 80=>0, 96=>0, 112=>0, 128=>0, 144=>0, 160=>0);
					}

					$dummy = array('error'=>$ThisFileInfo['error'], 'warning'=>$ThisFileInfo['warning'], 'avdataend'=>$ThisFileInfo['avdataend'], 'avdataoffset'=>$ThisFileInfo['avdataoffset']);
					$synchstartoffset = $ThisFileInfo['avdataoffset'];

					$FastMode = false;
					while (decodeMPEGaudioHeader($fd, $synchstartoffset, $dummy, false, false, $FastMode)) {
						$FastMode = true;
						$thisframebitrate = $MPEGaudioBitrateLookup[$MPEGaudioVersionLookup[$dummy['mpeg']['audio']['raw']['version']]][$MPEGaudioLayerLookup[$dummy['mpeg']['audio']['raw']['layer']]][$dummy['mpeg']['audio']['raw']['bitrate']];

						$ThisFileInfo['mpeg']['audio']['bitrate_distribution'][$thisframebitrate]++;
						$ThisFileInfo['mpeg']['audio']['stereo_distribution'][$dummy['mpeg']['audio']['channelmode']]++;
						$ThisFileInfo['mpeg']['audio']['version_distribution'][$dummy['mpeg']['audio']['version']]++;
						if (empty($dummy['mpeg']['audio']['framelength'])) {
							$ThisFileInfo['warning'] .= "\n".'Invalid/missing framelength in histogram analysis - aborting';
$synchstartoffset += 4;
//							return false;
						}
						$synchstartoffset += $dummy['mpeg']['audio']['framelength'];
					}

					$bittotal     = 0;
					$framecounter = 0;
					foreach ($ThisFileInfo['mpeg']['audio']['bitrate_distribution'] as $bitratevalue => $bitratecount) {
						$framecounter += $bitratecount;
						if ($bitratevalue != 'free') {
							$bittotal += ($bitratevalue * $bitratecount);
						}
					}
					if ($framecounter == 0) {
						$ThisFileInfo['error'] .= "\n".'Corrupt MP3 file: framecounter == zero';
						return false;
					}
					$ThisFileInfo['mpeg']['audio']['frame_count'] = $framecounter;
					$ThisFileInfo['mpeg']['audio']['bitrate']     = 1000 * ($bittotal / $framecounter);

					$ThisFileInfo['audio']['bitrate'] = $ThisFileInfo['mpeg']['audio']['bitrate'];


					// Definitively set VBR vs CBR, even if the Xing/LAME/VBRI header says differently
					$distinct_bitrates = 0;
					foreach ($ThisFileInfo['mpeg']['audio']['bitrate_distribution'] as $bitrate_value => $bitrate_count) {
						if ($bitrate_count > 0) {
							$distinct_bitrates++;
						}
					}
					if ($distinct_bitrates > 1) {
						$ThisFileInfo['mpeg']['audio']['bitrate_mode'] = 'vbr';
					} else {
						$ThisFileInfo['mpeg']['audio']['bitrate_mode'] = 'cbr';
					}
					$ThisFileInfo['audio']['bitrate_mode'] = $ThisFileInfo['mpeg']['audio']['bitrate_mode'];

				}

				break; // exit while()
			}
		}

		$SynchSeekOffset++;
		if (($avdataoffset + $SynchSeekOffset) >= $ThisFileInfo['avdataend']) {
			// end of file/data

			if (empty($ThisFileInfo['mpeg']['audio'])) {

				$ThisFileInfo['error'] .= "\n".'could not find valid MPEG synch before end of file';
				if (isset($ThisFileInfo['audio']['bitrate'])) {
					unset($ThisFileInfo['audio']['bitrate']);
				}
				if (isset($ThisFileInfo['mpeg']['audio'])) {
					unset($ThisFileInfo['mpeg']['audio']);
				}
				if (isset($ThisFileInfo['mpeg']) && (!is_array($ThisFileInfo['mpeg']) || empty($ThisFileInfo['mpeg']))) {
					unset($ThisFileInfo['mpeg']);
				}
				return false;

			}
			break;
		}

	}
	$ThisFileInfo['audio']['bits_per_sample'] = 16;
	$ThisFileInfo['audio']['channels']        = $ThisFileInfo['mpeg']['audio']['channels'];
	$ThisFileInfo['audio']['channelmode']     = $ThisFileInfo['mpeg']['audio']['channelmode'];
	$ThisFileInfo['audio']['sample_rate']     = $ThisFileInfo['mpeg']['audio']['sample_rate'];
	return true;
}


function MPEGaudioVersionArray() {
	static $MPEGaudioVersion = array('2.5', false, '2', '1');
	return $MPEGaudioVersion;
}

function MPEGaudioLayerArray() {
	static $MPEGaudioLayer = array(false, 'III', 'II', 'I');
	return $MPEGaudioLayer;
}

function MPEGaudioBitrateArray() {
	static $MPEGaudioBitrate;
	if (empty($MPEGaudioBitrate)) {
		$MPEGaudioBitrate['1']['I']     = array('free', 32, 64, 96, 128, 160, 192, 224, 256, 288, 320, 352, 384, 416, 448);
		$MPEGaudioBitrate['1']['II']    = array('free', 32, 48, 56,  64,  80,  96, 112, 128, 160, 192, 224, 256, 320, 384);
		$MPEGaudioBitrate['1']['III']   = array('free', 32, 40, 48,  56,  64,  80,  96, 112, 128, 160, 192, 224, 256, 320);
		$MPEGaudioBitrate['2']['I']     = array('free', 32, 48, 56,  64,  80,  96, 112, 128, 144, 160, 176, 192, 224, 256);
		$MPEGaudioBitrate['2']['II']    = array('free',  8, 16, 24,  32,  40,  48,  56,  64,  80,  96, 112, 128, 144, 160);
		$MPEGaudioBitrate['2']['III']   = $MPEGaudioBitrate['2']['II'];
		$MPEGaudioBitrate['2.5']['I']   = $MPEGaudioBitrate['2']['I'];
		$MPEGaudioBitrate['2.5']['II']  = $MPEGaudioBitrate['2']['II'];
		$MPEGaudioBitrate['2.5']['III'] = $MPEGaudioBitrate['2']['III'];
	}
	return $MPEGaudioBitrate;
}

function MPEGaudioFrequencyArray() {
	static $MPEGaudioFrequency;
	if (empty($MPEGaudioFrequency)) {
		$MPEGaudioFrequency['1']   = array(44100, 48000, 32000);
		$MPEGaudioFrequency['2']   = array(22050, 24000, 16000);
		$MPEGaudioFrequency['2.5'] = array(11025, 12000,  8000);
	}
	return $MPEGaudioFrequency;
}

function MPEGaudioChannelModeArray() {
	static $MPEGaudioChannelMode = array('stereo', 'joint stereo', 'dual channel', 'mono');
	return $MPEGaudioChannelMode;
}

function MPEGaudioModeExtensionArray() {
	static $MPEGaudioModeExtension;
	if (empty($MPEGaudioModeExtension)) {
		$MPEGaudioModeExtension['I']   = array('4-31', '8-31', '12-31', '16-31');
		$MPEGaudioModeExtension['II']  = array('4-31', '8-31', '12-31', '16-31');
		$MPEGaudioModeExtension['III'] = array('', 'IS', 'MS', 'IS+MS');
	}
	return $MPEGaudioModeExtension;
}

function MPEGaudioEmphasisArray() {
	static $MPEGaudioEmphasis = array('none', '50/15ms', false, 'CCIT J.17');
	return $MPEGaudioEmphasis;
}


function MPEGaudioHeaderBytesValid($head4) {
	return MPEGaudioHeaderValid(MPEGaudioHeaderDecode($head4));
}

function MPEGaudioHeaderValid($rawarray, $echoerrors=false) {

	if (($rawarray['synch'] & 0x0FFE) != 0x0FFE) {
		return false;
	}

	static $MPEGaudioVersionLookup;
	static $MPEGaudioLayerLookup;
	static $MPEGaudioBitrateLookup;
	static $MPEGaudioFrequencyLookup;
	static $MPEGaudioChannelModeLookup;
	static $MPEGaudioModeExtensionLookup;
	static $MPEGaudioEmphasisLookup;
	if (empty($MPEGaudioVersionLookup)) {
		$MPEGaudioVersionLookup       = MPEGaudioVersionArray();
		$MPEGaudioLayerLookup         = MPEGaudioLayerArray();
		$MPEGaudioBitrateLookup       = MPEGaudioBitrateArray();
		$MPEGaudioFrequencyLookup     = MPEGaudioFrequencyArray();
		$MPEGaudioChannelModeLookup   = MPEGaudioChannelModeArray();
		$MPEGaudioModeExtensionLookup = MPEGaudioModeExtensionArray();
		$MPEGaudioEmphasisLookup      = MPEGaudioEmphasisArray();
	}

	if (isset($MPEGaudioVersionLookup[$rawarray['version']])) {
		$decodedVersion = $MPEGaudioVersionLookup[$rawarray['version']];
	} else {
		if ($echoerrors) {
			echo "\n".'invalid Version ('.$rawarray['version'].')';
		}
		return false;
	}
	if (isset($MPEGaudioLayerLookup[$rawarray['layer']])) {
		$decodedLayer = $MPEGaudioLayerLookup[$rawarray['layer']];
	} else {
		if ($echoerrors) {
			echo "\n".'invalid Layer ('.$rawarray['layer'].')';
		}
		return false;
	}
	if (!isset($MPEGaudioBitrateLookup[$decodedVersion][$decodedLayer][$rawarray['bitrate']])) {
		if ($echoerrors) {
			echo "\n".'invalid Bitrate ('.$rawarray['bitrate'].')';
		}
		if ($rawarray['bitrate'] == 15) {
			// known issue in LAME 3.90 - 3.93.1 where free-format has bitrate ID of 15 instead of 0
			// let it go through here otherwise file will not be identified
		} else {
			return false;
		}
	}
	if (!isset($MPEGaudioFrequencyLookup[$decodedVersion][$rawarray['sample_rate']])) {
		if ($echoerrors) {
			echo "\n".'invalid Frequency ('.$rawarray['sample_rate'].')';
		}
		return false;
	}
	if (!isset($MPEGaudioChannelModeLookup[$rawarray['channelmode']])) {
		if ($echoerrors) {
			echo "\n".'invalid ChannelMode ('.$rawarray['channelmode'].')';
		}
		return false;
	}
	if (!isset($MPEGaudioModeExtensionLookup[$decodedLayer][$rawarray['modeextension']])) {
		if ($echoerrors) {
			echo "\n".'invalid Mode Extension ('.$rawarray['modeextension'].')';
		}
		return false;
	}
	if (!isset($MPEGaudioEmphasisLookup[$rawarray['emphasis']])) {
		if ($echoerrors) {
			echo "\n".'invalid Emphasis ('.$rawarray['emphasis'].')';
		}
		return false;
	}
	// These are just either set or not set, you can't mess that up :)
	// $rawarray['protection'];
	// $rawarray['padding'];
	// $rawarray['private'];
	// $rawarray['copyright'];
	// $rawarray['original'];

	return true;
}

function MPEGaudioHeaderDecode($Header4Bytes) {
	// AAAA AAAA  AAAB BCCD  EEEE FFGH  IIJJ KLMM
	// A - Frame sync (all bits set)
	// B - MPEG Audio version ID
	// C - Layer description
	// D - Protection bit
	// E - Bitrate index
	// F - Sampling rate frequency index
	// G - Padding bit
	// H - Private bit
	// I - Channel Mode
	// J - Mode extension (Only if Joint stereo)
	// K - Copyright
	// L - Original
	// M - Emphasis

	if (strlen($Header4Bytes) != 4) {
		return false;
	}

	$MPEGrawHeader['synch']         = (BigEndian2Int(substr($Header4Bytes, 0, 2)) & 0xFFE0) >> 4;
	$MPEGrawHeader['version']       = (ord($Header4Bytes{1}) & 0x18) >> 3; //    BB
	$MPEGrawHeader['layer']         = (ord($Header4Bytes{1}) & 0x06) >> 1; //      CC
	$MPEGrawHeader['protection']    = (ord($Header4Bytes{1}) & 0x01);      //        D
	$MPEGrawHeader['bitrate']       = (ord($Header4Bytes{2}) & 0xF0) >> 4; // EEEE
	$MPEGrawHeader['sample_rate']   = (ord($Header4Bytes{2}) & 0x0C) >> 2; //     FF
	$MPEGrawHeader['padding']       = (ord($Header4Bytes{2}) & 0x02) >> 1; //       G
	$MPEGrawHeader['private']       = (ord($Header4Bytes{2}) & 0x01);      //        H
	$MPEGrawHeader['channelmode']   = (ord($Header4Bytes{3}) & 0xC0) >> 6; // II
	$MPEGrawHeader['modeextension'] = (ord($Header4Bytes{3}) & 0x30) >> 4; //   JJ
	$MPEGrawHeader['copyright']     = (ord($Header4Bytes{3}) & 0x08) >> 3; //     K
	$MPEGrawHeader['original']      = (ord($Header4Bytes{3}) & 0x04) >> 2; //      L
	$MPEGrawHeader['emphasis']      = (ord($Header4Bytes{3}) & 0x03);      //       MM

	return $MPEGrawHeader;
}

function MPEGaudioFrameLength(&$bitrate, &$version, &$layer, $padding, &$samplerate) {
	static $AudioFrameLengthCache = array();

	if (!isset($AudioFrameLengthCache[$bitrate][$version][$layer][$padding][$samplerate])) {
		$AudioFrameLengthCache[$bitrate][$version][$layer][$padding][$samplerate] = false;
		if ($bitrate != 'free') {

			if ($version == '1') {

				if ($layer == 'I') {

					// For Layer I slot is 32 bits long
					$FrameLengthCoefficient = 48;
					$SlotLength = 4;

				} else { // Layer II / III

					// for Layer II and Layer III slot is 8 bits long.
					$FrameLengthCoefficient = 144;
					$SlotLength = 1;

				}

			} else { // MPEG-2 / MPEG-2.5

				if ($layer == 'I') {

					// For Layer I slot is 32 bits long
					$FrameLengthCoefficient = 24;
					$SlotLength = 4;

				} elseif ($layer == 'II') {

					// for Layer II and Layer III slot is 8 bits long.
					$FrameLengthCoefficient = 144;
					$SlotLength = 1;

				} else { // III

					// for Layer II and Layer III slot is 8 bits long.
					$FrameLengthCoefficient = 72;
					$SlotLength = 1;

				}

			}

			// FrameLengthInBytes = ((Coefficient * BitRate) / SampleRate) + Padding
			// http://66.96.216.160/cgi-bin/YaBB.pl?board=c&action=display&num=1018474068
			// -> [Finding the next frame synch] on www.r3mix.net forums if the above link goes dead
			if ($samplerate > 0) {
				$NewFramelength  = ($FrameLengthCoefficient * $bitrate * 1000) / $samplerate;
				$NewFramelength  = floor($NewFramelength / $SlotLength) * $SlotLength; // round to next-lower multiple of SlotLength (1 byte for Layer II/III, 4 bytes for Layer I)
				if ($padding) {
					$NewFramelength += $SlotLength;
				}
				$AudioFrameLengthCache[$bitrate][$version][$layer][$padding][$samplerate] = (int) $NewFramelength;
			}
		}
	}
	return $AudioFrameLengthCache[$bitrate][$version][$layer][$padding][$samplerate];
}

function LAMEvbrMethodLookup($VBRmethodID) {
	static $LAMEvbrMethodLookup = array();
	if (empty($LAMEvbrMethodLookup)) {
		$LAMEvbrMethodLookup[0x00] = 'unknown';
		$LAMEvbrMethodLookup[0x01] = 'cbr';
		$LAMEvbrMethodLookup[0x02] = 'abr';
		$LAMEvbrMethodLookup[0x03] = 'vbr-old / vbr-rh';
		$LAMEvbrMethodLookup[0x04] = 'vbr-mtrh';
		$LAMEvbrMethodLookup[0x05] = 'vbr-new / vbr-mt';
	}
	return (isset($LAMEvbrMethodLookup[$VBRmethodID]) ? $LAMEvbrMethodLookup[$VBRmethodID] : '');
}

function LAMEmiscStereoModeLookup($StereoModeID) {
	static $LAMEmiscStereoModeLookup = array();
	if (empty($LAMEmiscStereoModeLookup)) {
		$LAMEmiscStereoModeLookup[0] = 'mono';
		$LAMEmiscStereoModeLookup[1] = 'stereo';
		$LAMEmiscStereoModeLookup[2] = 'dual';
		$LAMEmiscStereoModeLookup[3] = 'joint';
		$LAMEmiscStereoModeLookup[4] = 'forced';
		$LAMEmiscStereoModeLookup[5] = 'auto';
		$LAMEmiscStereoModeLookup[6] = 'intensity';
		$LAMEmiscStereoModeLookup[7] = 'other';
	}
	return (isset($LAMEmiscStereoModeLookup[$StereoModeID]) ? $LAMEmiscStereoModeLookup[$StereoModeID] : '');
}

function LAMEmiscSourceSampleFrequencyLookup($SourceSampleFrequencyID) {
	static $LAMEmiscSourceSampleFrequencyLookup = array();
	if (empty($LAMEmiscSourceSampleFrequencyLookup)) {
		$LAMEmiscSourceSampleFrequencyLookup[0] = '<= 32 kHz';
		$LAMEmiscSourceSampleFrequencyLookup[1] = '44.1 kHz';
		$LAMEmiscSourceSampleFrequencyLookup[2] = '48 kHz';
		$LAMEmiscSourceSampleFrequencyLookup[3] = '> 48kHz';
	}
	return (isset($LAMEmiscSourceSampleFrequencyLookup[$SourceSampleFrequencyID]) ? $LAMEmiscSourceSampleFrequencyLookup[$SourceSampleFrequencyID] : '');
}

function LAMEsurroundInfoLookup($SurroundInfoID) {
	static $LAMEsurroundInfoLookup = array();
	if (empty($LAMEsurroundInfoLookup)) {
		$LAMEsurroundInfoLookup[0] = 'no surround info';
		$LAMEsurroundInfoLookup[1] = 'DPL encoding';
		$LAMEsurroundInfoLookup[2] = 'DPL2 encoding';
		$LAMEsurroundInfoLookup[3] = 'Ambisonic encoding';
	}
	return (isset($LAMEsurroundInfoLookup[$SurroundInfoID]) ? $LAMEsurroundInfoLookup[$SurroundInfoID] : 'reserved');
}

?>