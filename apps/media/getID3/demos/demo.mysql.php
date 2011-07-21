<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
//                                                             //
// /demo/demo.mysql.php - part of getID3()                     //
// Sample script for recursively scanning directories and      //
// storing the results in a database                           //
// See readme.txt for more details                             //
//                                                            ///
/////////////////////////////////////////////////////////////////


//die('Due to a security issue, this demo has been disabled. It can be enabled by removing line 16 in demos/demo.mysql.php');


// OPTIONS:
$getid3_demo_mysql_encoding = 'ISO-8859-1';
$getid3_demo_mysql_md5_data = false;        // All data hashes are by far the slowest part of scanning
$getid3_demo_mysql_md5_file = false;

define('GETID3_DB_HOST',  'localhost');
define('GETID3_DB_USER',  'root');
define('GETID3_DB_PASS',  'password');
define('GETID3_DB_DB',    'getid3');
define('GETID3_DB_TABLE', 'files');

// CREATE DATABASE `getid3`;

if (!@mysql_connect(GETID3_DB_HOST, GETID3_DB_USER, GETID3_DB_PASS)) {
	die('Could not connect to MySQL host: <blockquote style="background-color: #FF9933; padding: 10px;">'.mysql_error().'</blockquote>');
}
if (!@mysql_select_db(GETID3_DB_DB)) {
	die('Could not select database: <blockquote style="background-color: #FF9933; padding: 10px;">'.mysql_error().'</blockquote>');
}

if (!@include_once('../getid3/getid3.php')) {
	die('Cannot open '.realpath('../getid3/getid3.php'));
}
// Initialize getID3 engine
$getID3 = new getID3;
$getID3->setOption(array(
	'option_md5_data' => $getid3_demo_mysql_md5_data,
	'encoding'        => $getid3_demo_mysql_encoding,
));


function RemoveAccents($string) {
	// Revised version by markstewardØhotmail*com
	return strtr(strtr($string, 'ŠŽšžŸÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÑÒÓÔÕÖØÙÚÛÜÝàáâãäåçèéêëìíîïñòóôõöøùúûüýÿ', 'SZszYAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy'), array('Þ' => 'TH', 'þ' => 'th', 'Ð' => 'DH', 'ð' => 'dh', 'ß' => 'ss', 'Œ' => 'OE', 'œ' => 'oe', 'Æ' => 'AE', 'æ' => 'ae', 'µ' => 'u'));
}

function FixTextFields($text) {
	$text = getid3_lib::SafeStripSlashes($text);
	$text = htmlentities($text, ENT_QUOTES);
	return $text;
}

function BitrateColor($bitrate, $BitrateMaxScale=768) {
	// $BitrateMaxScale is bitrate of maximum-quality color (bright green)
	// below this is gradient, above is solid green

	$bitrate *= (256 / $BitrateMaxScale); // scale from 1-[768]kbps to 1-256
	$bitrate = round(min(max($bitrate, 1), 256));
	$bitrate--;    // scale from 1-256kbps to 0-255kbps

	$Rcomponent = max(255 - ($bitrate * 2), 0);
	$Gcomponent = max(($bitrate * 2) - 255, 0);
	if ($bitrate > 127) {
		$Bcomponent = max((255 - $bitrate) * 2, 0);
	} else {
		$Bcomponent = max($bitrate * 2, 0);
	}
	return str_pad(dechex($Rcomponent), 2, '0', STR_PAD_LEFT).str_pad(dechex($Gcomponent), 2, '0', STR_PAD_LEFT).str_pad(dechex($Bcomponent), 2, '0', STR_PAD_LEFT);
}

function BitrateText($bitrate, $decimals=0) {
	return '<span style="color: #'.BitrateColor($bitrate).'">'.number_format($bitrate, $decimals).' kbps</span>';
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

function RenameFileFromTo($from, $to, &$results) {
	$success = true;
	if ($from === $to) {
		$results = '<span style="color: #FF0000;"><b>Source and Destination filenames identical</b><br>FAILED to rename';
	} elseif (!file_exists($from)) {
		$results = '<span style="color: #FF0000;"><b>Source file does not exist</b><br>FAILED to rename';
	} elseif (file_exists($to) && (strtolower($from) !== strtolower($to))) {
		$results = '<span style="color: #FF0000;"><b>Destination file already exists</b><br>FAILED to rename';
	} elseif (@rename($from, $to)) {
		$SQLquery  = 'DELETE FROM `'.GETID3_DB_TABLE.'`';
		$SQLquery .= ' WHERE (`filename` = "'.mysql_escape_string($from).'")';
		safe_mysql_query($SQLquery);
		$results = '<span style="color: #008000;">Successfully renamed';
	} else {
		$results = '<br><span style="color: #FF0000;">FAILED to rename';
		$success = false;
	}
	$results .= ' from:<br><i>'.$from.'</i><br>to:<br><i>'.$to.'</i></span><hr>';
	return $success;
}

if (!empty($_REQUEST['renamefilefrom']) && !empty($_REQUEST['renamefileto'])) {

	$results = '';
	RenameFileFromTo($_REQUEST['renamefilefrom'], $_REQUEST['renamefileto'], $results);
	echo $results;
	exit;

} elseif (!empty($_REQUEST['m3ufilename'])) {

	header('Content-type: audio/x-mpegurl');
	echo '#EXTM3U'."\n";
	echo WindowsShareSlashTranslate($_REQUEST['m3ufilename'])."\n";
	exit;

} elseif (!isset($_REQUEST['m3u']) && !isset($_REQUEST['m3uartist']) && !isset($_REQUEST['m3utitle'])) {

	echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"  "http://www.w3.org/TR/html4/loose.dtd">';
	echo '<html><head><title>getID3() demo - /demo/mysql.php</title><style>BODY, TD, TH { font-family: sans-serif; font-size: 10pt; } A { text-decoration: none; } A:hover { text-decoration: underline; } A:visited { font-style: italic; }</style></head><body>';

}


function WindowsShareSlashTranslate($filename) {
	if (substr($filename, 0, 2) == '//') {
		return str_replace('/', '\\', $filename);
	}
	return $filename;
}

function safe_mysql_query($SQLquery) {
	$result = @mysql_query($SQLquery);
	if (mysql_error()) {
		die('<FONT COLOR="red">'.mysql_error().'</FONT><hr><TT>'.$SQLquery.'</TT>');
	}
	return $result;
}

function mysql_table_exists($tablename) {
	return (bool) mysql_query('DESCRIBE '.$tablename);
}

function AcceptableExtensions($fileformat, $audio_dataformat='', $video_dataformat='') {
	static $AcceptableExtensionsAudio = array();
	if (empty($AcceptableExtensionsAudio)) {
		$AcceptableExtensionsAudio['mp3']['mp3']  = array('mp3');
		$AcceptableExtensionsAudio['mp2']['mp2']  = array('mp2');
		$AcceptableExtensionsAudio['mp1']['mp1']  = array('mp1');
		$AcceptableExtensionsAudio['asf']['asf']  = array('asf');
		$AcceptableExtensionsAudio['asf']['wma']  = array('wma');
		$AcceptableExtensionsAudio['riff']['mp3'] = array('wav');
		$AcceptableExtensionsAudio['riff']['wav'] = array('wav');
	}
	static $AcceptableExtensionsVideo = array();
	if (empty($AcceptableExtensionsVideo)) {
		$AcceptableExtensionsVideo['mp3']['mp3']  = array('mp3');
		$AcceptableExtensionsVideo['mp2']['mp2']  = array('mp2');
		$AcceptableExtensionsVideo['mp1']['mp1']  = array('mp1');
		$AcceptableExtensionsVideo['asf']['asf']  = array('asf');
		$AcceptableExtensionsVideo['asf']['wmv']  = array('wmv');
		$AcceptableExtensionsVideo['gif']['gif']  = array('gif');
		$AcceptableExtensionsVideo['jpg']['jpg']  = array('jpg');
		$AcceptableExtensionsVideo['png']['png']  = array('png');
		$AcceptableExtensionsVideo['bmp']['bmp']  = array('bmp');
	}
	if (!empty($video_dataformat)) {
		return (isset($AcceptableExtensionsVideo[$fileformat][$video_dataformat]) ? $AcceptableExtensionsVideo[$fileformat][$video_dataformat] : array());
	} else {
		return (isset($AcceptableExtensionsAudio[$fileformat][$audio_dataformat]) ? $AcceptableExtensionsAudio[$fileformat][$audio_dataformat] : array());
	}
}


if (!empty($_REQUEST['scan'])) {
	if (mysql_table_exists(GETID3_DB_TABLE)) {
		$SQLquery  = 'DROP TABLE `'.GETID3_DB_TABLE.'`';
		safe_mysql_query($SQLquery);
	}
}
if (!mysql_table_exists(GETID3_DB_TABLE)) {
	$SQLquery  = 'CREATE TABLE `'.GETID3_DB_TABLE.'` (';
	$SQLquery .= ' `ID` mediumint(8) unsigned NOT NULL auto_increment,';
	$SQLquery .= ' `filename` text NOT NULL,';
	$SQLquery .= ' `LastModified` int(11) NOT NULL default "0",';
	$SQLquery .= ' `md5_file` varchar(32) NOT NULL default "",';
	$SQLquery .= ' `md5_data` varchar(32) NOT NULL default "",';
	$SQLquery .= ' `md5_data_source` varchar(32) NOT NULL default "",';
	$SQLquery .= ' `filesize` int(10) unsigned NOT NULL default "0",';
	$SQLquery .= ' `fileformat` varchar(255) NOT NULL default "",';
	$SQLquery .= ' `audio_dataformat` varchar(255) NOT NULL default "",';
	$SQLquery .= ' `video_dataformat` varchar(255) NOT NULL default "",';
	$SQLquery .= ' `audio_bitrate` float NOT NULL default "0",';
	$SQLquery .= ' `video_bitrate` float NOT NULL default "0",';
	$SQLquery .= ' `playtime_seconds` varchar(255) NOT NULL default "",';
	$SQLquery .= ' `tags` varchar(255) NOT NULL default "",';
	$SQLquery .= ' `artist` varchar(255) NOT NULL default "",';
	$SQLquery .= ' `title` varchar(255) NOT NULL default "",';
	$SQLquery .= ' `remix` varchar(255) NOT NULL default "",';
	$SQLquery .= ' `album` varchar(255) NOT NULL default "",';
	$SQLquery .= ' `genre` varchar(255) NOT NULL default "",';
	$SQLquery .= ' `comment` text NOT NULL,';
	$SQLquery .= ' `track` varchar(7) NOT NULL default "",';
	$SQLquery .= ' `comments_all` text NOT NULL,';
	$SQLquery .= ' `comments_id3v2` text NOT NULL,';
	$SQLquery .= ' `comments_ape` text NOT NULL,';
	$SQLquery .= ' `comments_lyrics3` text NOT NULL,';
	$SQLquery .= ' `comments_id3v1` text NOT NULL,';
	$SQLquery .= ' `warning` text NOT NULL,';
	$SQLquery .= ' `error` text NOT NULL,';
	$SQLquery .= ' `track_volume` float NOT NULL default "0",';
	$SQLquery .= ' `encoder_options` varchar(255) NOT NULL default "",';
	$SQLquery .= ' `vbr_method` varchar(255) NOT NULL default "",';
	$SQLquery .= ' PRIMARY KEY (`ID`)';
	$SQLquery .= ') TYPE=MyISAM;';

	safe_mysql_query($SQLquery);
}

$ExistingTableFields = array();
$result = mysql_query('DESCRIBE `'.GETID3_DB_TABLE.'`');
while ($row = mysql_fetch_array($result)) {
	$ExistingTableFields[$row['Field']] = $row;
}
if (!isset($ExistingTableFields['encoder_options'])) { // Added in 1.7.0b2
	echo '<b>adding field `encoder_options`</b><br>';
	mysql_query('ALTER TABLE `'.GETID3_DB_TABLE.'` ADD `encoder_options` VARCHAR(255) DEFAULT "" NOT NULL AFTER `error`');
	mysql_query('OPTIMIZE TABLE `'.GETID3_DB_TABLE.'`');
}
if (isset($ExistingTableFields['track']) && ($ExistingTableFields['track']['Type'] != 'varchar(7)')) { // Changed in 1.7.0b2
	echo '<b>changing field `track` to VARCHAR(7)</b><br>';
	mysql_query('ALTER TABLE `'.GETID3_DB_TABLE.'` CHANGE `track` `track` VARCHAR(7) DEFAULT "" NOT NULL');
	mysql_query('OPTIMIZE TABLE `'.GETID3_DB_TABLE.'`');
}
if (!isset($ExistingTableFields['track_volume'])) { // Added in 1.7.0b5
	echo '<H1><FONT COLOR="red">WARNING! You should erase your database and rescan everything because the comment storing has been changed since the last version</FONT></H1><hr>';
	echo '<b>adding field `track_volume`</b><br>';
	mysql_query('ALTER TABLE `'.GETID3_DB_TABLE.'` ADD `track_volume` FLOAT NOT NULL AFTER `error`');
	mysql_query('OPTIMIZE TABLE `'.GETID3_DB_TABLE.'`');
}
if (!isset($ExistingTableFields['remix'])) { // Added in 1.7.3b1
	echo '<b>adding field `encoder_options`, `alternate_name`, `parody`</b><br>';
	mysql_query('ALTER TABLE `'.GETID3_DB_TABLE.'` ADD `remix` VARCHAR(255) DEFAULT "" NOT NULL AFTER `title`');
	mysql_query('ALTER TABLE `'.GETID3_DB_TABLE.'` ADD `alternate_name` VARCHAR(255) DEFAULT "" NOT NULL AFTER `track`');
	mysql_query('ALTER TABLE `'.GETID3_DB_TABLE.'` ADD `parody` VARCHAR(255) DEFAULT "" NOT NULL AFTER `alternate_name`');
	mysql_query('OPTIMIZE TABLE `'.GETID3_DB_TABLE.'`');
}


function SynchronizeAllTags($filename, $synchronizefrom='all', $synchronizeto='A12', &$errors) {
	global $getID3;

	set_time_limit(30);

	$ThisFileInfo = $getID3->analyze($filename);
	getid3_lib::CopyTagsToComments($ThisFileInfo);

	if ($synchronizefrom == 'all') {
		$SourceArray = @$ThisFileInfo['comments'];
	} elseif (!empty($ThisFileInfo['tags'][$synchronizefrom])) {
		$SourceArray = @$ThisFileInfo['tags'][$synchronizefrom];
	} else {
		die('ERROR: $ThisFileInfo[tags]['.$synchronizefrom.'] does not exist');
	}

	$SQLquery  = 'DELETE FROM `'.GETID3_DB_TABLE.'`';
	$SQLquery .= ' WHERE (`filename` = "'.mysql_escape_string($filename).'")';
	safe_mysql_query($SQLquery);


	$TagFormatsToWrite = array();
	if ((strpos($synchronizeto, '2') !== false) && ($synchronizefrom != 'id3v2')) {
		$TagFormatsToWrite[] = 'id3v2.3';
	}
	if ((strpos($synchronizeto, 'A') !== false) && ($synchronizefrom != 'ape')) {
		$TagFormatsToWrite[] = 'ape';
	}
	if ((strpos($synchronizeto, 'L') !== false) && ($synchronizefrom != 'lyrics3')) {
		$TagFormatsToWrite[] = 'lyrics3';
	}
	if ((strpos($synchronizeto, '1') !== false) && ($synchronizefrom != 'id3v1')) {
		$TagFormatsToWrite[] = 'id3v1';
	}

	getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'write.php', __FILE__, true);
	$tagwriter = new getid3_writetags;
	$tagwriter->filename       = $filename;
	$tagwriter->tagformats     = $TagFormatsToWrite;
	$tagwriter->overwrite_tags = true;
	$tagwriter->tag_encoding   = $getID3->encoding;
	$tagwriter->tag_data       = $SourceArray;

	if ($tagwriter->WriteTags()) {
		$errors = $tagwriter->errors;
		return true;
	}
	$errors = $tagwriter->errors;
	return false;
}

