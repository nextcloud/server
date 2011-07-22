<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.tag.apetag.php                                       //
// module for analyzing APE tags                               //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////

class getid3_apetag
{

	function getid3_apetag(&$fd, &$ThisFileInfo, $overrideendoffset=0) {

		if ($ThisFileInfo['filesize'] >= pow(2, 31)) {
			$ThisFileInfo['warning'][] = 'Unable to check for APEtags because file is larger than 2GB';
			return false;
		}

		$id3v1tagsize     = 128;
		$apetagheadersize = 32;
		$lyrics3tagsize   = 10;

		if ($overrideendoffset == 0) {

			fseek($fd, 0 - $id3v1tagsize - $apetagheadersize - $lyrics3tagsize, SEEK_END);
			$APEfooterID3v1 = fread($fd, $id3v1tagsize + $apetagheadersize + $lyrics3tagsize);

			//if (preg_match('/APETAGEX.{24}TAG.{125}$/i', $APEfooterID3v1)) {
			if (substr($APEfooterID3v1, strlen($APEfooterID3v1) - $id3v1tagsize - $apetagheadersize, 8) == 'APETAGEX') {

				// APE tag found before ID3v1
				$ThisFileInfo['ape']['tag_offset_end'] = $ThisFileInfo['filesize'] - $id3v1tagsize;

			//} elseif (preg_match('/APETAGEX.{24}$/i', $APEfooterID3v1)) {
			} elseif (substr($APEfooterID3v1, strlen($APEfooterID3v1) - $apetagheadersize, 8) == 'APETAGEX') {

				// APE tag found, no ID3v1
				$ThisFileInfo['ape']['tag_offset_end'] = $ThisFileInfo['filesize'];

			}

		} else {

			fseek($fd, $overrideendoffset - $apetagheadersize, SEEK_SET);
			if (fread($fd, 8) == 'APETAGEX') {
				$ThisFileInfo['ape']['tag_offset_end'] = $overrideendoffset;
			}

		}
		if (!isset($ThisFileInfo['ape']['tag_offset_end'])) {

			// APE tag not found
			unset($ThisFileInfo['ape']);
			return false;

		}

		// shortcut
		$thisfile_ape = &$ThisFileInfo['ape'];

		fseek($fd, $thisfile_ape['tag_offset_end'] - $apetagheadersize, SEEK_SET);
		$APEfooterData = fread($fd, 32);
		if (!($thisfile_ape['footer'] = $this->parseAPEheaderFooter($APEfooterData))) {
			$ThisFileInfo['error'][] = 'Error parsing APE footer at offset '.$thisfile_ape['tag_offset_end'];
			return false;
		}

		if (isset($thisfile_ape['footer']['flags']['header']) && $thisfile_ape['footer']['flags']['header']) {
			fseek($fd, $thisfile_ape['tag_offset_end'] - $thisfile_ape['footer']['raw']['tagsize'] - $apetagheadersize, SEEK_SET);
			$thisfile_ape['tag_offset_start'] = ftell($fd);
			$APEtagData = fread($fd, $thisfile_ape['footer']['raw']['tagsize'] + $apetagheadersize);
		} else {
			$thisfile_ape['tag_offset_start'] = $thisfile_ape['tag_offset_end'] - $thisfile_ape['footer']['raw']['tagsize'];
			fseek($fd, $thisfile_ape['tag_offset_start'], SEEK_SET);
			$APEtagData = fread($fd, $thisfile_ape['footer']['raw']['tagsize']);
		}
		$ThisFileInfo['avdataend'] = $thisfile_ape['tag_offset_start'];

		if (isset($ThisFileInfo['id3v1']['tag_offset_start']) && ($ThisFileInfo['id3v1']['tag_offset_start'] < $thisfile_ape['tag_offset_end'])) {
			$ThisFileInfo['warning'][] = 'ID3v1 tag information ignored since it appears to be a false synch in APEtag data';
			unset($ThisFileInfo['id3v1']);
			foreach ($ThisFileInfo['warning'] as $key => $value) {
				if ($value == 'Some ID3v1 fields do not use NULL characters for padding') {
					unset($ThisFileInfo['warning'][$key]);
					sort($ThisFileInfo['warning']);
					break;
				}
			}
		}

		$offset = 0;
		if (isset($thisfile_ape['footer']['flags']['header']) && $thisfile_ape['footer']['flags']['header']) {
			if ($thisfile_ape['header'] = $this->parseAPEheaderFooter(substr($APEtagData, 0, $apetagheadersize))) {
				$offset += $apetagheadersize;
			} else {
				$ThisFileInfo['error'][] = 'Error parsing APE header at offset '.$thisfile_ape['tag_offset_start'];
				return false;
			}
		}

		// shortcut
		$ThisFileInfo['replay_gain'] = array();
		$thisfile_replaygain = &$ThisFileInfo['replay_gain'];

		for ($i = 0; $i < $thisfile_ape['footer']['raw']['tag_items']; $i++) {
			$value_size = getid3_lib::LittleEndian2Int(substr($APEtagData, $offset, 4));
			$offset += 4;
			$item_flags = getid3_lib::LittleEndian2Int(substr($APEtagData, $offset, 4));
			$offset += 4;
			if (strstr(substr($APEtagData, $offset), "\x00") === false) {
				$ThisFileInfo['error'][] = 'Cannot find null-byte (0x00) seperator between ItemKey #'.$i.' and value. ItemKey starts '.$offset.' bytes into the APE tag, at file offset '.($thisfile_ape['tag_offset_start'] + $offset);
				return false;
			}
			$ItemKeyLength = strpos($APEtagData, "\x00", $offset) - $offset;
			$item_key      = strtolower(substr($APEtagData, $offset, $ItemKeyLength));

			// shortcut
			$thisfile_ape['items'][$item_key] = array();
			$thisfile_ape_items_current = &$thisfile_ape['items'][$item_key];

			$offset += ($ItemKeyLength + 1); // skip 0x00 terminator
			$thisfile_ape_items_current['data'] = substr($APEtagData, $offset, $value_size);
			$offset += $value_size;

			$thisfile_ape_items_current['flags'] = $this->parseAPEtagFlags($item_flags);
			switch ($thisfile_ape_items_current['flags']['item_contents_raw']) {
				case 0: // UTF-8
				case 3: // Locator (URL, filename, etc), UTF-8 encoded
					$thisfile_ape_items_current['data'] = explode("\x00", trim($thisfile_ape_items_current['data']));
					break;

				default: // binary data
					break;
			}

			switch (strtolower($item_key)) {
				case 'replaygain_track_gain':
					$thisfile_replaygain['track']['adjustment'] = (float) str_replace(',', '.', $thisfile_ape_items_current['data'][0]); // float casting will see "0,95" as zero!
					$thisfile_replaygain['track']['originator'] = 'unspecified';
					break;

				case 'replaygain_track_peak':
					$thisfile_replaygain['track']['peak']       = (float) str_replace(',', '.', $thisfile_ape_items_current['data'][0]); // float casting will see "0,95" as zero!
					$thisfile_replaygain['track']['originator'] = 'unspecified';
					if ($thisfile_replaygain['track']['peak'] <= 0) {
						$ThisFileInfo['warning'][] = 'ReplayGain Track peak from APEtag appears invalid: '.$thisfile_replaygain['track']['peak'].' (original value = "'.$thisfile_ape_items_current['data'][0].'")';
					}
					break;

				case 'replaygain_album_gain':
					$thisfile_replaygain['album']['adjustment'] = (float) str_replace(',', '.', $thisfile_ape_items_current['data'][0]); // float casting will see "0,95" as zero!
					$thisfile_replaygain['album']['originator'] = 'unspecified';
					break;

				case 'replaygain_album_peak':
					$thisfile_replaygain['album']['peak']       = (float) str_replace(',', '.', $thisfile_ape_items_current['data'][0]); // float casting will see "0,95" as zero!
					$thisfile_replaygain['album']['originator'] = 'unspecified';
					if ($thisfile_replaygain['album']['peak'] <= 0) {
						$ThisFileInfo['warning'][] = 'ReplayGain Album peak from APEtag appears invalid: '.$thisfile_replaygain['album']['peak'].' (original value = "'.$thisfile_ape_items_current['data'][0].'")';
					}
					break;

				case 'mp3gain_undo':
					list($mp3gain_undo_left, $mp3gain_undo_right, $mp3gain_undo_wrap) = explode(',', $thisfile_ape_items_current['data'][0]);
					$thisfile_replaygain['mp3gain']['undo_left']  = intval($mp3gain_undo_left);
					$thisfile_replaygain['mp3gain']['undo_right'] = intval($mp3gain_undo_right);
					$thisfile_replaygain['mp3gain']['undo_wrap']  = (($mp3gain_undo_wrap == 'Y') ? true : false);
					break;

				case 'mp3gain_minmax':
					list($mp3gain_globalgain_min, $mp3gain_globalgain_max) = explode(',', $thisfile_ape_items_current['data'][0]);
					$thisfile_replaygain['mp3gain']['globalgain_track_min'] = intval($mp3gain_globalgain_min);
					$thisfile_replaygain['mp3gain']['globalgain_track_max'] = intval($mp3gain_globalgain_max);
					break;

				case 'mp3gain_album_minmax':
					list($mp3gain_globalgain_album_min, $mp3gain_globalgain_album_max) = explode(',', $thisfile_ape_items_current['data'][0]);
					$thisfile_replaygain['mp3gain']['globalgain_album_min'] = intval($mp3gain_globalgain_album_min);
					$thisfile_replaygain['mp3gain']['globalgain_album_max'] = intval($mp3gain_globalgain_album_max);
					break;

				case 'tracknumber':
					foreach ($thisfile_ape_items_current['data'] as $comment) {
						$thisfile_ape['comments']['track'][] = $comment;
					}
					break;

				default:
					foreach ($thisfile_ape_items_current['data'] as $comment) {
						$thisfile_ape['comments'][strtolower($item_key)][] = $comment;
					}
					break;
			}

		}
		if (empty($thisfile_replaygain)) {
			unset($ThisFileInfo['replay_gain']);
		}

		return true;
	}

