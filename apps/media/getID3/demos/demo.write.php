<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
//                                                             //
// /demo/demo.write.php - part of getID3()                     //
// sample script for demonstrating writing ID3v1 and ID3v2     //
// tags for MP3, or Ogg comment tags for Ogg Vorbis            //
// See readme.txt for more details                             //
//                                                            ///
/////////////////////////////////////////////////////////////////


die('Due to a security issue, this demo has been disabled. It can be enabled by removing line 16 in demos/demo.write.php');


$TaggingFormat = 'UTF-8';

header('Content-Type: text/html; charset='.$TaggingFormat);
echo '<HTML><HEAD><TITLE>getID3() - Sample tag writer</TITLE></HEAD><BODY>';

require_once('../getid3/getid3.php');
// Initialize getID3 engine
$getID3 = new getID3;
$getID3->setOption(array('encoding'=>$TaggingFormat));

getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'write.php', __FILE__, true);

$browsescriptfilename = 'demo.browse.php';

function FixTextFields($text) {
	return htmlentities(getid3_lib::SafeStripSlashes($text), ENT_QUOTES);
}

$Filename = (isset($_REQUEST['Filename']) ? getid3_lib::SafeStripSlashes($_REQUEST['Filename']) : '');



if (isset($_POST['WriteTags'])) {

	$TagFormatsToWrite = (isset($_POST['TagFormatsToWrite']) ? $_POST['TagFormatsToWrite'] : array());
	if (!empty($TagFormatsToWrite)) {
		echo 'starting to write tag(s)<BR>';

		$tagwriter = new getid3_writetags;
		$tagwriter->filename       = $Filename;
		$tagwriter->tagformats     = $TagFormatsToWrite;
		$tagwriter->overwrite_tags = true;
		$tagwriter->tag_encoding   = $TaggingFormat;
		if (!empty($_POST['remove_other_tags'])) {
			$tagwriter->remove_other_tags = true;
		}

		$commonkeysarray = array('Title', 'Artist', 'Album', 'Year', 'Comment');
		foreach ($commonkeysarray as $key) {
			if (!empty($_POST[$key])) {
				$TagData[strtolower($key)][] = getid3_lib::SafeStripSlashes($_POST[$key]);
			}
		}
		if (!empty($_POST['Genre'])) {
			$TagData['genre'][] = getid3_lib::SafeStripSlashes($_POST['Genre']);
		}
		if (!empty($_POST['GenreOther'])) {
			$TagData['genre'][] = getid3_lib::SafeStripSlashes($_POST['GenreOther']);
		}
		if (!empty($_POST['Track'])) {
			$TagData['track'][] = getid3_lib::SafeStripSlashes($_POST['Track'].(!empty($_POST['TracksTotal']) ? '/'.$_POST['TracksTotal'] : ''));
		}

		if (!empty($_FILES['userfile']['tmp_name'])) {
			if (in_array('id3v2.4', $tagwriter->tagformats) || in_array('id3v2.3', $tagwriter->tagformats) || in_array('id3v2.2', $tagwriter->tagformats)) {
				if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
					if ($fd = @fopen($_FILES['userfile']['tmp_name'], 'rb')) {
						$APICdata = fread($fd, filesize($_FILES['userfile']['tmp_name']));
						fclose ($fd);

						list($APIC_width, $APIC_height, $APIC_imageTypeID) = GetImageSize($_FILES['userfile']['tmp_name']);
						$imagetypes = array(1=>'gif', 2=>'jpeg', 3=>'png');
						if (isset($imagetypes[$APIC_imageTypeID])) {

							$TagData['attached_picture'][0]['data']          = $APICdata;
							$TagData['attached_picture'][0]['picturetypeid'] = $_POST['APICpictureType'];
							$TagData['attached_picture'][0]['description']   = $_FILES['userfile']['name'];
							$TagData['attached_picture'][0]['mime']          = 'image/'.$imagetypes[$APIC_imageTypeID];

						} else {
							echo '<B>invalid image format (only GIF, JPEG, PNG)</B><BR>';
						}
					} else {
						echo '<B>cannot open '.$_FILES['userfile']['tmp_name'].'</B><BR>';
					}
				} else {
					echo '<B>!is_uploaded_file('.$_FILES['userfile']['tmp_name'].')</B><BR>';
				}
			} else {
				echo '<B>WARNING:</B> Can only embed images for ID3v2<BR>';
			}
		}

		$tagwriter->tag_data = $TagData;
		if ($tagwriter->WriteTags()) {
			echo 'Successfully wrote tags<BR>';
			if (!empty($tagwriter->warnings)) {
				echo 'There were some warnings:<BLOCKQUOTE STYLE="background-color:#FFCC33; padding: 10px;">'.implode('<BR><BR>', $tagwriter->warnings).'</BLOCKQUOTE>';
			}
		} else {
			echo 'Failed to write tags!<BLOCKQUOTE STYLE="background-color:#FF9999; padding: 10px;">'.implode('<BR><BR>', $tagwriter->errors).'</BLOCKQUOTE>';
		}

	} else {

		echo 'WARNING: no tag formats selected for writing - nothing written';

	}
	echo '<HR>';

}