$IgnoreNoTagFormats = array('', 'png', 'jpg', 'gif', 'bmp', 'swf', 'pdf', 'zip', 'rar', 'mid', 'mod', 'xm', 'it', 's3m');

if (!empty($_REQUEST['scan']) || !empty($_REQUEST['newscan']) || !empty($_REQUEST['rescanerrors'])) {

	$SQLquery  = 'DELETE from `'.GETID3_DB_TABLE.'`';
	$SQLquery .= ' WHERE (`fileformat` = "")';
	safe_mysql_query($SQLquery);

	$FilesInDir = array();

	if (!empty($_REQUEST['rescanerrors'])) {

		echo '<a href="'.htmlentities($_SERVER['PHP_SELF']).'">abort</a><hr>';

		echo 'Re-scanning all media files already in database that had errors and/or warnings in last scan<hr>';

		$SQLquery  = 'SELECT `filename`';
		$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
		$SQLquery .= ' WHERE (`error` <> "")';
		$SQLquery .= ' OR (`warning` <> "")';
		$SQLquery .= ' ORDER BY `filename` ASC';
		$result = safe_mysql_query($SQLquery);
		while ($row = mysql_fetch_array($result)) {

			if (!file_exists($row['filename'])) {
				echo '<b>File missing: '.$row['filename'].'</b><br>';
				$SQLquery = 'DELETE FROM `'.GETID3_DB_TABLE.'`';
				$SQLquery .= ' WHERE (`filename` = "'.mysql_escape_string($row['filename']).'")';
				safe_mysql_query($SQLquery);
			} else {
				$FilesInDir[] = $row['filename'];
			}

		}

	} elseif (!empty($_REQUEST['scan']) || !empty($_REQUEST['newscan'])) {

		echo '<a href="'.htmlentities($_SERVER['PHP_SELF']).'">abort</a><hr>';

		echo 'Scanning all media files in <b>'.str_replace('\\', '/', realpath(!empty($_REQUEST['scan']) ? $_REQUEST['scan'] : $_REQUEST['newscan'])).'</b> (and subdirectories)<hr>';

		$SQLquery  = 'SELECT COUNT(*) AS `num`, `filename`';
		$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
		$SQLquery .= ' GROUP BY `filename`';
		$SQLquery .= ' ORDER BY `num` DESC';
		$result = safe_mysql_query($SQLquery);
		$DupesDeleted = 0;
		while ($row = mysql_fetch_array($result)) {
			set_time_limit(30);
			if ($row['num'] <= 1) {
				break;
			}
			$SQLquery  = 'DELETE FROM `'.GETID3_DB_TABLE.'`';
			$SQLquery .= ' WHERE `filename` LIKE "'.mysql_escape_string($row['filename']).'"';
			safe_mysql_query($SQLquery);
			$DupesDeleted++;
		}
		if ($DupesDeleted > 0) {
			echo 'Deleted <b>'.number_format($DupesDeleted).'</b> duplicate filenames<hr>';
		}

		if (!empty($_REQUEST['newscan'])) {
			$AlreadyInDatabase = array();
			set_time_limit(60);
			$SQLquery  = 'SELECT `filename`';
			$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
			$SQLquery .= ' ORDER BY `filename` ASC';
			$result = safe_mysql_query($SQLquery);
			while ($row = mysql_fetch_array($result)) {
				//$AlreadyInDatabase[] = strtolower($row['filename']);
				$AlreadyInDatabase[] = $row['filename'];
			}
		}

		$DirectoriesToScan  = array(@$_REQUEST['scan'] ? $_REQUEST['scan'] : $_REQUEST['newscan']);
		$DirectoriesScanned = array();
		while (count($DirectoriesToScan) > 0) {
			foreach ($DirectoriesToScan as $DirectoryKey => $startingdir) {
				if ($dir = opendir($startingdir)) {
					set_time_limit(30);
					echo '<b>'.str_replace('\\', '/', $startingdir).'</b><br>';
					flush();
					while (($file = readdir($dir)) !== false) {
						if (($file != '.') && ($file != '..')) {
							$RealPathName = realpath($startingdir.'/'.$file);
							if (is_dir($RealPathName)) {
								if (!in_array($RealPathName, $DirectoriesScanned) && !in_array($RealPathName, $DirectoriesToScan)) {
									$DirectoriesToScan[] = $RealPathName;
								}
							} else if (is_file($RealPathName)) {
								if (!empty($_REQUEST['newscan'])) {
									if (!in_array(str_replace('\\', '/', $RealPathName), $AlreadyInDatabase)) {
										$FilesInDir[] = $RealPathName;
									}
								} elseif (!empty($_REQUEST['scan'])) {
									$FilesInDir[] = $RealPathName;
								}
							}
						}
					}
					closedir($dir);
				} else {
					echo '<FONT COLOR="RED">Failed to open directory "<b>'.$startingdir.'</b>"</FONT><br><br>';
				}
				$DirectoriesScanned[] = $startingdir;
				unset($DirectoriesToScan[$DirectoryKey]);
			}
		}
		echo '<i>List of files to scan complete (added '.number_format(count($FilesInDir)).' files to scan)</i><hr>';
		flush();
	}

	$FilesInDir = array_unique($FilesInDir);
	sort($FilesInDir);

	$starttime = time();
	$rowcounter = 0;
	$totaltoprocess = count($FilesInDir);

	foreach ($FilesInDir as $filename) {
		set_time_limit(300);

		echo '<br>'.date('H:i:s').' ['.number_format(++$rowcounter).' / '.number_format($totaltoprocess).'] '.str_replace('\\', '/', $filename);

		$ThisFileInfo = $getID3->analyze($filename);
		getid3_lib::CopyTagsToComments($ThisFileInfo);

		if (file_exists($filename)) {
			$ThisFileInfo['file_modified_time'] = filemtime($filename);
			$ThisFileInfo['md5_file']           = ($getid3_demo_mysql_md5_file ? md5_file($filename) : '');
		}

		if (empty($ThisFileInfo['fileformat'])) {

			echo ' (<span style="color: #990099;">unknown file type</span>)';

		} else {

			if (!empty($ThisFileInfo['error'])) {
				echo ' (<span style="color: #FF0000;">errors</span>)';
			} elseif (!empty($ThisFileInfo['warning'])) {
				echo ' (<span style="color: #FF9999;">warnings</span>)';
			} else {
				echo ' (<span style="color: #009900;">OK</span>)';
			}

			$this_track_track = '';
			if (!empty($ThisFileInfo['comments']['track'])) {
				foreach ($ThisFileInfo['comments']['track'] as $key => $value) {
					if (strlen($value) > strlen($this_track_track)) {
						$this_track_track = str_pad($value, 2, '0', STR_PAD_LEFT);
					}
				}
				if (ereg('^([0-9]+)/([0-9]+)$', $this_track_track, $matches)) {
					// change "1/5"->"01/05", "3/12"->"03/12", etc
					$this_track_track = str_pad($matches[1], 2, '0', STR_PAD_LEFT).'/'.str_pad($matches[2], 2, '0', STR_PAD_LEFT);
				}
			}

			$this_track_remix = '';
			$this_track_title = '';
			if (!empty($ThisFileInfo['comments']['title'])) {
				foreach ($ThisFileInfo['comments']['title'] as $possible_title) {
					if (strlen($possible_title) > strlen($this_track_title)) {
						$this_track_title = $possible_title;
					}
				}
			}

			$ParenthesesPairs = array('()', '[]', '{}');
			foreach ($ParenthesesPairs as $pair) {
				if (preg_match_all('/(.*) '.preg_quote($pair{0}).'(([^'.preg_quote($pair).']*[\- '.preg_quote($pair{0}).'])?(cut|dub|edit|version|live|reprise|[a-z]*mix))'.preg_quote($pair{1}).'/iU', $this_track_title, $matches)) {
					$this_track_title = $matches[1][0];
					$this_track_remix = implode("\t", $matches[2]);
				}
			}



			if (!empty($_REQUEST['rescanerrors'])) {

				$SQLquery  = 'UPDATE `'.GETID3_DB_TABLE.'` SET ';
				$SQLquery .= '`LastModified` = "'.mysql_escape_string(@$ThisFileInfo['file_modified_time']).'", ';
				$SQLquery .= '`md5_file` = "'.mysql_escape_string(@$ThisFileInfo['md5_file']).'", ';
				$SQLquery .= '`md5_data` = "'.mysql_escape_string(@$ThisFileInfo['md5_data']).'", ';
				$SQLquery .= '`md5_data_source` = "'.mysql_escape_string(@$ThisFileInfo['md5_data_source']).'", ';
				$SQLquery .= '`filesize` = "'.mysql_escape_string(@$ThisFileInfo['filesize']).'", ';
				$SQLquery .= '`fileformat` = "'.mysql_escape_string(@$ThisFileInfo['fileformat']).'", ';
				$SQLquery .= '`audio_dataformat` = "'.mysql_escape_string(@$ThisFileInfo['audio']['dataformat']).'", ';
				$SQLquery .= '`video_dataformat` = "'.mysql_escape_string(@$ThisFileInfo['video']['dataformat']).'", ';
				$SQLquery .= '`audio_bitrate` = "'.mysql_escape_string(floatval(@$ThisFileInfo['audio']['bitrate'])).'", ';
				$SQLquery .= '`video_bitrate` = "'.mysql_escape_string(floatval(@$ThisFileInfo['video']['bitrate'])).'", ';
				$SQLquery .= '`playtime_seconds` = "'.mysql_escape_string(floatval(@$ThisFileInfo['playtime_seconds'])).'", ';
				$SQLquery .= '`tags` = "'.mysql_escape_string(@implode("\t", @array_keys(@$ThisFileInfo['tags']))).'", ';
				$SQLquery .= '`artist` = "'.mysql_escape_string(@implode("\t", @$ThisFileInfo['comments']['artist'])).'", ';

				$SQLquery .= '`title` = "'.mysql_escape_string($this_track_title).'", ';
				$SQLquery .= '`remix` = "'.mysql_escape_string($this_track_remix).'", ';

				$SQLquery .= '`album` = "'.mysql_escape_string(@implode("\t", @$ThisFileInfo['comments']['album'])).'", ';
				$SQLquery .= '`genre` = "'.mysql_escape_string(@implode("\t", @$ThisFileInfo['comments']['genre'])).'", ';
				$SQLquery .= '`comment` = "'.mysql_escape_string(@implode("\t", @$ThisFileInfo['comments']['comment'])).'", ';

				$SQLquery .= '`track` = "'.mysql_escape_string($this_track_track).'", ';

				$SQLquery .= '`comments_all` = "'.mysql_escape_string(@serialize(@$ThisFileInfo['comments'])).'", ';
				$SQLquery .= '`comments_id3v2` = "'.mysql_escape_string(@serialize(@$ThisFileInfo['tags']['id3v2'])).'", ';
				$SQLquery .= '`comments_ape` = "'.mysql_escape_string(@serialize(@$ThisFileInfo['tags']['ape'])).'", ';
				$SQLquery .= '`comments_lyrics3` = "'.mysql_escape_string(@serialize(@$ThisFileInfo['tags']['lyrics3'])).'", ';
				$SQLquery .= '`comments_id3v1` = "'.mysql_escape_string(@serialize(@$ThisFileInfo['tags']['id3v1'])).'", ';
				$SQLquery .= '`warning` = "'.mysql_escape_string(@implode("\t", @$ThisFileInfo['warning'])).'", ';
				$SQLquery .= '`error` = "'.mysql_escape_string(@implode("\t", @$ThisFileInfo['error'])).'", ';
				$SQLquery .= '`encoder_options` = "'.mysql_escape_string(trim(@$ThisFileInfo['audio']['encoder'].' '.@$ThisFileInfo['audio']['encoder_options'])).'", ';
				$SQLquery .= '`vbr_method` = "'.mysql_escape_string(@$ThisFileInfo['mpeg']['audio']['VBR_method']).'", ';
				$SQLquery .= '`track_volume` = "'.mysql_escape_string(floatval(@$ThisFileInfo['replay_gain']['track']['volume'])).'" ';
				$SQLquery .= 'WHERE (`filename` = "'.mysql_escape_string(@$ThisFileInfo['filenamepath']).'")';

			} elseif (!empty($_REQUEST['scan']) || !empty($_REQUEST['newscan'])) {

				$SQLquery  = 'INSERT INTO `'.GETID3_DB_TABLE.'` (`filename`, `LastModified`, `md5_file`, `md5_data`, `md5_data_source`, `filesize`, `fileformat`, `audio_dataformat`, `video_dataformat`, `audio_bitrate`, `video_bitrate`, `playtime_seconds`, `tags`, `artist`, `title`, `remix`, `album`, `genre`, `comment`, `track`, `comments_all`, `comments_id3v2`, `comments_ape`, `comments_lyrics3`, `comments_id3v1`, `warning`, `error`, `encoder_options`, `vbr_method`, `track_volume`) VALUES (';
				$SQLquery .= '"'.mysql_escape_string(@$ThisFileInfo['filenamepath']).'", ';
				$SQLquery .= '"'.mysql_escape_string(@$ThisFileInfo['file_modified_time']).'", ';
				$SQLquery .= '"'.mysql_escape_string(@$ThisFileInfo['md5_file']).'", ';
				$SQLquery .= '"'.mysql_escape_string(@$ThisFileInfo['md5_data']).'", ';
				$SQLquery .= '"'.mysql_escape_string(@$ThisFileInfo['md5_data_source']).'", ';
				$SQLquery .= '"'.mysql_escape_string(@$ThisFileInfo['filesize']).'", ';
				$SQLquery .= '"'.mysql_escape_string(@$ThisFileInfo['fileformat']).'", ';
				$SQLquery .= '"'.mysql_escape_string(@$ThisFileInfo['audio']['dataformat']).'", ';
				$SQLquery .= '"'.mysql_escape_string(@$ThisFileInfo['video']['dataformat']).'", ';
				$SQLquery .= '"'.mysql_escape_string(floatval(@$ThisFileInfo['audio']['bitrate'])).'", ';
				$SQLquery .= '"'.mysql_escape_string(floatval(@$ThisFileInfo['video']['bitrate'])).'", ';
				$SQLquery .= '"'.mysql_escape_string(floatval(@$ThisFileInfo['playtime_seconds'])).'", ';
				$SQLquery .= '"'.mysql_escape_string(@implode("\t", @array_keys(@$ThisFileInfo['tags']))).'", ';
				$SQLquery .= '"'.mysql_escape_string(@implode("\t", @$ThisFileInfo['comments']['artist'])).'", ';

				$SQLquery .= '"'.mysql_escape_string($this_track_title).'", ';
				$SQLquery .= '"'.mysql_escape_string($this_track_remix).'", ';

				$SQLquery .= '"'.mysql_escape_string(@implode("\t", @$ThisFileInfo['comments']['album'])).'", ';
				$SQLquery .= '"'.mysql_escape_string(@implode("\t", @$ThisFileInfo['comments']['genre'])).'", ';
				$SQLquery .= '"'.mysql_escape_string(@implode("\t", @$ThisFileInfo['comments']['comment'])).'", ';

				$SQLquery .= '"'.mysql_escape_string($this_track_track).'", ';

				$SQLquery .= '"'.mysql_escape_string(@serialize(@$ThisFileInfo['comments'])).'", ';
				$SQLquery .= '"'.mysql_escape_string(@serialize(@$ThisFileInfo['tags']['id3v2'])).'", ';
				$SQLquery .= '"'.mysql_escape_string(@serialize(@$ThisFileInfo['tags']['ape'])).'", ';
				$SQLquery .= '"'.mysql_escape_string(@serialize(@$ThisFileInfo['tags']['lyrics3'])).'", ';
				$SQLquery .= '"'.mysql_escape_string(@serialize(@$ThisFileInfo['tags']['id3v1'])).'", ';
				$SQLquery .= '"'.mysql_escape_string(@implode("\t", @$ThisFileInfo['warning'])).'", ';
				$SQLquery .= '"'.mysql_escape_string(@implode("\t", @$ThisFileInfo['error'])).'", ';
				$SQLquery .= '"'.mysql_escape_string(trim(@$ThisFileInfo['audio']['encoder'].' '.@$ThisFileInfo['audio']['encoder_options'])).'", ';
				$SQLquery .= '"'.mysql_escape_string(!empty($ThisFileInfo['mpeg']['audio']['LAME']) ? 'LAME' : @$ThisFileInfo['mpeg']['audio']['VBR_method']).'", ';
				$SQLquery .= '"'.mysql_escape_string(floatval(@$ThisFileInfo['replay_gain']['track']['volume'])).'")';

			}
			flush();
			safe_mysql_query($SQLquery);
		}

	}

	$SQLquery = 'OPTIMIZE TABLE `'.GETID3_DB_TABLE.'`';
	safe_mysql_query($SQLquery);

	echo '<hr>Done scanning!<hr>';

} elseif (!empty($_REQUEST['missingtrackvolume'])) {

	$MissingTrackVolumeFilesScanned  = 0;
	$MissingTrackVolumeFilesAdjusted = 0;
	$MissingTrackVolumeFilesDeleted  = 0;
	$SQLquery  = 'SELECT `filename`';
	$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
	$SQLquery .= ' WHERE (`track_volume` = "0")';
	$SQLquery .= ' AND (`audio_bitrate` > "0")';
	$result = safe_mysql_query($SQLquery);
	echo 'Scanning <span ID="missingtrackvolumeNowScanning">0</span> / '.number_format(mysql_num_rows($result)).' files for track volume information:<hr>';
	while ($row = mysql_fetch_array($result)) {
		set_time_limit(30);
		echo '<script type="text/javascript">if (document.getElementById("missingtrackvolumeNowScanning")) document.getElementById("missingtrackvolumeNowScanning").innerHTML = "'.number_format($MissingTrackVolumeFilesScanned++).'";</script>. ';
		flush();
		if (file_exists($row['filename'])) {

			$ThisFileInfo = $getID3->analyze($row['filename']);
			if (!empty($ThisFileInfo['replay_gain']['track']['volume'])) {
				$MissingTrackVolumeFilesAdjusted++;
				$SQLquery  = 'UPDATE `'.GETID3_DB_TABLE.'`';
				$SQLquery .= ' SET `track_volume` = "'.$ThisFileInfo['replay_gain']['track']['volume'].'"';
				$SQLquery .= ' WHERE (`filename` = "'.mysql_escape_string($row['filename']).'")';
				safe_mysql_query($SQLquery);
			}

		} else {

			$MissingTrackVolumeFilesDeleted++;
			$SQLquery  = 'DELETE FROM `'.GETID3_DB_TABLE.'`';
			$SQLquery .= ' WHERE (`filename` = "'.mysql_escape_string($row['filename']).'")';
			safe_mysql_query($SQLquery);

		}
	}
	echo '<hr>Scanned '.number_format($MissingTrackVolumeFilesScanned).' files with no track volume information.<br>';
	echo 'Found track volume information for '.number_format($MissingTrackVolumeFilesAdjusted).' of them (could not find info for '.number_format($MissingTrackVolumeFilesScanned - $MissingTrackVolumeFilesAdjusted).' files; deleted '.number_format($MissingTrackVolumeFilesDeleted).' records of missing files)<hr>';

} elseif (!empty($_REQUEST['deadfilescheck'])) {

	$SQLquery  = 'SELECT COUNT(*) AS `num`, `filename`';
	$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
	$SQLquery .= ' GROUP BY `filename`';
	$SQLquery .= ' ORDER BY `num` DESC';
	$result = safe_mysql_query($SQLquery);
	$DupesDeleted = 0;
	while ($row = mysql_fetch_array($result)) {
		set_time_limit(30);
		if ($row['num'] <= 1) {
			break;
		}
		echo '<br>'.FixTextFields($row['filename']).' (<font color="#FF9999">duplicate</font>)';
		$SQLquery  = 'DELETE FROM `'.GETID3_DB_TABLE.'`';
		$SQLquery .= ' WHERE `filename` LIKE "'.mysql_escape_string($row['filename']).'"';
		safe_mysql_query($SQLquery);
		$DupesDeleted++;
	}
	if ($DupesDeleted > 0) {
		echo '<hr>Deleted <b>'.number_format($DupesDeleted).'</b> duplicate filenames<hr>';
	}

	$SQLquery  = 'SELECT `filename`, `filesize`, `LastModified`';
	$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
	$SQLquery .= ' ORDER BY `filename` ASC';
	$result = safe_mysql_query($SQLquery);
	$totalchecked = 0;
	$totalremoved = 0;
	$previousdir = '';
	while ($row = mysql_fetch_array($result)) {
		$totalchecked++;
		set_time_limit(30);
		$reason = '';
		if (!file_exists($row['filename'])) {
			$reason = 'deleted';
		} elseif (filesize($row['filename']) != $row['filesize']) {
			$reason = 'filesize changed';
		} elseif (filemtime($row['filename']) != $row['LastModified']) {
			if (abs(filemtime($row['filename']) - $row['LastModified']) != 3600) {
				// off by exactly one hour == daylight savings time
				$reason = 'last-modified time changed';
			}
		}

		$thisdir = dirname($row['filename']);
		if ($reason) {

			$totalremoved++;
			echo '<br>'.FixTextFields($row['filename']).' (<font color="#FF9999">'.$reason.'</font>)';
			flush();
			$SQLquery  = 'DELETE FROM `'.GETID3_DB_TABLE.'`';
			$SQLquery .= ' WHERE (`filename` = "'.mysql_escape_string($row['filename']).'")';
			safe_mysql_query($SQLquery);

		} elseif ($thisdir != $previousdir) {

			echo '. ';
			flush();

		}
		$previousdir = $thisdir;
	}

	echo '<hr><b>'.number_format($totalremoved).' of '.number_format($totalchecked).' files in database no longer exist, or have been altered since last scan. Removed from database.</b><hr>';

} elseif (!empty($_REQUEST['encodedbydistribution'])) {

	if (!empty($_REQUEST['m3u'])) {

		header('Content-type: audio/x-mpegurl');
		echo '#EXTM3U'."\n";

		$SQLquery  = 'SELECT `filename`, `comments_id3v2`';
		$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
		$SQLquery .= ' WHERE (`encoder_options` = "'.mysql_escape_string($_REQUEST['encodedbydistribution']).'")';
		$result = mysql_query($SQLquery);
		$NonBlankEncodedBy = '';
		$BlankEncodedBy = '';
		while ($row = mysql_fetch_array($result)) {
			set_time_limit(30);
			$CommentArray = unserialize($row['comments_id3v2']);
			if (isset($CommentArray['encoded_by'][0])) {
				$NonBlankEncodedBy .= WindowsShareSlashTranslate($row['filename'])."\n";
			} else {
				$BlankEncodedBy    .= WindowsShareSlashTranslate($row['filename'])."\n";
			}
		}
		echo $NonBlankEncodedBy;
		echo $BlankEncodedBy;
		exit;

	} elseif (!empty($_REQUEST['showfiles'])) {

		echo '<a href="'.htmlentities($_SERVER['PHP_SELF'].'?encodedbydistribution='.urlencode('%')).'">show all</a><br>';
		echo '<table border="1">';

		$SQLquery  = 'SELECT `filename`, `comments_id3v2`';
		$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
		$result = mysql_query($SQLquery);
		while ($row = mysql_fetch_array($result)) {
			set_time_limit(30);
			$CommentArray = unserialize($row['comments_id3v2']);
			if (($_REQUEST['encodedbydistribution'] == '%') || (!empty($CommentArray['encoded_by'][0]) && ($_REQUEST['encodedbydistribution'] == $CommentArray['encoded_by'][0]))) {
				echo '<tr><td><a href="'.htmlentities($_SERVER['PHP_SELF'].'?m3ufilename='.urlencode($row['filename'])).'">m3u</a></td>';
				echo '<td><a href="'.htmlentities('demo.browse.php?filename='.rawurlencode($row['filename'])).'">'.FixTextFields($row['filename']).'</a></td></tr>';
			}
		}
		echo '</table>';

	} else {

		$SQLquery  = 'SELECT `encoder_options`, `comments_id3v2`';
		$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
		$SQLquery .= ' ORDER BY (`encoder_options` LIKE "LAME%") DESC, (`encoder_options` LIKE "CBR%") DESC';
		$result = mysql_query($SQLquery);
		$EncodedBy = array();
		while ($row = mysql_fetch_array($result)) {
			set_time_limit(30);
			$CommentArray = unserialize($row['comments_id3v2']);
			if (isset($EncodedBy[$row['encoder_options']][@$CommentArray['encoded_by'][0]])) {
				$EncodedBy[$row['encoder_options']][@$CommentArray['encoded_by'][0]]++;
			} else {
				$EncodedBy[$row['encoder_options']][@$CommentArray['encoded_by'][0]] = 1;
			}
		}
		echo '<a href="'.htmlentities($_SERVER['PHP_SELF'].'?encodedbydistribution='.urlencode('%').'&m3u=1').'">.m3u version</a><br>';
		echo '<table border="1"><tr><th>m3u</th><th>Encoder Options</th><th>Encoded By (ID3v2)</th></tr>';
		foreach ($EncodedBy as $key => $value) {
			echo '<tr><TD VALIGN="TOP"><a href="'.htmlentities($_SERVER['PHP_SELF'].'?encodedbydistribution='.urlencode($key).'&showfiles=1&m3u=1').'">m3u</a></td>';
			echo '<TD VALIGN="TOP"><b>'.$key.'</b></td>';
			echo '<td><table border="0" WIDTH="100%">';
			arsort($value);
			foreach ($value as $string => $count) {
				echo '<tr><TD ALIGN="RIGHT" WIDTH="50"><i>'.number_format($count).'</i></td><td>&nbsp;</td>';
				echo '<td><a href="'.htmlentities($_SERVER['PHP_SELF'].'?encodedbydistribution='.urlencode($string).'&showfiles=1').'">'.$string.'</a></td></tr>';
			}
			echo '</table></td></tr>';
		}
		echo '</table>';

	}

} elseif (!empty($_REQUEST['audiobitrates'])) {

	getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.audio.mp3.php', __FILE__, true);
	$BitrateDistribution = array();
	$SQLquery  = 'SELECT ROUND(audio_bitrate / 1000) AS `RoundBitrate`, COUNT(*) AS `num`';
	$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
	$SQLquery .= ' WHERE (`audio_bitrate` > 0)';
	$SQLquery .= ' GROUP BY `RoundBitrate`';
	$result = safe_mysql_query($SQLquery);
	while ($row = mysql_fetch_array($result)) {
		@$BitrateDistribution[getid3_mp3::ClosestStandardMP3Bitrate($row['RoundBitrate'] * 1000)] += $row['num'];  // safe_inc
	}

	echo '<table border="1" cellspacing="0" cellpadding="3">';
	echo '<tr><th>Bitrate</th><th>Count</th></tr>';
	foreach ($BitrateDistribution as $Bitrate => $Count) {
		echo '<tr>';
		echo '<TD ALIGN="RIGHT">'.round($Bitrate / 1000).' kbps</td>';
		echo '<TD ALIGN="RIGHT">'.number_format($Count).'</td>';
		echo '</tr>';
	}
	echo '</table>';


} elseif (!empty($_REQUEST['emptygenres'])) {

	$SQLquery  = 'SELECT `fileformat`, `filename`, `genre`';
	$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
	$SQLquery .= ' WHERE (`genre` = "")';
	$SQLquery .= ' OR (`genre` = "Unknown")';
	$SQLquery .= ' OR (`genre` = "Other")';
	$SQLquery .= ' ORDER BY `filename` ASC';
	$result = safe_mysql_query($SQLquery);

	if (!empty($_REQUEST['m3u'])) {

		header('Content-type: audio/x-mpegurl');
		echo '#EXTM3U'."\n";
		while ($row = mysql_fetch_array($result)) {
			if (!in_array($row['fileformat'], $IgnoreNoTagFormats)) {
				echo WindowsShareSlashTranslate($row['filename'])."\n";
			}
		}
		exit;

	} else {

		echo '<a href="'.htmlentities($_SERVER['PHP_SELF'].'?emptygenres='.urlencode($_REQUEST['emptygenres']).'&m3u=1').'">.m3u version</a><br>';
		$EmptyGenreCounter = 0;
		echo '<table border="1" cellspacing="0" cellpadding="3">';
		echo '<tr><th>m3u</th><th>filename</th></tr>';
		while ($row = mysql_fetch_array($result)) {
			if (!in_array($row['fileformat'], $IgnoreNoTagFormats)) {
				$EmptyGenreCounter++;
				echo '<tr>';
				echo '<td><a href="'.htmlentities($_SERVER['PHP_SELF'].'?m3ufilename='.urlencode($row['filename'])).'">m3u</a></td>';
				echo '<td><a href="'.htmlentities('demo.browse.php?filename='.rawurlencode($row['filename'])).'">'.FixTextFields($row['filename']).'</a></td>';
				echo '</tr>';
			}
		}
		echo '</table>';
		echo '<b>'.number_format($EmptyGenreCounter).'</b> files with empty genres';

	}

} elseif (!empty($_REQUEST['nonemptycomments'])) {

	$SQLquery  = 'SELECT `filename`, `comment`';
	$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
	$SQLquery .= ' WHERE (`comment` <> "")';
	$SQLquery .= ' ORDER BY `comment` ASC';
	$result = safe_mysql_query($SQLquery);

	if (!empty($_REQUEST['m3u'])) {

		header('Content-type: audio/x-mpegurl');
		echo '#EXTM3U'."\n";
		while ($row = mysql_fetch_array($result)) {
			echo WindowsShareSlashTranslate($row['filename'])."\n";
		}
		exit;

	} else {

		$NonEmptyCommentsCounter = 0;
		echo '<a href="'.htmlentities($_SERVER['PHP_SELF'].'?nonemptycomments='.urlencode($_REQUEST['nonemptycomments']).'&m3u=1').'">.m3u version</a><br>';
		echo '<table border="1" cellspacing="0" cellpadding="3">';
		echo '<tr><th>m3u</th><th>filename</th><th>comments</th></tr>';
		while ($row = mysql_fetch_array($result)) {
			$NonEmptyCommentsCounter++;
			echo '<tr>';
			echo '<td><a href="'.htmlentities($_SERVER['PHP_SELF'].'?m3ufilename='.urlencode($row['filename'])).'">m3u</a></td>';
			echo '<td><a href="'.htmlentities('demo.browse.php?filename='.rawurlencode($row['filename'])).'">'.FixTextFields($row['filename']).'</a></td>';
			if (strlen(trim($row['comment'])) > 0) {
				echo '<td>'.FixTextFields($row['comment']).'</td>';
			} else {
				echo '<td><i>space</i></td>';
			}
			echo '</tr>';
		}
		echo '</table>';
		echo '<b>'.number_format($NonEmptyCommentsCounter).'</b> files with non-empty comments';

	}

} elseif (!empty($_REQUEST['trackzero'])) {

	$SQLquery  = 'SELECT `filename`, `track`';
	$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
	$SQLquery .= ' WHERE (`track` <> "")';
	$SQLquery .= ' AND ((`track` < "1")';
	$SQLquery .= ' OR (`track` > "99"))';
	$SQLquery .= ' ORDER BY `filename` ASC';
	$result = safe_mysql_query($SQLquery);

	if (!empty($_REQUEST['m3u'])) {

		header('Content-type: audio/x-mpegurl');
		echo '#EXTM3U'."\n";
		while ($row = mysql_fetch_array($result)) {
			if ((strlen($row['track']) > 0) && ($row['track'] < 1) || ($row['track'] > 99)) {
				echo WindowsShareSlashTranslate($row['filename'])."\n";
			}
		}
		exit;

	} else {

		echo '<a href="'.htmlentities($_SERVER['PHP_SELF'].'?trackzero='.urlencode($_REQUEST['trackzero']).'&m3u=1').'">.m3u version</a><br>';
		$TrackZeroCounter = 0;
		echo '<table border="1" cellspacing="0" cellpadding="3">';
		echo '<tr><th>m3u</th><th>filename</th><th>track</th></tr>';
		while ($row = mysql_fetch_array($result)) {
			if ((strlen($row['track']) > 0) && ($row['track'] < 1) || ($row['track'] > 99)) {
				$TrackZeroCounter++;
				echo '<tr>';
				echo '<td><a href="'.htmlentities($_SERVER['PHP_SELF'].'?m3ufilename='.urlencode($row['filename'])).'">m3u</a></td>';
				echo '<td><a href="'.htmlentities('demo.browse.php?filename='.rawurlencode($row['filename'])).'">'.FixTextFields($row['filename']).'</a></td>';
				echo '<td>'.FixTextFields($row['track']).'</td>';
				echo '</tr>';
			}
		}
		echo '</table>';
		echo '<b>'.number_format($TrackZeroCounter).'</b> files with track "zero"';

	}


} elseif (!empty($_REQUEST['titlefeat'])) {

	$SQLquery  = 'SELECT `filename`, `title`';
	$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
	$SQLquery .= ' WHERE (`title` LIKE "%feat.%")';
	$SQLquery .= ' ORDER BY `filename` ASC';
	$result = safe_mysql_query($SQLquery);

	if (!empty($_REQUEST['m3u'])) {

		header('Content-type: audio/x-mpegurl');
		echo '#EXTM3U'."\n";
		while ($row = mysql_fetch_array($result)) {
			echo WindowsShareSlashTranslate($row['filename'])."\n";
		}
		exit;

	} else {

		echo '<b>'.number_format(mysql_num_rows($result)).'</b> files with "feat." in the title (instead of the artist)<br><br>';
		echo '<a href="'.htmlentities($_SERVER['PHP_SELF'].'?titlefeat='.urlencode($_REQUEST['titlefeat']).'&m3u=1').'">.m3u version</a><br>';
		echo '<table border="1" cellspacing="0" cellpadding="3">';
		echo '<tr><th>m3u</th><th>filename</th><th>title</th></tr>';
		while ($row = mysql_fetch_array($result)) {
			echo '<tr>';
			echo '<td><a href="'.htmlentities($_SERVER['PHP_SELF'].'?m3ufilename='.urlencode($row['filename'])).'">m3u</a></td>';
			echo '<td><a href="'.htmlentities('demo.browse.php?filename='.rawurlencode($row['filename'])).'">'.FixTextFields($row['filename']).'</a></td>';
			echo '<td>'.eregi_replace('(feat\. .*)', '<b>\\1</b>', FixTextFields($row['title'])).'</td>';
			echo '</tr>';
		}
		echo '</table>';

	}


} elseif (!empty($_REQUEST['tracknoalbum'])) {

	$SQLquery  = 'SELECT `filename`, `track`, `album`';
	$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
	$SQLquery .= ' WHERE (`track` <> "")';
	$SQLquery .= ' AND (`album` = "")';
	$SQLquery .= ' ORDER BY `filename` ASC';
	$result = safe_mysql_query($SQLquery);

	if (!empty($_REQUEST['m3u'])) {

		header('Content-type: audio/x-mpegurl');
		echo '#EXTM3U'."\n";
		while ($row = mysql_fetch_array($result)) {
			echo WindowsShareSlashTranslate($row['filename'])."\n";
		}
		exit;

	} else {

		echo '<b>'.number_format(mysql_num_rows($result)).'</b> files with a track number, but no album<br><br>';
		echo '<a href="'.htmlentities($_SERVER['PHP_SELF'].'?tracknoalbum='.urlencode($_REQUEST['tracknoalbum']).'&m3u=1').'">.m3u version</a><br>';
		echo '<table border="1" cellspacing="0" cellpadding="3">';
		echo '<tr><th>m3u</th><th>filename</th><th>track</th><th>album</th></tr>';
		while ($row = mysql_fetch_array($result)) {
			echo '<tr>';
			echo '<td><a href="'.htmlentities($_SERVER['PHP_SELF'].'?m3ufilename='.urlencode($row['filename'])).'">m3u</a></td>';
			echo '<td><a href="'.htmlentities('demo.browse.php?filename='.rawurlencode($row['filename'])).'">'.FixTextFields($row['filename']).'</a></td>';
			echo '<td>'.FixTextFields($row['track']).'</td>';
			echo '<td>'.FixTextFields($row['album']).'</td>';
			echo '</tr>';
		}
		echo '</table>';

	}


} elseif (!empty($_REQUEST['synchronizetagsfrom']) && !empty($_REQUEST['filename'])) {

	echo 'Applying new tags from <b>'.$_REQUEST['synchronizetagsfrom'].'</b> in <b>'.FixTextFields($_REQUEST['filename']).'</b><ul>';
	$errors = array();
	if (SynchronizeAllTags($_REQUEST['filename'], $_REQUEST['synchronizetagsfrom'], 'A12', $errors)) {
		echo '<li>Sucessfully wrote tags</li>';
	} else {
		echo '<li>Tag writing had errors: <ul><li>'.implode('</li><li>', $errors).'</li></ul></li>';
	}
	echo '</ul>';


} elseif (!empty($_REQUEST['unsynchronizedtags'])) {

	$NotOKfiles        = 0;
	$Autofixedfiles    = 0;
	$FieldsToCompare   = array('title', 'artist', 'album', 'year', 'genre', 'comment', 'track');
	$TagsToCompare     = array('id3v2'=>false, 'ape'=>false, 'lyrics3'=>false, 'id3v1'=>false);
	$ID3v1FieldLengths = array('title'=>30, 'artist'=>30, 'album'=>30, 'year'=>4, 'genre'=>99, 'comment'=>28);
	if (strpos($_REQUEST['unsynchronizedtags'], '2') !== false) {
		$TagsToCompare['id3v2'] = true;
	}
	if (strpos($_REQUEST['unsynchronizedtags'], 'A') !== false) {
		$TagsToCompare['ape'] = true;
	}
	if (strpos($_REQUEST['unsynchronizedtags'], 'L') !== false) {
		$TagsToCompare['lyrics3'] = true;
	}
	if (strpos($_REQUEST['unsynchronizedtags'], '1') !== false) {
		$TagsToCompare['id3v1'] = true;
	}

	echo '<a href="'.htmlentities($_SERVER['PHP_SELF'].'?unsynchronizedtags='.urlencode($_REQUEST['unsynchronizedtags']).'&autofix=1').'">Auto-fix empty tags</a><br><br>';
	echo '<div id="Autofixing"></div>';
	echo '<table border="1" cellspacing="0" cellpadding="3">';
	echo '<tr>';
	echo '<th>View</th>';
	echo '<th>Filename</th>';
	echo '<th>Combined</th>';
	if ($TagsToCompare['id3v2']) {
		echo '<th><a href="'.htmlentities($_SERVER['PHP_SELF'].'?unsynchronizedtags='.urlencode($_REQUEST['unsynchronizedtags']).'&autofix=1&autofixforcesource=id3v2&autofixforcedest=A1').'" title="Auto-fix all tags to match ID3v2 contents" onClick="return confirm(\'Are you SURE you want to synchronize all tags to match ID3v2?\');">ID3v2</a></th>';
	}
	if ($TagsToCompare['ape']) {
		echo '<th><a href="'.htmlentities($_SERVER['PHP_SELF'].'?unsynchronizedtags='.urlencode($_REQUEST['unsynchronizedtags']).'&autofix=1&autofixforcesource=ape&autofixforcedest=21').'" title="Auto-fix all tags to match APE contents" onClick="return confirm(\'Are you SURE you want to synchronize all tags to match APE?\');">APE</a></th>';
	}
	if ($TagsToCompare['lyrics3']) {
		echo '<th>Lyrics3</th>';
	}
	if ($TagsToCompare['id3v1']) {
		echo '<th><a href="'.htmlentities($_SERVER['PHP_SELF'].'?unsynchronizedtags='.urlencode($_REQUEST['unsynchronizedtags']).'&autofix=1&autofixforcesource=ape&autofixforcedest=2A').'" title="Auto-fix all tags to match ID3v1 contents" onClick="return confirm(\'Are you SURE you want to synchronize all tags to match ID3v1?\');">ID3v1</a></th>';
	}
	echo '</tr>';

	$SQLquery  = 'SELECT `filename`, `comments_all`, `comments_id3v2`, `comments_ape`, `comments_lyrics3`, `comments_id3v1`';
	$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
	$SQLquery .= ' WHERE (`fileformat` = "mp3")';
	$SQLquery .= ' ORDER BY `filename` ASC';
	$result = safe_mysql_query($SQLquery);
	$lastdir = '';
	while ($row = mysql_fetch_array($result)) {
		set_time_limit(30);
		if ($lastdir != dirname($row['filename'])) {
			echo '<script type="text/javascript">if (document.getElementById("Autofixing")) document.getElementById("Autofixing").innerHTML = "'.htmlentities($lastdir, ENT_QUOTES).'";</script>';
			flush();
		}

		$FileOK      = true;
		$Mismatched  = array('id3v2'=>false, 'ape'=>false, 'lyrics3'=>false, 'id3v1'=>false);
		$SemiMatched = array('id3v2'=>false, 'ape'=>false, 'lyrics3'=>false, 'id3v1'=>false);
		$EmptyTags   = array('id3v2'=>true,  'ape'=>true,  'lyrics3'=>true,  'id3v1'=>true);

		$Comments['all']     = @unserialize($row['comments_all']);
		$Comments['id3v2']   = @unserialize($row['comments_id3v2']);
		$Comments['ape']     = @unserialize($row['comments_ape']);
		$Comments['lyrics3'] = @unserialize($row['comments_lyrics3']);
		$Comments['id3v1']   = @unserialize($row['comments_id3v1']);

		if (isset($Comments['ape']['tracknumber'])) {
			$Comments['ape']['track'] = $Comments['ape']['tracknumber'];
			unset($Comments['ape']['tracknumber']);
		}
		if (isset($Comments['ape']['track_number'])) {
			$Comments['ape']['track'] = $Comments['ape']['track_number'];
			unset($Comments['ape']['track_number']);
		}
		if (isset($Comments['id3v2']['track_number'])) {
			$Comments['id3v2']['track'] = $Comments['id3v2']['track_number'];
			unset($Comments['id3v2']['track_number']);
		}
		if (!empty($Comments['all']['track'])) {
			$besttrack = '';
			foreach ($Comments['all']['track'] as $key => $value) {
				if (strlen($value) > strlen($besttrack)) {
					$besttrack = $value;
				}
			}
			$Comments['all']['track'] = array(0=>$besttrack);
		}

		$ThisLine  = '<tr>';
		$ThisLine .= '<td><a href="'.htmlentities('demo.browse.php?filename='.rawurlencode($row['filename'])).'">view</a></td>';
		$ThisLine .= '<td><a href="'.htmlentities($_SERVER['PHP_SELF'].'?m3ufilename='.urlencode($row['filename'])).'">'.FixTextFields($row['filename']).'</a></td>';
		$tagvalues = '';
		foreach ($FieldsToCompare as $fieldname) {
			$tagvalues .= $fieldname.' = '.@implode(" \n", @$Comments['all'][$fieldname])." \n";
		}
		$ThisLine .= '<td><a href="'.htmlentities($_SERVER['PHP_SELF'].'?synchronizetagsfrom=all&filename='.urlencode($row['filename'])).'" title="'.htmlentities(rtrim($tagvalues, "\n"), ENT_QUOTES).'" target="retagwindow">all</a></td>';
		foreach ($TagsToCompare as $tagtype => $CompareThisTagType) {
			if ($CompareThisTagType) {
				$tagvalues = '';
				foreach ($FieldsToCompare as $fieldname) {

					if ($tagtype == 'id3v1') {

						getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.tag.id3v1.php', __FILE__, true);
						if (($fieldname == 'genre') && !getid3_id3v1::LookupGenreID(@$Comments['all'][$fieldname][0])) {

							// non-standard genres can never match, so just ignore
							$tagvalues .= $fieldname.' = '.@$Comments[$tagtype][$fieldname][0]."\n";

						} elseif ($fieldname == 'comment') {

							if (rtrim(substr(@$Comments[$tagtype][$fieldname][0], 0, 28)) != rtrim(substr(@$Comments['all'][$fieldname][0], 0, 28))) {
//echo __LINE__.'<br>';
//echo '<pre>';
//var_dump($tagtype);
//var_dump($fieldname);
//echo '<pre>';
//exit;
								$tagvalues .= $fieldname.' = [['.@$Comments[$tagtype][$fieldname][0].']]'."\n";
								if (trim(strtolower(RemoveAccents(substr(@$Comments[$tagtype][$fieldname][0], 0, 28)))) == trim(strtolower(RemoveAccents(substr(@$Comments['all'][$fieldname][0], 0, 28))))) {
									$SemiMatched[$tagtype] = true;
								} else {
									$Mismatched[$tagtype]  = true;
								}
								$FileOK = false;
							} else {
								$tagvalues .= $fieldname.' = '.@$Comments[$tagtype][$fieldname][0]."\n";
							}

						} elseif ($fieldname == 'track') {

							// intval('01/20') == intval('1')
							if (intval(@$Comments[$tagtype][$fieldname][0]) != intval(@$Comments['all'][$fieldname][0])) {
//echo __LINE__.'<br>';
//echo '<pre>';
//var_dump($tagtype);
//var_dump($fieldname);
//echo '<pre>';
//exit;
								$tagvalues .= $fieldname.' = [['.@$Comments[$tagtype][$fieldname][0].']]'."\n";
								$Mismatched[$tagtype]  = true;
								$FileOK = false;
							} else {
								$tagvalues .= $fieldname.' = '.@$Comments[$tagtype][$fieldname][0]."\n";
							}

						} elseif (rtrim(substr(@$Comments[$tagtype][$fieldname][0], 0, 30)) != rtrim(substr(@$Comments['all'][$fieldname][0], 0, 30))) {

//echo __LINE__.'<br>';
//echo '<pre>';
//var_dump($tagtype);
//var_dump($fieldname);
//echo '<pre>';
//exit;
							$tagvalues .= $fieldname.' = [['.@$Comments[$tagtype][$fieldname][0].']]'."\n";
							if (strtolower(RemoveAccents(trim(substr(@$Comments[$tagtype][$fieldname][0], 0, 30)))) == strtolower(RemoveAccents(trim(substr(@$Comments['all'][$fieldname][0], 0, 30))))) {
								$SemiMatched[$tagtype] = true;
							} else {
								$Mismatched[$tagtype]  = true;
							}
							$FileOK = false;
							if (strlen(trim(@$Comments[$tagtype][$fieldname][0])) > 0) {
								$EmptyTags[$tagtype] = false;
							}

						} else {

							$tagvalues .= $fieldname.' = '.@$Comments[$tagtype][$fieldname][0]."\n";
							if (strlen(trim(@$Comments[$tagtype][$fieldname][0])) > 0) {
								$EmptyTags[$tagtype] = false;
							}

						}

					} elseif (($tagtype == 'ape') && ($fieldname == 'year')) {

						if ((@$Comments['ape']['date'][0] != @$Comments['all']['year'][0]) && (@$Comments['ape']['year'][0] != @$Comments['all']['year'][0])) {

							$tagvalues .= $fieldname.' = [['.@$Comments['ape']['date'][0].']]'."\n";
							$Mismatched[$tagtype]  = true;
							$FileOK = false;
							if (strlen(trim(@$Comments['ape']['date'][0])) > 0) {
								$EmptyTags[$tagtype] = false;
							}

						} else {

							$tagvalues .= $fieldname.' = '.@$Comments[$tagtype][$fieldname][0]."\n";
							if (strlen(trim(@$Comments[$tagtype][$fieldname][0])) > 0) {
								$EmptyTags[$tagtype] = false;
							}

						}

					} elseif (($fieldname == 'genre') && !empty($Comments['all'][$fieldname]) && !empty($Comments[$tagtype][$fieldname]) && in_array($Comments[$tagtype][$fieldname][0], $Comments['all'][$fieldname])) {

						$tagvalues .= $fieldname.' = '.@$Comments[$tagtype][$fieldname][0]."\n";
						if (strlen(trim(@$Comments[$tagtype][$fieldname][0])) > 0) {
							$EmptyTags[$tagtype] = false;
						}

					} elseif (@$Comments[$tagtype][$fieldname][0] != @$Comments['all'][$fieldname][0]) {

//echo __LINE__.'<br>';
//echo '<pre>';
//var_dump($tagtype);
//var_dump($fieldname);
//var_dump($Comments[$tagtype][$fieldname][0]);
//var_dump($Comments['all'][$fieldname][0]);
//echo '<pre>';
//exit;
						$skiptracknumberfield = false;
						switch ($fieldname) {
							case 'track':
							case 'tracknumber':
							case 'track_number':
								if (intval(@$Comments[$tagtype][$fieldname][0]) == intval(@$Comments['all'][$fieldname][0])) {
									$skiptracknumberfield = true;
								}
								break;
						}
						if (!$skiptracknumberfield) {
							$tagvalues .= $fieldname.' = [['.@$Comments[$tagtype][$fieldname][0].']]'."\n";
							if (trim(strtolower(RemoveAccents(@$Comments[$tagtype][$fieldname][0]))) == trim(strtolower(RemoveAccents(@$Comments['all'][$fieldname][0])))) {
								$SemiMatched[$tagtype] = true;
							} else {
								$Mismatched[$tagtype]  = true;
							}
							$FileOK = false;
							if (strlen(trim(@$Comments[$tagtype][$fieldname][0])) > 0) {
								$EmptyTags[$tagtype] = false;
							}
						}

					} else {

						$tagvalues .= $fieldname.' = '.@$Comments[$tagtype][$fieldname][0]."\n";
						if (strlen(trim(@$Comments[$tagtype][$fieldname][0])) > 0) {
							$EmptyTags[$tagtype] = false;
						}

					}
				}

				if ($EmptyTags[$tagtype]) {
					$FileOK = false;
					$ThisLine .= '<td bgcolor="#0099cc">';
				} elseif ($SemiMatched[$tagtype]) {
					$ThisLine .= '<td bgcolor="#ff9999">';
				} elseif ($Mismatched[$tagtype]) {
					$ThisLine .= '<td bgcolor="#ff0000">';
				} else {
					$ThisLine .= '<td bgcolor="#00cc00">';
				}
				$ThisLine .= '<a href="'.htmlentities($_SERVER['PHP_SELF'].'?synchronizetagsfrom='.$tagtype.'&filename='.urlencode($row['filename'])).'" title="'.htmlentities(rtrim($tagvalues, "\n"), ENT_QUOTES).'" TARGET="retagwindow">'.$tagtype.'</a>';
				$ThisLine .= '</td>';
			}
		}
		$ThisLine .= '</tr>';

		if (!$FileOK) {
			$NotOKfiles++;

			echo '<script type="text/javascript">if (document.getElementById("Autofixing")) document.getElementById("Autofixing").innerHTML = "'.htmlentities($row['filename'], ENT_QUOTES).'";</script>';
			flush();

			if (!empty($_REQUEST['autofix'])) {

				$AnyMismatched = false;
				foreach ($Mismatched as $key => $value) {
					if ($value && ($EmptyTags["$key"] === false)) {
						$AnyMismatched = true;
					}
				}
				if ($AnyMismatched && empty($_REQUEST['autofixforcesource'])) {

					echo $ThisLine;

				} else {

					$TagsToSynch = '';
					foreach ($EmptyTags as $key => $value) {
						if ($value) {
							switch ($key) {
								case 'id3v1':
									$TagsToSynch .= '1';
									break;
								case 'id3v2':
									$TagsToSynch .= '2';
									break;
								case 'ape':
									$TagsToSynch .= 'A';
									break;
							}
						}
					}

					$autofixforcesource = (@$_REQUEST['autofixforcesource'] ? $_REQUEST['autofixforcesource'] : 'all');
					$TagsToSynch        = (@$_REQUEST['autofixforcedest']   ? $_REQUEST['autofixforcedest']   : $TagsToSynch);

					$errors = array();
					if (SynchronizeAllTags($row['filename'], $autofixforcesource, $TagsToSynch, $errors)) {
						$Autofixedfiles++;
						echo '<tr bgcolor="#00CC00">';
					} else {
						echo '<tr bgcolor="#FF0000">';
					}
					echo '<td>&nbsp;</th>';
					echo '<td><a href="'.htmlentities($_SERVER['PHP_SELF'].'?m3ufilename='.urlencode($row['filename'])).'" title="'.FixTextFields(implode("\n", $errors)).'">'.FixTextFields($row['filename']).'</a></td>';
					echo '<td><table border="0">';
					echo '<tr><td><b>'.$TagsToSynch.'</b></td></tr>';
					echo '</table></td></tr>';
				}

			} else {

				echo $ThisLine;

			}
		}
	}

	echo '</table><br>';
	echo '<script type="text/javascript">if (document.getElementById("Autofixing")) document.getElementById("Autofixing").innerHTML = "";</script>';
	echo 'Found <b>'.number_format($NotOKfiles).'</b> files with unsynchronized tags, and auto-fixed '.number_format($Autofixedfiles).' of them.';

} elseif (!empty($_REQUEST['filenamepattern'])) {

	$patterns['A'] = 'artist';
	$patterns['T'] = 'title';
	$patterns['M'] = 'album';
	$patterns['N'] = 'track';
	$patterns['G'] = 'genre';
	$patterns['R'] = 'remix';

	$FieldsToUse = explode(' ', wordwrap(eregi_replace('[^A-Z]', '', $_REQUEST['filenamepattern']), 1, ' ', 1));
	//$FieldsToUse = explode(' ', wordwrap($_REQUEST['filenamepattern'], 1, ' ', 1));
	foreach ($FieldsToUse as $FieldID) {
		$FieldNames[] = $patterns["$FieldID"];
	}

	$SQLquery  = 'SELECT `filename`, `fileformat`, '.implode(', ', $FieldNames);
	$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
	$SQLquery .= ' WHERE (`fileformat` NOT LIKE "'.implode('") AND (`fileformat` NOT LIKE "', $IgnoreNoTagFormats).'")';
	$SQLquery .= ' ORDER BY `filename` ASC';
	$result = safe_mysql_query($SQLquery);
	echo 'Files that do not match naming pattern: (<a href="'.htmlentities($_SERVER['PHP_SELF'].'?filenamepattern='.urlencode($_REQUEST['filenamepattern']).'&autofix=1').'">auto-fix</a>)<br>';
	echo '<table border="1" cellspacing="0" cellpadding="3">';
	echo '<tr><th>view</th><th>Why</th><td><b>Actual filename</b><br>(click to play/edit file)</td><td><b>Correct filename (based on tags)</b>'.(!@$_REQUEST['autofix'] ? '<br>(click to rename file to this)' : '').'</td></tr>';
	$nonmatchingfilenames = 0;
	$Pattern = $_REQUEST['filenamepattern'];
	$PatternLength = strlen($Pattern);
	while ($row = mysql_fetch_array($result)) {
		set_time_limit(10);
		$PatternFilename = '';
		for ($i = 0; $i < $PatternLength; $i++) {
			if (isset($patterns[$Pattern{$i}])) {
				$PatternFilename .= trim(strtr($row[$patterns[$Pattern{$i}]], ':\\*<>|', ';-¤«»¦'), ' ');
			} else {
				$PatternFilename .= $Pattern{$i};
			}
		}

		// Replace "~" with "-" if characters immediately before and after are both numbers,
		// "/" has been replaced with "~" above which is good for multi-song medley dividers,
		// but for things like 24/7, 7/8ths, etc it looks better if it's 24-7, 7-8ths, etc.
		$PatternFilename = eregi_replace('([ a-z]+)/([ a-z]+)', '\\1~\\2', $PatternFilename);
		$PatternFilename = str_replace('/',  '×',  $PatternFilename);

		$PatternFilename = str_replace('?',  '¿',  $PatternFilename);
		$PatternFilename = str_replace(' "', ' “', $PatternFilename);
		$PatternFilename = str_replace('("', '(“', $PatternFilename);
		$PatternFilename = str_replace('-"', '-“', $PatternFilename);
		$PatternFilename = str_replace('" ', '” ', $PatternFilename.' ');
		$PatternFilename = str_replace('"',  '”',  $PatternFilename);
		$PatternFilename = str_replace('  ', ' ',  $PatternFilename);


		$ParenthesesPairs = array('()', '[]', '{}');
		foreach ($ParenthesesPairs as $pair) {

			// multiple remixes are stored tab-seperated in the database.
			// change "{2000 Version\tSomebody Remix}" into "{2000 Version} {Somebody Remix}"
			while (ereg('^(.*)'.preg_quote($pair{0}).'([^'.preg_quote($pair{1}).']*)('."\t".')([^'.preg_quote($pair{0}).']*)'.preg_quote($pair{1}), $PatternFilename, $matches)) {
				$PatternFilename = $matches[1].$pair{0}.$matches[2].$pair{1}.' '.$pair{0}.$matches[4].$pair{1};
			}

			// remove empty parenthesized pairs (probably where no track numbers, remix version, etc)
			$PatternFilename = ereg_replace(preg_quote($pair), '', $PatternFilename);

			// "[01]  - Title With No Artist.mp3"  ==>  "[01] Title With No Artist.mp3"
			$PatternFilename = ereg_replace(preg_quote($pair{1}).' +\- ', $pair{1}.' ', $PatternFilename);

		}

		// get rid of leading & trailing spaces if end items (artist or title for example) are missing
		$PatternFilename  = trim($PatternFilename, ' -');

		if (!$PatternFilename) {
			// no tags to create a filename from -- skip this file
			continue;
		}
		$PatternFilename .= '.'.$row['fileformat'];

		$ActualFilename = basename($row['filename']);
		if ($ActualFilename != $PatternFilename) {

			$NotMatchedReasons = '';
			if (strtolower($ActualFilename) === strtolower($PatternFilename)) {
				$NotMatchedReasons .= 'Aa ';
			} elseif (RemoveAccents($ActualFilename) === RemoveAccents($PatternFilename)) {
				$NotMatchedReasons .= 'ée ';
			}


			$actualExt  = '.'.fileextension($ActualFilename);
			$patternExt = '.'.fileextension($PatternFilename);
			$ActualFilenameNoExt  = (($actualExt  != '.') ? substr($ActualFilename,   0, 0 - strlen($actualExt))  : $ActualFilename);
			$PatternFilenameNoExt = (($patternExt != '.') ? substr($PatternFilename,  0, 0 - strlen($patternExt)) : $PatternFilename);

			if (strpos($PatternFilenameNoExt, $ActualFilenameNoExt) !== false) {
				$DifferenceBoldedName  = str_replace($ActualFilenameNoExt, '</b>'.$ActualFilenameNoExt.'<b>', $PatternFilenameNoExt);
			} else {
				$ShortestNameLength = min(strlen($ActualFilenameNoExt), strlen($PatternFilenameNoExt));
				for ($DifferenceOffset = 0; $DifferenceOffset < $ShortestNameLength; $DifferenceOffset++) {
					if ($ActualFilenameNoExt{$DifferenceOffset} !== $PatternFilenameNoExt{$DifferenceOffset}) {
						break;
					}
				}
				$DifferenceBoldedName  = '</b>'.substr($PatternFilenameNoExt, 0, $DifferenceOffset).'<b>'.substr($PatternFilenameNoExt, $DifferenceOffset);
			}
			$DifferenceBoldedName .= (($actualExt == $patternExt) ? '</b>'.$patternExt.'<b>' : $patternExt);


			echo '<tr>';
			echo '<td><a href="'.htmlentities('demo.browse.php?filename='.rawurlencode($row['filename'])).'">view</a></td>';
			echo '<td>&nbsp;'.$NotMatchedReasons.'</td>';
			echo '<td><a href="'.htmlentities($_SERVER['PHP_SELF'].'?m3ufilename='.urlencode($row['filename'])).'">'.FixTextFields($ActualFilename).'</a></td>';

			if (@$_REQUEST['autofix']) {

				$results = '';
				if (RenameFileFromTo($row['filename'], dirname($row['filename']).'/'.$PatternFilename, $results)) {
					echo '<TD BGCOLOR="#009900">';
				} else {
					echo '<TD BGCOLOR="#FF0000">';
				}
				echo '<b>'.$DifferenceBoldedName.'</b></td>';


			} else {

				echo '<td><a href="'.htmlentities($_SERVER['PHP_SELF'].'?filenamepattern='.urlencode($_REQUEST['filenamepattern']).'&renamefilefrom='.urlencode($row['filename']).'&renamefileto='.urlencode(dirname($row['filename']).'/'.$PatternFilename)).'" title="'.FixTextFields(basename($row['filename']))."\n".FixTextFields(basename($PatternFilename)).'" TARGET="renamewindow">';
				echo '<b>'.$DifferenceBoldedName.'</b></a></td>';

			}
			echo '</tr>';

			$nonmatchingfilenames++;
		}
	}
	echo '</table><br>';
	echo 'Found '.number_format($nonmatchingfilenames).' files that do not match naming pattern<br>';


} elseif (!empty($_REQUEST['encoderoptionsdistribution'])) {

	if (isset($_REQUEST['showtagfiles'])) {
		$SQLquery  = 'SELECT `filename`, `encoder_options` FROM `'.GETID3_DB_TABLE.'`';
		$SQLquery .= ' WHERE (`encoder_options` LIKE "'.mysql_escape_string($_REQUEST['showtagfiles']).'")';
		$SQLquery .= ' AND (`fileformat` NOT LIKE "'.implode('") AND (`fileformat` NOT LIKE "', $IgnoreNoTagFormats).'")';
		$SQLquery .= ' ORDER BY `filename` ASC';
		$result = safe_mysql_query($SQLquery);

		if (!empty($_REQUEST['m3u'])) {

			header('Content-type: audio/x-mpegurl');
			echo '#EXTM3U'."\n";
			while ($row = mysql_fetch_array($result)) {
				echo WindowsShareSlashTranslate($row['filename'])."\n";
			}
			exit;

		} else {

			echo '<a href="'.htmlentities($_SERVER['PHP_SELF'].'?encoderoptionsdistribution=1').'">Show all Encoder Options</a><hr>';
			echo 'Files with Encoder Options <b>'.$_REQUEST['showtagfiles'].'</b>:<br>';
			echo '<table border="1" cellspacing="0" cellpadding="3">';
			while ($row = mysql_fetch_array($result)) {
				echo '<tr>';
				echo '<td><a href="'.htmlentities('demo.browse.php?filename='.rawurlencode($row['filename'])).'">'.FixTextFields($row['filename']).'</a></td>';
				echo '<td>'.$row['encoder_options'].'</td>';
				echo '</tr>';
			}
			echo '</table>';

		}

	} elseif (!isset($_REQUEST['m3u'])) {

		$SQLquery  = 'SELECT `encoder_options`, COUNT(*) AS `num` FROM `'.GETID3_DB_TABLE.'`';
		$SQLquery .= ' WHERE (`fileformat` NOT LIKE "'.implode('") AND (`fileformat` NOT LIKE "', $IgnoreNoTagFormats).'")';
		$SQLquery .= ' GROUP BY `encoder_options`';
		$SQLquery .= ' ORDER BY (`encoder_options` LIKE "LAME%") DESC, (`encoder_options` LIKE "CBR%") DESC, `num` DESC, `encoder_options` ASC';
		$result = safe_mysql_query($SQLquery);
		echo 'Files with Encoder Options:<br>';
		echo '<table border="1" cellspacing="0" cellpadding="3">';
		echo '<tr><th>Encoder Options</th><th>Count</th><th>M3U</th></tr>';
		while ($row = mysql_fetch_array($result)) {
			echo '<tr>';
			echo '<td>'.$row['encoder_options'].'</td>';
			echo '<TD ALIGN="RIGHT"><a href="'.htmlentities($_SERVER['PHP_SELF'].'?encoderoptionsdistribution=1&showtagfiles='.($row['encoder_options'] ? urlencode($row['encoder_options']) : '')).'">'.number_format($row['num']).'</a></td>';
			echo '<TD ALIGN="RIGHT"><a href="'.htmlentities($_SERVER['PHP_SELF'].'?encoderoptionsdistribution=1&showtagfiles='.($row['encoder_options'] ? urlencode($row['encoder_options']) : '').'&m3u=.m3u').'">m3u</a></td>';
			echo '</tr>';
		}
		echo '</table><hr>';

	}

} elseif (!empty($_REQUEST['tagtypes'])) {

	if (!isset($_REQUEST['m3u'])) {
		$SQLquery  = 'SELECT `tags`, COUNT(*) AS `num` FROM `'.GETID3_DB_TABLE.'`';
		$SQLquery .= ' WHERE (`fileformat` NOT LIKE "'.implode('") AND (`fileformat` NOT LIKE "', $IgnoreNoTagFormats).'")';
		$SQLquery .= ' GROUP BY `tags`';
		$SQLquery .= ' ORDER BY `num` DESC';
		$result = safe_mysql_query($SQLquery);
		echo 'Files with tags:<br>';
		echo '<table border="1" cellspacing="0" cellpadding="3">';
		echo '<tr><th>Tags</th><th>Count</th><th>M3U</th></tr>';
		while ($row = mysql_fetch_array($result)) {
			echo '<tr>';
			echo '<td>'.$row['tags'].'</td>';
			echo '<TD ALIGN="RIGHT"><a href="'.htmlentities($_SERVER['PHP_SELF'].'?tagtypes=1&showtagfiles='.($row['tags'] ? urlencode($row['tags']) : '')).'">'.number_format($row['num']).'</a></td>';
			echo '<TD ALIGN="RIGHT"><a href="'.htmlentities($_SERVER['PHP_SELF'].'?tagtypes=1&showtagfiles='.($row['tags'] ? urlencode($row['tags']) : '').'&m3u=.m3u').'">m3u</a></td>';
			echo '</tr>';
		}
		echo '</table><hr>';
	}

	if (isset($_REQUEST['showtagfiles'])) {
		$SQLquery  = 'SELECT `filename`, `tags` FROM `'.GETID3_DB_TABLE.'`';
		$SQLquery .= ' WHERE (`tags` LIKE "'.mysql_escape_string($_REQUEST['showtagfiles']).'")';
		$SQLquery .= ' AND (`fileformat` NOT LIKE "'.implode('") AND (`fileformat` NOT LIKE "', $IgnoreNoTagFormats).'")';
		$SQLquery .= ' ORDER BY `filename` ASC';
		$result = safe_mysql_query($SQLquery);

		if (!empty($_REQUEST['m3u'])) {

			header('Content-type: audio/x-mpegurl');
			echo '#EXTM3U'."\n";
			while ($row = mysql_fetch_array($result)) {
				echo WindowsShareSlashTranslate($row['filename'])."\n";
			}
			exit;

		} else {

			echo '<table border="1" cellspacing="0" cellpadding="3">';
			while ($row = mysql_fetch_array($result)) {
				echo '<tr>';
				echo '<td><a href="demo.browse.php?filename='.rawurlencode($row['filename']).'">'.FixTextFields($row['filename']).'</a></td>';
				echo '<td>'.$row['tags'].'</td>';
				echo '</tr>';
			}
			echo '</table>';

		}
	}


} elseif (!empty($_REQUEST['md5datadupes'])) {

	$OtherFormats = '';
	$AVFormats    = '';

	$SQLquery  = 'SELECT `md5_data`, `filename`, COUNT(*) AS `num`';
	$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
	$SQLquery .= ' WHERE (`md5_data` <> "")';
	$SQLquery .= ' GROUP BY `md5_data`';
	$SQLquery .= ' ORDER BY `num` DESC';
	$result = safe_mysql_query($SQLquery);
	while (($row = mysql_fetch_array($result)) && ($row['num'] > 1)) {
		set_time_limit(30);

		$filenames = array();
		$tags      = array();
		$md5_data  = array();
		$SQLquery  = 'SELECT `fileformat`, `filename`, `tags`';
		$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
		$SQLquery .= ' WHERE (`md5_data` = "'.mysql_escape_string($row['md5_data']).'")';
		$SQLquery .= ' ORDER BY `filename` ASC';
		$result2 = safe_mysql_query($SQLquery);
		while ($row2 = mysql_fetch_array($result2)) {
			$thisfileformat = $row2['fileformat'];
			$filenames[] = $row2['filename'];
			$tags[]      = $row2['tags'];
			$md5_data[]  = $row['md5_data'];
		}

		$thisline  = '<tr>';
		$thisline .= '<TD VALIGN="TOP" style="font-family: monospace;">'.implode('<br>', $md5_data).'</td>';
		$thisline .= '<TD VALIGN="TOP" NOWRAP>'.implode('<br>', $tags).'</td>';
		$thisline .= '<TD VALIGN="TOP">'.implode('<br>', $filenames).'</td>';
		$thisline .= '</tr>';

		if (in_array($thisfileformat, $IgnoreNoTagFormats)) {
			$OtherFormats .= $thisline;
		} else {
			$AVFormats .= $thisline;
		}
	}
	echo 'Duplicated MD5_DATA (Audio/Video files):<table border="1" cellspacing="0" cellpadding="2">';
	echo $AVFormats.'</table><hr>';
	echo 'Duplicated MD5_DATA (Other files):<table border="1" cellspacing="0" cellpadding="2">';
	echo $OtherFormats.'</table><hr>';


} elseif (!empty($_REQUEST['artisttitledupes'])) {

	if (isset($_REQUEST['m3uartist']) && isset($_REQUEST['m3utitle'])) {

		header('Content-type: audio/x-mpegurl');
		echo '#EXTM3U'."\n";
		$SQLquery  = 'SELECT `filename`';
		$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
		$SQLquery .= ' WHERE (`artist` = "'.mysql_escape_string($_REQUEST['m3uartist']).'")';
		$SQLquery .= ' AND (`title` = "'.mysql_escape_string($_REQUEST['m3utitle']).'")';
		$SQLquery .= ' ORDER BY `playtime_seconds` ASC, `remix` ASC, `filename` ASC';
		$result = safe_mysql_query($SQLquery);
		while ($row = mysql_fetch_array($result)) {
			echo WindowsShareSlashTranslate($row['filename'])."\n";
		}
		exit;

	}

	$SQLquery  = 'SELECT `artist`, `title`, `filename`, COUNT(*) AS `num`';
	$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
	$SQLquery .= ' WHERE (`artist` <> "")';
	$SQLquery .= ' AND (`title` <> "")';
	$SQLquery .= ' GROUP BY `artist`, `title`'.(@$_REQUEST['samemix'] ? ', `remix`' : '');
	$SQLquery .= ' ORDER BY `num` DESC, `artist` ASC, `title` ASC, `playtime_seconds` ASC, `remix` ASC';
	$result = safe_mysql_query($SQLquery);
	$uniquetitles = 0;
	$uniquefiles  = 0;

	if (!empty($_REQUEST['m3u'])) {

		header('Content-type: audio/x-mpegurl');
		echo '#EXTM3U'."\n";
		while (($row = mysql_fetch_array($result)) && ($row['num'] > 1)) {
			$SQLquery  = 'SELECT `filename`';
			$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
			$SQLquery .= ' WHERE (`artist` = "'.mysql_escape_string($row['artist']).'")';
			$SQLquery .= ' AND (`title` = "'.mysql_escape_string($row['title']).'")';
			if (@$_REQUEST['samemix']) {
				$SQLquery .= ' AND (`remix` = "'.mysql_escape_string($row['remix']).'")';
			}
			$SQLquery .= ' ORDER BY `playtime_seconds` ASC, `remix` ASC, `filename` ASC';
			$result2 = safe_mysql_query($SQLquery);
			while ($row2 = mysql_fetch_array($result2)) {
				echo WindowsShareSlashTranslate($row2['filename'])."\n";
			}
		}
		exit;

	} else {

		echo 'Duplicated aritst + title: (<a href="'.htmlentities($_SERVER['PHP_SELF'].'?artisttitledupes=1&samemix=1').'">Identical Mix/Version only</a>)<br>';
		echo '(<a href="'.htmlentities($_SERVER['PHP_SELF'].'?artisttitledupes=1&m3u=.m3u').'">.m3u version</a>)<br>';
		echo '<table border="1" cellspacing="0" cellpadding="2">';
		echo '<tr><th colspan="3">&nbsp;</th><th>Artist</th><th>Title</th><th>Version</th><th>&nbsp;</th><th>&nbsp;</th><th>Filename</th></tr>';

		while (($row = mysql_fetch_array($result)) && ($row['num'] > 1)) {
			$uniquetitles++;
			set_time_limit(30);

			$filenames = array();
			$artists   = array();
			$titles    = array();
			$remixes   = array();
			$bitrates  = array();
			$playtimes = array();
			$SQLquery  = 'SELECT `filename`, `artist`, `title`, `remix`, `audio_bitrate`, `vbr_method`, `playtime_seconds`, `encoder_options`';
			$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
			$SQLquery .= ' WHERE (`artist` = "'.mysql_escape_string($row['artist']).'")';
			$SQLquery .= ' AND (`title` = "'.mysql_escape_string($row['title']).'")';
			$SQLquery .= ' ORDER BY `playtime_seconds` ASC, `remix` ASC, `filename` ASC';
			$result2 = safe_mysql_query($SQLquery);
			while ($row2 = mysql_fetch_array($result2)) {
				$uniquefiles++;
				$filenames[] = $row2['filename'];
				$artists[]   = $row2['artist'];
				$titles[]    = $row2['title'];
				$remixes[]   = $row2['remix'];
				if ($row2['vbr_method']) {
					$bitrates[]  = '<B'.($row2['encoder_options'] ? ' style="text-decoration: underline; cursor: help;" title="'.$row2['encoder_options'] : '').'">'.BitrateText($row2['audio_bitrate'] / 1000).'</b>';
				} else {
					$bitrates[]  = BitrateText($row2['audio_bitrate'] / 1000);
				}
				$playtimes[] = getid3_lib::PlaytimeString($row2['playtime_seconds']);
			}

			echo '<tr>';
			echo '<TD NOWRAP VALIGN="TOP">';
			foreach ($filenames as $file) {
				echo '<a href="'.htmlentities('demo.browse.php?deletefile='.urlencode($file).'&noalert=1').'" onClick="return confirm(\'Are you sure you want to delete '.addslashes($file).'? \n(this action cannot be un-done)\');" title="Permanently delete '."\n".FixTextFields($file)."\n".'" TARGET="deletedupewindow">delete</a><br>';
			}
			echo '</td>';
			echo '<TD NOWRAP VALIGN="TOP">';
			foreach ($filenames as $file) {
				echo '<a href="'.htmlentities($_SERVER['PHP_SELF'].'?m3ufilename='.urlencode($file)).'">play</a><br>';
			}
			echo '</td>';
			echo '<TD VALIGN="MIDDLE" ALIGN="CENTER" ><a href="'.htmlentities($_SERVER['PHP_SELF'].'?artisttitledupes=1&m3uartist='.urlencode($artists[0]).'&m3utitle='.urlencode($titles[0])).'">play all</a></td>';
			echo '<TD VALIGN="TOP" NOWRAP>'.implode('<br>', $artists).'</td>';
			echo '<TD VALIGN="TOP" NOWRAP>'.implode('<br>', $titles).'</td>';
			echo '<TD VALIGN="TOP" NOWRAP>'.implode('<br>', $remixes).'</td>';
			echo '<TD VALIGN="TOP" NOWRAP ALIGN="RIGHT">'.implode('<br>', $bitrates).'</td>';
			echo '<TD VALIGN="TOP" NOWRAP ALIGN="RIGHT">'.implode('<br>', $playtimes).'</td>';

			echo '<TD VALIGN="TOP" NOWRAP ALIGN="LEFT"><table border="0" cellspacing="0" cellpadding="0">';
			foreach ($filenames as $file) {
				echo '<tr><TD NOWRAP ALIGN="RIGHT"><a href="'.htmlentities('demo.browse.php?filename='.rawurlencode($file)).'"><span style="color: #339966;">'.dirname($file).'/</span>'.basename($file).'</a></td></tr>';
			}
			echo '</table></td>';

			echo '</tr>';
		}

	}
	echo '</table>';
	echo number_format($uniquefiles).' files with '.number_format($uniquetitles).' unique <i>aritst + title</i><br>';
	echo '<hr>';

} elseif (!empty($_REQUEST['filetypelist'])) {

	list($fileformat, $audioformat) = explode('|', $_REQUEST['filetypelist']);
	$SQLquery  = 'SELECT `filename`, `fileformat`, `audio_dataformat`';
	$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
	$SQLquery .= ' WHERE (`fileformat` = "'.mysql_escape_string($fileformat).'")';
	$SQLquery .= ' AND (`audio_dataformat` = "'.mysql_escape_string($audioformat).'")';
	$SQLquery .= ' ORDER BY `filename` ASC';
	$result = safe_mysql_query($SQLquery);
	echo 'Files of format <b>'.$fileformat.'.'.$audioformat.'</b>:<table border="1" cellspacing="0" cellpadding="4">';
	echo '<tr><th>file</th><th>audio</th><th>filename</th></tr>';
	while ($row = mysql_fetch_array($result)) {
		echo '<tr>';
		echo '<td>'.$row['fileformat'].'</td>';
		echo '<td>'.$row['audio_dataformat'].'</td>';
		echo '<td><a href="'.htmlentities('demo.browse.php?filename='.rawurlencode($row['filename'])).'">'.FixTextFields($row['filename']).'</a></td>';
		echo '</tr>';
	}
	echo '</table><hr>';

} elseif (!empty($_REQUEST['trackinalbum'])) {

	$SQLquery  = 'SELECT `filename`, `album`';
	$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
	$SQLquery .= ' WHERE (`album` LIKE "% [%")';
	$SQLquery .= ' ORDER BY `album` ASC, `filename` ASC';
	$result = safe_mysql_query($SQLquery);
	if (!empty($_REQUEST['m3u'])) {

		header('Content-type: audio/x-mpegurl');
		echo '#EXTM3U'."\n";
		while ($row = mysql_fetch_array($result)) {
			echo WindowsShareSlashTranslate($row['filename'])."\n";
		}
		exit;

	} elseif (!empty($_REQUEST['autofix'])) {

		getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.tag.id3v1.php', __FILE__, true);
		getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.tag.id3v2.php', __FILE__, true);

		while ($row = mysql_fetch_array($result)) {
			set_time_limit(30);
			$ThisFileInfo = $getID3->analyze($filename);
			getid3_lib::CopyTagsToComments($ThisFileInfo);

			if (!empty($ThisFileInfo['tags'])) {

				$Album = trim(str_replace(strstr($ThisFileInfo['comments']['album'][0], ' ['), '', $ThisFileInfo['comments']['album'][0]));
				$Track = (string) intval(str_replace(' [', '', str_replace(']', '', strstr($ThisFileInfo['comments']['album'][0], ' ['))));
				if ($Track == '0') {
					$Track = '';
				}
				if ($Album && $Track) {
					echo '<hr>'.FixTextFields($row['filename']).'<br>';
					echo '<i>'.$Album.'</i> (track #'.$Track.')<br>';
					echo '<b>ID3v2:</b> '.(RemoveID3v2($row['filename'], false) ? 'removed' : 'REMOVAL FAILED!').', ';
					echo '<b>ID3v1:</b> '.(WriteID3v1($row['filename'], @$ThisFileInfo['comments']['title'][0], @$ThisFileInfo['comments']['artist'][0], $Album, @$ThisFileInfo['comments']['year'][0], @$ThisFileInfo['comments']['comment'][0], @$ThisFileInfo['comments']['genreid'][0], $Track, false) ? 'updated' : 'UPDATE FAILED').'<br>';
				} else {
					echo ' . ';
				}

			} else {

				echo '<hr>FAILED<br>'.FixTextFields($row['filename']).'<hr>';

			}
			flush();
		}

	} else {

		echo '<b>'.number_format(mysql_num_rows($result)).'</b> files with <b>[??]</b>-format track numbers in album field:<br>';
		if (mysql_num_rows($result) > 0) {
			echo '(<a href="'.htmlentities($_SERVER['PHP_SELF'].'?trackinalbum=1&m3u=.m3u').'">.m3u version</a>)<br>';
			echo '<a href="'.htmlentities($_SERVER['PHP_SELF'].'?trackinalbum=1&autofix=1').'">Try to auto-fix</a><br>';
			echo '<table border="1" cellspacing="0" cellpadding="4">';
			while ($row = mysql_fetch_array($result)) {
				echo '<tr>';
				echo '<td>'.$row['album'].'</td>';
				echo '<td><a href="'.htmlentities('demo.browse.php?filename='.rawurlencode($row['filename'])).'">'.FixTextFields($row['filename']).'</a></td>';
				echo '</tr>';
			}
			echo '</table>';
		}
		echo '<hr>';

	}

} elseif (!empty($_REQUEST['fileextensions'])) {

	$SQLquery  = 'SELECT `filename`, `fileformat`, `audio_dataformat`, `video_dataformat`, `tags`';
	$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
	$SQLquery .= ' ORDER BY `filename` ASC';
	$result = safe_mysql_query($SQLquery);
	$invalidextensionfiles = 0;
	$invalidextensionline  = '<table border="1" cellspacing="0" cellpadding="4">';
	$invalidextensionline .= '<tr><th>file</th><th>audio</th><th>video</th><th>tags</th><th>actual</th><th>correct</th><th>filename</th></tr>';
	while ($row = mysql_fetch_array($result)) {
		set_time_limit(30);

		$acceptableextensions = AcceptableExtensions($row['fileformat'], $row['audio_dataformat'], $row['video_dataformat']);
		$actualextension      = strtolower(fileextension($row['filename']));
		if ($acceptableextensions && !in_array($actualextension, $acceptableextensions)) {
			$invalidextensionfiles++;

			$invalidextensionline .= '<tr>';
			$invalidextensionline .= '<td>'.$row['fileformat'].'</td>';
			$invalidextensionline .= '<td>'.$row['audio_dataformat'].'</td>';
			$invalidextensionline .= '<td>'.$row['video_dataformat'].'</td>';
			$invalidextensionline .= '<td>'.$row['tags'].'</td>';
			$invalidextensionline .= '<td>'.$actualextension.'</td>';
			$invalidextensionline .= '<td>'.implode('; ', $acceptableextensions).'</td>';
			$invalidextensionline .= '<td><a href="'.htmlentities('demo.browse.php?filename='.rawurlencode($row['filename'])).'">'.FixTextFields($row['filename']).'</a></td>';
			$invalidextensionline .= '</tr>';
		}
	}
	$invalidextensionline .= '</table><hr>';
	echo number_format($invalidextensionfiles).' files with incorrect filename extension:<br>';
	echo $invalidextensionline;

} elseif (isset($_REQUEST['genredistribution'])) {

	if (!empty($_REQUEST['m3u'])) {

		header('Content-type: audio/x-mpegurl');
		echo '#EXTM3U'."\n";
		$SQLquery  = 'SELECT `filename`';
		$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
		$SQLquery .= ' WHERE (BINARY `genre` = "'.$_REQUEST['genredistribution'].'")';
		$SQLquery .= ' AND (`fileformat` NOT LIKE "'.implode('") AND (`fileformat` NOT LIKE "', $IgnoreNoTagFormats).'")';
		$SQLquery .= ' ORDER BY `filename` ASC';
		$result = safe_mysql_query($SQLquery);
		while ($row = mysql_fetch_array($result)) {
			echo WindowsShareSlashTranslate($row['filename'])."\n";
		}
		exit;

	} else {

		if ($_REQUEST['genredistribution'] == '%') {

			$SQLquery  = 'SELECT COUNT(*) AS `num`, `genre`';
			$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
			$SQLquery .= ' WHERE (`fileformat` NOT LIKE "'.implode('") AND (`fileformat` NOT LIKE "', $IgnoreNoTagFormats).'")';
			$SQLquery .= ' GROUP BY `genre`';
			$SQLquery .= ' ORDER BY `num` DESC';
			$result = safe_mysql_query($SQLquery);
			getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.tag.id3v1.php', __FILE__, true);
			echo '<table border="1" cellspacing="0" cellpadding="4">';
			echo '<tr><th>Count</th><th>Genre</th><th>m3u</th></tr>';
			while ($row = mysql_fetch_array($result)) {
				$GenreID = getid3_id3v1::LookupGenreID($row['genre']);
				if (is_numeric($GenreID)) {
					echo '<tr bgcolor="#00FF00;">';
				} else {
					echo '<tr bgcolor="#FF9999;">';
				}
				echo '<td><a href="'.htmlentities($_SERVER['PHP_SELF'].'?genredistribution='.urlencode($row['genre'])).'">'.number_format($row['num']).'</a></td>';
				echo '<td nowrap>'.str_replace("\t", '<br>', $row['genre']).'</td>';
				echo '<td><a href="'.htmlentities($_SERVER['PHP_SELF'].'?m3u=.m3u&genredistribution='.urlencode($row['genre'])).'">.m3u</a></td>';
				echo '</tr>';
			}
			echo '</table><hr>';

		} else {

			$SQLquery  = 'SELECT `filename`, `genre`';
			$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
			$SQLquery .= ' WHERE (`genre` LIKE "'.mysql_escape_string($_REQUEST['genredistribution']).'")';
			$SQLquery .= ' ORDER BY `filename` ASC';
			$result = safe_mysql_query($SQLquery);
			echo '<a href="'.htmlentities($_SERVER['PHP_SELF'].'?genredistribution='.urlencode('%')).'">All Genres</a><br>';
			echo '<table border="1" cellspacing="0" cellpadding="4">';
			echo '<tr><th>Genre</th><th>m3u</th><th>Filename</th></tr>';
			while ($row = mysql_fetch_array($result)) {
				echo '<tr>';
				echo '<TD NOWRAP>'.str_replace("\t", '<br>', $row['genre']).'</td>';
				echo '<td><a href="'.htmlentities($_SERVER['PHP_SELF'].'?m3ufilename='.urlencode($row['filename'])).'">m3u</a></td>';
				echo '<td><a href="'.htmlentities('demo.browse.php?filename='.rawurlencode($row['filename'])).'">'.FixTextFields($row['filename']).'</a></td>';
				echo '</tr>';
			}
			echo '</table><hr>';

		}


	}

} elseif (!empty($_REQUEST['formatdistribution'])) {

	$SQLquery  = 'SELECT `fileformat`, `audio_dataformat`, COUNT(*) AS `num`';
	$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
	$SQLquery .= ' GROUP BY `fileformat`, `audio_dataformat`';
	$SQLquery .= ' ORDER BY `num` DESC';
	$result = safe_mysql_query($SQLquery);
	echo 'File format distribution:<table border="1" cellspacing="0" cellpadding="4">';
	echo '<tr><th>Number</th><th>Format</th></tr>';
	while ($row = mysql_fetch_array($result)) {
		echo '<tr>';
		echo '<TD ALIGN="RIGHT">'.number_format($row['num']).'</td>';
		echo '<td><a href="'.htmlentities($_SERVER['PHP_SELF'].'?filetypelist='.$row['fileformat'].'|'.$row['audio_dataformat']).'">'.($row['fileformat'] ? $row['fileformat'] : '<i>unknown</i>').(($row['audio_dataformat'] && ($row['audio_dataformat'] != $row['fileformat'])) ? '.'.$row['audio_dataformat'] : '').'</a></td>';
		echo '</tr>';
	}
	echo '</table><hr>';

} elseif (!empty($_REQUEST['errorswarnings'])) {

	$SQLquery  = 'SELECT `filename`, `error`, `warning`';
	$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
	$SQLquery .= ' WHERE (`error` <> "")';
	$SQLquery .= ' OR (`warning` <> "")';
	$SQLquery .= ' ORDER BY `filename` ASC';
	$result = safe_mysql_query($SQLquery);

	if (!empty($_REQUEST['m3u'])) {

		header('Content-type: audio/x-mpegurl');
		echo '#EXTM3U'."\n";
		while ($row = mysql_fetch_array($result)) {
			echo WindowsShareSlashTranslate($row['filename'])."\n";
		}
		exit;

	} else {

		echo number_format(mysql_num_rows($result)).' files with errors or warnings:<br>';
		echo '(<a href="'.htmlentities($_SERVER['PHP_SELF'].'?errorswarnings=1&m3u=.m3u').'">.m3u version</a>)<br>';
		echo '<table border="1" cellspacing="0" cellpadding="4">';
		echo '<tr><th>Filename</th><th>Error</th><th>Warning</th></tr>';
		while ($row = mysql_fetch_array($result)) {
			echo '<tr>';
			echo '<td><a href="'.htmlentities('demo.browse.php?filename='.rawurlencode($row['filename'])).'">'.FixTextFields($row['filename']).'</a></td>';
			echo '<td>'.(!empty($row['error'])   ? '<li>'.str_replace("\t", '<li>', FixTextFields($row['error'])).'</li>' : '&nbsp;').'</td>';
			echo '<td>'.(!empty($row['warning']) ? '<li>'.str_replace("\t", '<li>', FixTextFields($row['warning'])).'</li>' : '&nbsp;').'</td>';
			echo '</tr>';
		}
	}
	echo '</table><hr>';

} elseif (!empty($_REQUEST['fixid3v1padding'])) {

	getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'write.id3v1.php', __FILE__, true);
	$id3v1_writer = new getid3_write_id3v1;

	$SQLquery  = 'SELECT `filename`, `error`, `warning`';
	$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
	$SQLquery .= ' WHERE (`fileformat` = "mp3")';
	$SQLquery .= ' AND (`warning` <> "")';
	$SQLquery .= ' ORDER BY `filename` ASC';
	$result = safe_mysql_query($SQLquery);
	$totaltofix = mysql_num_rows($result);
	$rowcounter = 0;
	while ($row = mysql_fetch_array($result)) {
		set_time_limit(30);
		if (strpos($row['warning'], 'Some ID3v1 fields do not use NULL characters for padding') !== false) {
			set_time_limit(30);
			$id3v1_writer->filename = $row['filename'];
			echo ($id3v1_writer->FixID3v1Padding() ? '<span style="color: #009900;">fixed - ' : '<span style="color: #FF0000;">error - ');
		} else {
			echo '<span style="color: #0000FF;">No error? - ';
		}
		echo '['.++$rowcounter.' / '.$totaltofix.'] ';
		echo FixTextFields($row['filename']).'</span><br>';
		flush();
	}

} elseif (!empty($_REQUEST['vbrmethod'])) {

	if ($_REQUEST['vbrmethod'] == '1') {

		$SQLquery  = 'SELECT COUNT(*) AS `num`, `vbr_method`';
		$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
		$SQLquery .= ' GROUP BY `vbr_method`';
		$SQLquery .= ' ORDER BY `vbr_method`';
		$result = safe_mysql_query($SQLquery);
		echo 'VBR methods:<table border="1" cellspacing="0" cellpadding="4">';
		echo '<tr><th>Count</th><th>VBR Method</th></tr>';
		while ($row = mysql_fetch_array($result)) {
			echo '<tr>';
			echo '<TD ALIGN="RIGHT">'.FixTextFields(number_format($row['num'])).'</td>';
			if ($row['vbr_method']) {
				echo '<td><a href="'.htmlentities($_SERVER['PHP_SELF'].'?vbrmethod='.$row['vbr_method']).'">'.FixTextFields($row['vbr_method']).'</a></td>';
			} else {
				echo '<td><i>CBR</i></td>';
			}
			echo '</tr>';
		}
		echo '</table>';

	} else {

		$SQLquery  = 'SELECT `filename`';
		$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
		$SQLquery .= ' WHERE (`vbr_method` = "'.mysql_escape_string($_REQUEST['vbrmethod']).'")';
		$result = safe_mysql_query($SQLquery);
		echo number_format(mysql_num_rows($result)).' files with VBR_method of "'.$_REQUEST['vbrmethod'].'":<table border="1" cellspacing="0" cellpadding="3">';
		while ($row = mysql_fetch_array($result)) {
			echo '<tr><td><a href="'.htmlentities($_SERVER['PHP_SELF'].'?m3ufilename='.urlencode($row['filename'])).'">m3u</a></td>';
			echo '<td><a href="'.htmlentities('demo.browse.php?filename='.rawurlencode($row['filename'])).'">'.FixTextFields($row['filename']).'</a></td></tr>';
		}
		echo '</table>';

	}
	echo '<hr>';

} elseif (!empty($_REQUEST['correctcase'])) {

	$SQLquery  = 'SELECT `filename`, `fileformat`';
	$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
	$SQLquery .= ' WHERE (`fileformat` <> "")';
	$SQLquery .= ' ORDER BY `filename` ASC';
	$result = safe_mysql_query($SQLquery);
	echo 'Copy and paste the following into a DOS batch file. You may have to run this script more than once to catch all the changes (remember to scan for deleted/changed files and rescan directory between scans)<hr>';
	echo '<PRE>';
	$lastdir = '';
	while ($row = mysql_fetch_array($result)) {
		set_time_limit(30);
		$CleanedFilename = CleanUpFileName($row['filename']);
		if ($row['filename'] != $CleanedFilename) {
			if (strtolower($lastdir) != strtolower(str_replace('/', '\\', dirname($row['filename'])))) {
				$lastdir = str_replace('/', '\\', dirname($row['filename']));
				echo 'cd "'.$lastdir.'"'."\n";
			}
			echo 'ren "'.basename($row['filename']).'" "'.basename(CleanUpFileName($row['filename'])).'"'."\n";
		}
	}
	echo '</PRE>';
	echo '<hr>';

}

