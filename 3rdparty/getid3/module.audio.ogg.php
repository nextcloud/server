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

class getid3_ogg extends getid3_handler
{
	var $inline_attachments = true; // true: return full data for all attachments; false: return no data for all attachments; integer: return data for attachments <= than this; string: save as file to this directory

	function Analyze() {
		$info = &$this->getid3->info;

		$info['fileformat'] = 'ogg';

		// Warn about illegal tags - only vorbiscomments are allowed
		if (isset($info['id3v2'])) {
			$info['warning'][] = 'Illegal ID3v2 tag present.';
		}
		if (isset($info['id3v1'])) {
			$info['warning'][] = 'Illegal ID3v1 tag present.';
		}
		if (isset($info['ape'])) {
			$info['warning'][] = 'Illegal APE tag present.';
		}


		// Page 1 - Stream Header

		fseek($this->getid3->fp, $info['avdataoffset'], SEEK_SET);

		$oggpageinfo = $this->ParseOggPageHeader();
		$info['ogg']['pageheader'][$oggpageinfo['page_seqno']] = $oggpageinfo;

		if (ftell($this->getid3->fp) >= $this->getid3->fread_buffer_size()) {
			$info['error'][] = 'Could not find start of Ogg page in the first '.$this->getid3->fread_buffer_size().' bytes (this might not be an Ogg-Vorbis file?)';
			unset($info['fileformat']);
			unset($info['ogg']);
			return false;
		}

		$filedata = fread($this->getid3->fp, $oggpageinfo['page_length']);
		$filedataoffset = 0;

		if (substr($filedata, 0, 4) == 'fLaC') {

			$info['audio']['dataformat']   = 'flac';
			$info['audio']['bitrate_mode'] = 'vbr';
			$info['audio']['lossless']     = true;

		} elseif (substr($filedata, 1, 6) == 'vorbis') {

			$this->ParseVorbisPageHeader($filedata, $filedataoffset, $oggpageinfo);

		} elseif (substr($filedata, 0, 8) == 'Speex   ') {

			// http://www.speex.org/manual/node10.html

			$info['audio']['dataformat']   = 'speex';
			$info['mime_type']             = 'audio/speex';
			$info['audio']['bitrate_mode'] = 'abr';
			$info['audio']['lossless']     = false;

			$info['ogg']['pageheader'][$oggpageinfo['page_seqno']]['speex_string']           =                              substr($filedata, $filedataoffset, 8); // hard-coded to 'Speex   '
			$filedataoffset += 8;
			$info['ogg']['pageheader'][$oggpageinfo['page_seqno']]['speex_version']          =                              substr($filedata, $filedataoffset, 20);
			$filedataoffset += 20;
			$info['ogg']['pageheader'][$oggpageinfo['page_seqno']]['speex_version_id']       = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
			$filedataoffset += 4;
			$info['ogg']['pageheader'][$oggpageinfo['page_seqno']]['header_size']            = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
			$filedataoffset += 4;
			$info['ogg']['pageheader'][$oggpageinfo['page_seqno']]['rate']                   = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
			$filedataoffset += 4;
			$info['ogg']['pageheader'][$oggpageinfo['page_seqno']]['mode']                   = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
			$filedataoffset += 4;
			$info['ogg']['pageheader'][$oggpageinfo['page_seqno']]['mode_bitstream_version'] = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
			$filedataoffset += 4;
			$info['ogg']['pageheader'][$oggpageinfo['page_seqno']]['nb_channels']            = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
			$filedataoffset += 4;
			$info['ogg']['pageheader'][$oggpageinfo['page_seqno']]['bitrate']                = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
			$filedataoffset += 4;
			$info['ogg']['pageheader'][$oggpageinfo['page_seqno']]['framesize']              = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
			$filedataoffset += 4;
			$info['ogg']['pageheader'][$oggpageinfo['page_seqno']]['vbr']                    = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
			$filedataoffset += 4;
			$info['ogg']['pageheader'][$oggpageinfo['page_seqno']]['frames_per_packet']      = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
			$filedataoffset += 4;
			$info['ogg']['pageheader'][$oggpageinfo['page_seqno']]['extra_headers']          = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
			$filedataoffset += 4;
			$info['ogg']['pageheader'][$oggpageinfo['page_seqno']]['reserved1']              = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
			$filedataoffset += 4;
			$info['ogg']['pageheader'][$oggpageinfo['page_seqno']]['reserved2']              = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
			$filedataoffset += 4;

			$info['speex']['speex_version'] = trim($info['ogg']['pageheader'][$oggpageinfo['page_seqno']]['speex_version']);
			$info['speex']['sample_rate']   = $info['ogg']['pageheader'][$oggpageinfo['page_seqno']]['rate'];
			$info['speex']['channels']      = $info['ogg']['pageheader'][$oggpageinfo['page_seqno']]['nb_channels'];
			$info['speex']['vbr']           = (bool) $info['ogg']['pageheader'][$oggpageinfo['page_seqno']]['vbr'];
			$info['speex']['band_type']     = $this->SpeexBandModeLookup($info['ogg']['pageheader'][$oggpageinfo['page_seqno']]['mode']);

			$info['audio']['sample_rate']   = $info['speex']['sample_rate'];
			$info['audio']['channels']      = $info['speex']['channels'];
			if ($info['speex']['vbr']) {
				$info['audio']['bitrate_mode'] = 'vbr';
			}


		} elseif (substr($filedata, 0, 8) == "fishead\x00") {

			// Ogg Skeleton version 3.0 Format Specification
			// http://xiph.org/ogg/doc/skeleton.html
			$filedataoffset += 8;
			$info['ogg']['skeleton']['fishead']['raw']['version_major']                = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset,  2));
			$filedataoffset += 2;
			$info['ogg']['skeleton']['fishead']['raw']['version_minor']                = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset,  2));
			$filedataoffset += 2;
			$info['ogg']['skeleton']['fishead']['raw']['presentationtime_numerator']   = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset,  8));
			$filedataoffset += 8;
			$info['ogg']['skeleton']['fishead']['raw']['presentationtime_denominator'] = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset,  8));
			$filedataoffset += 8;
			$info['ogg']['skeleton']['fishead']['raw']['basetime_numerator']           = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset,  8));
			$filedataoffset += 8;
			$info['ogg']['skeleton']['fishead']['raw']['basetime_denominator']         = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset,  8));
			$filedataoffset += 8;
			$info['ogg']['skeleton']['fishead']['raw']['utc']                          = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 20));
			$filedataoffset += 20;

			$info['ogg']['skeleton']['fishead']['version']          = $info['ogg']['skeleton']['fishead']['raw']['version_major'].'.'.$info['ogg']['skeleton']['fishead']['raw']['version_minor'];
			$info['ogg']['skeleton']['fishead']['presentationtime'] = $info['ogg']['skeleton']['fishead']['raw']['presentationtime_numerator'] / $info['ogg']['skeleton']['fishead']['raw']['presentationtime_denominator'];
			$info['ogg']['skeleton']['fishead']['basetime']         = $info['ogg']['skeleton']['fishead']['raw']['basetime_numerator']         / $info['ogg']['skeleton']['fishead']['raw']['basetime_denominator'];
			$info['ogg']['skeleton']['fishead']['utc']              = $info['ogg']['skeleton']['fishead']['raw']['utc'];


			$counter = 0;
			do {
				$oggpageinfo = $this->ParseOggPageHeader();
				$info['ogg']['pageheader'][$oggpageinfo['page_seqno'].'.'.$counter++] = $oggpageinfo;
				$filedata = fread($this->getid3->fp, $oggpageinfo['page_length']);
				fseek($this->getid3->fp, $oggpageinfo['page_end_offset'], SEEK_SET);

				if (substr($filedata, 0, 8) == "fisbone\x00") {

					$filedataoffset = 8;
					$info['ogg']['skeleton']['fisbone']['raw']['message_header_offset']   = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset,  4));
					$filedataoffset += 4;
					$info['ogg']['skeleton']['fisbone']['raw']['serial_number']           = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset,  4));
					$filedataoffset += 4;
					$info['ogg']['skeleton']['fisbone']['raw']['number_header_packets']   = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset,  4));
					$filedataoffset += 4;
					$info['ogg']['skeleton']['fisbone']['raw']['granulerate_numerator']   = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset,  8));
					$filedataoffset += 8;
					$info['ogg']['skeleton']['fisbone']['raw']['granulerate_denominator'] = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset,  8));
					$filedataoffset += 8;
					$info['ogg']['skeleton']['fisbone']['raw']['basegranule']             = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset,  8));
					$filedataoffset += 8;
					$info['ogg']['skeleton']['fisbone']['raw']['preroll']                 = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset,  4));
					$filedataoffset += 4;
					$info['ogg']['skeleton']['fisbone']['raw']['granuleshift']            = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset,  1));
					$filedataoffset += 1;
					$info['ogg']['skeleton']['fisbone']['raw']['padding']                 =                              substr($filedata, $filedataoffset,  3);
					$filedataoffset += 3;

				} elseif (substr($filedata, 1, 6) == 'theora') {

					$info['video']['dataformat'] = 'theora';
$info['error'][] = 'Ogg Theora not correctly handled in this version of getID3 ['.$this->getid3->version().']';
//break;

				} elseif (substr($filedata, 1, 6) == 'vorbis') {

					$this->ParseVorbisPageHeader($filedata, $filedataoffset, $oggpageinfo);

				} else {
$info['error'][] = 'unexpected';
//break;
				}
			//} while ($oggpageinfo['page_seqno'] == 0);
			} while (($oggpageinfo['page_seqno'] == 0) && (substr($filedata, 0, 8) != "fisbone\x00"));
			fseek($this->getid3->fp, $oggpageinfo['page_start_offset'], SEEK_SET);


