<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.graphic.svg.php                                      //
// module for analyzing SVG Image files                        //
// dependencies: NONE                                          //
// author: Bryce Harrington <bryceØbryceharrington*org>        //
//                                                            ///
/////////////////////////////////////////////////////////////////


class getid3_svg
{


	function getid3_svg(&$fd, &$ThisFileInfo) {
		fseek($fd, $ThisFileInfo['avdataoffset'], SEEK_SET);

		// I'm making this up, please modify as appropriate
		$SVGheader = fread($fd, 32);
		$ThisFileInfo['svg']['magic']  = substr($SVGheader, 0, 4);
		if ($ThisFileInfo['svg']['magic'] == 'aBcD') {

			$ThisFileInfo['fileformat']                  = 'svg';
			$ThisFileInfo['video']['dataformat']         = 'svg';
			$ThisFileInfo['video']['lossless']           = true;
			$ThisFileInfo['video']['bits_per_sample']    = 24;
			$ThisFileInfo['video']['pixel_aspect_ratio'] = (float) 1;

			$ThisFileInfo['svg']['width']  = getid3_lib::LittleEndian2Int(substr($fileData, 4, 4));
			$ThisFileInfo['svg']['height'] = getid3_lib::LittleEndian2Int(substr($fileData, 8, 4));

			$ThisFileInfo['video']['resolution_x'] = $ThisFileInfo['svg']['width'];
			$ThisFileInfo['video']['resolution_y'] = $ThisFileInfo['svg']['height'];

			return true;
		}
		$ThisFileInfo['error'][] = 'Did not find SVG magic bytes "aBcD" at '.$ThisFileInfo['avdataoffset'];
		unset($ThisFileInfo['fileformat']);
		return false;
	}

}


?>