function CleanUpFileName($filename) {
	$DirectoryName = dirname($filename);
	$FileExtension = fileextension(basename($filename));
	$BaseFilename  = basename($filename, '.'.$FileExtension);

	$BaseFilename = strtolower($BaseFilename);
	$BaseFilename = str_replace('_', ' ', $BaseFilename);
	//$BaseFilename = str_replace('-', ' - ', $BaseFilename);
	$BaseFilename = str_replace('(', ' (', $BaseFilename);
	$BaseFilename = str_replace('( ', '(', $BaseFilename);
	$BaseFilename = str_replace(')', ') ', $BaseFilename);
	$BaseFilename = str_replace(' )', ')', $BaseFilename);
	$BaseFilename = str_replace(' \'\'', ' “', $BaseFilename);
	$BaseFilename = str_replace('\'\' ', '” ', $BaseFilename);
	$BaseFilename = str_replace(' vs ', ' vs. ', $BaseFilename);
	while (strstr($BaseFilename, '  ') !== false) {
		$BaseFilename = str_replace('  ', ' ', $BaseFilename);
	}
	$BaseFilename = trim($BaseFilename);

	return $DirectoryName.'/'.BetterUCwords($BaseFilename).'.'.strtolower($FileExtension);
}

function BetterUCwords($string) {
	$stringlength = strlen($string);

	$string{0} = strtoupper($string{0});
	for ($i = 1; $i < $stringlength; $i++) {
		if (($string{$i - 1} == '\'') && ($i > 1) && (($string{$i - 2} == 'O') || ($string{$i - 2} == ' '))) {
			// O'Clock, 'Em
			$string{$i} = strtoupper($string{$i});
		} elseif (ereg('^[\'A-Za-z0-9À-ÿ]$', $string{$i - 1})) {
			$string{$i} = strtolower($string{$i});
		} else {
			$string{$i} = strtoupper($string{$i});
		}
	}

	static $LowerCaseWords = array('vs.', 'feat.');
	static $UpperCaseWords = array('DJ', 'USA', 'II', 'MC', 'CD', 'TV', '\'N\'');

	$OutputListOfWords = array();
	$ListOfWords = explode(' ', $string);
	foreach ($ListOfWords as $ThisWord) {
		if (in_array(strtolower(str_replace('(', '', $ThisWord)), $LowerCaseWords)) {
			$ThisWord = strtolower($ThisWord);
		} elseif (in_array(strtoupper(str_replace('(', '', $ThisWord)), $UpperCaseWords)) {
			$ThisWord = strtoupper($ThisWord);
		} elseif ((substr($ThisWord, 0, 2) == 'Mc') && (strlen($ThisWord) > 2)) {
			$ThisWord{2} = strtoupper($ThisWord{2});
		} elseif ((substr($ThisWord, 0, 3) == 'Mac') && (strlen($ThisWord) > 3)) {
			$ThisWord{3} = strtoupper($ThisWord{3});
		}
		$OutputListOfWords[] = $ThisWord;
	}
	$UCstring = implode(' ', $OutputListOfWords);
	$UCstring = str_replace(' From “', ' from “', $UCstring);
	$UCstring = str_replace(' \'n\' ', ' \'N\' ', $UCstring);

	return $UCstring;
}