$info['error'][] = 'Ogg Skeleton not correctly handled in this version of getID3 ['.$this->getid3->version().']';
//return false;


		} else {

			$info['error'][] = 'Expecting either "Speex   " or "vorbis" identifier strings, found "'.substr($filedata, 0, 8).'"';
			unset($info['ogg']);
			unset($info['mime_type']);
			return false;

		}

		// Page 2 - Comment Header
		$oggpageinfo = $this->ParseOggPageHeader();
		$info['ogg']['pageheader'][$oggpageinfo['page_seqno']] = $oggpageinfo;

		switch ($info['audio']['dataformat']) {
			case 'vorbis':
				$filedata = fread($this->getid3->fp, $info['ogg']['pageheader'][$oggpageinfo['page_seqno']]['page_length']);
				$info['ogg']['pageheader'][$oggpageinfo['page_seqno']]['packet_type'] = getid3_lib::LittleEndian2Int(substr($filedata, 0, 1));
				$info['ogg']['pageheader'][$oggpageinfo['page_seqno']]['stream_type'] =                              substr($filedata, 1, 6); // hard-coded to 'vorbis'

				$this->ParseVorbisCommentsFilepointer();
				break;

			case 'flac':
				$getid3_flac = new getid3_flac($this->getid3);
				if (!$getid3_flac->FLACparseMETAdata()) {
					$info['error'][] = 'Failed to parse FLAC headers';
					return false;
				}
				unset($getid3_flac);
				break;

			case 'speex':
				fseek($this->getid3->fp, $info['ogg']['pageheader'][$oggpageinfo['page_seqno']]['page_length'], SEEK_CUR);
				$this->ParseVorbisCommentsFilepointer();
				break;

		}



		// Last Page - Number of Samples

		if (!getid3_lib::intValueSupported($info['avdataend'])) {

			$info['warning'][] = 'Unable to parse Ogg end chunk file (PHP does not support file operations beyond '.round(PHP_INT_MAX / 1073741824).'GB)';

		} else {

			fseek($this->getid3->fp, max($info['avdataend'] - $this->getid3->fread_buffer_size(), 0), SEEK_SET);
			$LastChunkOfOgg = strrev(fread($this->getid3->fp, $this->getid3->fread_buffer_size()));
			if ($LastOggSpostion = strpos($LastChunkOfOgg, 'SggO')) {
				fseek($this->getid3->fp, $info['avdataend'] - ($LastOggSpostion + strlen('SggO')), SEEK_SET);
				$info['avdataend'] = ftell($this->getid3->fp);
				$info['ogg']['pageheader']['eos'] = $this->ParseOggPageHeader();
				$info['ogg']['samples']   = $info['ogg']['pageheader']['eos']['pcm_abs_position'];
				if ($info['ogg']['samples'] == 0) {
					$info['error'][] = 'Corrupt Ogg file: eos.number of samples == zero';
					return false;
				}
				if (!empty($info['audio']['sample_rate'])) {
					$info['ogg']['bitrate_average'] = (($info['avdataend'] - $info['avdataoffset']) * 8) / ($info['ogg']['samples'] / $info['audio']['sample_rate']);
				}
			}

		}

		if (!empty($info['ogg']['bitrate_average'])) {
			$info['audio']['bitrate'] = $info['ogg']['bitrate_average'];
		} elseif (!empty($info['ogg']['bitrate_nominal'])) {
			$info['audio']['bitrate'] = $info['ogg']['bitrate_nominal'];
		} elseif (!empty($info['ogg']['bitrate_min']) && !empty($info['ogg']['bitrate_max'])) {
			$info['audio']['bitrate'] = ($info['ogg']['bitrate_min'] + $info['ogg']['bitrate_max']) / 2;
		}
		if (isset($info['audio']['bitrate']) && !isset($info['playtime_seconds'])) {
			if ($info['audio']['bitrate'] == 0) {
				$info['error'][] = 'Corrupt Ogg file: bitrate_audio == zero';
				return false;
			}
			$info['playtime_seconds'] = (float) ((($info['avdataend'] - $info['avdataoffset']) * 8) / $info['audio']['bitrate']);
		}

		if (isset($info['ogg']['vendor'])) {
			$info['audio']['encoder'] = preg_replace('/^Encoded with /', '', $info['ogg']['vendor']);

			// Vorbis only
			if ($info['audio']['dataformat'] == 'vorbis') {

				// Vorbis 1.0 starts with Xiph.Org
				if  (preg_match('/^Xiph.Org/', $info['audio']['encoder'])) {

					if ($info['audio']['bitrate_mode'] == 'abr') {

						// Set -b 128 on abr files
						$info['audio']['encoder_options'] = '-b '.round($info['ogg']['bitrate_nominal'] / 1000);

					} elseif (($info['audio']['bitrate_mode'] == 'vbr') && ($info['audio']['channels'] == 2) && ($info['audio']['sample_rate'] >= 44100) && ($info['audio']['sample_rate'] <= 48000)) {
						// Set -q N on vbr files
						$info['audio']['encoder_options'] = '-q '.$this->get_quality_from_nominal_bitrate($info['ogg']['bitrate_nominal']);

					}
				}

				if (empty($info['audio']['encoder_options']) && !empty($info['ogg']['bitrate_nominal'])) {
					$info['audio']['encoder_options'] = 'Nominal bitrate: '.intval(round($info['ogg']['bitrate_nominal'] / 1000)).'kbps';
				}
			}
		}

		return true;
	}

	function ParseVorbisPageHeader(&$filedata, &$filedataoffset, &$oggpageinfo) {
		$info = &$this->getid3->info;
		$info['audio']['dataformat'] = 'vorbis';
		$info['audio']['lossless']   = false;

		$info['ogg']['pageheader'][$oggpageinfo['page_seqno']]['packet_type'] = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 1));
		$filedataoffset += 1;
		$info['ogg']['pageheader'][$oggpageinfo['page_seqno']]['stream_type'] = substr($filedata, $filedataoffset, 6); // hard-coded to 'vorbis'
		$filedataoffset += 6;
		$info['ogg']['bitstreamversion'] = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
		$filedataoffset += 4;
		$info['ogg']['numberofchannels'] = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 1));
		$filedataoffset += 1;
		$info['audio']['channels']       = $info['ogg']['numberofchannels'];
		$info['ogg']['samplerate']       = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
		$filedataoffset += 4;
		if ($info['ogg']['samplerate'] == 0) {
			$info['error'][] = 'Corrupt Ogg file: sample rate == zero';
			return false;
		}
		$info['audio']['sample_rate']    = $info['ogg']['samplerate'];
		$info['ogg']['samples']          = 0; // filled in later
		$info['ogg']['bitrate_average']  = 0; // filled in later
		$info['ogg']['bitrate_max']      = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
		$filedataoffset += 4;
		$info['ogg']['bitrate_nominal']  = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
		$filedataoffset += 4;
		$info['ogg']['bitrate_min']      = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 4));
		$filedataoffset += 4;
		$info['ogg']['blocksize_small']  = pow(2,  getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 1)) & 0x0F);
		$info['ogg']['blocksize_large']  = pow(2, (getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 1)) & 0xF0) >> 4);
		$info['ogg']['stop_bit']         = getid3_lib::LittleEndian2Int(substr($filedata, $filedataoffset, 1)); // must be 1, marks end of packet

		$info['audio']['bitrate_mode'] = 'vbr'; // overridden if actually abr
		if ($info['ogg']['bitrate_max'] == 0xFFFFFFFF) {
			unset($info['ogg']['bitrate_max']);
			$info['audio']['bitrate_mode'] = 'abr';
		}
		if ($info['ogg']['bitrate_nominal'] == 0xFFFFFFFF) {
			unset($info['ogg']['bitrate_nominal']);
		}
		if ($info['ogg']['bitrate_min'] == 0xFFFFFFFF) {
			unset($info['ogg']['bitrate_min']);
			$info['audio']['bitrate_mode'] = 'abr';
		}
		return true;
	}

	function ParseOggPageHeader() {
		// http://xiph.org/ogg/vorbis/doc/framing.html
		$oggheader['page_start_offset'] = ftell($this->getid3->fp); // where we started from in the file

		$filedata = fread($this->getid3->fp, $this->getid3->fread_buffer_size());
		$filedataoffset = 0;
		while ((substr($filedata, $filedataoffset++, 4) != 'OggS')) {
			if ((ftell($this->getid3->fp) - $oggheader['page_start_offset']) >= $this->getid3->fread_buffer_size()) {
				// should be found before here
				return false;
			}
			if ((($filedataoffset + 28) > strlen($filedata)) || (strlen($filedata) < 28)) {
				if (feof($this->getid3->fp) || (($filedata .= fread($this->getid3->fp, $this->getid3->fread_buffer_size())) === false)) {
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
		fseek($this->getid3->fp, $oggheader['header_end_offset'], SEEK_SET);

		return $oggheader;
	}


	function ParseVorbisCommentsFilepointer() {
		$info = &$this->getid3->info;

		$OriginalOffset = ftell($this->getid3->fp);
		$commentdataoffset = 0;
		$VorbisCommentPage = 1;

		switch ($info['audio']['dataformat']) {
			case 'vorbis':
				$CommentStartOffset = $info['ogg']['pageheader'][$VorbisCommentPage]['page_start_offset'];  // Second Ogg page, after header block
				fseek($this->getid3->fp, $CommentStartOffset, SEEK_SET);
				$commentdataoffset = 27 + $info['ogg']['pageheader'][$VorbisCommentPage]['page_segments'];
				$commentdata = fread($this->getid3->fp, getid3_ogg::OggPageSegmentLength($info['ogg']['pageheader'][$VorbisCommentPage], 1) + $commentdataoffset);

				$commentdataoffset += (strlen('vorbis') + 1);
				break;

			case 'flac':
				$CommentStartOffset = $info['flac']['VORBIS_COMMENT']['raw']['offset'] + 4;
				fseek($this->getid3->fp, $CommentStartOffset, SEEK_SET);
				$commentdata = fread($this->getid3->fp, $info['flac']['VORBIS_COMMENT']['raw']['block_length']);
				break;

			case 'speex':
				$CommentStartOffset = $info['ogg']['pageheader'][$VorbisCommentPage]['page_start_offset'];  // Second Ogg page, after header block
				fseek($this->getid3->fp, $CommentStartOffset, SEEK_SET);
				$commentdataoffset = 27 + $info['ogg']['pageheader'][$VorbisCommentPage]['page_segments'];
				$commentdata = fread($this->getid3->fp, getid3_ogg::OggPageSegmentLength($info['ogg']['pageheader'][$VorbisCommentPage], 1) + $commentdataoffset);
				break;

			default:
				return false;
				break;
		}

		$VendorSize = getid3_lib::LittleEndian2Int(substr($commentdata, $commentdataoffset, 4));
		$commentdataoffset += 4;

		$info['ogg']['vendor'] = substr($commentdata, $commentdataoffset, $VendorSize);
		$commentdataoffset += $VendorSize;

		$CommentsCount = getid3_lib::LittleEndian2Int(substr($commentdata, $commentdataoffset, 4));
		$commentdataoffset += 4;
		$info['avdataoffset'] = $CommentStartOffset + $commentdataoffset;

		$basicfields = array('TITLE', 'ARTIST', 'ALBUM', 'TRACKNUMBER', 'GENRE', 'DATE', 'DESCRIPTION', 'COMMENT');
		$ThisFileInfo_ogg_comments_raw = &$info['ogg']['comments_raw'];
		for ($i = 0; $i < $CommentsCount; $i++) {

			$ThisFileInfo_ogg_comments_raw[$i]['dataoffset'] = $CommentStartOffset + $commentdataoffset;

			if (ftell($this->getid3->fp) < ($ThisFileInfo_ogg_comments_raw[$i]['dataoffset'] + 4)) {
				if ($oggpageinfo = $this->ParseOggPageHeader()) {
					$info['ogg']['pageheader'][$oggpageinfo['page_seqno']] = $oggpageinfo;

					$VorbisCommentPage++;

					// First, save what we haven't read yet
					$AsYetUnusedData = substr($commentdata, $commentdataoffset);

					// Then take that data off the end
					$commentdata     = substr($commentdata, 0, $commentdataoffset);

					// Add [headerlength] bytes of dummy data for the Ogg Page Header, just to keep absolute offsets correct
					$commentdata .= str_repeat("\x00", 27 + $info['ogg']['pageheader'][$oggpageinfo['page_seqno']]['page_segments']);
					$commentdataoffset += (27 + $info['ogg']['pageheader'][$oggpageinfo['page_seqno']]['page_segments']);

					// Finally, stick the unused data back on the end
					$commentdata .= $AsYetUnusedData;

					//$commentdata .= fread($this->getid3->fp, $info['ogg']['pageheader'][$oggpageinfo['page_seqno']]['page_length']);
					$commentdata .= fread($this->getid3->fp, $this->OggPageSegmentLength($info['ogg']['pageheader'][$VorbisCommentPage], 1));
				}

			}
			$ThisFileInfo_ogg_comments_raw[$i]['size'] = getid3_lib::LittleEndian2Int(substr($commentdata, $commentdataoffset, 4));

			// replace avdataoffset with position just after the last vorbiscomment
			$info['avdataoffset'] = $ThisFileInfo_ogg_comments_raw[$i]['dataoffset'] + $ThisFileInfo_ogg_comments_raw[$i]['size'] + 4;

			$commentdataoffset += 4;
			while ((strlen($commentdata) - $commentdataoffset) < $ThisFileInfo_ogg_comments_raw[$i]['size']) {
				if (($ThisFileInfo_ogg_comments_raw[$i]['size'] > $info['avdataend']) || ($ThisFileInfo_ogg_comments_raw[$i]['size'] < 0)) {
					$info['warning'][] = 'Invalid Ogg comment size (comment #'.$i.', claims to be '.number_format($ThisFileInfo_ogg_comments_raw[$i]['size']).' bytes) - aborting reading comments';
					break 2;
				}

				$VorbisCommentPage++;

				$oggpageinfo = $this->ParseOggPageHeader();
				$info['ogg']['pageheader'][$oggpageinfo['page_seqno']] = $oggpageinfo;

				// First, save what we haven't read yet
				$AsYetUnusedData = substr($commentdata, $commentdataoffset);

				// Then take that data off the end
				$commentdata     = substr($commentdata, 0, $commentdataoffset);

				// Add [headerlength] bytes of dummy data for the Ogg Page Header, just to keep absolute offsets correct
				$commentdata .= str_repeat("\x00", 27 + $info['ogg']['pageheader'][$oggpageinfo['page_seqno']]['page_segments']);
				$commentdataoffset += (27 + $info['ogg']['pageheader'][$oggpageinfo['page_seqno']]['page_segments']);

				// Finally, stick the unused data back on the end
				$commentdata .= $AsYetUnusedData;

				//$commentdata .= fread($this->getid3->fp, $info['ogg']['pageheader'][$oggpageinfo['page_seqno']]['page_length']);
				if (!isset($info['ogg']['pageheader'][$VorbisCommentPage])) {
					$info['warning'][] = 'undefined Vorbis Comment page "'.$VorbisCommentPage.'" at offset '.ftell($this->getid3->fp);
					break;
				}
				$readlength = getid3_ogg::OggPageSegmentLength($info['ogg']['pageheader'][$VorbisCommentPage], 1);
				if ($readlength <= 0) {
					$info['warning'][] = 'invalid length Vorbis Comment page "'.$VorbisCommentPage.'" at offset '.ftell($this->getid3->fp);
					break;
				}
				$commentdata .= fread($this->getid3->fp, $readlength);

				//$filebaseoffset += $oggpageinfo['header_end_offset'] - $oggpageinfo['page_start_offset'];
			}
			$ThisFileInfo_ogg_comments_raw[$i]['offset'] = $commentdataoffset;
			$commentstring = substr($commentdata, $commentdataoffset, $ThisFileInfo_ogg_comments_raw[$i]['size']);
			$commentdataoffset += $ThisFileInfo_ogg_comments_raw[$i]['size'];

			if (!$commentstring) {

				// no comment?
				$info['warning'][] = 'Blank Ogg comment ['.$i.']';

			} elseif (strstr($commentstring, '=')) {

				$commentexploded = explode('=', $commentstring, 2);
				$ThisFileInfo_ogg_comments_raw[$i]['key']         = strtoupper($commentexploded[0]);
				$ThisFileInfo_ogg_comments_raw[$i]['value']       = (isset($commentexploded[1]) ? $commentexploded[1] : '');
				$ThisFileInfo_ogg_comments_raw[$i]['data']        = base64_decode($ThisFileInfo_ogg_comments_raw[$i]['value']);
				$ThisFileInfo_ogg_comments_raw[$i]['data_length'] = strlen($ThisFileInfo_ogg_comments_raw[$i]['data']);

				if (preg_match('#^(BM|GIF|\xFF\xD8\xFF|\x89\x50\x4E\x47\x0D\x0A\x1A\x0A|II\x2A\x00|MM\x00\x2A)#s', $ThisFileInfo_ogg_comments_raw[$i]['data'])) {
					$imageinfo = array();
					$imagechunkcheck = getid3_lib::GetDataImageSize($ThisFileInfo_ogg_comments_raw[$i]['data'], $imageinfo);
					unset($imageinfo);
					if (!empty($imagechunkcheck)) {
						$ThisFileInfo_ogg_comments_raw[$i]['image_mime'] = image_type_to_mime_type($imagechunkcheck[2]);
						if ($ThisFileInfo_ogg_comments_raw[$i]['image_mime'] && ($ThisFileInfo_ogg_comments_raw[$i]['image_mime'] != 'application/octet-stream')) {
							unset($ThisFileInfo_ogg_comments_raw[$i]['value']);
						}
					}
				}

				if (isset($ThisFileInfo_ogg_comments_raw[$i]['value'])) {
					unset($ThisFileInfo_ogg_comments_raw[$i]['data']);
					$info['ogg']['comments'][strtolower($ThisFileInfo_ogg_comments_raw[$i]['key'])][] = $ThisFileInfo_ogg_comments_raw[$i]['value'];
				} else {
					do {
						if ($this->inline_attachments === false) {
							// skip entirely
							unset($ThisFileInfo_ogg_comments_raw[$i]['data']);
							break;
						}
						if ($this->inline_attachments === true) {
							// great
						} elseif (is_int($this->inline_attachments)) {
							if ($this->inline_attachments < $ThisFileInfo_ogg_comments_raw[$i]['data_length']) {
								// too big, skip
								$info['warning'][] = 'attachment at '.$ThisFileInfo_ogg_comments_raw[$i]['offset'].' is too large to process inline ('.number_format($ThisFileInfo_ogg_comments_raw[$i]['data_length']).' bytes)';
								unset($ThisFileInfo_ogg_comments_raw[$i]['data']);
								break;
							}
						} elseif (is_string($this->inline_attachments)) {
							$this->inline_attachments = rtrim(str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $this->inline_attachments), DIRECTORY_SEPARATOR);
							if (!is_dir($this->inline_attachments) || !is_writable($this->inline_attachments)) {
								// cannot write, skip
								$info['warning'][] = 'attachment at '.$ThisFileInfo_ogg_comments_raw[$i]['offset'].' cannot be saved to "'.$this->inline_attachments.'" (not writable)';
								unset($ThisFileInfo_ogg_comments_raw[$i]['data']);
								break;
							}
						}
						// if we get this far, must be OK
						if (is_string($this->inline_attachments)) {
							$destination_filename = $this->inline_attachments.DIRECTORY_SEPARATOR.md5($info['filenamepath']).'_'.$ThisFileInfo_ogg_comments_raw[$i]['offset'];
							if (!file_exists($destination_filename) || is_writable($destination_filename)) {
								file_put_contents($destination_filename, $ThisFileInfo_ogg_comments_raw[$i]['data']);
							} else {
								$info['warning'][] = 'attachment at '.$ThisFileInfo_ogg_comments_raw[$i]['offset'].' cannot be saved to "'.$destination_filename.'" (not writable)';
							}
							$ThisFileInfo_ogg_comments_raw[$i]['data_filename'] = $destination_filename;
							unset($ThisFileInfo_ogg_comments_raw[$i]['data']);
						} else {
							$info['ogg']['comments']['picture'][] = array('data'=>$ThisFileInfo_ogg_comments_raw[$i]['data'], 'image_mime'=>$ThisFileInfo_ogg_comments_raw[$i]['image_mime']);
						}
					} while (false);

				}


			} else {

				$info['warning'][] = '[known problem with CDex >= v1.40, < v1.50b7] Invalid Ogg comment name/value pair ['.$i.']: '.$commentstring;

			}
		}


		// Replay Gain Adjustment
		// http://privatewww.essex.ac.uk/~djmrob/replaygain/
		if (isset($info['ogg']['comments']) && is_array($info['ogg']['comments'])) {
			foreach ($info['ogg']['comments'] as $index => $commentvalue) {
				switch ($index) {
					case 'rg_audiophile':
					case 'replaygain_album_gain':
						$info['replay_gain']['album']['adjustment'] = (double) $commentvalue[0];
						unset($info['ogg']['comments'][$index]);
						break;

					case 'rg_radio':
					case 'replaygain_track_gain':
						$info['replay_gain']['track']['adjustment'] = (double) $commentvalue[0];
						unset($info['ogg']['comments'][$index]);
						break;

					case 'replaygain_album_peak':
						$info['replay_gain']['album']['peak'] = (double) $commentvalue[0];
						unset($info['ogg']['comments'][$index]);
						break;

					case 'rg_peak':
					case 'replaygain_track_peak':
						$info['replay_gain']['track']['peak'] = (double) $commentvalue[0];
						unset($info['ogg']['comments'][$index]);
						break;

					case 'replaygain_reference_loudness':
						$info['replay_gain']['reference_volume'] = (double) $commentvalue[0];
						unset($info['ogg']['comments'][$index]);
						break;

					default:
						// do nothing
						break;
				}
			}
		}

		fseek($this->getid3->fp, $OriginalOffset, SEEK_SET);

		return true;
	}

	static function SpeexBandModeLookup($mode) {
		static $SpeexBandModeLookup = array();
		if (empty($SpeexBandModeLookup)) {
			$SpeexBandModeLookup[0] = 'narrow';
			$SpeexBandModeLookup[1] = 'wide';
			$SpeexBandModeLookup[2] = 'ultra-wide';
		}
		return (isset($SpeexBandModeLookup[$mode]) ? $SpeexBandModeLookup[$mode] : null);
	}


	static function OggPageSegmentLength($OggInfoArray, $SegmentNumber=1) {
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


	static function get_quality_from_nominal_bitrate($nominal_bitrate) {

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