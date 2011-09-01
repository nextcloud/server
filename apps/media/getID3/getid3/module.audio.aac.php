<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.audio.aac.php                                        //
// module for analyzing AAC Audio files                        //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////


class getid3_aac
{

	// new combined constructor
	function getid3_aac(&$fd, &$ThisFileInfo, $option) {

		if ($option === 'adif') {
			$this->getAACADIFheaderFilepointer($fd, $ThisFileInfo);
		}
		elseif ($option === 'adts') {
			$this->getAACADTSheaderFilepointer($fd, $ThisFileInfo);
		}
	}



	function getAACADIFheaderFilepointer(&$fd, &$ThisFileInfo) {
		$ThisFileInfo['fileformat']          = 'aac';
		$ThisFileInfo['audio']['dataformat'] = 'aac';
		$ThisFileInfo['audio']['lossless']   = false;

		fseek($fd, $ThisFileInfo['avdataoffset'], SEEK_SET);
		$AACheader = fread($fd, 1024);
		$offset    = 0;

		if (substr($AACheader, 0, 4) == 'ADIF') {

			// http://faac.sourceforge.net/wiki/index.php?page=ADIF

			// http://libmpeg.org/mpeg4/doc/w2203tfs.pdf
			// adif_header() {
			//     adif_id                                32
			//     copyright_id_present                    1
			//     if( copyright_id_present )
			//         copyright_id                       72
			//     original_copy                           1
			//     home                                    1
			//     bitstream_type                          1
			//     bitrate                                23
			//     num_program_config_elements             4
			//     for (i = 0; i < num_program_config_elements + 1; i++ ) {
			//         if( bitstream_type == '0' )
			//             adif_buffer_fullness           20
			//         program_config_element()
			//     }
			// }

			$AACheaderBitstream = getid3_lib::BigEndian2Bin($AACheader);
			$bitoffset          = 0;

			$ThisFileInfo['aac']['header_type']                   = 'ADIF';
			$bitoffset += 32;
			$ThisFileInfo['aac']['header']['mpeg_version']        = 4;

			$ThisFileInfo['aac']['header']['copyright']           = (bool) (substr($AACheaderBitstream, $bitoffset, 1) == '1');
			$bitoffset += 1;
			if ($ThisFileInfo['aac']['header']['copyright']) {
				$ThisFileInfo['aac']['header']['copyright_id']    = getid3_lib::Bin2String(substr($AACheaderBitstream, $bitoffset, 72));
				$bitoffset += 72;
			}
			$ThisFileInfo['aac']['header']['original_copy']       = (bool) (substr($AACheaderBitstream, $bitoffset, 1) == '1');
			$bitoffset += 1;
			$ThisFileInfo['aac']['header']['home']                = (bool) (substr($AACheaderBitstream, $bitoffset, 1) == '1');
			$bitoffset += 1;
			$ThisFileInfo['aac']['header']['is_vbr']              = (bool) (substr($AACheaderBitstream, $bitoffset, 1) == '1');
			$bitoffset += 1;
			if ($ThisFileInfo['aac']['header']['is_vbr']) {
				$ThisFileInfo['audio']['bitrate_mode']            = 'vbr';
				$ThisFileInfo['aac']['header']['bitrate_max']     = getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 23));
				$bitoffset += 23;
			} else {
				$ThisFileInfo['audio']['bitrate_mode']            = 'cbr';
				$ThisFileInfo['aac']['header']['bitrate']         = getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 23));
				$bitoffset += 23;
				$ThisFileInfo['audio']['bitrate']                 = $ThisFileInfo['aac']['header']['bitrate'];
			}
			if ($ThisFileInfo['audio']['bitrate'] == 0) {
				$ThisFileInfo['error'][] = 'Corrupt AAC file: bitrate_audio == zero';
				return false;
			}
			$ThisFileInfo['aac']['header']['num_program_configs'] = 1 + getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 4));
			$bitoffset += 4;

			for ($i = 0; $i < $ThisFileInfo['aac']['header']['num_program_configs']; $i++) {
				// http://www.audiocoding.com/wiki/index.php?page=program_config_element

				// buffer_fullness                       20

				// element_instance_tag                   4
				// object_type                            2
				// sampling_frequency_index               4
				// num_front_channel_elements             4
				// num_side_channel_elements              4
				// num_back_channel_elements              4
				// num_lfe_channel_elements               2
				// num_assoc_data_elements                3
				// num_valid_cc_elements                  4
				// mono_mixdown_present                   1
				// mono_mixdown_element_number            4   if mono_mixdown_present == 1
				// stereo_mixdown_present                 1
				// stereo_mixdown_element_number          4   if stereo_mixdown_present == 1
				// matrix_mixdown_idx_present             1
				// matrix_mixdown_idx                     2   if matrix_mixdown_idx_present == 1
				// pseudo_surround_enable                 1   if matrix_mixdown_idx_present == 1
				// for (i = 0; i < num_front_channel_elements; i++) {
				//     front_element_is_cpe[i]            1
				//     front_element_tag_select[i]        4
				// }
				// for (i = 0; i < num_side_channel_elements; i++) {
				//     side_element_is_cpe[i]             1
				//     side_element_tag_select[i]         4
				// }
				// for (i = 0; i < num_back_channel_elements; i++) {
				//     back_element_is_cpe[i]             1
				//     back_element_tag_select[i]         4
				// }
				// for (i = 0; i < num_lfe_channel_elements; i++) {
				//     lfe_element_tag_select[i]          4
				// }
				// for (i = 0; i < num_assoc_data_elements; i++) {
				//     assoc_data_element_tag_select[i]   4
				// }
				// for (i = 0; i < num_valid_cc_elements; i++) {
				//     cc_element_is_ind_sw[i]            1
				//     valid_cc_element_tag_select[i]     4
				// }
				// byte_alignment()                       VAR
				// comment_field_bytes                    8
				// for (i = 0; i < comment_field_bytes; i++) {
				//     comment_field_data[i]              8
				// }

				if (!$ThisFileInfo['aac']['header']['is_vbr']) {
					$ThisFileInfo['aac']['program_configs'][$i]['buffer_fullness']        = getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 20));
					$bitoffset += 20;
				}
				$ThisFileInfo['aac']['program_configs'][$i]['element_instance_tag']       = getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 4));
				$bitoffset += 4;
				$ThisFileInfo['aac']['program_configs'][$i]['object_type']                = getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 2));
				$bitoffset += 2;
				$ThisFileInfo['aac']['program_configs'][$i]['sampling_frequency_index']   = getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 4));
				$bitoffset += 4;
				$ThisFileInfo['aac']['program_configs'][$i]['num_front_channel_elements'] = getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 4));
				$bitoffset += 4;
				$ThisFileInfo['aac']['program_configs'][$i]['num_side_channel_elements']  = getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 4));
				$bitoffset += 4;
				$ThisFileInfo['aac']['program_configs'][$i]['num_back_channel_elements']  = getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 4));
				$bitoffset += 4;
				$ThisFileInfo['aac']['program_configs'][$i]['num_lfe_channel_elements']   = getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 2));
				$bitoffset += 2;
				$ThisFileInfo['aac']['program_configs'][$i]['num_assoc_data_elements']    = getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 3));
				$bitoffset += 3;
				$ThisFileInfo['aac']['program_configs'][$i]['num_valid_cc_elements']      = getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 4));
				$bitoffset += 4;
				$ThisFileInfo['aac']['program_configs'][$i]['mono_mixdown_present']       = (bool) getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 1));
				$bitoffset += 1;
				if ($ThisFileInfo['aac']['program_configs'][$i]['mono_mixdown_present']) {
					$ThisFileInfo['aac']['program_configs'][$i]['mono_mixdown_element_number']    = getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 4));
					$bitoffset += 4;
				}
				$ThisFileInfo['aac']['program_configs'][$i]['stereo_mixdown_present']             = (bool) getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 1));
				$bitoffset += 1;
				if ($ThisFileInfo['aac']['program_configs'][$i]['stereo_mixdown_present']) {
					$ThisFileInfo['aac']['program_configs'][$i]['stereo_mixdown_element_number']  = getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 4));
					$bitoffset += 4;
				}
				$ThisFileInfo['aac']['program_configs'][$i]['matrix_mixdown_idx_present']         = (bool) getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 1));
				$bitoffset += 1;
				if ($ThisFileInfo['aac']['program_configs'][$i]['matrix_mixdown_idx_present']) {
					$ThisFileInfo['aac']['program_configs'][$i]['matrix_mixdown_idx']             = getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 2));
					$bitoffset += 2;
					$ThisFileInfo['aac']['program_configs'][$i]['pseudo_surround_enable']         = (bool) getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 1));
					$bitoffset += 1;
				}
				for ($j = 0; $j < $ThisFileInfo['aac']['program_configs'][$i]['num_front_channel_elements']; $j++) {
					$ThisFileInfo['aac']['program_configs'][$i]['front_element_is_cpe'][$j]     = (bool) getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 1));
					$bitoffset += 1;
					$ThisFileInfo['aac']['program_configs'][$i]['front_element_tag_select'][$j] = getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 4));
					$bitoffset += 4;
				}
				for ($j = 0; $j < $ThisFileInfo['aac']['program_configs'][$i]['num_side_channel_elements']; $j++) {
					$ThisFileInfo['aac']['program_configs'][$i]['side_element_is_cpe'][$j]     = (bool) getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 1));
					$bitoffset += 1;
					$ThisFileInfo['aac']['program_configs'][$i]['side_element_tag_select'][$j] = getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 4));
					$bitoffset += 4;
				}
				for ($j = 0; $j < $ThisFileInfo['aac']['program_configs'][$i]['num_back_channel_elements']; $j++) {
					$ThisFileInfo['aac']['program_configs'][$i]['back_element_is_cpe'][$j]     = (bool) getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 1));
					$bitoffset += 1;
					$ThisFileInfo['aac']['program_configs'][$i]['back_element_tag_select'][$j] = getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 4));
					$bitoffset += 4;
				}
				for ($j = 0; $j < $ThisFileInfo['aac']['program_configs'][$i]['num_lfe_channel_elements']; $j++) {
					$ThisFileInfo['aac']['program_configs'][$i]['lfe_element_tag_select'][$j] = getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 4));
					$bitoffset += 4;
				}
				for ($j = 0; $j < $ThisFileInfo['aac']['program_configs'][$i]['num_assoc_data_elements']; $j++) {
					$ThisFileInfo['aac']['program_configs'][$i]['assoc_data_element_tag_select'][$j] = getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 4));
					$bitoffset += 4;
				}
				for ($j = 0; $j < $ThisFileInfo['aac']['program_configs'][$i]['num_valid_cc_elements']; $j++) {
					$ThisFileInfo['aac']['program_configs'][$i]['cc_element_is_ind_sw'][$j]          = (bool) getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 1));
					$bitoffset += 1;
					$ThisFileInfo['aac']['program_configs'][$i]['valid_cc_element_tag_select'][$j]   = getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 4));
					$bitoffset += 4;
				}

				$bitoffset = ceil($bitoffset / 8) * 8;

				$ThisFileInfo['aac']['program_configs'][$i]['comment_field_bytes'] = getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 8));
				$bitoffset += 8;
				$ThisFileInfo['aac']['program_configs'][$i]['comment_field']       = getid3_lib::Bin2String(substr($AACheaderBitstream, $bitoffset, 8 * $ThisFileInfo['aac']['program_configs'][$i]['comment_field_bytes']));
				$bitoffset += 8 * $ThisFileInfo['aac']['program_configs'][$i]['comment_field_bytes'];


				$ThisFileInfo['aac']['header']['profile_text']                      = $this->AACprofileLookup($ThisFileInfo['aac']['program_configs'][$i]['object_type'], $ThisFileInfo['aac']['header']['mpeg_version']);
				$ThisFileInfo['aac']['program_configs'][$i]['sampling_frequency']   = $this->AACsampleRateLookup($ThisFileInfo['aac']['program_configs'][$i]['sampling_frequency_index']);
				$ThisFileInfo['audio']['sample_rate']                               = $ThisFileInfo['aac']['program_configs'][$i]['sampling_frequency'];
				$ThisFileInfo['audio']['channels']                                  = $this->AACchannelCountCalculate($ThisFileInfo['aac']['program_configs'][$i]);
				if ($ThisFileInfo['aac']['program_configs'][$i]['comment_field']) {
					$ThisFileInfo['aac']['comments'][]                          = $ThisFileInfo['aac']['program_configs'][$i]['comment_field'];
				}
			}
			$ThisFileInfo['playtime_seconds'] = (($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']) * 8) / $ThisFileInfo['audio']['bitrate'];

			$ThisFileInfo['audio']['encoder_options'] = $ThisFileInfo['aac']['header_type'].' '.$ThisFileInfo['aac']['header']['profile_text'];



			return true;

		} else {

			unset($ThisFileInfo['fileformat']);
			unset($ThisFileInfo['aac']);
			$ThisFileInfo['error'][] = 'AAC-ADIF synch not found at offset '.$ThisFileInfo['avdataoffset'].' (expected "ADIF", found "'.substr($AACheader, 0, 4).'" instead)';
			return false;

		}

	}


	function getAACADTSheaderFilepointer(&$fd, &$ThisFileInfo, $MaxFramesToScan=1000000, $ReturnExtendedInfo=false) {
		// based loosely on code from AACfile by Jurgen Faul  <jfaulØgmx.de>
		// http://jfaul.de/atl  or  http://j-faul.virtualave.net/atl/atl.html


		// http://faac.sourceforge.net/wiki/index.php?page=ADTS

		// * ADTS Fixed Header: these don't change from frame to frame
		// syncword                                       12    always: '111111111111'
		// ID                                              1    0: MPEG-4, 1: MPEG-2
		// layer                                           2    always: '00'
		// protection_absent                               1
		// profile                                         2
		// sampling_frequency_index                        4
		// private_bit                                     1
		// channel_configuration                           3
		// original/copy                                   1
		// home                                            1
		// emphasis                                        2    only if ID == 0 (ie MPEG-4)

		// * ADTS Variable Header: these can change from frame to frame
		// copyright_identification_bit                    1
		// copyright_identification_start                  1
		// aac_frame_length                               13    length of the frame including header (in bytes)
		// adts_buffer_fullness                           11    0x7FF indicates VBR
		// no_raw_data_blocks_in_frame                     2

		// * ADTS Error check
		// crc_check                                      16    only if protection_absent == 0

		$byteoffset  = 0;
		$framenumber = 0;

		// Init bit pattern array
		static $decbin = array();

		// Populate $bindec
		for ($i = 0; $i < 256; $i++) {
			$decbin[chr($i)] = str_pad(decbin($i), 8, '0', STR_PAD_LEFT);
		}

		// used to calculate bitrate below
		$BitrateCache = array();


		while (true) {
			// breaks out when end-of-file encountered, or invalid data found,
			// or MaxFramesToScan frames have been scanned

			if ($byteoffset >= pow(2, 31)) {
				$ThisFileInfo['warning'][] = 'Unable to parse AAC file beyond '.ftell($fd).' (PHP does not support file operations beyond 2GB)';
				return false;
			}
			fseek($fd, $byteoffset, SEEK_SET);

			// First get substring
			$substring = fread($fd, 10);
			$substringlength = strlen($substring);
			if ($substringlength != 10) {
				$ThisFileInfo['error'][] = 'Failed to read 10 bytes at offset '.(ftell($fd) - $substringlength).' (only read '.$substringlength.' bytes)';
				return false;
			}

			// Initialise $AACheaderBitstream
			$AACheaderBitstream = '';

			// Loop thru substring chars
			for ($i = 0; $i < 10; $i++) {
				$AACheaderBitstream .= $decbin[$substring{$i}];
			}

			$bitoffset = 0;

			$synctest = bindec(substr($AACheaderBitstream, $bitoffset, 12));

			$bitoffset += 12;
			if ($synctest != 0x0FFF) {
				$ThisFileInfo['error'][] = 'Synch pattern (0x0FFF) not found at offset '.(ftell($fd) - 10).' (found 0x0'.strtoupper(dechex($synctest)).' instead)';
				if ($ThisFileInfo['fileformat'] == 'aac') {
					return true;
				}
				return false;
			}

			// Gather info for first frame only - this takes time to do 1000 times!
			if ($framenumber > 0) {

				if (!$AACheaderBitstream[$bitoffset]) {

					// MPEG-4
					$bitoffset += 20;

				} else {

					// MPEG-2
					$bitoffset += 18;

				}

			} else {

				$ThisFileInfo['aac']['header_type']                      = 'ADTS';
				$ThisFileInfo['aac']['header']['synch']                  = $synctest;
				$ThisFileInfo['fileformat']                              = 'aac';
				$ThisFileInfo['audio']['dataformat']                     = 'aac';

				$ThisFileInfo['aac']['header']['mpeg_version']           = ((substr($AACheaderBitstream, $bitoffset, 1) == '0') ? 4 : 2);
				$bitoffset += 1;
				$ThisFileInfo['aac']['header']['layer']                  = getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 2));
				$bitoffset += 2;
				if ($ThisFileInfo['aac']['header']['layer'] != 0) {
					$ThisFileInfo['error'][] = 'Layer error - expected 0x00, found 0x'.dechex($ThisFileInfo['aac']['header']['layer']).' instead';
					return false;
				}
				$ThisFileInfo['aac']['header']['crc_present']            = ((substr($AACheaderBitstream, $bitoffset, 1) == '0') ? true : false);
				$bitoffset += 1;
				$ThisFileInfo['aac']['header']['profile_id']             = getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 2));
				$bitoffset += 2;
				$ThisFileInfo['aac']['header']['profile_text']           = $this->AACprofileLookup($ThisFileInfo['aac']['header']['profile_id'], $ThisFileInfo['aac']['header']['mpeg_version']);

				$ThisFileInfo['aac']['header']['sample_frequency_index'] = getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 4));
				$bitoffset += 4;
				$ThisFileInfo['aac']['header']['sample_frequency']       = $this->AACsampleRateLookup($ThisFileInfo['aac']['header']['sample_frequency_index']);
				if ($ThisFileInfo['aac']['header']['sample_frequency'] == 0) {
					$ThisFileInfo['error'][] = 'Corrupt AAC file: sample_frequency == zero';
					return false;
				}
				$ThisFileInfo['audio']['sample_rate']                    = $ThisFileInfo['aac']['header']['sample_frequency'];

				$ThisFileInfo['aac']['header']['private']                = (bool) getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 1));
				$bitoffset += 1;
				$ThisFileInfo['aac']['header']['channel_configuration']  = getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 3));
				$bitoffset += 3;
				$ThisFileInfo['audio']['channels']                       = $ThisFileInfo['aac']['header']['channel_configuration'];
				$ThisFileInfo['aac']['header']['original']               = (bool) getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 1));
				$bitoffset += 1;
				$ThisFileInfo['aac']['header']['home']                   = (bool) getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 1));
				$bitoffset += 1;

				if ($ThisFileInfo['aac']['header']['mpeg_version'] == 4) {
					$ThisFileInfo['aac']['header']['emphasis']           = getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 2));
					$bitoffset += 2;
				}

				if ($ReturnExtendedInfo) {

					$ThisFileInfo['aac'][$framenumber]['copyright_id_bit']   = (bool) getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 1));
					$bitoffset += 1;
					$ThisFileInfo['aac'][$framenumber]['copyright_id_start'] = (bool) getid3_lib::Bin2Dec(substr($AACheaderBitstream, $bitoffset, 1));
					$bitoffset += 1;

				} else {

					$bitoffset += 2;

				}

			}

			$FrameLength = bindec(substr($AACheaderBitstream, $bitoffset, 13));

			if (!isset($BitrateCache[$FrameLength])) {
				$BitrateCache[$FrameLength] = ($ThisFileInfo['aac']['header']['sample_frequency'] / 1024) * $FrameLength * 8;
			}
			@$ThisFileInfo['aac']['bitrate_distribution'][$BitrateCache[$FrameLength]]++;

			$ThisFileInfo['aac'][$framenumber]['aac_frame_length']     = $FrameLength;
			$bitoffset += 13;
			$ThisFileInfo['aac'][$framenumber]['adts_buffer_fullness'] = bindec(substr($AACheaderBitstream, $bitoffset, 11));
			$bitoffset += 11;
			if ($ThisFileInfo['aac'][$framenumber]['adts_buffer_fullness'] == 0x07FF) {
				$ThisFileInfo['audio']['bitrate_mode'] = 'vbr';
			} else {
				$ThisFileInfo['audio']['bitrate_mode'] = 'cbr';
			}
			$ThisFileInfo['aac'][$framenumber]['num_raw_data_blocks']  = bindec(substr($AACheaderBitstream, $bitoffset, 2));
			$bitoffset += 2;

			if ($ThisFileInfo['aac']['header']['crc_present']) {
				//$ThisFileInfo['aac'][$framenumber]['crc']              = bindec(substr($AACheaderBitstream, $bitoffset, 16));
				$bitoffset += 16;
			}

			if (!$ReturnExtendedInfo) {
				unset($ThisFileInfo['aac'][$framenumber]);
			}

			$byteoffset += $FrameLength;
			if ((++$framenumber < $MaxFramesToScan) && (($byteoffset + 10) < $ThisFileInfo['avdataend'])) {

				// keep scanning

			} else {

				$ThisFileInfo['aac']['frames']    = $framenumber;
				$ThisFileInfo['playtime_seconds'] = ($ThisFileInfo['avdataend'] / $byteoffset) * (($framenumber * 1024) / $ThisFileInfo['aac']['header']['sample_frequency']);  // (1 / % of file scanned) * (samples / (samples/sec)) = seconds
				if ($ThisFileInfo['playtime_seconds'] == 0) {
					$ThisFileInfo['error'][] = 'Corrupt AAC file: playtime_seconds == zero';
					return false;
				}
				$ThisFileInfo['audio']['bitrate']    = (($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']) * 8) / $ThisFileInfo['playtime_seconds'];
				ksort($ThisFileInfo['aac']['bitrate_distribution']);

				$ThisFileInfo['audio']['encoder_options'] = $ThisFileInfo['aac']['header_type'].' '.$ThisFileInfo['aac']['header']['profile_text'];

				return true;

			}
		}
		// should never get here.
	}

	function AACsampleRateLookup($samplerateid) {
		static $AACsampleRateLookup = array();
		if (empty($AACsampleRateLookup)) {
			$AACsampleRateLookup[0]  = 96000;
			$AACsampleRateLookup[1]  = 88200;
			$AACsampleRateLookup[2]  = 64000;
			$AACsampleRateLookup[3]  = 48000;
			$AACsampleRateLookup[4]  = 44100;
			$AACsampleRateLookup[5]  = 32000;
			$AACsampleRateLookup[6]  = 24000;
			$AACsampleRateLookup[7]  = 22050;
			$AACsampleRateLookup[8]  = 16000;
			$AACsampleRateLookup[9]  = 12000;
			$AACsampleRateLookup[10] = 11025;
			$AACsampleRateLookup[11] = 8000;
			$AACsampleRateLookup[12] = 0;
			$AACsampleRateLookup[13] = 0;
			$AACsampleRateLookup[14] = 0;
			$AACsampleRateLookup[15] = 0;
		}
		return (isset($AACsampleRateLookup[$samplerateid]) ? $AACsampleRateLookup[$samplerateid] : 'invalid');
	}

	function AACprofileLookup($profileid, $mpegversion) {
		static $AACprofileLookup = array();
		if (empty($AACprofileLookup)) {
			$AACprofileLookup[2][0]  = 'Main profile';
			$AACprofileLookup[2][1]  = 'Low Complexity profile (LC)';
			$AACprofileLookup[2][2]  = 'Scalable Sample Rate profile (SSR)';
			$AACprofileLookup[2][3]  = '(reserved)';
			$AACprofileLookup[4][0]  = 'AAC_MAIN';
			$AACprofileLookup[4][1]  = 'AAC_LC';
			$AACprofileLookup[4][2]  = 'AAC_SSR';
			$AACprofileLookup[4][3]  = 'AAC_LTP';
		}
		return (isset($AACprofileLookup[$mpegversion][$profileid]) ? $AACprofileLookup[$mpegversion][$profileid] : 'invalid');
	}

	function AACchannelCountCalculate($program_configs) {
		$channels = 0;
		for ($i = 0; $i < $program_configs['num_front_channel_elements']; $i++) {
			$channels++;
			if ($program_configs['front_element_is_cpe'][$i]) {
				// each front element is channel pair (CPE = Channel Pair Element)
				$channels++;
			}
		}
		for ($i = 0; $i < $program_configs['num_side_channel_elements']; $i++) {
			$channels++;
			if ($program_configs['side_element_is_cpe'][$i]) {
				// each side element is channel pair (CPE = Channel Pair Element)
				$channels++;
			}
		}
		for ($i = 0; $i < $program_configs['num_back_channel_elements']; $i++) {
			$channels++;
			if ($program_configs['back_element_is_cpe'][$i]) {
				// each back element is channel pair (CPE = Channel Pair Element)
				$channels++;
			}
		}
		for ($i = 0; $i < $program_configs['num_lfe_channel_elements']; $i++) {
			$channels++;
		}
		return $channels;
	}

}


?>