echo '<hr><form action="'.FixTextFields($_SERVER['PHP_SELF']).'">';
echo '<b>Warning:</b> Scanning a new directory will erase all previous entries in the database!<br>';
echo 'Directory: <input type="text" name="scan" size="50" value="'.FixTextFields(!empty($_REQUEST['scan']) ? $_REQUEST['scan'] : '').'"> ';
echo '<input type="submit" value="Go" onClick="return confirm(\'Are you sure you want to erase all entries in the database and start scanning again?\');">';
echo '</form>';
echo '<hr><form action="'.FixTextFields($_SERVER['PHP_SELF']).'">';
echo 'Re-scanning a new directory will only add new, previously unscanned files into the list (and not erase the database).<br>';
echo 'Directory: <input type="text" name="newscan" size="50" value="'.FixTextFields(!empty($_REQUEST['newscan']) ? $_REQUEST['newscan'] : '').'"> ';
echo '<input type="SUBMIT" value="Go">';
echo '</form><hr>';
echo '<ul>';
echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?deadfilescheck=1').'">Remove deleted or changed files from database</a></li>';
echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?md5datadupes=1').'">List files with identical MD5_DATA values</a></li>';
echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?artisttitledupes=1').'">List files with identical artist + title</a> (<a href="'.$_SERVER['PHP_SELF'].'?artisttitledupes=1&samemix=1">same mix only</a>)</li>';
echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?fileextensions=1').'">File with incorrect file extension</a></li>';
echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?formatdistribution=1').'">File Format Distribution</a></li>';
echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?audiobitrates=1').'">Audio Bitrate Distribution</a></li>';
echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?vbrmethod=1').'">VBR_Method Distribution</a></li>';
echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?tagtypes=1').'">Tag Type Distribution</a></li>';
echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?genredistribution='.urlencode('%')).'">Genre Distribution</a></li>';
//echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?missingtrackvolume=1').'">Scan for missing track volume information (update database from pre-v1.7.0b5)</a></li>';
echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?encoderoptionsdistribution=1').'">Encoder Options Distribution</a></li>';
echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?encodedbydistribution='.urlencode('%')).'">Encoded By (ID3v2) Distribution</a></li>';
echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?trackinalbum=1').'">Track number in Album field</a></li>';
echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?tracknoalbum=1').'">Track number, but no Album</a></li>';
echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?titlefeat=1').'">"feat." in Title field</a></li>';
echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?emptygenres=1').'">Blank genres</a></li>';
echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?trackzero=1').'">Track "zero"</a></li>';
echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?nonemptycomments=1').'">non-empty comments</a></li>';
echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?unsynchronizedtags=2A1').'">Tags that are not synchronized</a> (<a href="'.$_SERVER['PHP_SELF'].'?unsynchronizedtags=2A1&autofix=1">autofix</a>)</li>';
echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?filenamepattern='.urlencode('[N] A - T {R}')).'">Filenames that don\'t match pattern</a> (<a href="?filenamepattern='.urlencode('[N] A - T {R}').'&autofix=1">auto-fix</a>)</li>';
//echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?filenamepattern='.urlencode('A - T')).'">Filenames that don\'t match pattern</a></li>';
echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?correctcase=1').'">Correct filename case (Win/DOS)</a></li>';
echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?fixid3v1padding=1').'">Fix ID3v1 invalid padding</a></li>';
echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?errorswarnings=1').'">Files with Errors and/or Warnings</a></li>';
echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?rescanerrors=1').'">Re-scan only files with Errors and/or Warnings</a></li>';
echo '</ul>';

