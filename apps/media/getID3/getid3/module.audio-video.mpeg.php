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


class getid3_mpeg
{

	function getid3_mpeg(&$fd, &$ThisFileInfo) {
		if ($ThisFileInfo['avdataend'] <= $ThisFileInfo['avdataoffset']) {
			$ThisFileInfo['error'][] = '"avdataend" ('.$ThisFileInfo['avdataend'].') is unexpectedly less-than-or-equal-to "avdataoffset" ('.$ThisFileInfo['avdataoffset'].')';
			return false;
		}
		$ThisFileInfo['fileformat'] = 'mpeg';
		fseek($fd, $ThisFileInfo['avdataoffset'], SEEK_SET);
		$MPEGstreamData       = fread($fd, min(100000, $ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']));
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

			$ThisFileInfo['video']['dataformat'] = 'mpeg';

			$VideoChunkOffset += (strlen(GETID3_MPEG_VIDEO_SEQUENCE_HEADER) - 1);

			$FrameSizeDWORD = getid3_lib::BigEndian2Int(substr($MPEGstreamData, $VideoChunkOffset, 3));
			$VideoChunkOffset += 3;

			$AspectRatioFrameRateDWORD = getid3_lib::BigEndian2Int(substr($MPEGstreamData, $VideoChunkOffset, 1));
			$VideoChunkOffset += 1;

			$assortedinformation = getid3_lib::BigEndian2Bin(substr($MPEGstreamData, $VideoChunkOffset, 4));
			$VideoChunkOffset += 4;

			$ThisFileInfo['mpeg']['video']['raw']['framesize_horizontal'] = ($FrameSizeDWORD & 0xFFF000) >> 12; // 12 bits for horizontal frame size
			$ThisFileInfo['mpeg']['video']['raw']['framesize_vertical']   = ($FrameSizeDWORD & 0x000FFF);       // 12 bits for vertical frame size
			$ThisFileInfo['mpeg']['video']['raw']['pixel_aspect_ratio']   = ($AspectRatioFrameRateDWORD & 0xF0) >> 4;
			$ThisFileInfo['mpeg']['video']['raw']['frame_rate']           = ($AspectRatioFrameRateDWORD & 0x0F);

			$ThisFileInfo['mpeg']['video']['framesize_horizontal'] = $ThisFileInfo['mpeg']['video']['raw']['framesize_horizontal'];
			$ThisFileInfo['mpeg']['video']['framesize_vertical']   = $ThisFileInfo['mpeg']['video']['raw']['framesize_vertical'];

			$ThisFileInfo['mpeg']['video']['pixel_aspect_ratio']      = $this->MPEGvideoAspectRatioLookup($ThisFileInfo['mpeg']['video']['raw']['pixel_aspect_ratio']);
			$ThisFileInfo['mpeg']['video']['pixel_aspect_ratio_text'] = $this->MPEGvideoAspectRatioTextLookup($ThisFileInfo['mpeg']['video']['raw']['pixel_aspect_ratio']);
			$ThisFileInfo['mpeg']['video']['frame_rate']              = $this->MPEGvideoFramerateLookup($ThisFileInfo['mpeg']['video']['raw']['frame_rate']);

			$ThisFileInfo['mpeg']['video']['raw']['bitrate']                =        getid3_lib::Bin2Dec(substr($assortedinformation,  0, 18));
			$ThisFileInfo['mpeg']['video']['raw']['marker_bit']             = (bool) getid3_lib::Bin2Dec(substr($assortedinformation, 18,  1));
			$ThisFileInfo['mpeg']['video']['raw']['vbv_buffer_size']        =        getid3_lib::Bin2Dec(substr($assortedinformation, 19, 10));
			$ThisFileInfo['mpeg']['video']['raw']['constrained_param_flag'] = (bool) getid3_lib::Bin2Dec(substr($assortedinformation, 29,  1));
			$ThisFileInfo['mpeg']['video']['raw']['intra_quant_flag']       = (bool) getid3_lib::Bin2Dec(substr($assortedinformation, 30,  1));
			if ($ThisFileInfo['mpeg']['video']['raw']['intra_quant_flag']) {

				// read 512 bits
				$ThisFileInfo['mpeg']['video']['raw']['intra_quant']          = getid3_lib::BigEndian2Bin(substr($MPEGstreamData, $VideoChunkOffset, 64));
				$VideoChunkOffset += 64;

				$ThisFileInfo['mpeg']['video']['raw']['non_intra_quant_flag'] = (bool) getid3_lib::Bin2Dec(substr($ThisFileInfo['mpeg']['video']['raw']['intra_quant'], 511,  1));
				$ThisFileInfo['mpeg']['video']['raw']['intra_quant']          =        getid3_lib::Bin2Dec(substr($assortedinformation, 31,  1)).substr(getid3_lib::BigEndian2Bin(substr($MPEGstreamData, $VideoChunkOffset, 64)), 0, 511);

				if ($ThisFileInfo['mpeg']['video']['raw']['non_intra_quant_flag']) {
					$ThisFileInfo['mpeg']['video']['raw']['non_intra_quant'] = substr($MPEGstreamData, $VideoChunkOffset, 64);
					$VideoChunkOffset += 64;
				}

			} else {

				$ThisFileInfo['mpeg']['video']['raw']['non_intra_quant_flag'] = (bool) getid3_lib::Bin2Dec(substr($assortedinformation, 31,  1));
				if ($ThisFileInfo['mpeg']['video']['raw']['non_intra_quant_flag']) {
					$ThisFileInfo['mpeg']['video']['raw']['non_intra_quant'] = substr($MPEGstreamData, $VideoChunkOffset, 64);
					$VideoChunkOffset += 64;
				}

			}

			if ($ThisFileInfo['mpeg']['video']['raw']['bitrate'] == 0x3FFFF) { // 18 set bits

				$ThisFileInfo['warning'][] = 'This version of getID3() ['.GETID3_VERSION.'] cannot determine average bitrate of VBR MPEG video files';
				$ThisFileInfo['mpeg']['video']['bitrate_mode'] = 'vbr';

			} else {

				$ThisFileInfo['mpeg']['video']['bitrate']      = $ThisFileInfo['mpeg']['video']['raw']['bitrate'] * 400;
				$ThisFileInfo['mpeg']['video']['bitrate_mode'] = 'cbr';
				$ThisFileInfo['video']['bitrate']              = $ThisFileInfo['mpeg']['video']['bitrate'];

			}

			$ThisFileInfo['video']['resolution_x']       = $ThisFileInfo['mpeg']['video']['framesize_horizontal'];
			$ThisFileInfo['video']['resolution_y']       = $ThisFileInfo['mpeg']['video']['framesize_vertical'];
			$ThisFileInfo['video']['frame_rate']         = $ThisFileInfo['mpeg']['video']['frame_rate'];
			$ThisFileInfo['video']['bitrate_mode']       = $ThisFileInfo['mpeg']['video']['bitrate_mode'];
			$ThisFileInfo['video']['pixel_aspect_ratio'] = $ThisFileInfo['mpeg']['video']['pixel_aspect_ratio'];
			$ThisFileInfo['video']['lossless']           = false;
			$ThisFileInfo['video']['bits_per_sample']    = 24;

		} else {

			$ThisFileInfo['error'][] = 'Could not find start of video block in the first 100,000 bytes (or before end of file) - this might not be an MPEG-video file?';

		}

		//0x000001B3 begins the sequence_header of every MPEG video stream.
		//But in MPEG-2, this header must immediately be followed by an
		//extension_start_code (0x000001B5) with a sequence_extension ID (1).
		//(This extension contains all the additional MPEG-2 stuff.)
		//MPEG-1 doesn't have this extension, so that's a sure way to tell the
		//difference between MPEG-1 and MPEG-2 video streams.

		if (substr($MPEGstreamData, $VideoChunkOffset, 4) == GETID3_MPEG_VIDEO_EXTENSION_START) {
			$ThisFileInfo['video']['codec'] = 'MPEG-2';
		} else {
			$ThisFileInfo['video']['codec'] = 'MPEG-1';
		}


		$AudioChunkOffset = 0;
		while (true) {
			while (substr($MPEGstreamData, $AudioChunkOffset++, 4) !== GETID3_MPEG_AUDIO_START) {
				if ($AudioChunkOffset >= $MPEGstreamDataLength) {
					break 2;
				}
			}

			for ($i = 0; $i <= 7; $i++) {
				// some files have the MPEG-audio header 8 bytes after the end of the $00 $00 $01 $C0 signature, some have it up to 13 bytes (or more?) after
				// I have no idea why or what the difference is, so this is a stupid hack.
				// If anybody has any better idea of what's going on, please let me know - info@getid3.org

				$dummy = $ThisFileInfo;
				if (getid3_mp3::decodeMPEGaudioHeader($fd, ($AudioChunkOffset + 3) + 8 + $i, $dummy, false)) {
					$ThisFileInfo = $dummy;
					$ThisFileInfo['audio']['bitrate_mode']    = 'cbr';
					$ThisFileInfo['audio']['lossless']        = false;
					break 2;

				}
			}
		}

		// Temporary hack to account for interleaving overhead:
		if (!empty($ThisFileInfo['video']['bitrate']) && !empty($ThisFileInfo['audio']['bitrate'])) {
			$ThisFileInfo['playtime_seconds'] = (($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']) * 8) / ($ThisFileInfo['video']['bitrate'] + $ThisFileInfo['audio']['bitrate']);

			// Interleaved MPEG audio/video files have a certain amount of overhead that varies
			// by both video and audio bitrates, and not in any sensible, linear/logarithmic patter
			// Use interpolated lookup tables to approximately guess how much is overhead, because
			// playtime is calculated as filesize / total-bitrate
			$ThisFileInfo['playtime_seconds'] *= $this->MPEGsystemNonOverheadPercentage($ThisFileInfo['video']['bitrate'], $ThisFileInfo['audio']['bitrate']);

			//switch ($ThisFileInfo['video']['bitrate']) {
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
			//$ThisFileInfo['playtime_seconds'] *= $multiplier;
			//$ThisFileInfo['warning'][] = 'Interleaved MPEG audio/video playtime may be inaccurate. With current hack should be within a few seconds of accurate. Report to info@getid3.org if off by more than 10 seconds.';
			if ($ThisFileInfo['video']['bitrate'] < 50000) {
				$ThisFileInfo['warning'][] = 'Interleaved MPEG audio/video playtime may be slightly inaccurate for video bitrates below 100kbps. Except in extreme low-bitrate situations, error should be less than 1%. Report to info@getid3.org if greater than this.';
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