	function parseAPEheaderFooter($APEheaderFooterData) {
		// http://www.uni-jena.de/~pfk/mpp/sv8/apeheader.html

		// shortcut
		$headerfooterinfo['raw'] = array();
		$headerfooterinfo_raw = &$headerfooterinfo['raw'];

		$headerfooterinfo_raw['footer_tag']   =                  substr($APEheaderFooterData,  0, 8);
		if ($headerfooterinfo_raw['footer_tag'] != 'APETAGEX') {
			return false;
		}
		$headerfooterinfo_raw['version']      = getid3_lib::LittleEndian2Int(substr($APEheaderFooterData,  8, 4));
		$headerfooterinfo_raw['tagsize']      = getid3_lib::LittleEndian2Int(substr($APEheaderFooterData, 12, 4));
		$headerfooterinfo_raw['tag_items']    = getid3_lib::LittleEndian2Int(substr($APEheaderFooterData, 16, 4));
		$headerfooterinfo_raw['global_flags'] = getid3_lib::LittleEndian2Int(substr($APEheaderFooterData, 20, 4));
		$headerfooterinfo_raw['reserved']     =                              substr($APEheaderFooterData, 24, 8);

		$headerfooterinfo['tag_version']         = $headerfooterinfo_raw['version'] / 1000;
		if ($headerfooterinfo['tag_version'] >= 2) {
			$headerfooterinfo['flags'] = $this->parseAPEtagFlags($headerfooterinfo_raw['global_flags']);
		}
		return $headerfooterinfo;
	}

