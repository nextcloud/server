<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.archive.doc.php                                      //
// module for analyzing MS Office (.doc, .xls, etc) files      //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////


class getid3_doc
{

	function getid3_doc(&$fd, &$ThisFileInfo) {

		$ThisFileInfo['fileformat'] = 'doc';

		$ThisFileInfo['error'][] = 'MS Office (.doc, .xls, etc) parsing not enabled in this version of getID3()';
		return false;

	}

}


?>