<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.misc.par2.php                                        //
// module for analyzing PAR2 files                             //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////


class getid3_par2
{

	function getid3_par2(&$fd, &$ThisFileInfo) {

		$ThisFileInfo['fileformat'] = 'par2';

		$ThisFileInfo['error'][] = 'PAR2 parsing not enabled in this version of getID3()';
		return false;

	}

}


?>