	function parseAPEtagFlags($rawflagint) {
		// "Note: APE Tags 1.0 do not use any of the APE Tag flags.
		// All are set to zero on creation and ignored on reading."
		// http://www.uni-jena.de/~pfk/mpp/sv8/apetagflags.html
		$flags['header']            = (bool) ($rawflagint & 0x80000000);
		$flags['footer']            = (bool) ($rawflagint & 0x40000000);
		$flags['this_is_header']    = (bool) ($rawflagint & 0x20000000);
		$flags['item_contents_raw'] =        ($rawflagint & 0x00000006) >> 1;
		$flags['read_only']         = (bool) ($rawflagint & 0x00000001);

		$flags['item_contents']     = $this->APEcontentTypeFlagLookup($flags['item_contents_raw']);

		return $flags;
	}

	function APEcontentTypeFlagLookup($contenttypeid) {
		static $APEcontentTypeFlagLookup = array(
			0 => 'utf-8',
			1 => 'binary',
			2 => 'external',
			3 => 'reserved'
		);
		return (isset($APEcontentTypeFlagLookup[$contenttypeid]) ? $APEcontentTypeFlagLookup[$contenttypeid] : 'invalid');
	}

	function APEtagItemIsUTF8Lookup($itemkey) {
		static $APEtagItemIsUTF8Lookup = array(
			'title',
			'subtitle',
			'artist',
			'album',
			'debut album',
			'publisher',
			'conductor',
			'track',
			'composer',
			'comment',
			'copyright',
			'publicationright',
			'file',
			'year',
			'record date',
			'record location',
			'genre',
			'media',
			'related',
			'isrc',
			'abstract',
			'language',
			'bibliography'
		);
		return in_array(strtolower($itemkey), $APEtagItemIsUTF8Lookup);
	}

}

?>