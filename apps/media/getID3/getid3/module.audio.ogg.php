<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.audio.ogg.php                                        //
// module for analyzing Ogg Vorbis, OggFLAC and Speex files    //
// dependencies: module.audio.flac.php                         //
//                                                            ///
/////////////////////////////////////////////////////////////////

getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.audio.flac.php', __FILE__, true);

class getid3_ogg
{

	function getid3_ogg(&$fd, &$ThisFileInfo) {

		$ThisFileInfo['fileformat'] = 'ogg';

		// Warn about illegal tags - only vorbiscomments are allowed
		if (isset($ThisFileInfo['id3v2'])) {
			$ThisFileInfo['warning'][] = 'Illegal ID3v2 tag present.';
		}
		if (isset($ThisFileInfo['id3v1'])) {
			$ThisFileInfo['warning'][] = 'Illegal ID3v1 tag present.';
		}
		if (isset($ThisFileInfo['ape'])) {
			$ThisFileInfo['warning'][] = 'Illegal APE tag present.';
		}


		// Page 1 - Stream Header

		fseek($fd, $ThisFileInfo['avdataoffset'], SEEK_SET);

		$oggpageinfo = getid3_ogg::ParseOggPageHeader($fd);
		$ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']] = $oggpageinfo;

		if (ftell($fd) >= GETID3_FREAD_BUFFER_SIZE) {
			$ThisFileInfo['error'][] = 'Could not find start of Ogg page in the first '.GETID3_FREAD_BUFFER_SIZE.' bytes (this might not be an Ogg-Vorbis file?)';
			unset($ThisFileInfo['fileformat']);
			unset($ThisFileInfo['ogg']);
			return false;
		}

		$filedata = fread($fd, $oggpageinfo['page_length']);
		$filedataoffset = 0;

		if (substr($filedata, 0, 4) == 'fLaC') {

			$ThisFileInfo['audio']['dataformat']   = 'flac';
			$ThisFileInfo['audio']['bitrate_mode'] = 'vbr';
			$ThisFileInfo['audio']['lossless']     = true;

		} elseif (substr($filedata, 1, 6) == 'vorbis') {

			$this->ParseVorbisPageHeader($filedata, $filedataoffset, $ThisFileInfo, $oggpageinfo);

		} elseif (substr($filedata, 0, 8) == 'Speex   ') {

			// http://www.speex.org/manual/node10.html

			$ThisFileInfo['audio']['dataformat']   = 'speex';
			$ThisFileInfo['mime_type']             = 'audio/speex';
			$ThisFileInfo['audio']['bitrate_mode'] = 'abr';
			$ThisFileInfo['audio']['lossless']     = false;

			$ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']]['speex_string']           =                              substr($filedata, $filedataoffset, 8); // hard-coded to 'Speex   '
			$filedataoffset += 8;
			$ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']]['speex_version']          =                              substr($filedata, $filedataoffset, 20);
			$filedataoffset += 20;
			$ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']]['speex_version_id']       = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
			$filedataoffset += 4;
			$ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']]['header_size']            = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
			$filedataoffset += 4;
			$ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']]['rate']                   = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
			$filedataoffset += 4;
			$ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']]['mode']                   = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
			$filedataoffset += 4;
			$ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']]['mode_bitstream_version'] = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
			$filedataoffset += 4;
			$ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']]['nb_channels']            = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
			$filedataoffset += 4;
			$ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']]['bitrate']                = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
			$filedataoffset += 4;
			$ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']]['framesize']              = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
			$filedataoffset += 4;
			$ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']]['vbr']                    = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
			$filedataoffset += 4;
			$ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']]['frames_per_packet']      = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
			$filedataoffset += 4;
			$ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']]['extra_headers']          = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
			$filedataoffset += 4;
			$ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']]['reserved1']              = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
			$filedataoffset += 4;
			$ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']]['reserved2']              = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
			$filedataoffset += 4;

			$ThisFileInfo['speex']['speex_version'] = trim($ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']]['speex_version']);
			$ThisFileInfo['speex']['sample_rate']   = $ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']]['rate'];
			$ThisFileInfo['speex']['channels']      = $ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']]['nb_channels'];
			$ThisFileInfo['speex']['vbr']           = (bool) $ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']]['vbr'];
			$ThisFileInfo['speex']['band_type']     = getid3_ogg::SpeexBandModeLookup($ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']]['mode']);

			$ThisFileInfo['audio']['sample_rate']   = $ThisFileInfo['speex']['sample_rate'];
			$ThisFileInfo['audio']['channels']      = $ThisFileInfo['speex']['channels'];
			if ($ThisFileInfo['speex']['vbr']) {
				$ThisFileInfo['audio']['bitrate_mode'] = 'vbr';
			}

		} else {

			$ThisFileInfo['error'][] = 'Expecting either "Speex   " or "vorbis" identifier strings, found neither';
			unset($ThisFileInfo['ogg']);
			unset($ThisFileInfo['mime_type']);
			return false;

		}


		// Page 2 - Comment Header

		$oggpageinfo = getid3_ogg::ParseOggPageHeader($fd);
		$ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']] = $oggpageinfo;

		switch ($ThisFileInfo['audio']['dataformat']) {

			case 'vorbis':
				$filedata = fread($fd, $ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']]['page_length']);
				$ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']]['packet_type'] = getid3_lib::LittleEndian2Int(substr($filedata, 0, 1));
				$ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']]['stream_type'] =                              substr($filedata, 1, 6); // hard-coded to 'vorbis'

				getid3_ogg::ParseVorbisCommentsFilepointer($fd, $ThisFileInfo);
				break;

			case 'flac':
				if (!getid3_flac::FLACparseMETAdata($fd, $ThisFileInfo)) {
					$ThisFileInfo['error'][] = 'Failed to parse FLAC headers';
					return false;
				}
				break;

			case 'speex':
				fseek($fd, $ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']]['page_length'], SEEK_CUR);
				getid3_ogg::ParseVorbisCommentsFilepointer($fd, $ThisFileInfo);
				break;

		}



		// Last Page - Number of Samples

		if ($ThisFileInfo['avdataend'] >= pow(2, 31)) {

			$ThisFileInfo['warning'][] = 'Unable to parse Ogg end chunk file (PHP does not support file operations beyond 2GB)';

		} else {

			fseek($fd, max($ThisFileInfo['avdataend'] - GETID3_FREAD_BUFFER_SIZE, 0), SEEK_SET);
			$LastChunkOfOgg = strrev(fread($fd, GETID3_FREAD_BUFFER_SIZE));
			if ($LastOggSpostion = strpos($LastChunkOfOgg, 'SggO')) {
				fseek($fd, $ThisFileInfo['avdataend'] - ($LastOggSpostion + strlen('SggO')), SEEK_SET);
				$ThisFileInfo['avdataend'] = ftell($fd);
				$ThisFileInfo['ogg']['pageheader']['eos'] = getid3_ogg::ParseOggPageHeader($fd);
				$ThisFileInfo['ogg']['samples']   = $ThisFileInfo['ogg']['pageheader']['eos']['pcm_abs_position'];
				if ($ThisFileInfo['ogg']['samples'] == 0) {
					$ThisFileInfo['error'][] = 'Corrupt Ogg file: eos.number of samples == zero';
					return false;
				}
				$ThisFileInfo['ogg']['bitrate_average'] = (($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']) * 8) / ($ThisFileInfo['ogg']['samples'] / $ThisFileInfo['audio']['sample_rate']);
			}

		}

		if (!empty($ThisFileInfo['ogg']['bitrate_average'])) {
			$ThisFileInfo['audio']['bitrate'] = $ThisFileInfo['ogg']['bitrate_average'];
		} elseif (!empty($ThisFileInfo['ogg']['bitrate_nominal'])) {
			$ThisFileInfo['audio']['bitrate'] = $ThisFileInfo['ogg']['bitrate_nominal'];
		} elseif (!empty($ThisFileInfo['ogg']['bitrate_min']) && !empty($ThisFileInfo['ogg']['bitrate_max'])) {
			$ThisFileInfo['audio']['bitrate'] = ($ThisFileInfo['ogg']['bitrate_min'] + $ThisFileInfo['ogg']['bitrate_max']) / 2;
		}
		if (isset($ThisFileInfo['audio']['bitrate']) && !isset($ThisFileInfo['playtime_seconds'])) {
			if ($ThisFileInfo['audio']['bitrate'] == 0) {
				$ThisFileInfo['error'][] = 'Corrupt Ogg file: bitrate_audio == zero';
				return false;
			}
			$ThisFileInfo['playtime_seconds'] = (float) ((($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']) * 8) / $ThisFileInfo['audio']['bitrate']);
		}

		if (isset($ThisFileInfo['ogg']['vendor'])) {
			$ThisFileInfo['audio']['encoder'] = preg_replace('/^Encoded with /', '', $ThisFileInfo['ogg']['vendor']);

			// Vorbis only
			if ($ThisFileInfo['audio']['dataformat'] == 'vorbis') {

				// Vorbis 1.0 starts with Xiph.Org
				if  (preg_match('/^Xiph.Org/', $ThisFileInfo['audio']['encoder'])) {

					if ($ThisFileInfo['audio']['bitrate_mode'] == 'abr') {

						// Set -b 128 on abr files
						$ThisFileInfo['audio']['encoder_options'] = '-b '.round($ThisFileInfo['ogg']['bitrate_nominal'] / 1000);

					} elseif (($ThisFileInfo['audio']['bitrate_mode'] == 'vbr') && ($ThisFileInfo['audio']['channels'] == 2) && ($ThisFileInfo['audio']['sample_rate'] >= 44100) && ($ThisFileInfo['audio']['sample_rate'] <= 48000)) {
						// Set -q N on vbr files
						$ThisFileInfo['audio']['encoder_options'] = '-q '.$this->get_quality_from_nominal_bitrate($ThisFileInfo['ogg']['bitrate_nominal']);

					}
				}

				if (empty($ThisFileInfo['audio']['encoder_options']) && !empty($ThisFileInfo['ogg']['bitrate_nominal'])) {
					$ThisFileInfo['audio']['encoder_options'] = 'Nominal bitrate: '.intval(round($ThisFileInfo['ogg']['bitrate_nominal'] / 1000)).'kbps';
				}
			}
		}

		return true;
	}

	function ParseVorbisPageHeader(&$filedata, &$filedataoffset, &$ThisFileInfo, &$oggpageinfo) {
		$ThisFileInfo['audio']['dataformat'] = 'vorbis';
		$ThisFileInfo['audio']['lossless']   = false;

		$ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']]['packet_type'] = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 1));
		$filedataoffset += 1;
		$ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']]['stream_type'] = substr($filedata, $filedataoffset, 6); // hard-coded to 'vorbis'
		$filedataoffset += 6;
		$ThisFileInfo['ogg']['bitstreamversion'] = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
		$filedataoffset += 4;
		$ThisFileInfo['ogg']['numberofchannels'] = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 1));
		$filedataoffset += 1;
		$ThisFileInfo['audio']['channels']       = $ThisFileInfo['ogg']['numberofchannels'];
		$ThisFileInfo['ogg']['samplerate']       = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
		$filedataoffset += 4;
		if ($ThisFileInfo['ogg']['samplerate'] == 0) {
			$ThisFileInfo['error'][] = 'Corrupt Ogg file: sample rate == zero';
			return false;
		}
		$ThisFileInfo['audio']['sample_rate']    = $ThisFileInfo['ogg']['samplerate'];
		$ThisFileInfo['ogg']['samples']          = 0; // filled in later
		$ThisFileInfo['ogg']['bitrate_average']  = 0; // filled in later
		$ThisFileInfo['ogg']['bitrate_max']      = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
		$filedataoffset += 4;
		$ThisFileInfo['ogg']['bitrate_nominal']  = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
		$filedataoffset += 4;
		$ThisFileInfo['ogg']['bitrate_min']      = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
		$filedataoffset += 4;
		$ThisFileInfo['ogg']['blocksize_small']  = pow(2,  getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 1)) & 0x0F);
		$ThisFileInfo['ogg']['blocksize_large']  = pow(2, (getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 1)) & 0xF0) >> 4);
		$ThisFileInfo['ogg']['stop_bit']         = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 1)); // must be 1, marks end of packet

		$ThisFileInfo['audio']['bitrate_mode'] = 'vbr'; // overridden if actually abr
		if ($ThisFileInfo['ogg']['bitrate_max'] == 0xFFFFFFFF) {
			unset($ThisFileInfo['ogg']['bitrate_max']);
			$ThisFileInfo['audio']['bitrate_mode'] = 'abr';
		}
		if ($ThisFileInfo['ogg']['bitrate_nominal'] == 0xFFFFFFFF) {
			unset($ThisFileInfo['ogg']['bitrate_nominal']);
		}
		if ($ThisFileInfo['ogg']['bitrate_min'] == 0xFFFFFFFF) {
			unset($ThisFileInfo['ogg']['bitrate_min']);
			$ThisFileInfo['audio']['bitrate_mode'] = 'abr';
		}
		return true;
	}

	function ParseOggPageHeader(&$fd) {
		// http://xiph.org/ogg/vorbis/doc/framing.html
		$oggheader['page_start_offset'] = ftell($fd); // where we started from in the file

		$filedata = fread($fd, GETID3_FREAD_BUFFER_SIZE);
		$filedataoffset = 0;
		while ((substr($filedata, $filedataoffset++, 4) != 'OggS')) {
			if ((ftell($fd) - $oggheader['page_start_offset']) >= GETID3_FREAD_BUFFER_SIZE) {
				// should be found before here
				return false;
			}
			if ((($filedataoffset + 28) > strlen($filedata)) || (strlen($filedata) < 28)) {
				if (feof($fd) || (($filedata .= fread($fd, GETID3_FREAD_BUFFER_SIZE)) === false)) {
					// get some more data, unless eof, in which case fail
					return false;
				}
			}
		}
		$filedataoffset += strlen('OggS') - 1; // page, delimited by 'OggS'

		$oggheader['stream_structver']  = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 1));
		$filedataoffset += 1;
		$oggheader['flags_raw']         = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 1));
		$filedataoffset += 1;
		$oggheader['flags']['fresh']    = (bool) ($oggheader['flags_raw'] & 0x01); // fresh packet
		$oggheader['flags']['bos']      = (bool) ($oggheader['flags_raw'] & 0x02); // first page of logical bitstream (bos)
		$oggheader['flags']['eos']      = (bool) ($oggheader['flags_raw'] & 0x04); // last page of logical bitstream (eos)

		$oggheader['pcm_abs_position']  = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 8));
		$filedataoffset += 8;
		$oggheader['stream_serialno']   = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
		$filedataoffset += 4;
		$oggheader['page_seqno']        = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
		$filedataoffset += 4;
		$oggheader['page_checksum']     = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
		$filedataoffset += 4;
		$oggheader['page_segments']     = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 1));
		$filedataoffset += 1;
		$oggheader['page_length'] = 0;
		for ($i = 0; $i < $oggheader['page_segments']; $i++) {
			$oggheader['segment_table'][$i] = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 1));
			$filedataoffset += 1;
			$oggheader['page_length'] += $oggheader['segment_table'][$i];
		}
		$oggheader['header_end_offset'] = $oggheader['page_start_offset'] + $filedataoffset;
		$oggheader['page_end_offset']   = $oggheader['header_end_offset'] + $oggheader['page_length'];
		fseek($fd, $oggheader['header_end_offset'], SEEK_SET);

		return $oggheader;
	}


	function ParseVorbisCommentsFilepointer(&$fd, &$ThisFileInfo) {

		$OriginalOffset = ftell($fd);
		$CommentStartOffset = $OriginalOffset;
		$commentdataoffset = 0;
		$VorbisCommentPage = 1;

		switch ($ThisFileInfo['audio']['dataformat']) {
			case 'vorbis':
				$CommentStartOffset = $ThisFileInfo['ogg']['pageheader'][$VorbisCommentPage]['page_start_offset'];  // Second Ogg page, after header block
				fseek($fd, $CommentStartOffset, SEEK_SET);
				$commentdataoffset = 27 + $ThisFileInfo['ogg']['pageheader'][$VorbisCommentPage]['page_segments'];
				$commentdata = fread($fd, getid3_ogg::OggPageSegmentLength($ThisFileInfo['ogg']['pageheader'][$VorbisCommentPage], 1) + $commentdataoffset);

				$commentdataoffset += (strlen('vorbis') + 1);
				break;

			case 'flac':
				fseek($fd, $ThisFileInfo['flac']['VORBIS_COMMENT']['raw']['offset'] + 4, SEEK_SET);
				$commentdata = fread($fd, $ThisFileInfo['flac']['VORBIS_COMMENT']['raw']['block_length']);
				break;

			case 'speex':
				$CommentStartOffset = $ThisFileInfo['ogg']['pageheader'][$VorbisCommentPage]['page_start_offset'];  // Second Ogg page, after header block
				fseek($fd, $CommentStartOffset, SEEK_SET);
				$commentdataoffset = 27 + $ThisFileInfo['ogg']['pageheader'][$VorbisCommentPage]['page_segments'];
				$commentdata = fread($fd, getid3_ogg::OggPageSegmentLength($ThisFileInfo['ogg']['pageheader'][$VorbisCommentPage], 1) + $commentdataoffset);
				break;

			default:
				return false;
				break;
		}

		$VendorSize = getid3_lib::LittleEndian2Int(substr($commentdata, $commentdataoffset, 4));
		$commentdataoffset += 4;

		$ThisFileInfo['ogg']['vendor'] = substr($commentdata, $commentdataoffset, $VendorSize);
		$commentdataoffset += $VendorSize;

		$CommentsCount = getid3_lib::LittleEndian2Int(substr($commentdata, $commentdataoffset, 4));
		$commentdataoffset += 4;
		$ThisFileInfo['avdataoffset'] = $CommentStartOffset + $commentdataoffset;

		$basicfields = array('TITLE', 'ARTIST', 'ALBUM', 'TRACKNUMBER', 'GENRE', 'DATE', 'DESCRIPTION', 'COMMENT');
		for ($i = 0; $i < $CommentsCount; $i++) {

			$ThisFileInfo['ogg']['comments_raw'][$i]['dataoffset'] = $CommentStartOffset + $commentdataoffset;

			if (ftell($fd) < ($ThisFileInfo['ogg']['comments_raw'][$i]['dataoffset'] + 4)) {
				$VorbisCommentPage++;

				$oggpageinfo = getid3_ogg::ParseOggPageHeader($fd);
				$ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']] = $oggpageinfo;

				// First, save what we haven't read yet
				$AsYetUnusedData = substr($commentdata, $commentdataoffset);

				// Then take that data off the end
				$commentdata     = substr($commentdata, 0, $commentdataoffset);

				// Add [headerlength] bytes of dummy data for the Ogg Page Header, just to keep absolute offsets correct
				$commentdata .= str_repeat("\x00", 27 + $ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']]['page_segments']);
				$commentdataoffset += (27 + $ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']]['page_segments']);

				// Finally, stick the unused data back on the end
				$commentdata .= $AsYetUnusedData;

				//$commentdata .= fread($fd, $ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']]['page_length']);
				$commentdata .= fread($fd, getid3_ogg::OggPageSegmentLength($ThisFileInfo['ogg']['pageheader'][$VorbisCommentPage], 1));

			}
			$ThisFileInfo['ogg']['comments_raw'][$i]['size']       = getid3_lib::LittleEndian2Int(substr($commentdata, $commentdataoffset, 4));

			// replace avdataoffset with position just after the last vorbiscomment
			$ThisFileInfo['avdataoffset'] = $ThisFileInfo['ogg']['comments_raw'][$i]['dataoffset'] + $ThisFileInfo['ogg']['comments_raw'][$i]['size'] + 4;

			$commentdataoffset += 4;
			while ((strlen($commentdata) - $commentdataoffset) < $ThisFileInfo['ogg']['comments_raw'][$i]['size']) {
				if (($ThisFileInfo['ogg']['comments_raw'][$i]['size'] > $ThisFileInfo['avdataend']) || ($ThisFileInfo['ogg']['comments_raw'][$i]['size'] < 0)) {
					$ThisFileInfo['error'][] = 'Invalid Ogg comment size (comment #'.$i.', claims to be '.number_format($ThisFileInfo['ogg']['comments_raw'][$i]['size']).' bytes) - aborting reading comments';
					break 2;
				}

				$VorbisCommentPage++;

				$oggpageinfo = getid3_ogg::ParseOggPageHeader($fd);
				$ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']] = $oggpageinfo;

				// First, save what we haven't read yet
				$AsYetUnusedData = substr($commentdata, $commentdataoffset);

				// Then take that data off the end
				$commentdata     = substr($commentdata, 0, $commentdataoffset);

				// Add [headerlength] bytes of dummy data for the Ogg Page Header, just to keep absolute offsets correct
				$commentdata .= str_repeat("\x00", 27 + $ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']]['page_segments']);
				$commentdataoffset += (27 + $ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']]['page_segments']);

				// Finally, stick the unused data back on the end
				$commentdata .= $AsYetUnusedData;

				//$commentdata .= fread($fd, $ThisFileInfo['ogg']['pageheader'][$oggpageinfo['page_seqno']]['page_length']);
				$commentdata .= fread($fd, getid3_ogg::OggPageSegmentLength($ThisFileInfo['ogg']['pageheader'][$VorbisCommentPage], 1));

				//$filebaseoffset += $oggpageinfo['header_end_offset'] - $oggpageinfo['page_start_offset'];
			}
			$commentstring = substr($commentdata, $commentdataoffset, $ThisFileInfo['ogg']['comments_raw'][$i]['size']);
			$commentdataoffset += $ThisFileInfo['ogg']['comments_raw'][$i]['size'];

			if (!$commentstring) {

				// no comment?
				$ThisFileInfo['warning'][] = 'Blank Ogg comment ['.$i.']';

			} elseif (strstr($commentstring, '=')) {

				$commentexploded = explode('=', $commentstring, 2);
				$ThisFileInfo['ogg']['comments_raw'][$i]['key']   = strtoupper($commentexploded[0]);
				$ThisFileInfo['ogg']['comments_raw'][$i]['value'] = @$commentexploded[1];
				$ThisFileInfo['ogg']['comments_raw'][$i]['data']  = base64_decode($ThisFileInfo['ogg']['comments_raw'][$i]['value']);

				$ThisFileInfo['ogg']['comments'][strtolower($ThisFileInfo['ogg']['comments_raw'][$i]['key'])][] = $ThisFileInfo['ogg']['comments_raw'][$i]['value'];

				$imageinfo = array();
				$imagechunkcheck = getid3_lib::GetDataImageSize($ThisFileInfo['ogg']['comments_raw'][$i]['data'], $imageinfo);
				$ThisFileInfo['ogg']['comments_raw'][$i]['image_mime'] = getid3_lib::image_type_to_mime_type($imagechunkcheck[2]);
				if (!$ThisFileInfo['ogg']['comments_raw'][$i]['image_mime'] || ($ThisFileInfo['ogg']['comments_raw'][$i]['image_mime'] == 'application/octet-stream')) {
					unset($ThisFileInfo['ogg']['comments_raw'][$i]['image_mime']);
					unset($ThisFileInfo['ogg']['comments_raw'][$i]['data']);
				}

			} else {

				$ThisFileInfo['warning'][] = '[known problem with CDex >= v1.40, < v1.50b7] Invalid Ogg comment name/value pair ['.$i.']: '.$commentstring;

			}
		}


		// Replay Gain Adjustment
		// http://privatewww.essex.ac.uk/~djmrob/replaygain/
		if (isset($ThisFileInfo['ogg']['comments']) && is_array($ThisFileInfo['ogg']['comments'])) {
			foreach ($ThisFileInfo['ogg']['comments'] as $index => $commentvalue) {
				switch ($index) {
					case 'rg_audiophile':
					case 'replaygain_album_gain':
						$ThisFileInfo['replay_gain']['album']['adjustment'] = (double) $commentvalue[0];
						unset($ThisFileInfo['ogg']['comments'][$index]);
						break;

					case 'rg_radio':
					case 'replaygain_track_gain':
						$ThisFileInfo['replay_gain']['track']['adjustment'] = (double) $commentvalue[0];
						unset($ThisFileInfo['ogg']['comments'][$index]);
						break;

					case 'replaygain_album_peak':
						$ThisFileInfo['replay_gain']['album']['peak'] = (double) $commentvalue[0];
						unset($ThisFileInfo['ogg']['comments'][$index]);
						break;

					case 'rg_peak':
					case 'replaygain_track_peak':
						$ThisFileInfo['replay_gain']['track']['peak'] = (double) $commentvalue[0];
						unset($ThisFileInfo['ogg']['comments'][$index]);
						break;


					default:
						// do nothing
						break;
				}
			}
		}

		fseek($fd, $OriginalOffset, SEEK_SET);

		return true;
	}

	function SpeexBandModeLookup($mode) {
		static $SpeexBandModeLookup = array();
		if (empty($SpeexBandModeLookup)) {
			$SpeexBandModeLookup[0] = 'narrow';
			$SpeexBandModeLookup[1] = 'wide';
			$SpeexBandModeLookup[2] = 'ultra-wide';
		}
		return (isset($SpeexBandModeLookup[$mode]) ? $SpeexBandModeLookup[$mode] : null);
	}


	function OggPageSegmentLength($OggInfoArray, $SegmentNumber=1) {
		for ($i = 0; $i < $SegmentNumber; $i++) {
			$segmentlength = 0;
			foreach ($OggInfoArray['segment_table'] as $key => $value) {
				$segmentlength += $value;
				if ($value < 255) {
					break;
				}
			}
		}
		return $segmentlength;
	}


	function get_quality_from_nominal_bitrate($nominal_bitrate) {

		// decrease precision
		$nominal_bitrate = $nominal_bitrate / 1000;

		if ($nominal_bitrate < 128) {
			// q-1 to q4
			$qval = ($nominal_bitrate - 64) / 16;
		} elseif ($nominal_bitrate < 256) {
			// q4 to q8
			$qval = $nominal_bitrate / 32;
		} elseif ($nominal_bitrate < 320) {
			// q8 to q9
			$qval = ($nominal_bitrate + 256) / 64;
		} else {
			// q9 to q10
			$qval = ($nominal_bitrate + 1300) / 180;
		}
		//return $qval; // 5.031324
		//return intval($qval); // 5
		return round($qval, 1); // 5 or 4.9
	}

}

?>