echo '<H4>Sample tag editor/writer</H4>';
echo '<A HREF="'.$browsescriptfilename.'?listdirectory='.rawurlencode(realpath(dirname($Filename))).'">Browse current directory</A><BR>';
if (!empty($Filename)) {
	echo '<A HREF="'.$_SERVER['PHP_SELF'].'">Start Over</A><BR><BR>';
	echo '<TABLE BORDER="3" CELLSPACING="0" CELLPADDING="4"><FORM ACTION="'.$_SERVER['PHP_SELF'].'" METHOD="POST" ENCTYPE="multipart/form-data">';
	echo '<TR><TD ALIGN="RIGHT"><B>Filename: </B></TD><TD><INPUT TYPE="HIDDEN" NAME="Filename" VALUE="'.FixTextFields($Filename).'"><A HREF="'.$browsescriptfilename.'?filename='.rawurlencode($Filename).'" TARGET="_blank">'.$Filename.'</A></TD></TR>';
	if (file_exists($Filename)) {

		// Initialize getID3 engine
		$getID3 = new getID3;
		$OldThisFileInfo = $getID3->analyze($Filename);
		getid3_lib::CopyTagsToComments($OldThisFileInfo);

		switch ($OldThisFileInfo['fileformat']) {
			case 'mp3':
			case 'mp2':
			case 'mp1':
				$ValidTagTypes = array('id3v1', 'id3v2.3', 'ape');
				break;

			case 'mpc':
				$ValidTagTypes = array('ape');
				break;

			case 'ogg':
				if (@$OldThisFileInfo['audio']['dataformat'] == 'flac') {
					//$ValidTagTypes = array('metaflac');
					// metaflac doesn't (yet) work with OggFLAC files
					$ValidTagTypes = array();
				} else {
					$ValidTagTypes = array('vorbiscomment');
				}
				break;

			case 'flac':
				$ValidTagTypes = array('metaflac');
				break;

			case 'real':
				$ValidTagTypes = array('real');
				break;

			default:
				$ValidTagTypes = array();
				break;
		}
		echo '<TR><TD ALIGN="RIGHT"><B>Title</B></TD> <TD><INPUT TYPE="TEXT" SIZE="40" NAME="Title"  VALUE="'.FixTextFields(@implode(', ', @$OldThisFileInfo['comments']['title'])).'"></TD></TR>';
		echo '<TR><TD ALIGN="RIGHT"><B>Artist</B></TD><TD><INPUT TYPE="TEXT" SIZE="40" NAME="Artist" VALUE="'.FixTextFields(@implode(', ', @$OldThisFileInfo['comments']['artist'])).'"></TD></TR>';
		echo '<TR><TD ALIGN="RIGHT"><B>Album</B></TD> <TD><INPUT TYPE="TEXT" SIZE="40" NAME="Album"  VALUE="'.FixTextFields(@implode(', ', @$OldThisFileInfo['comments']['album'])).'"></TD></TR>';
		echo '<TR><TD ALIGN="RIGHT"><B>Year</B></TD>  <TD><INPUT TYPE="TEXT" SIZE="4"  NAME="Year"   VALUE="'.FixTextFields(@implode(', ', @$OldThisFileInfo['comments']['year'])).'"></TD></TR>';

		$TracksTotal = '';
		$TrackNumber = '';
		if (!empty($OldThisFileInfo['comments']['tracknumber']) && is_array($OldThisFileInfo['comments']['tracknumber'])) {
			$RawTrackNumberArray = $OldThisFileInfo['comments']['tracknumber'];
		} elseif (!empty($OldThisFileInfo['comments']['track']) && is_array($OldThisFileInfo['comments']['track'])) {
			$RawTrackNumberArray = $OldThisFileInfo['comments']['track'];
		} else {
			$RawTrackNumberArray = array();
		}
		foreach ($RawTrackNumberArray as $key => $value) {
			if (strlen($value) > strlen($TrackNumber)) {
				// ID3v1 may store track as "3" but ID3v2/APE would store as "03/16"
				$TrackNumber = $value;
			}
		}
		if (strstr($TrackNumber, '/')) {
			list($TrackNumber, $TracksTotal) = explode('/', $TrackNumber);
		}
		echo '<TR><TD ALIGN="RIGHT"><B>Track</B></TD><TD><INPUT TYPE="TEXT" SIZE="2"  NAME="Track"  VALUE="'.FixTextFields($TrackNumber).'"> of <INPUT TYPE="TEXT" SIZE="2" NAME="TracksTotal"  VALUE="'.FixTextFields($TracksTotal).'"></TD></TR>';

		$ArrayOfGenresTemp = getid3_id3v1::ArrayOfGenres();   // get the array of genres
		foreach ($ArrayOfGenresTemp as $key => $value) {      // change keys to match displayed value
			$ArrayOfGenres[$value] = $value;
		}
		unset($ArrayOfGenresTemp);                            // remove temporary array
		unset($ArrayOfGenres['Cover']);                       // take off these special cases
		unset($ArrayOfGenres['Remix']);
		unset($ArrayOfGenres['Unknown']);
		$ArrayOfGenres['']      = '- Unknown -';              // Add special cases back in with renamed key/value
		$ArrayOfGenres['Cover'] = '-Cover-';
		$ArrayOfGenres['Remix'] = '-Remix-';
		asort($ArrayOfGenres);                                // sort into alphabetical order
		echo '<TR><TD ALIGN="RIGHT"><B>Genre</B></TD><TD><SELECT NAME="Genre">';
		$AllGenresArray = (!empty($OldThisFileInfo['comments']['genre']) ? $OldThisFileInfo['comments']['genre'] : array());
		foreach ($ArrayOfGenres as $key => $value) {
			echo '<OPTION VALUE="'.$key.'"';
			if (in_array($key, $AllGenresArray)) {
				echo ' SELECTED';
				unset($AllGenresArray[array_search($key, $AllGenresArray)]);
				sort($AllGenresArray);
			}
			echo '>'.$value.'</OPTION>';
			//echo '<OPTION VALUE="'.FixTextFields($value).'"'.((@$OldThisFileInfo['comments']['genre'][0] == $value) ? ' SELECTED' : '').'>'.$value.'</OPTION>';
		}
		echo '</SELECT><INPUT TYPE="TEXT" NAME="GenreOther" SIZE="10" VALUE="'.FixTextFields(@$AllGenresArray[0]).'"></TD></TR>';

		echo '<TR><TD ALIGN="RIGHT"><B>Write Tags</B></TD><TD>';
		foreach ($ValidTagTypes as $ValidTagType) {
			echo '<INPUT TYPE="CHECKBOX" NAME="TagFormatsToWrite[]" VALUE="'.$ValidTagType.'"';
			if (count($ValidTagTypes) == 1) {
				echo ' CHECKED';
			} else {
				switch ($ValidTagType) {
					case 'id3v2.2':
					case 'id3v2.3':
					case 'id3v2.4':
						if (isset($OldThisFileInfo['tags']['id3v2'])) {
							echo ' CHECKED';
						}
						break;

					default:
						if (isset($OldThisFileInfo['tags'][$ValidTagType])) {
							echo ' CHECKED';
						}
						break;
				}
			}
			echo '>'.$ValidTagType.'<BR>';
		}
		if (count($ValidTagTypes) > 1) {
			echo '<hr><input type="checkbox" name="remove_other_tags" value="1"> Remove non-selected tag formats when writing new tag<br>';
		}
		echo '</TD></TR>';

		echo '<TR><TD ALIGN="RIGHT"><B>Comment</B></TD><TD><TEXTAREA COLS="30" ROWS="3" NAME="Comment" WRAP="VIRTUAL">'.(isset($OldThisFileInfo['comments']['comment']) ? @implode("\n", $OldThisFileInfo['comments']['comment']) : '').'</TEXTAREA></TD></TR>';

		echo '<TR><TD ALIGN="RIGHT"><B>Picture</B><BR>(ID3v2 only)</TD><TD><INPUT TYPE="FILE" NAME="userfile" ACCEPT="image/jpeg, image/gif, image/png"><BR>';
		echo '<SELECT NAME="APICpictureType">';
		$APICtypes = getid3_id3v2::APICPictureTypeLookup('', true);
		foreach ($APICtypes as $key => $value) {
			echo '<OPTION VALUE="'.FixTextFields($key).'">'.FixTextFields($value).'</OPTION>';
		}
		echo '</SELECT></TD></TR>';
		echo '<TR><TD ALIGN="CENTER" COLSPAN="2"><INPUT TYPE="SUBMIT" NAME="WriteTags" VALUE="Save Changes"> ';
		echo '<INPUT TYPE="RESET" VALUE="Reset"></TD></TR>';

	} else {

		echo '<TR><TD ALIGN="RIGHT"><B>Error</B></TD><TD>'.FixTextFields($Filename).' does not exist</TD></TR>';

	}
	echo '</FORM></TABLE>';

}

?>
</BODY>
</HTML>