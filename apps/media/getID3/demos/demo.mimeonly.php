<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
//                                                             //
// /demo/demo.mimeonly.php - part of getID3()                  //
// Sample script for scanning a single file and returning only //
// the MIME information                                        //
// See readme.txt for more details                             //
//                                                            ///
/////////////////////////////////////////////////////////////////

echo '<HTML><HEAD><STYLE>BODY, TD, TH { font-family: sans-serif; font-size: 10pt; }</STYLE></HEAD><BODY>';

if (!empty($_REQUEST['filename'])) {

	echo 'The file "'.$_REQUEST['filename'].'" has a MIME type of "'.GetMIMEtype($_REQUEST['filename']).'"';

} else {

	echo 'Usage: <TT>'.$_SERVER['PHP_SELF'].'?filename=<I>filename.ext</I></TT>';

}


function GetMIMEtype($filename) {
	// include getID3() library (can be in a different directory if full path is specified)
	require_once('../getid3/getid3.php');
	// Initialize getID3 engine
	$getID3 = new getID3;

	$DeterminedMIMEtype = '';
	if ($fp = fopen($filename, 'rb')) {
		$ThisFileInfo = array('avdataoffset'=>0, 'avdataend'=>0);

		getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.tag.id3v2.php', __FILE__, true);
		$tag = new getid3_id3v2($fp, $ThisFileInfo);

		fseek($fp, $ThisFileInfo['avdataoffset'], SEEK_SET);
		$formattest = fread($fp, 16);  // 16 bytes is sufficient for any format except ISO CD-image
		fclose($fp);

		$DeterminedFormatInfo = $getID3->GetFileFormat($formattest);
		$DeterminedMIMEtype = $DeterminedFormatInfo['mime_type'];
	}
	return $DeterminedMIMEtype;
}

?>
</BODY>
</HTML>