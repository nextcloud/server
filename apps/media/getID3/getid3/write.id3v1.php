<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// write.id3v1.php                                             //
// module for writing ID3v1 tags                               //
// dependencies: module.tag.id3v1.php                          //
//                                                            ///
/////////////////////////////////////////////////////////////////

getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.tag.id3v1.php', __FILE__, true);

class getid3_write_id3v1
{
	var $filename;
	var $tag_data;
	var $warnings = array(); // any non-critical errors will be stored here
	var $errors   = array(); // any critical errors will be stored here

	function getid3_write_id3v1() {
		return true;
	}

	function WriteID3v1() {
		if ((filesize($this->filename) >= (pow(2, 31) - 128)) || (filesize($this->filename) < 0)) {
			$this->errors[] = 'Unable to write ID3v1 because file is larger than 2GB';
			return false;
		}

		// File MUST be writeable - CHMOD(646) at least
		if (is_writeable($this->filename)) {
			if ($fp_source = @fopen($this->filename, 'r+b')) {

				fseek($fp_source, -128, SEEK_END);
				if (fread($fp_source, 3) == 'TAG') {
					fseek($fp_source, -128, SEEK_END); // overwrite existing ID3v1 tag
				} else {
					fseek($fp_source, 0, SEEK_END);    // append new ID3v1 tag
				}
				$this->tag_data['track'] = (isset($this->tag_data['track']) ? $this->tag_data['track'] : (isset($this->tag_data['track_number']) ? $this->tag_data['track_number'] : (isset($this->tag_data['tracknumber']) ? $this->tag_data['tracknumber'] : '')));

				$new_id3v1_tag_data = getid3_id3v1::GenerateID3v1Tag(
														@$this->tag_data['title'],
														@$this->tag_data['artist'],
														@$this->tag_data['album'],
														@$this->tag_data['year'],
														@$this->tag_data['genreid'],
														@$this->tag_data['comment'],
														@$this->tag_data['track']);
				fwrite($fp_source, $new_id3v1_tag_data, 128);
				fclose($fp_source);
				return true;

			} else {
				$this->errors[] = 'Could not open '.$this->filename.' mode "r+b"';
				return false;
			}
		}
		$this->errors[] = 'File is not writeable: '.$this->filename;
		return false;
	}

	function FixID3v1Padding() {
		// ID3v1 data is supposed to be padded with NULL characters, but some taggers incorrectly use spaces
		// This function rewrites the ID3v1 tag with correct padding

		// Initialize getID3 engine
		$getID3 = new getID3;
		$ThisFileInfo = $getID3->analyze($this->filename);
		if ($ThisFileInfo['filesize'] >= (pow(2, 31) - 128)) {
			// cannot write tags on files > 2GB
			return false;
		}
		if (isset($ThisFileInfo['tags']['id3v1'])) {
			foreach ($ThisFileInfo['tags']['id3v1'] as $key => $value) {
				$id3v1data[$key] = implode(',', $value);
			}
			$this->tag_data = $id3v1data;
			return $this->WriteID3v1();
		}
		return false;
	}

	function RemoveID3v1() {
		if ($ThisFileInfo['filesize'] >= pow(2, 31)) {
			$this->errors[] = 'Unable to write ID3v1 because file is larger than 2GB';
			return false;
		}

		// File MUST be writeable - CHMOD(646) at least
		if (is_writeable($this->filename)) {
			if ($fp_source = @fopen($this->filename, 'r+b')) {

				fseek($fp_source, -128, SEEK_END);
				if (fread($fp_source, 3) == 'TAG') {
					ftruncate($fp_source, filesize($this->filename) - 128);
				} else {
					// no ID3v1 tag to begin with - do nothing
				}
				fclose($fp_source);
				return true;

			} else {
				$this->errors[] = 'Could not open '.$this->filename.' mode "r+b"';
			}
		} else {
			$this->errors[] = $this->filename.' is not writeable';
		}
		return false;
	}

}

?>