$SQLquery  = 'SELECT COUNT(*) AS `TotalFiles`, SUM(`playtime_seconds`) AS `TotalPlaytime`, SUM(`filesize`) AS `TotalFilesize`, AVG(`playtime_seconds`) AS `AvgPlaytime`, AVG(`filesize`) AS `AvgFilesize`, AVG(`audio_bitrate` + `video_bitrate`) AS `AvgBitrate`';
$SQLquery .= ' FROM `'.GETID3_DB_TABLE.'`';
$result = mysql_query($SQLquery);
if ($row = mysql_fetch_array($result)) {
	echo '<hr><b>Currently in the database:</b><TABLE>';
	echo '<tr><TH ALIGN="LEFT">Total Files</th><td>'.number_format($row['TotalFiles']).'</td></tr>';
	echo '<tr><TH ALIGN="LEFT">Total Filesize</th><td>'.number_format($row['TotalFilesize'] / 1048576).' MB</td></tr>';
	echo '<tr><TH ALIGN="LEFT">Total Playtime</th><td>'.number_format($row['TotalPlaytime'] / 3600, 1).' hours</td></tr>';
	echo '<tr><TH ALIGN="LEFT">Average Filesize</th><td>'.number_format($row['AvgFilesize'] / 1048576, 1).' MB</td></tr>';
	echo '<tr><TH ALIGN="LEFT">Average Playtime</th><td>'.getid3_lib::PlaytimeString($row['AvgPlaytime']).'</td></tr>';
	echo '<tr><TH ALIGN="LEFT">Average Bitrate</th><td>'.BitrateText($row['AvgBitrate'] / 1000, 1).'</td></tr>';
	echo '</table>';
}

?>
</BODY>
</HTML>