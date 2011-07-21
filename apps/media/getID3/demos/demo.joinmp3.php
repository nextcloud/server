<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
//                                                             //
// /demo/demo.joinmp3.php - part of getID3()                   //
// Sample script for splicing two or more MP3s together into   //
// one file. Does not attempt to fix VBR header frames.        //
// See readme.txt for more details                             //
//                                                            ///
/////////////////////////////////////////////////////////////////


// sample usage:
// $FilenameOut   = 'combined.mp3';
// $FilenamesIn[] = 'file1.mp3';
// $FilenamesIn[] = 'file2.mp3';
// $FilenamesIn[] = 'file3.mp3';
//
// if (CombineMultipleMP3sTo($FilenameOut, $FilenamesIn)) {
//     echo 'Successfully copied '.implode(' + ', $FilenamesIn).' to '.$FilenameOut;
// } else {
//     echo 'Failed to copy '.implode(' + ', $FilenamesIn).' to '.$FilenameOut;
// }

function CombineMultipleMP3sTo($FilenameOut, $FilenamesIn) {

	foreach ($FilenamesIn as $nextinputfilename) {
		if (!is_readable($nextinputfilename)) {
			echo 'Cannot read "'.$nextinputfilename.'"<BR>';
			return false;
		}
	}
	if (!is_writeable($FilenameOut)) {
		echo 'Cannot write "'.$FilenameOut.'"<BR>';
		return false;
	}

	require_once('../getid3/getid3.php');
	if ($fp_output = @fopen($FilenameOut, 'wb')) {

		// Initialize getID3 engine
		$getID3 = new getID3;
		foreach ($FilenamesIn as $nextinputfilename) {

			$CurrentFileInfo = $getID3->analyze($nextinputfilename);
			if ($CurrentFileInfo['fileformat'] == 'mp3') {

				if ($fp_source = @fopen($nextinputfilename, 'rb')) {

					$CurrentOutputPosition = ftell($fp_output);

					// copy audio data from first file
					fseek($fp_source, $CurrentFileInfo['avdataoffset'], SEEK_SET);
					while (!feof($fp_source) && (ftell($fp_source) < $CurrentFileInfo['avdataend'])) {
						fwrite($fp_output, fread($fp_source, 32768));
					}
					fclose($fp_source);

					// trim post-audio data (if any) copied from first file that we don't need or want
					$EndOfFileOffset = $CurrentOutputPosition + ($CurrentFileInfo['avdataend'] - $CurrentFileInfo['avdataoffset']);
					fseek($fp_output, $EndOfFileOffset, SEEK_SET);
					ftruncate($fp_output, $EndOfFileOffset);

				} else {

					echo 'failed to open '.$nextinputfilename.' for reading';
					fclose($fp_output);
					return false;

				}

			} else {

				echo $nextinputfilename.' is not MP3 format';
				fclose($fp_output);
				return false;

			}

		}

	} else {

		echo 'failed to open '.$FilenameOut.' for writing';
		return false;

	}

	fclose($fp_output);
	return true;
}

?>