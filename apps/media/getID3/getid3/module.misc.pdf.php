<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.misc.pdf.php                                         //
// module for analyzing PDF files                              //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////


class getid3_pdf
{

	function getid3_pdf(&$fd, &$ThisFileInfo) {

		$ThisFileInfo['fileformat'] = 'pdf';

		$ThisFileInfo['error'][] = 'PDF parsing not enabled in this version of getID3()';
		return false;

	}

}


?>