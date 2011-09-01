<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.misc.exe.php                                         //
// module for analyzing EXE files                              //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////


class getid3_exe
{

	function getid3_exe(&$fd, &$ThisFileInfo) {

		fseek($fd, $ThisFileInfo['avdataoffset'], SEEK_SET);
		$EXEheader = fread($fd, 28);

		if (substr($EXEheader, 0, 2) != 'MZ') {
			$ThisFileInfo['error'][] = 'Expecting "MZ" at offset '.$ThisFileInfo['avdataoffset'].', found "'.substr($EXEheader, 0, 2).'" instead.';
			return false;
		}

		$ThisFileInfo['fileformat'] = 'exe';
		$ThisFileInfo['exe']['mz']['magic'] = 'MZ';

		$ThisFileInfo['exe']['mz']['raw']['last_page_size']          = getid3_lib::LittleEndian2Int(substr($EXEheader,  2, 2));
		$ThisFileInfo['exe']['mz']['raw']['page_count']              = getid3_lib::LittleEndian2Int(substr($EXEheader,  4, 2));
		$ThisFileInfo['exe']['mz']['raw']['relocation_count']        = getid3_lib::LittleEndian2Int(substr($EXEheader,  6, 2));
		$ThisFileInfo['exe']['mz']['raw']['header_paragraphs']       = getid3_lib::LittleEndian2Int(substr($EXEheader,  8, 2));
		$ThisFileInfo['exe']['mz']['raw']['min_memory_paragraphs']   = getid3_lib::LittleEndian2Int(substr($EXEheader, 10, 2));
		$ThisFileInfo['exe']['mz']['raw']['max_memory_paragraphs']   = getid3_lib::LittleEndian2Int(substr($EXEheader, 12, 2));
		$ThisFileInfo['exe']['mz']['raw']['initial_ss']              = getid3_lib::LittleEndian2Int(substr($EXEheader, 14, 2));
		$ThisFileInfo['exe']['mz']['raw']['initial_sp']              = getid3_lib::LittleEndian2Int(substr($EXEheader, 16, 2));
		$ThisFileInfo['exe']['mz']['raw']['checksum']                = getid3_lib::LittleEndian2Int(substr($EXEheader, 18, 2));
		$ThisFileInfo['exe']['mz']['raw']['cs_ip']                   = getid3_lib::LittleEndian2Int(substr($EXEheader, 20, 4));
		$ThisFileInfo['exe']['mz']['raw']['relocation_table_offset'] = getid3_lib::LittleEndian2Int(substr($EXEheader, 24, 2));
		$ThisFileInfo['exe']['mz']['raw']['overlay_number']          = getid3_lib::LittleEndian2Int(substr($EXEheader, 26, 2));

		$ThisFileInfo['exe']['mz']['byte_size']          = (($ThisFileInfo['exe']['mz']['raw']['page_count'] - 1)) * 512 + $ThisFileInfo['exe']['mz']['raw']['last_page_size'];
		$ThisFileInfo['exe']['mz']['header_size']        = $ThisFileInfo['exe']['mz']['raw']['header_paragraphs'] * 16;
		$ThisFileInfo['exe']['mz']['memory_minimum']     = $ThisFileInfo['exe']['mz']['raw']['min_memory_paragraphs'] * 16;
		$ThisFileInfo['exe']['mz']['memory_recommended'] = $ThisFileInfo['exe']['mz']['raw']['max_memory_paragraphs'] * 16;

$ThisFileInfo['error'][] = 'EXE parsing not enabled in this version of getID3()';
return false;

	}

}


?>