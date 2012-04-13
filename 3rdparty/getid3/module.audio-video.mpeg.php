<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.audio-video.mpeg.php                                 //
// module for analyzing MPEG files                             //
// dependencies: module.audio.mp3.php                          //
//                                                            ///
/////////////////////////////////////////////////////////////////

getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.audio.mp3.php', __FILE__, true);

define('GETID3_MPEG_VIDEO_PICTURE_START',   "\x00\x00\x01\x00");
define('GETID3_MPEG_VIDEO_USER_DATA_START', "\x00\x00\x01\xB2");
define('GETID3_MPEG_VIDEO_SEQUENCE_HEADER', "\x00\x00\x01\xB3");
define('GETID3_MPEG_VIDEO_SEQUENCE_ERROR',  "\x00\x00\x01\xB4");
define('GETID3_MPEG_VIDEO_EXTENSION_START', "\x00\x00\x01\xB5");
define('GETID3_MPEG_VIDEO_SEQUENCE_END',    "\x00\x00\x01\xB7");
define('GETID3_MPEG_VIDEO_GROUP_START',     "\x00\x00\x01\xB8");
define('GETID3_MPEG_AUDIO_START',           "\x00\x00\x01\xC0");


class getid3_mpeg extends getid3_handler
{

	function Analyze() {
		$info = &$this->getid3->info;

		if ($info['avdataend'] <= $info['avdataoffset']) {
			$info['error'][] = '"avdataend" ('.$info['avdataend'].') is unexpectedly less-than-or-equal-to "avdataoffset" ('.$info['avdataoffset'].')';
			return false;
		}
		$info['fileformat'] = 'mpeg';
		fseek($this->getid3->fp, $info['avdataoffset'], SEEK_SET);
		$MPEGstreamData       = fread($this->getid3->fp, min(100000, $info['avdataend'] - $info['avdataoffset']));
		$MPEGstreamDataLength = strlen($MPEGstreamData);

		$foundVideo = true;
		$VideoChunkOffset = 0;
		while (substr($MPEGstreamData, $VideoChunkOffset++, 4) !== GETID3_MPEG_VIDEO_SEQUENCE_HEADER) {
			if ($VideoChunkOffset >= $MPEGstreamDataLength) {
				$foundVideo = false;
				break;
			}
		}
		if ($foundVideo) {

			// Start code                       32 bits
			// horizontal frame size            12 bits
			// vertical frame size              12 bits
			// pixel aspect ratio                4 bits
			// frame rate                        4 bits
			// bitrate                          18 bits
			// marker bit                        1 bit
			// VBV buffer size                  10 bits
			// constrained parameter flag        1 bit
			// intra quant. matrix flag          1 bit
			// intra quant. matrix values      512 bits (present if matrix flag == 1)
			// non-intra quant. matrix flag      1 bit
			// non-intra quant. matrix values  512 bits (present if matrix flag == 1)

			$info['video']['dataformat'] = 'mpeg';

			$VideoChunkOffset += (strlen(GETID3_MPEG_VIDEO_SEQUENCE_HEADER) - 1);

			$FrameSizeDWORD = getid3_lib::BigEndian2Int(substr($MPEGstreamData, $VideoChunkOffset, 3));
			$VideoChunkOffset += 3;

			$AspectRatioFrameRateDWORD = getid3_lib::BigEndian2Int(substr($MPEGstreamData, $VideoChunkOffset, 1));
			$VideoChunkOffset += 1;

			$assortedinformation = getid3_lib::BigEndian2Bin(substr($MPEGstreamData, $VideoChunkOffset, 4));
			$VideoChunkOffset += 4;

			$info['mpeg']['video']['raw']['framesize_horizontal'] = ($FrameSizeDWORD & 0xFFF000) >> 12; // 12 bits for horizontal frame size
			$info['mpeg']['video']['raw']['framesize_vertical']   = ($FrameSizeDWORD & 0x000FFF);       // 12 bits for vertical frame size
			$info['mpeg']['video']['raw']['pixel_aspect_ratio']   = ($AspectRatioFrameRateDWORD & 0xF0) >> 4;
			$info['mpeg']['video']['raw']['frame_rate']           = ($AspectRatioFrameRateDWORD & 0x0F);

			$info['mpeg']['video']['framesize_horizontal'] = $info['mpeg']['video']['raw']['framesize_horizontal'];
			$info['mpeg']['video']['framesize_vertical']   = $info['mpeg']['video']['raw']['framesize_vertical'];

			$info['mpeg']['video']['pixel_aspect_ratio']      = $this->MPEGvideoAspectRatioLookup($info['mpeg']['video']['raw']['pixel_aspect_ratio']);
			$info['mpeg']['video']['pixel_aspect_ratio_text'] = $this->MPEGvideoAspectRatioTextLookup($info['mpeg']['video']['raw']['pixel_aspect_ratio']);
			$info['mpeg']['video']['frame_rate']              = $this->MPEGvideoFramerateLookup($info['mpeg']['video']['raw']['frame_rate']);

			$info['mpeg']['video']['raw']['bitrate']                =        getid3_lib::Bin2Dec(substr($assortedinformation,  0, 18));
			$info['mpeg']['video']['raw']['marker_bit']             = (bool) getid3_lib::Bin2Dec(substr($assortedinformation, 18,  1));
			$info['mpeg']['video']['raw']['vbv_buffer_size']        =        getid3_lib::Bin2Dec(substr($assortedinformation, 19, 10));
			$info['mpeg']['video']['raw']['constrained_param_flag'] = (bool) getid3_lib::Bin2Dec(substr($assortedinformation, 29,  1));
			$info['mpeg']['video']['raw']['intra_quant_flag']       = (bool) getid3_lib::Bin2Dec(substr($assortedinformation, 30,  1));
			if ($info['mpeg']['video']['raw']['intra_quant_flag']) {

				// read 512 bits
				$info['mpeg']['video']['raw']['intra_quant']          = getid3_lib::BigEndian2Bin(substr($MPEGstreamData, $VideoChunkOffset, 64));
				$VideoChunkOffset += 64;

				$info['mpeg']['video']['raw']['non_intra_quant_flag'] = (bool) getid3_lib::Bin2Dec(substr($info['mpeg']['video']['raw']['intra_quant'], 511,  1));
				$info['mpeg']['video']['raw']['intra_quant']          =        getid3_lib::Bin2Dec(substr($assortedinformation, 31,  1)).substr(getid3_lib::BigEndian2Bin(substr($MPEGstreamData, $VideoChunkOffset, 64)), 0, 511);

				if ($info['mpeg']['video']['raw']['non_intra_quant_flag']) {
					$info['mpeg']['video']['raw']['non_intra_quant'] = substr($MPEGstreamData, $VideoChunkOffset, 64);
					$VideoChunkOffset += 64;
				}

			} else {

				$info['mpeg']['video']['raw']['non_intra_quant_flag'] = (bool) getid3_lib::Bin2Dec(substr($assortedinformation, 31,  1));
				if ($info['mpeg']['video']['raw']['non_intra_quant_flag']) {
					$info['mpeg']['video']['raw']['non_intra_quant'] = substr($MPEGstreamData, $VideoChunkOffset, 64);
					$VideoChunkOffset += 64;
				}

			}

			if ($info['mpeg']['video']['raw']['bitrate'] == 0x3FFFF) { // 18 set bits

				$info['warning'][] = 'This version of getID3() ['.$this->getid3->version().'] cannot determine average bitrate of VBR MPEG video files';
				$info['mpeg']['video']['bitrate_mode'] = 'vbr';

			} else {

				$info['mpeg']['video']['bitrate']      = $info['mpeg']['video']['raw']['bitrate'] * 400;
				$info['mpeg']['video']['bitrate_mode'] = 'cbr';
				$info['video']['bitrate']              = $info['mpeg']['video']['bitrate'];

			}

			$info['video']['resolution_x']       = $info['mpeg']['video']['framesize_horizontal'];
			$info['video']['resolution_y']       = $info['mpeg']['video']['framesize_vertical'];
			$info['video']['frame_rate']         = $info['mpeg']['video']['frame_rate'];
			$info['video']['bitrate_mode']       = $info['mpeg']['video']['bitrate_mode'];
			$info['video']['pixel_aspect_ratio'] = $info['mpeg']['video']['pixel_aspect_ratio'];
			$info['video']['lossless']           = false;
			$info['video']['bits_per_sample']    = 24;

		} else {

			$info['error'][] = 'Could not find start of video block in the first 100,000 bytes (or before end of file) - this might not be an MPEG-video file?';

		}

		//0x000001B3 begins the sequence_header of every MPEG video stream.
		//But in MPEG-2, this header must immediately be followed by an
		//extension_start_code (0x000001B5) with a sequence_extension ID (1).
		//(This extension contains all the additional MPEG-2 stuff.)
		//MPEG-1 doesn't have this extension, so that's a sure way to tell the
		//difference between MPEG-1 and MPEG-2 video streams.

		if (substr($MPEGstreamData, $VideoChunkOffset, 4) == GETID3_MPEG_VIDEO_EXTENSION_START) {
			$info['video']['codec'] = 'MPEG-2';
		} else {
			$info['video']['codec'] = 'MPEG-1';
		}


		$AudioChunkOffset = 0;
		while (true) {
			while (substr($MPEGstreamData, $AudioChunkOffset++, 4) !== GETID3_MPEG_AUDIO_START) {
				if ($AudioChunkOffset >= $MPEGstreamDataLength) {
					break 2;
				}
			}

			$getid3_temp = new getID3();
			$getid3_temp->openfile($this->getid3->filename);
			$getid3_temp->info = $info;
			$getid3_mp3 = new getid3_mp3($getid3_temp);
			for ($i = 0; $i <= 7; $i++) {
				// some files have the MPEG-audio header 8 bytes after the end of the $00 $00 $01 $C0 signature, some have it up to 13 bytes (or more?) after
				// I have no idea why or what the difference is, so this is a stupid hack.
				// If anybody has any better idea of what's going on, please let me know - info@getid3.org
				fseek($getid3_temp->fp, ftell($this->getid3->fp), SEEK_SET);
				$getid3_temp->info = $info; // only overwrite real data if valid header found
				if ($getid3_mp3->decodeMPEGaudioHeader(($AudioChunkOffset + 3) + 8 + $i, $getid3_temp->info, false)) {
					$info = $getid3_temp->info;
					$info['audio']['bitrate_mode']    = 'cbr';
					$info['audio']['lossless']        = false;
					unset($getid3_temp, $getid3_mp3);
					break 2;
				}
			}
			unset($getid3_temp, $getid3_mp3);
		}

		// Temporary hack to account for interleaving overhead:
		if (!empty($info['video']['bitrate']) && !empty($info['audio']['bitrate'])) {
			$info['playtime_seconds'] = (($info['avdataend'] - $info['avdataoffset']) * 8) / ($info['video']['bitrate'] + $info['audio']['bitrate']);

			// Interleaved MPEG audio/video files have a certain amount of overhead that varies
			// by both video and audio bitrates, and not in any sensible, linear/logarithmic patter
			// Use interpolated lookup tables to approximately guess how much is overhead, because
			// playtime is calculated as filesize / total-bitrate
			$info['playtime_seconds'] *= $this->MPEGsystemNonOverheadPercentage($info['video']['bitrate'], $info['audio']['bitrate']);

			//switch ($info['video']['bitrate']) {
			//	case('5000000'):
			//		$multiplier = 0.93292642112380355828048824319889;
			//		break;
			//	case('5500000'):
			//		$multiplier = 0.93582895375200989965359777343219;
			//		break;
			//	case('6000000'):
			//		$multiplier = 0.93796247714820932532911373859139;
			//		break;
			//	case('7000000'):
			//		$multiplier = 0.9413264083635103463010117778776;
			//		break;
			//	default:
			//		$multiplier = 1;
			//		break;
			//}
			//$info['playtime_seconds'] *= $multiplier;
			//$info['warning'][] = 'Interleaved MPEG audio/video playtime may be inaccurate. With current hack should be within a few seconds of accurate. Report to info@getid3.org if off by more than 10 seconds.';
			if ($info['video']['bitrate'] < 50000) {
				$info['warning'][] = 'Interleaved MPEG audio/video playtime may be slightly inaccurate for video bitrates below 100kbps. Except in extreme low-bitrate situations, error should be less than 1%. Report to info@getid3.org if greater than this.';
			}
		}

		return true;
	}


