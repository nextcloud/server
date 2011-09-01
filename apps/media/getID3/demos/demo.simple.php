<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
//                                                             //
// /demo/demo.simple.php - part of getID3()                    //
// Sample script for scanning a single directory and           //
// displaying a few pieces of information for each file        //
// See readme.txt for more details                             //
//                                                            ///
/////////////////////////////////////////////////////////////////

echo '<HTML><HEAD>';
echo '<TITLE>getID3() - /demo/demo.simple.php (sample script)</TITLE>';
echo '<STYLE>BODY,TD,TH { font-family: sans-serif; font-size: 9pt; }</STYLE>';
echo '</HEAD><BODY>';


// include getID3() library (can be in a different directory if full path is specified)
require_once('../getid3/getid3.php');

// Initialize getID3 engine
$getID3 = new getID3;

$DirectoryToScan = '/change/to/directory/you/want/to/scan'; // change to whatever directory you want to scan
$dir = opendir($DirectoryToScan);
echo '<TABLE BORDER="1" CELLSPACING="0" CELLPADDING="3">';
echo '<TR><TH>Filename</TH><TH>Artist</TH><TH>Title</TH><TH>Bitrate</TH><TH>Playtime</TH></TR>';
while (($file = readdir($dir)) !== false) {
	$FullFileName = realpath($DirectoryToScan.'/'.$file);
	if (is_file($FullFileName)) {
		set_time_limit(30);

		$ThisFileInfo = $getID3->analyze($FullFileName);

		getid3_lib::CopyTagsToComments($ThisFileInfo);

		// output desired information in whatever format you want
		echo '<TR>';
		echo '<TD>'.$ThisFileInfo['filenamepath'].'</TD>';
		echo '<TD>'.(!empty($ThisFileInfo['comments_html']['artist']) ? implode('<BR>', $ThisFileInfo['comments_html']['artist']) : '&nbsp;').'</TD>';
		echo '<TD>'.(!empty($ThisFileInfo['comments_html']['title'])  ? implode('<BR>', $ThisFileInfo['comments_html']['title'])  : '&nbsp;').'</TD>';
		echo '<TD ALIGN="RIGHT">'.(!empty($ThisFileInfo['audio']['bitrate'])        ? round($ThisFileInfo['audio']['bitrate'] / 1000).' kbps'   : '&nbsp;').'</TD>';
		echo '<TD ALIGN="RIGHT">'.(!empty($ThisFileInfo['playtime_string'])         ? $ThisFileInfo['playtime_string']                          : '&nbsp;').'</TD>';
		echo '</TR>';
	}
}

?>
</BODY>
</HTML>