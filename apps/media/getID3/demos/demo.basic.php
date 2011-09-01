<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
//                                                             //
// /demo/demo.basic.php - part of getID3()                     //
// Sample script showing most basic use of getID3()            //
// See readme.txt for more details                             //
//                                                            ///
/////////////////////////////////////////////////////////////////

// include getID3() library (can be in a different directory if full path is specified)
require_once('../getid3/getid3.php');

// Initialize getID3 engine
$getID3 = new getID3;

// Analyze file and store returned data in $ThisFileInfo
$ThisFileInfo = $getID3->analyze($filename);

// Optional: copies data from all subarrays of [tags] into [comments] so
// metadata is all available in one location for all tag formats
// metainformation is always available under [tags] even if this is not called
getid3_lib::CopyTagsToComments($ThisFileInfo);

// Output desired information in whatever format you want
// Note: all entries in [comments] or [tags] are arrays of strings
// See structure.txt for information on what information is available where
// or check out the output of /demos/demo.browse.php for a particular file
// to see the full detail of what information is returned where in the array
echo @$ThisFileInfo['comments_html']['artist'][0]; // artist from any/all available tag formats
echo @$ThisFileInfo['tags']['id3v2']['title'][0];  // title from ID3v2
echo @$ThisFileInfo['audio']['bitrate'];           // audio bitrate
echo @$ThisFileInfo['playtime_string'];            // playtime in minutes:seconds, formatted string

?>