	function MPEGsystemNonOverheadPercentage($VideoBitrate, $AudioBitrate) {
		$OverheadPercentage = 0;

		$AudioBitrate = max(min($AudioBitrate / 1000,   384), 32); // limit to range of 32kbps - 384kbps (should be only legal bitrates, but maybe VBR?)
		$VideoBitrate = max(min($VideoBitrate / 1000, 10000), 10); // limit to range of 10kbps -  10Mbps (beyond that curves flatten anyways, no big loss)


		//OMBB[audiobitrate]              = array(video-10kbps,       video-100kbps,      video-1000kbps,     video-10000kbps)
		$OverheadMultiplierByBitrate[32]  = array(0, 0.9676287944368530, 0.9802276264360310, 0.9844916183244460, 0.9852821845179940);
		$OverheadMultiplierByBitrate[48]  = array(0, 0.9779100089209830, 0.9787770035359320, 0.9846738664076130, 0.9852683013799960);
		$OverheadMultiplierByBitrate[56]  = array(0, 0.9731249855367600, 0.9776624308938040, 0.9832606361852130, 0.9843922606633340);
		$OverheadMultiplierByBitrate[64]  = array(0, 0.9755642683275760, 0.9795256705493390, 0.9836573009193170, 0.9851122539404470);
		$OverheadMultiplierByBitrate[96]  = array(0, 0.9788025247497290, 0.9798553314148700, 0.9822956869792560, 0.9834815119124690);
		$OverheadMultiplierByBitrate[128] = array(0, 0.9816940050925480, 0.9821675936072120, 0.9829756927470870, 0.9839763420152050);
		$OverheadMultiplierByBitrate[160] = array(0, 0.9825894094561180, 0.9820913399073960, 0.9823907143253970, 0.9832821783651570);
		$OverheadMultiplierByBitrate[192] = array(0, 0.9832038474336260, 0.9825731694317960, 0.9821028622712400, 0.9828262076447620);
		$OverheadMultiplierByBitrate[224] = array(0, 0.9836516298538770, 0.9824718601823890, 0.9818302180625380, 0.9823735101626480);
		$OverheadMultiplierByBitrate[256] = array(0, 0.9845863022094920, 0.9837229411967540, 0.9824521662210830, 0.9828645172100790);
		$OverheadMultiplierByBitrate[320] = array(0, 0.9849565280263180, 0.9837683142805110, 0.9822885275960400, 0.9824424382727190);
		$OverheadMultiplierByBitrate[384] = array(0, 0.9856094774357600, 0.9844573394432720, 0.9825970399837330, 0.9824673808303890);

		$BitrateToUseMin = 32;
		$BitrateToUseMax = 32;
		$previousBitrate = 32;
		foreach ($OverheadMultiplierByBitrate as $key => $value) {
			if ($AudioBitrate >= $previousBitrate) {
				$BitrateToUseMin = $previousBitrate;
			}
			if ($AudioBitrate < $key) {
				$BitrateToUseMax = $key;
				break;
			}
			$previousBitrate = $key;
		}
		$FactorA = ($BitrateToUseMax - $AudioBitrate) / ($BitrateToUseMax - $BitrateToUseMin);

		$VideoBitrateLog10 = log10($VideoBitrate);
		$VideoFactorMin1 = $OverheadMultiplierByBitrate[$BitrateToUseMin][floor($VideoBitrateLog10)];
		$VideoFactorMin2 = $OverheadMultiplierByBitrate[$BitrateToUseMax][floor($VideoBitrateLog10)];
		$VideoFactorMax1 = $OverheadMultiplierByBitrate[$BitrateToUseMin][ceil($VideoBitrateLog10)];
		$VideoFactorMax2 = $OverheadMultiplierByBitrate[$BitrateToUseMax][ceil($VideoBitrateLog10)];
		$FactorV = $VideoBitrateLog10 - floor($VideoBitrateLog10);

		$OverheadPercentage  = $VideoFactorMin1 *      $FactorA  *      $FactorV;
		$OverheadPercentage += $VideoFactorMin2 * (1 - $FactorA) *      $FactorV;
		$OverheadPercentage += $VideoFactorMax1 *      $FactorA  * (1 - $FactorV);
		$OverheadPercentage += $VideoFactorMax2 * (1 - $FactorA) * (1 - $FactorV);

		return $OverheadPercentage;
	}


	function MPEGvideoFramerateLookup($rawframerate) {
		$MPEGvideoFramerateLookup = array(0, 23.976, 24, 25, 29.97, 30, 50, 59.94, 60);
		return (isset($MPEGvideoFramerateLookup[$rawframerate]) ? (float) $MPEGvideoFramerateLookup[$rawframerate] : (float) 0);
	}

	function MPEGvideoAspectRatioLookup($rawaspectratio) {
		$MPEGvideoAspectRatioLookup = array(0, 1, 0.6735, 0.7031, 0.7615, 0.8055, 0.8437, 0.8935, 0.9157, 0.9815, 1.0255, 1.0695, 1.0950, 1.1575, 1.2015, 0);
		return (isset($MPEGvideoAspectRatioLookup[$rawaspectratio]) ? (float) $MPEGvideoAspectRatioLookup[$rawaspectratio] : (float) 0);
	}

	function MPEGvideoAspectRatioTextLookup($rawaspectratio) {
		$MPEGvideoAspectRatioTextLookup = array('forbidden', 'square pixels', '0.6735', '16:9, 625 line, PAL', '0.7615', '0.8055', '16:9, 525 line, NTSC', '0.8935', '4:3, 625 line, PAL, CCIR601', '0.9815', '1.0255', '1.0695', '4:3, 525 line, NTSC, CCIR601', '1.1575', '1.2015', 'reserved');
		return (isset($MPEGvideoAspectRatioTextLookup[$rawaspectratio]) ? $MPEGvideoAspectRatioTextLookup[$rawaspectratio] : '');
	}

}


?>