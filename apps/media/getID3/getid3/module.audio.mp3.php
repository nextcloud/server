<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.audio.mp3.php                                        //
// module for analyzing MP3 files                              //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////


// number of frames to scan to determine if MPEG-audio sequence is valid
// Lower this number to 5-20 for faster scanning
// Increase this number to 50+ for most accurate detection of valid VBR/CBR
// mpeg-audio streams
define('GETID3_MP3_VALID_CHECK_FRAMES', 35);


class getid3_mp3
{

	var $allow_bruteforce = false; // forces getID3() to scan the file byte-by-byte and log all the valid audio frame headers - extremely slow, unrecommended, but may provide data from otherwise-unusuable files

	function getid3_mp3(&$fd, &$ThisFileInfo) {

		if (!$this->getOnlyMPEGaudioInfo($fd, $ThisFileInfo, $ThisFileInfo['avdataoffset'])) {
			if ($this->allow_bruteforce) {
				$ThisFileInfo['error'][] = 'Rescanning file in BruteForce mode';
				$this->getOnlyMPEGaudioInfoBruteForce($fd, $ThisFileInfo);
			}
		}


		if (isset($ThisFileInfo['mpeg']['audio']['bitrate_mode'])) {
			$ThisFileInfo['audio']['bitrate_mode'] = strtolower($ThisFileInfo['mpeg']['audio']['bitrate_mode']);
		}

		if (((isset($ThisFileInfo['id3v2']['headerlength']) && ($ThisFileInfo['avdataoffset'] > $ThisFileInfo['id3v2']['headerlength'])) || (!isset($ThisFileInfo['id3v2']) && ($ThisFileInfo['avdataoffset'] > 0)))) {

			$synchoffsetwarning = 'Unknown data before synch ';
			if (isset($ThisFileInfo['id3v2']['headerlength'])) {
				$synchoffsetwarning .= '(ID3v2 header ends at '.$ThisFileInfo['id3v2']['headerlength'].', then '.($ThisFileInfo['avdataoffset'] - $ThisFileInfo['id3v2']['headerlength']).' bytes garbage, ';
			} else {
				$synchoffsetwarning .= '(should be at beginning of file, ';
			}
			$synchoffsetwarning .= 'synch detected at '.$ThisFileInfo['avdataoffset'].')';
			if (@$ThisFileInfo['audio']['bitrate_mode'] == 'cbr') {

				if (!empty($ThisFileInfo['id3v2']['headerlength']) && (($ThisFileInfo['avdataoffset'] - $ThisFileInfo['id3v2']['headerlength']) == $ThisFileInfo['mpeg']['audio']['framelength'])) {

					$synchoffsetwarning .= '. This is a known problem with some versions of LAME (3.90-3.92) DLL in CBR mode.';
					$ThisFileInfo['audio']['codec'] = 'LAME';
					$CurrentDataLAMEversionString = 'LAME3.';

				} elseif (empty($ThisFileInfo['id3v2']['headerlength']) && ($ThisFileInfo['avdataoffset'] == $ThisFileInfo['mpeg']['audio']['framelength'])) {

					$synchoffsetwarning .= '. This is a known problem with some versions of LAME (3.90 - 3.92) DLL in CBR mode.';
					$ThisFileInfo['audio']['codec'] = 'LAME';
					$CurrentDataLAMEversionString = 'LAME3.';

				}

			}
			$ThisFileInfo['warning'][] = $synchoffsetwarning;

		}

		if (isset($ThisFileInfo['mpeg']['audio']['LAME'])) {
			$ThisFileInfo['audio']['codec'] = 'LAME';
			if (!empty($ThisFileInfo['mpeg']['audio']['LAME']['long_version'])) {
				$ThisFileInfo['audio']['encoder'] = rtrim($ThisFileInfo['mpeg']['audio']['LAME']['long_version'], "\x00");
			} elseif (!empty($ThisFileInfo['mpeg']['audio']['LAME']['short_version'])) {
				$ThisFileInfo['audio']['encoder'] = rtrim($ThisFileInfo['mpeg']['audio']['LAME']['short_version'], "\x00");
			}
		}

		$CurrentDataLAMEversionString = (!empty($CurrentDataLAMEversionString) ? $CurrentDataLAMEversionString : @$ThisFileInfo['audio']['encoder']);
		if (!empty($CurrentDataLAMEversionString) && (substr($CurrentDataLAMEversionString, 0, 6) == 'LAME3.') && !preg_match('[0-9\)]', substr($CurrentDataLAMEversionString, -1))) {
			// a version number of LAME that does not end with a number like "LAME3.92"
			// or with a closing parenthesis like "LAME3.88 (alpha)"
			// or a version of LAME with the LAMEtag-not-filled-in-DLL-mode bug (3.90-3.92)

			// not sure what the actual last frame length will be, but will be less than or equal to 1441
			$PossiblyLongerLAMEversion_FrameLength = 1441;

			// Not sure what version of LAME this is - look in padding of last frame for longer version string
			$PossibleLAMEversionStringOffset = $ThisFileInfo['avdataend'] - $PossiblyLongerLAMEversion_FrameLength;
			fseek($fd, $PossibleLAMEversionStringOffset);
			$PossiblyLongerLAMEversion_Data = fread($fd, $PossiblyLongerLAMEversion_FrameLength);
			switch (substr($CurrentDataLAMEversionString, -1)) {
				case 'a':
				case 'b':
					// "LAME3.94a" will have a longer version string of "LAME3.94 (alpha)" for example
					// need to trim off "a" to match longer string
					$CurrentDataLAMEversionString = substr($CurrentDataLAMEversionString, 0, -1);
					break;
			}
			if (($PossiblyLongerLAMEversion_String = strstr($PossiblyLongerLAMEversion_Data, $CurrentDataLAMEversionString)) !== false) {
				if (substr($PossiblyLongerLAMEversion_String, 0, strlen($CurrentDataLAMEversionString)) == $CurrentDataLAMEversionString) {
					$PossiblyLongerLAMEversion_NewString = substr($PossiblyLongerLAMEversion_String, 0, strspn($PossiblyLongerLAMEversion_String, 'LAME0123456789., (abcdefghijklmnopqrstuvwxyzJFSOND)')); //"LAME3.90.3"  "LAME3.87 (beta 1, Sep 27 2000)" "LAME3.88 (beta)"
					if (strlen($PossiblyLongerLAMEversion_NewString) > strlen(@$ThisFileInfo['audio']['encoder'])) {
						$ThisFileInfo['audio']['encoder'] = $PossiblyLongerLAMEversion_NewString;
					}
				}
			}
		}
		if (!empty($ThisFileInfo['audio']['encoder'])) {
			$ThisFileInfo['audio']['encoder'] = rtrim($ThisFileInfo['audio']['encoder'], "\x00 ");
		}

		switch (@$ThisFileInfo['mpeg']['audio']['layer']) {
			case 1:
			case 2:
				$ThisFileInfo['audio']['dataformat'] = 'mp'.$ThisFileInfo['mpeg']['audio']['layer'];
				break;
		}
		if (@$ThisFileInfo['fileformat'] == 'mp3') {
			switch ($ThisFileInfo['audio']['dataformat']) {
				case 'mp1':
				case 'mp2':
				case 'mp3':
					$ThisFileInfo['fileformat'] = $ThisFileInfo['audio']['dataformat'];
					break;

				default:
					$ThisFileInfo['warning'][] = 'Expecting [audio][dataformat] to be mp1/mp2/mp3 when fileformat == mp3, [audio][dataformat] actually "'.$ThisFileInfo['audio']['dataformat'].'"';
					break;
			}
		}

		if (empty($ThisFileInfo['fileformat'])) {
			unset($ThisFileInfo['fileformat']);
			unset($ThisFileInfo['audio']['bitrate_mode']);
			unset($ThisFileInfo['avdataoffset']);
			unset($ThisFileInfo['avdataend']);
			return false;
		}

		$ThisFileInfo['mime_type']         = 'audio/mpeg';
		$ThisFileInfo['audio']['lossless'] = false;

		// Calculate playtime
		if (!isset($ThisFileInfo['playtime_seconds']) && isset($ThisFileInfo['audio']['bitrate']) && ($ThisFileInfo['audio']['bitrate'] > 0)) {
			$ThisFileInfo['playtime_seconds'] = ($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']) * 8 / $ThisFileInfo['audio']['bitrate'];
		}

		$ThisFileInfo['audio']['encoder_options'] = $this->GuessEncoderOptions($ThisFileInfo);

		return true;
	}


	function GuessEncoderOptions(&$ThisFileInfo) {
		// shortcuts
		if (!empty($ThisFileInfo['mpeg']['audio'])) {
			$thisfile_mpeg_audio = &$ThisFileInfo['mpeg']['audio'];
			if (!empty($thisfile_mpeg_audio['LAME'])) {
				$thisfile_mpeg_audio_lame = &$thisfile_mpeg_audio['LAME'];
			}
		}

		$encoder_options = '';
		static $NamedPresetBitrates = array(16, 24, 40, 56, 112, 128, 160, 192, 256);

		if ((@$thisfile_mpeg_audio['VBR_method'] == 'Fraunhofer') && !empty($thisfile_mpeg_audio['VBR_quality'])) {

			$encoder_options = 'VBR q'.$thisfile_mpeg_audio['VBR_quality'];

		} elseif (!empty($thisfile_mpeg_audio_lame['preset_used']) && (!in_array($thisfile_mpeg_audio_lame['preset_used_id'], $NamedPresetBitrates))) {

			$encoder_options = $thisfile_mpeg_audio_lame['preset_used'];

		} elseif (!empty($thisfile_mpeg_audio_lame['vbr_quality'])) {

			static $KnownEncoderValues = array();
			if (empty($KnownEncoderValues)) {

				//$KnownEncoderValues[abrbitrate_minbitrate][vbr_quality][raw_vbr_method][raw_noise_shaping][raw_stereo_mode][ath_type][lowpass_frequency] = 'preset name';
				$KnownEncoderValues[0xFF][58][1][1][3][2][20500] = '--alt-preset insane';        // 3.90,   3.90.1, 3.92
				$KnownEncoderValues[0xFF][58][1][1][3][2][20600] = '--alt-preset insane';        // 3.90.2, 3.90.3, 3.91
				$KnownEncoderValues[0xFF][57][1][1][3][4][20500] = '--alt-preset insane';        // 3.94,   3.95
				$KnownEncoderValues['**'][78][3][2][3][2][19500] = '--alt-preset extreme';       // 3.90,   3.90.1, 3.92
				$KnownEncoderValues['**'][78][3][2][3][2][19600] = '--alt-preset extreme';       // 3.90.2, 3.91
				$KnownEncoderValues['**'][78][3][1][3][2][19600] = '--alt-preset extreme';       // 3.90.3
				$KnownEncoderValues['**'][78][4][2][3][2][19500] = '--alt-preset fast extreme';  // 3.90,   3.90.1, 3.92
				$KnownEncoderValues['**'][78][4][2][3][2][19600] = '--alt-preset fast extreme';  // 3.90.2, 3.90.3, 3.91
				$KnownEncoderValues['**'][78][3][2][3][4][19000] = '--alt-preset standard';      // 3.90,   3.90.1, 3.90.2, 3.91, 3.92
				$KnownEncoderValues['**'][78][3][1][3][4][19000] = '--alt-preset standard';      // 3.90.3
				$KnownEncoderValues['**'][78][4][2][3][4][19000] = '--alt-preset fast standard'; // 3.90,   3.90.1, 3.90.2, 3.91, 3.92
				$KnownEncoderValues['**'][78][4][1][3][4][19000] = '--alt-preset fast standard'; // 3.90.3
				$KnownEncoderValues['**'][88][4][1][3][3][19500] = '--r3mix';                    // 3.90,   3.90.1, 3.92
				$KnownEncoderValues['**'][88][4][1][3][3][19600] = '--r3mix';                    // 3.90.2, 3.90.3, 3.91
				$KnownEncoderValues['**'][67][4][1][3][4][18000] = '--r3mix';                    // 3.94,   3.95
				$KnownEncoderValues['**'][68][3][2][3][4][18000] = '--alt-preset medium';        // 3.90.3
				$KnownEncoderValues['**'][68][4][2][3][4][18000] = '--alt-preset fast medium';   // 3.90.3

				$KnownEncoderValues[0xFF][99][1][1][1][2][0]     = '--preset studio';            // 3.90,   3.90.1, 3.90.2, 3.91, 3.92
				$KnownEncoderValues[0xFF][58][2][1][3][2][20600] = '--preset studio';            // 3.90.3, 3.93.1
				$KnownEncoderValues[0xFF][58][2][1][3][2][20500] = '--preset studio';            // 3.93
				$KnownEncoderValues[0xFF][57][2][1][3][4][20500] = '--preset studio';            // 3.94,   3.95
				$KnownEncoderValues[0xC0][88][1][1][1][2][0]     = '--preset cd';                // 3.90,   3.90.1, 3.90.2,   3.91, 3.92
				$KnownEncoderValues[0xC0][58][2][2][3][2][19600] = '--preset cd';                // 3.90.3, 3.93.1
				$KnownEncoderValues[0xC0][58][2][2][3][2][19500] = '--preset cd';                // 3.93
				$KnownEncoderValues[0xC0][57][2][1][3][4][19500] = '--preset cd';                // 3.94,   3.95
				$KnownEncoderValues[0xA0][78][1][1][3][2][18000] = '--preset hifi';              // 3.90,   3.90.1, 3.90.2,   3.91, 3.92
				$KnownEncoderValues[0xA0][58][2][2][3][2][18000] = '--preset hifi';              // 3.90.3, 3.93,   3.93.1
				$KnownEncoderValues[0xA0][57][2][1][3][4][18000] = '--preset hifi';              // 3.94,   3.95
				$KnownEncoderValues[0x80][67][1][1][3][2][18000] = '--preset tape';              // 3.90,   3.90.1, 3.90.2,   3.91, 3.92
				$KnownEncoderValues[0x80][67][1][1][3][2][15000] = '--preset radio';             // 3.90,   3.90.1, 3.90.2,   3.91, 3.92
				$KnownEncoderValues[0x70][67][1][1][3][2][15000] = '--preset fm';                // 3.90,   3.90.1, 3.90.2,   3.91, 3.92
				$KnownEncoderValues[0x70][58][2][2][3][2][16000] = '--preset tape/radio/fm';     // 3.90.3, 3.93,   3.93.1
				$KnownEncoderValues[0x70][57][2][1][3][4][16000] = '--preset tape/radio/fm';     // 3.94,   3.95
				$KnownEncoderValues[0x38][58][2][2][0][2][10000] = '--preset voice';             // 3.90.3, 3.93,   3.93.1
				$KnownEncoderValues[0x38][57][2][1][0][4][15000] = '--preset voice';             // 3.94,   3.95
				$KnownEncoderValues[0x38][57][2][1][0][4][16000] = '--preset voice';             // 3.94a14
				$KnownEncoderValues[0x28][65][1][1][0][2][7500]  = '--preset mw-us';             // 3.90,   3.90.1, 3.92
				$KnownEncoderValues[0x28][65][1][1][0][2][7600]  = '--preset mw-us';             // 3.90.2, 3.91
				$KnownEncoderValues[0x28][58][2][2][0][2][7000]  = '--preset mw-us';             // 3.90.3, 3.93,   3.93.1
				$KnownEncoderValues[0x28][57][2][1][0][4][10500] = '--preset mw-us';             // 3.94,   3.95
				$KnownEncoderValues[0x28][57][2][1][0][4][11200] = '--preset mw-us';             // 3.94a14
				$KnownEncoderValues[0x28][57][2][1][0][4][8800]  = '--preset mw-us';             // 3.94a15
				$KnownEncoderValues[0x18][58][2][2][0][2][4000]  = '--preset phon+/lw/mw-eu/sw'; // 3.90.3, 3.93.1
				$KnownEncoderValues[0x18][58][2][2][0][2][3900]  = '--preset phon+/lw/mw-eu/sw'; // 3.93
				$KnownEncoderValues[0x18][57][2][1][0][4][5900]  = '--preset phon+/lw/mw-eu/sw'; // 3.94,   3.95
				$KnownEncoderValues[0x18][57][2][1][0][4][6200]  = '--preset phon+/lw/mw-eu/sw'; // 3.94a14
				$KnownEncoderValues[0x18][57][2][1][0][4][3200]  = '--preset phon+/lw/mw-eu/sw'; // 3.94a15
				$KnownEncoderValues[0x10][58][2][2][0][2][3800]  = '--preset phone';             // 3.90.3, 3.93.1
				$KnownEncoderValues[0x10][58][2][2][0][2][3700]  = '--preset phone';             // 3.93
				$KnownEncoderValues[0x10][57][2][1][0][4][5600]  = '--preset phone';             // 3.94,   3.95
			}

			if (isset($KnownEncoderValues[$thisfile_mpeg_audio_lame['raw']['abrbitrate_minbitrate']][$thisfile_mpeg_audio_lame['vbr_quality']][$thisfile_mpeg_audio_lame['raw']['vbr_method']][$thisfile_mpeg_audio_lame['raw']['noise_shaping']][$thisfile_mpeg_audio_lame['raw']['stereo_mode']][$thisfile_mpeg_audio_lame['ath_type']][$thisfile_mpeg_audio_lame['lowpass_frequency']])) {

				$encoder_options = $KnownEncoderValues[$thisfile_mpeg_audio_lame['raw']['abrbitrate_minbitrate']][$thisfile_mpeg_audio_lame['vbr_quality']][$thisfile_mpeg_audio_lame['raw']['vbr_method']][$thisfile_mpeg_audio_lame['raw']['noise_shaping']][$thisfile_mpeg_audio_lame['raw']['stereo_mode']][$thisfile_mpeg_audio_lame['ath_type']][$thisfile_mpeg_audio_lame['lowpass_frequency']];

			} elseif (isset($KnownEncoderValues['**'][$thisfile_mpeg_audio_lame['vbr_quality']][$thisfile_mpeg_audio_lame['raw']['vbr_method']][$thisfile_mpeg_audio_lame['raw']['noise_shaping']][$thisfile_mpeg_audio_lame['raw']['stereo_mode']][$thisfile_mpeg_audio_lame['ath_type']][$thisfile_mpeg_audio_lame['lowpass_frequency']])) {

				$encoder_options = $KnownEncoderValues['**'][$thisfile_mpeg_audio_lame['vbr_quality']][$thisfile_mpeg_audio_lame['raw']['vbr_method']][$thisfile_mpeg_audio_lame['raw']['noise_shaping']][$thisfile_mpeg_audio_lame['raw']['stereo_mode']][$thisfile_mpeg_audio_lame['ath_type']][$thisfile_mpeg_audio_lame['lowpass_frequency']];

			} elseif ($ThisFileInfo['audio']['bitrate_mode'] == 'vbr') {

				// http://gabriel.mp3-tech.org/mp3infotag.html
				// int    Quality = (100 - 10 * gfp->VBR_q - gfp->quality)h


				$LAME_V_value = 10 - ceil($thisfile_mpeg_audio_lame['vbr_quality'] / 10);
				$LAME_q_value = 100 - $thisfile_mpeg_audio_lame['vbr_quality'] - ($LAME_V_value * 10);
				$encoder_options = '-V'.$LAME_V_value.' -q'.$LAME_q_value;

			} elseif ($ThisFileInfo['audio']['bitrate_mode'] == 'cbr') {

				$encoder_options = strtoupper($ThisFileInfo['audio']['bitrate_mode']).ceil($ThisFileInfo['audio']['bitrate'] / 1000);

			} else {

				$encoder_options = strtoupper($ThisFileInfo['audio']['bitrate_mode']);

			}

		} elseif (!empty($thisfile_mpeg_audio_lame['bitrate_abr'])) {

			$encoder_options = 'ABR'.$thisfile_mpeg_audio_lame['bitrate_abr'];

		} elseif (!empty($ThisFileInfo['audio']['bitrate'])) {

			if ($ThisFileInfo['audio']['bitrate_mode'] == 'cbr') {
				$encoder_options = strtoupper($ThisFileInfo['audio']['bitrate_mode']).ceil($ThisFileInfo['audio']['bitrate'] / 1000);
			} else {
				$encoder_options = strtoupper($ThisFileInfo['audio']['bitrate_mode']);
			}

		}
		if (!empty($thisfile_mpeg_audio_lame['bitrate_min'])) {
			$encoder_options .= ' -b'.$thisfile_mpeg_audio_lame['bitrate_min'];
		}

		if (@$thisfile_mpeg_audio_lame['encoding_flags']['nogap_prev'] || @$thisfile_mpeg_audio_lame['encoding_flags']['nogap_next']) {
			$encoder_options .= ' --nogap';
		}

		if (!empty($thisfile_mpeg_audio_lame['lowpass_frequency'])) {
			$ExplodedOptions = explode(' ', $encoder_options, 4);
			if ($ExplodedOptions[0] == '--r3mix') {
				$ExplodedOptions[1] = 'r3mix';
			}
			switch ($ExplodedOptions[0]) {
				case '--preset':
				case '--alt-preset':
				case '--r3mix':
					if ($ExplodedOptions[1] == 'fast') {
						$ExplodedOptions[1] .= ' '.$ExplodedOptions[2];
					}
					switch ($ExplodedOptions[1]) {
						case 'portable':
						case 'medium':
						case 'standard':
						case 'extreme':
						case 'insane':
						case 'fast portable':
						case 'fast medium':
						case 'fast standard':
						case 'fast extreme':
						case 'fast insane':
						case 'r3mix':
							static $ExpectedLowpass = array(
									'insane|20500'        => 20500,
									'insane|20600'        => 20600,  // 3.90.2, 3.90.3, 3.91
									'medium|18000'        => 18000,
									'fast medium|18000'   => 18000,
									'extreme|19500'       => 19500,  // 3.90,   3.90.1, 3.92, 3.95
									'extreme|19600'       => 19600,  // 3.90.2, 3.90.3, 3.91, 3.93.1
									'fast extreme|19500'  => 19500,  // 3.90,   3.90.1, 3.92, 3.95
									'fast extreme|19600'  => 19600,  // 3.90.2, 3.90.3, 3.91, 3.93.1
									'standard|19000'      => 19000,
									'fast standard|19000' => 19000,
									'r3mix|19500'         => 19500,  // 3.90,   3.90.1, 3.92
									'r3mix|19600'         => 19600,  // 3.90.2, 3.90.3, 3.91
									'r3mix|18000'         => 18000,  // 3.94,   3.95
								);
							if (!isset($ExpectedLowpass[$ExplodedOptions[1].'|'.$thisfile_mpeg_audio_lame['lowpass_frequency']]) && ($thisfile_mpeg_audio_lame['lowpass_frequency'] < 22050) && (round($thisfile_mpeg_audio_lame['lowpass_frequency'] / 1000) < round($thisfile_mpeg_audio['sample_rate'] / 2000))) {
								$encoder_options .= ' --lowpass '.$thisfile_mpeg_audio_lame['lowpass_frequency'];
							}
							break;

						default:
							break;
					}
					break;
			}
		}

		if (isset($thisfile_mpeg_audio_lame['raw']['source_sample_freq'])) {
			if (($thisfile_mpeg_audio['sample_rate'] == 44100) && ($thisfile_mpeg_audio_lame['raw']['source_sample_freq'] != 1)) {
				$encoder_options .= ' --resample 44100';
			} elseif (($thisfile_mpeg_audio['sample_rate'] == 48000) && ($thisfile_mpeg_audio_lame['raw']['source_sample_freq'] != 2)) {
				$encoder_options .= ' --resample 48000';
			} elseif ($thisfile_mpeg_audio['sample_rate'] < 44100) {
				switch ($thisfile_mpeg_audio_lame['raw']['source_sample_freq']) {
					case 0: // <= 32000
						// may or may not be same as source frequency - ignore
						break;
					case 1: // 44100
					case 2: // 48000
					case 3: // 48000+
						$ExplodedOptions = explode(' ', $encoder_options, 4);
						switch ($ExplodedOptions[0]) {
							case '--preset':
							case '--alt-preset':
								switch ($ExplodedOptions[1]) {
									case 'fast':
									case 'portable':
									case 'medium':
									case 'standard':
									case 'extreme':
									case 'insane':
										$encoder_options .= ' --resample '.$thisfile_mpeg_audio['sample_rate'];
										break;

									default:
										static $ExpectedResampledRate = array(
												'phon+/lw/mw-eu/sw|16000' => 16000,
												'mw-us|24000'             => 24000, // 3.95
												'mw-us|32000'             => 32000, // 3.93
												'mw-us|16000'             => 16000, // 3.92
												'phone|16000'             => 16000,
												'phone|11025'             => 11025, // 3.94a15
												'radio|32000'             => 32000, // 3.94a15
												'fm/radio|32000'          => 32000, // 3.92
												'fm|32000'                => 32000, // 3.90
												'voice|32000'             => 32000);
										if (!isset($ExpectedResampledRate[$ExplodedOptions[1].'|'.$thisfile_mpeg_audio['sample_rate']])) {
											$encoder_options .= ' --resample '.$thisfile_mpeg_audio['sample_rate'];
										}
										break;
								}
								break;

							case '--r3mix':
							default:
								$encoder_options .= ' --resample '.$thisfile_mpeg_audio['sample_rate'];
								break;
						}
						break;
				}
			}
		}
		if (empty($encoder_options) && !empty($ThisFileInfo['audio']['bitrate']) && !empty($ThisFileInfo['audio']['bitrate_mode'])) {
			//$encoder_options = strtoupper($ThisFileInfo['audio']['bitrate_mode']).ceil($ThisFileInfo['audio']['bitrate'] / 1000);
			$encoder_options = strtoupper($ThisFileInfo['audio']['bitrate_mode']);
		}

		return $encoder_options;
	}


	function decodeMPEGaudioHeader($fd, $offset, &$ThisFileInfo, $recursivesearch=true, $ScanAsCBR=false, $FastMPEGheaderScan=false) {

		static $MPEGaudioVersionLookup;
		static $MPEGaudioLayerLookup;
		static $MPEGaudioBitrateLookup;
		static $MPEGaudioFrequencyLookup;
		static $MPEGaudioChannelModeLookup;
		static $MPEGaudioModeExtensionLookup;
		static $MPEGaudioEmphasisLookup;
		if (empty($MPEGaudioVersionLookup)) {
			$MPEGaudioVersionLookup       = getid3_mp3::MPEGaudioVersionArray();
			$MPEGaudioLayerLookup         = getid3_mp3::MPEGaudioLayerArray();
			$MPEGaudioBitrateLookup       = getid3_mp3::MPEGaudioBitrateArray();
			$MPEGaudioFrequencyLookup     = getid3_mp3::MPEGaudioFrequencyArray();
			$MPEGaudioChannelModeLookup   = getid3_mp3::MPEGaudioChannelModeArray();
			$MPEGaudioModeExtensionLookup = getid3_mp3::MPEGaudioModeExtensionArray();
			$MPEGaudioEmphasisLookup      = getid3_mp3::MPEGaudioEmphasisArray();
		}

		if ($offset >= $ThisFileInfo['avdataend']) {
			$ThisFileInfo['error'][] = 'end of file encounter looking for MPEG synch';
			return false;
		}
		fseek($fd, $offset, SEEK_SET);
		//$headerstring = fread($fd, 1441); // worst-case max length = 32kHz @ 320kbps layer 3 = 1441 bytes/frame
		$headerstring = fread($fd, 226); // LAME header at offset 36 + 190 bytes of Xing/LAME data

		// MP3 audio frame structure:
		// $aa $aa $aa $aa [$bb $bb] $cc...
		// where $aa..$aa is the four-byte mpeg-audio header (below)
		// $bb $bb is the optional 2-byte CRC
		// and $cc... is the audio data

		$head4 = substr($headerstring, 0, 4);

		static $MPEGaudioHeaderDecodeCache = array();
		if (isset($MPEGaudioHeaderDecodeCache[$head4])) {
			$MPEGheaderRawArray = $MPEGaudioHeaderDecodeCache[$head4];
		} else {
			$MPEGheaderRawArray = getid3_mp3::MPEGaudioHeaderDecode($head4);
			$MPEGaudioHeaderDecodeCache[$head4] = $MPEGheaderRawArray;
		}

		static $MPEGaudioHeaderValidCache = array();

		// Not in cache
		if (!isset($MPEGaudioHeaderValidCache[$head4])) {
			//$MPEGaudioHeaderValidCache[$head4] = getid3_mp3::MPEGaudioHeaderValid($MPEGheaderRawArray, false, true);  // allow badly-formatted freeformat (from LAME 3.90 - 3.93.1)
			$MPEGaudioHeaderValidCache[$head4] = getid3_mp3::MPEGaudioHeaderValid($MPEGheaderRawArray, false, false);
		}

		// shortcut
		if (!isset($ThisFileInfo['mpeg']['audio'])) {
			$ThisFileInfo['mpeg']['audio'] = array();
		}
		$thisfile_mpeg_audio = &$ThisFileInfo['mpeg']['audio'];


		if ($MPEGaudioHeaderValidCache[$head4]) {
			$thisfile_mpeg_audio['raw'] = $MPEGheaderRawArray;
		} else {
			$ThisFileInfo['error'][] = 'Invalid MPEG audio header at offset '.$offset;
			return false;
		}

		if (!$FastMPEGheaderScan) {

			$thisfile_mpeg_audio['version']       = $MPEGaudioVersionLookup[$thisfile_mpeg_audio['raw']['version']];
			$thisfile_mpeg_audio['layer']         = $MPEGaudioLayerLookup[$thisfile_mpeg_audio['raw']['layer']];

			$thisfile_mpeg_audio['channelmode']   = $MPEGaudioChannelModeLookup[$thisfile_mpeg_audio['raw']['channelmode']];
			$thisfile_mpeg_audio['channels']      = (($thisfile_mpeg_audio['channelmode'] == 'mono') ? 1 : 2);
			$thisfile_mpeg_audio['sample_rate']   = $MPEGaudioFrequencyLookup[$thisfile_mpeg_audio['version']][$thisfile_mpeg_audio['raw']['sample_rate']];
			$thisfile_mpeg_audio['protection']    = !$thisfile_mpeg_audio['raw']['protection'];
			$thisfile_mpeg_audio['private']       = (bool) $thisfile_mpeg_audio['raw']['private'];
			$thisfile_mpeg_audio['modeextension'] = $MPEGaudioModeExtensionLookup[$thisfile_mpeg_audio['layer']][$thisfile_mpeg_audio['raw']['modeextension']];
			$thisfile_mpeg_audio['copyright']     = (bool) $thisfile_mpeg_audio['raw']['copyright'];
			$thisfile_mpeg_audio['original']      = (bool) $thisfile_mpeg_audio['raw']['original'];
			$thisfile_mpeg_audio['emphasis']      = $MPEGaudioEmphasisLookup[$thisfile_mpeg_audio['raw']['emphasis']];

			$ThisFileInfo['audio']['channels']    = $thisfile_mpeg_audio['channels'];
			$ThisFileInfo['audio']['sample_rate'] = $thisfile_mpeg_audio['sample_rate'];

			if ($thisfile_mpeg_audio['protection']) {
				$thisfile_mpeg_audio['crc'] = getid3_lib::BigEndian2Int(substr($headerstring, 4, 2));
			}

		}

		if ($thisfile_mpeg_audio['raw']['bitrate'] == 15) {
			// http://www.hydrogenaudio.org/?act=ST&f=16&t=9682&st=0
			$ThisFileInfo['warning'][] = 'Invalid bitrate index (15), this is a known bug in free-format MP3s encoded by LAME v3.90 - 3.93.1';
			$thisfile_mpeg_audio['raw']['bitrate'] = 0;
		}
		$thisfile_mpeg_audio['padding'] = (bool) $thisfile_mpeg_audio['raw']['padding'];
		$thisfile_mpeg_audio['bitrate'] = $MPEGaudioBitrateLookup[$thisfile_mpeg_audio['version']][$thisfile_mpeg_audio['layer']][$thisfile_mpeg_audio['raw']['bitrate']];

		if (($thisfile_mpeg_audio['bitrate'] == 'free') && ($offset == $ThisFileInfo['avdataoffset'])) {
			// only skip multiple frame check if free-format bitstream found at beginning of file
			// otherwise is quite possibly simply corrupted data
			$recursivesearch = false;
		}

		// For Layer 2 there are some combinations of bitrate and mode which are not allowed.
		if (!$FastMPEGheaderScan && ($thisfile_mpeg_audio['layer'] == '2')) {

			$ThisFileInfo['audio']['dataformat'] = 'mp2';
			switch ($thisfile_mpeg_audio['channelmode']) {

				case 'mono':
					if (($thisfile_mpeg_audio['bitrate'] == 'free') || ($thisfile_mpeg_audio['bitrate'] <= 192000)) {
						// these are ok
					} else {
						$ThisFileInfo['error'][] = $thisfile_mpeg_audio['bitrate'].'kbps not allowed in Layer 2, '.$thisfile_mpeg_audio['channelmode'].'.';
						return false;
					}
					break;

				case 'stereo':
				case 'joint stereo':
				case 'dual channel':
					if (($thisfile_mpeg_audio['bitrate'] == 'free') || ($thisfile_mpeg_audio['bitrate'] == 64000) || ($thisfile_mpeg_audio['bitrate'] >= 96000)) {
						// these are ok
					} else {
						$ThisFileInfo['error'][] = intval(round($thisfile_mpeg_audio['bitrate'] / 1000)).'kbps not allowed in Layer 2, '.$thisfile_mpeg_audio['channelmode'].'.';
						return false;
					}
					break;

			}

		}


		if ($ThisFileInfo['audio']['sample_rate'] > 0) {
			$thisfile_mpeg_audio['framelength'] = getid3_mp3::MPEGaudioFrameLength($thisfile_mpeg_audio['bitrate'], $thisfile_mpeg_audio['version'], $thisfile_mpeg_audio['layer'], (int) $thisfile_mpeg_audio['padding'], $ThisFileInfo['audio']['sample_rate']);
		}

		$nextframetestoffset = $offset + 1;
		if ($thisfile_mpeg_audio['bitrate'] != 'free') {

			$ThisFileInfo['audio']['bitrate'] = $thisfile_mpeg_audio['bitrate'];

			if (isset($thisfile_mpeg_audio['framelength'])) {
				$nextframetestoffset = $offset + $thisfile_mpeg_audio['framelength'];
			} else {
				$ThisFileInfo['error'][] = 'Frame at offset('.$offset.') is has an invalid frame length.';
				return false;
			}

		}

		$ExpectedNumberOfAudioBytes = 0;

		////////////////////////////////////////////////////////////////////////////////////
		// Variable-bitrate headers

		if (substr($headerstring, 4 + 32, 4) == 'VBRI') {
			// Fraunhofer VBR header is hardcoded 'VBRI' at offset 0x24 (36)
			// specs taken from http://minnie.tuhs.org/pipermail/mp3encoder/2001-January/001800.html

			$thisfile_mpeg_audio['bitrate_mode'] = 'vbr';
			$thisfile_mpeg_audio['VBR_method']   = 'Fraunhofer';
			$ThisFileInfo['audio']['codec']                = 'Fraunhofer';

			$SideInfoData = substr($headerstring, 4 + 2, 32);

			$FraunhoferVBROffset = 36;

			$thisfile_mpeg_audio['VBR_encoder_version']     = getid3_lib::BigEndian2Int(substr($headerstring, $FraunhoferVBROffset +  4, 2)); // VbriVersion
			$thisfile_mpeg_audio['VBR_encoder_delay']       = getid3_lib::BigEndian2Int(substr($headerstring, $FraunhoferVBROffset +  6, 2)); // VbriDelay
			$thisfile_mpeg_audio['VBR_quality']             = getid3_lib::BigEndian2Int(substr($headerstring, $FraunhoferVBROffset +  8, 2)); // VbriQuality
			$thisfile_mpeg_audio['VBR_bytes']               = getid3_lib::BigEndian2Int(substr($headerstring, $FraunhoferVBROffset + 10, 4)); // VbriStreamBytes
			$thisfile_mpeg_audio['VBR_frames']              = getid3_lib::BigEndian2Int(substr($headerstring, $FraunhoferVBROffset + 14, 4)); // VbriStreamFrames
			$thisfile_mpeg_audio['VBR_seek_offsets']        = getid3_lib::BigEndian2Int(substr($headerstring, $FraunhoferVBROffset + 18, 2)); // VbriTableSize
			$thisfile_mpeg_audio['VBR_seek_scale']          = getid3_lib::BigEndian2Int(substr($headerstring, $FraunhoferVBROffset + 20, 2)); // VbriTableScale
			$thisfile_mpeg_audio['VBR_entry_bytes']         = getid3_lib::BigEndian2Int(substr($headerstring, $FraunhoferVBROffset + 22, 2)); // VbriEntryBytes
			$thisfile_mpeg_audio['VBR_entry_frames']        = getid3_lib::BigEndian2Int(substr($headerstring, $FraunhoferVBROffset + 24, 2)); // VbriEntryFrames

			$ExpectedNumberOfAudioBytes = $thisfile_mpeg_audio['VBR_bytes'];

			$previousbyteoffset = $offset;
			for ($i = 0; $i < $thisfile_mpeg_audio['VBR_seek_offsets']; $i++) {
				$Fraunhofer_OffsetN = getid3_lib::BigEndian2Int(substr($headerstring, $FraunhoferVBROffset, $thisfile_mpeg_audio['VBR_entry_bytes']));
				$FraunhoferVBROffset += $thisfile_mpeg_audio['VBR_entry_bytes'];
				$thisfile_mpeg_audio['VBR_offsets_relative'][$i] = ($Fraunhofer_OffsetN * $thisfile_mpeg_audio['VBR_seek_scale']);
				$thisfile_mpeg_audio['VBR_offsets_absolute'][$i] = ($Fraunhofer_OffsetN * $thisfile_mpeg_audio['VBR_seek_scale']) + $previousbyteoffset;
				$previousbyteoffset += $Fraunhofer_OffsetN;
			}


		} else {

			// Xing VBR header is hardcoded 'Xing' at a offset 0x0D (13), 0x15 (21) or 0x24 (36)
			// depending on MPEG layer and number of channels

			$VBRidOffset = getid3_mp3::XingVBRidOffset($thisfile_mpeg_audio['version'], $thisfile_mpeg_audio['channelmode']);
			$SideInfoData = substr($headerstring, 4 + 2, $VBRidOffset - 4);

			if ((substr($headerstring, $VBRidOffset, strlen('Xing')) == 'Xing') || (substr($headerstring, $VBRidOffset, strlen('Info')) == 'Info')) {
				// 'Xing' is traditional Xing VBR frame
				// 'Info' is LAME-encoded CBR (This was done to avoid CBR files to be recognized as traditional Xing VBR files by some decoders.)
				// 'Info' *can* legally be used to specify a VBR file as well, however.

				// http://www.multiweb.cz/twoinches/MP3inside.htm
				//00..03 = "Xing" or "Info"
				//04..07 = Flags:
				//  0x01  Frames Flag     set if value for number of frames in file is stored
				//  0x02  Bytes Flag      set if value for filesize in bytes is stored
				//  0x04  TOC Flag        set if values for TOC are stored
				//  0x08  VBR Scale Flag  set if values for VBR scale is stored
				//08..11  Frames: Number of frames in file (including the first Xing/Info one)
				//12..15  Bytes:  File length in Bytes
				//16..115  TOC (Table of Contents):
				//  Contains of 100 indexes (one Byte length) for easier lookup in file. Approximately solves problem with moving inside file.
				//  Each Byte has a value according this formula:
				//  (TOC[i] / 256) * fileLenInBytes
				//  So if song lasts eg. 240 sec. and you want to jump to 60. sec. (and file is 5 000 000 Bytes length) you can use:
				//  TOC[(60/240)*100] = TOC[25]
				//  and corresponding Byte in file is then approximately at:
				//  (TOC[25]/256) * 5000000
				//116..119  VBR Scale


				// should be safe to leave this at 'vbr' and let it be overriden to 'cbr' if a CBR preset/mode is used by LAME
//				if (substr($headerstring, $VBRidOffset, strlen('Info')) == 'Xing') {
					$thisfile_mpeg_audio['bitrate_mode'] = 'vbr';
					$thisfile_mpeg_audio['VBR_method']   = 'Xing';
//				} else {
//					$ScanAsCBR = true;
//					$thisfile_mpeg_audio['bitrate_mode'] = 'cbr';
//				}

				$thisfile_mpeg_audio['xing_flags_raw'] = getid3_lib::BigEndian2Int(substr($headerstring, $VBRidOffset + 4, 4));

				$thisfile_mpeg_audio['xing_flags']['frames']    = (bool) ($thisfile_mpeg_audio['xing_flags_raw'] & 0x00000001);
				$thisfile_mpeg_audio['xing_flags']['bytes']     = (bool) ($thisfile_mpeg_audio['xing_flags_raw'] & 0x00000002);
				$thisfile_mpeg_audio['xing_flags']['toc']       = (bool) ($thisfile_mpeg_audio['xing_flags_raw'] & 0x00000004);
				$thisfile_mpeg_audio['xing_flags']['vbr_scale'] = (bool) ($thisfile_mpeg_audio['xing_flags_raw'] & 0x00000008);

				if ($thisfile_mpeg_audio['xing_flags']['frames']) {
					$thisfile_mpeg_audio['VBR_frames'] = getid3_lib::BigEndian2Int(substr($headerstring, $VBRidOffset +  8, 4));
					//$thisfile_mpeg_audio['VBR_frames']--; // don't count header Xing/Info frame
				}
				if ($thisfile_mpeg_audio['xing_flags']['bytes']) {
					$thisfile_mpeg_audio['VBR_bytes']  = getid3_lib::BigEndian2Int(substr($headerstring, $VBRidOffset + 12, 4));
				}

				//if (($thisfile_mpeg_audio['bitrate'] == 'free') && !empty($thisfile_mpeg_audio['VBR_frames']) && !empty($thisfile_mpeg_audio['VBR_bytes'])) {
				if (!empty($thisfile_mpeg_audio['VBR_frames']) && !empty($thisfile_mpeg_audio['VBR_bytes'])) {

					$framelengthfloat = $thisfile_mpeg_audio['VBR_bytes'] / $thisfile_mpeg_audio['VBR_frames'];

					if ($thisfile_mpeg_audio['layer'] == '1') {
						// BitRate = (((FrameLengthInBytes / 4) - Padding) * SampleRate) / 12
						//$ThisFileInfo['audio']['bitrate'] = ((($framelengthfloat / 4) - intval($thisfile_mpeg_audio['padding'])) * $thisfile_mpeg_audio['sample_rate']) / 12;
						$ThisFileInfo['audio']['bitrate'] = ($framelengthfloat / 4) * $thisfile_mpeg_audio['sample_rate'] * (2 / $ThisFileInfo['audio']['channels']) / 12;
					} else {
						// Bitrate = ((FrameLengthInBytes - Padding) * SampleRate) / 144
						//$ThisFileInfo['audio']['bitrate'] = (($framelengthfloat - intval($thisfile_mpeg_audio['padding'])) * $thisfile_mpeg_audio['sample_rate']) / 144;
						$ThisFileInfo['audio']['bitrate'] = $framelengthfloat * $thisfile_mpeg_audio['sample_rate'] * (2 / $ThisFileInfo['audio']['channels']) / 144;
					}
					$thisfile_mpeg_audio['framelength'] = floor($framelengthfloat);
				}

				if ($thisfile_mpeg_audio['xing_flags']['toc']) {
					$LAMEtocData = substr($headerstring, $VBRidOffset + 16, 100);
					for ($i = 0; $i < 100; $i++) {
						$thisfile_mpeg_audio['toc'][$i] = ord($LAMEtocData{$i});
					}
				}
				if ($thisfile_mpeg_audio['xing_flags']['vbr_scale']) {
					$thisfile_mpeg_audio['VBR_scale'] = getid3_lib::BigEndian2Int(substr($headerstring, $VBRidOffset + 116, 4));
				}


				// http://gabriel.mp3-tech.org/mp3infotag.html
				if (substr($headerstring, $VBRidOffset + 120, 4) == 'LAME') {

					// shortcut
					$thisfile_mpeg_audio['LAME'] = array();
					$thisfile_mpeg_audio_lame    = &$thisfile_mpeg_audio['LAME'];


					$thisfile_mpeg_audio_lame['long_version']  = substr($headerstring, $VBRidOffset + 120, 20);
					$thisfile_mpeg_audio_lame['short_version'] = substr($thisfile_mpeg_audio_lame['long_version'], 0, 9);

					if ($thisfile_mpeg_audio_lame['short_version'] >= 'LAME3.90') {

						// extra 11 chars are not part of version string when LAMEtag present
						unset($thisfile_mpeg_audio_lame['long_version']);

						// It the LAME tag was only introduced in LAME v3.90
						// http://www.hydrogenaudio.org/?act=ST&f=15&t=9933

						// Offsets of various bytes in http://gabriel.mp3-tech.org/mp3infotag.html
						// are assuming a 'Xing' identifier offset of 0x24, which is the case for
						// MPEG-1 non-mono, but not for other combinations
						$LAMEtagOffsetContant = $VBRidOffset - 0x24;

						// shortcuts
						$thisfile_mpeg_audio_lame['RGAD']    = array('track'=>array(), 'album'=>array());
						$thisfile_mpeg_audio_lame_RGAD       = &$thisfile_mpeg_audio_lame['RGAD'];
						$thisfile_mpeg_audio_lame_RGAD_track = &$thisfile_mpeg_audio_lame_RGAD['track'];
						$thisfile_mpeg_audio_lame_RGAD_album = &$thisfile_mpeg_audio_lame_RGAD['album'];
						$thisfile_mpeg_audio_lame['raw'] = array();
						$thisfile_mpeg_audio_lame_raw    = &$thisfile_mpeg_audio_lame['raw'];

						// byte $9B  VBR Quality
						// This field is there to indicate a quality level, although the scale was not precised in the original Xing specifications.
						// Actually overwrites original Xing bytes
						unset($thisfile_mpeg_audio['VBR_scale']);
						$thisfile_mpeg_audio_lame['vbr_quality'] = getid3_lib::BigEndian2Int(substr($headerstring, $LAMEtagOffsetContant + 0x9B, 1));

						// bytes $9C-$A4  Encoder short VersionString
						$thisfile_mpeg_audio_lame['short_version'] = substr($headerstring, $LAMEtagOffsetContant + 0x9C, 9);

						// byte $A5  Info Tag revision + VBR method
						$LAMEtagRevisionVBRmethod = getid3_lib::BigEndian2Int(substr($headerstring, $LAMEtagOffsetContant + 0xA5, 1));

						$thisfile_mpeg_audio_lame['tag_revision']   = ($LAMEtagRevisionVBRmethod & 0xF0) >> 4;
						$thisfile_mpeg_audio_lame_raw['vbr_method'] =  $LAMEtagRevisionVBRmethod & 0x0F;
						$thisfile_mpeg_audio_lame['vbr_method']     = getid3_mp3::LAMEvbrMethodLookup($thisfile_mpeg_audio_lame_raw['vbr_method']);
						$thisfile_mpeg_audio['bitrate_mode']        = substr($thisfile_mpeg_audio_lame['vbr_method'], 0, 3); // usually either 'cbr' or 'vbr', but truncates 'vbr-old / vbr-rh' to 'vbr'

						// byte $A6  Lowpass filter value
						$thisfile_mpeg_audio_lame['lowpass_frequency'] = getid3_lib::BigEndian2Int(substr($headerstring, $LAMEtagOffsetContant + 0xA6, 1)) * 100;

						// bytes $A7-$AE  Replay Gain
						// http://privatewww.essex.ac.uk/~djmrob/replaygain/rg_data_format.html
						// bytes $A7-$AA : 32 bit floating point "Peak signal amplitude"
						if ($thisfile_mpeg_audio_lame['short_version'] >= 'LAME3.94b') {
							// LAME 3.94a16 and later - 9.23 fixed point
							// ie 0x0059E2EE / (2^23) = 5890798 / 8388608 = 0.7022378444671630859375
							$thisfile_mpeg_audio_lame_RGAD['peak_amplitude'] = (float) ((getid3_lib::BigEndian2Int(substr($headerstring, $LAMEtagOffsetContant + 0xA7, 4))) / 8388608);
						} else {
							// LAME 3.94a15 and earlier - 32-bit floating point
							// Actually 3.94a16 will fall in here too and be WRONG, but is hard to detect 3.94a16 vs 3.94a15
							$thisfile_mpeg_audio_lame_RGAD['peak_amplitude'] = getid3_lib::LittleEndian2Float(substr($headerstring, $LAMEtagOffsetContant + 0xA7, 4));
						}
						if ($thisfile_mpeg_audio_lame_RGAD['peak_amplitude'] == 0) {
							unset($thisfile_mpeg_audio_lame_RGAD['peak_amplitude']);
						} else {
							$thisfile_mpeg_audio_lame_RGAD['peak_db'] = getid3_lib::RGADamplitude2dB($thisfile_mpeg_audio_lame_RGAD['peak_amplitude']);
						}

						$thisfile_mpeg_audio_lame_raw['RGAD_track']      =   getid3_lib::BigEndian2Int(substr($headerstring, $LAMEtagOffsetContant + 0xAB, 2));
						$thisfile_mpeg_audio_lame_raw['RGAD_album']      =   getid3_lib::BigEndian2Int(substr($headerstring, $LAMEtagOffsetContant + 0xAD, 2));


						if ($thisfile_mpeg_audio_lame_raw['RGAD_track'] != 0) {

							$thisfile_mpeg_audio_lame_RGAD_track['raw']['name']        = ($thisfile_mpeg_audio_lame_raw['RGAD_track'] & 0xE000) >> 13;
							$thisfile_mpeg_audio_lame_RGAD_track['raw']['originator']  = ($thisfile_mpeg_audio_lame_raw['RGAD_track'] & 0x1C00) >> 10;
							$thisfile_mpeg_audio_lame_RGAD_track['raw']['sign_bit']    = ($thisfile_mpeg_audio_lame_raw['RGAD_track'] & 0x0200) >> 9;
							$thisfile_mpeg_audio_lame_RGAD_track['raw']['gain_adjust'] =  $thisfile_mpeg_audio_lame_raw['RGAD_track'] & 0x01FF;
							$thisfile_mpeg_audio_lame_RGAD_track['name']       = getid3_lib::RGADnameLookup($thisfile_mpeg_audio_lame_RGAD_track['raw']['name']);
							$thisfile_mpeg_audio_lame_RGAD_track['originator'] = getid3_lib::RGADoriginatorLookup($thisfile_mpeg_audio_lame_RGAD_track['raw']['originator']);
							$thisfile_mpeg_audio_lame_RGAD_track['gain_db']    = getid3_lib::RGADadjustmentLookup($thisfile_mpeg_audio_lame_RGAD_track['raw']['gain_adjust'], $thisfile_mpeg_audio_lame_RGAD_track['raw']['sign_bit']);

							if (!empty($thisfile_mpeg_audio_lame_RGAD['peak_amplitude'])) {
								$ThisFileInfo['replay_gain']['track']['peak']   = $thisfile_mpeg_audio_lame_RGAD['peak_amplitude'];
							}
							$ThisFileInfo['replay_gain']['track']['originator'] = $thisfile_mpeg_audio_lame_RGAD_track['originator'];
							$ThisFileInfo['replay_gain']['track']['adjustment'] = $thisfile_mpeg_audio_lame_RGAD_track['gain_db'];
						} else {
							unset($thisfile_mpeg_audio_lame_RGAD['track']);
						}
						if ($thisfile_mpeg_audio_lame_raw['RGAD_album'] != 0) {

							$thisfile_mpeg_audio_lame_RGAD_album['raw']['name']        = ($thisfile_mpeg_audio_lame_raw['RGAD_album'] & 0xE000) >> 13;
							$thisfile_mpeg_audio_lame_RGAD_album['raw']['originator']  = ($thisfile_mpeg_audio_lame_raw['RGAD_album'] & 0x1C00) >> 10;
							$thisfile_mpeg_audio_lame_RGAD_album['raw']['sign_bit']    = ($thisfile_mpeg_audio_lame_raw['RGAD_album'] & 0x0200) >> 9;
							$thisfile_mpeg_audio_lame_RGAD_album['raw']['gain_adjust'] = $thisfile_mpeg_audio_lame_raw['RGAD_album'] & 0x01FF;
							$thisfile_mpeg_audio_lame_RGAD_album['name']       = getid3_lib::RGADnameLookup($thisfile_mpeg_audio_lame_RGAD_album['raw']['name']);
							$thisfile_mpeg_audio_lame_RGAD_album['originator'] = getid3_lib::RGADoriginatorLookup($thisfile_mpeg_audio_lame_RGAD_album['raw']['originator']);
							$thisfile_mpeg_audio_lame_RGAD_album['gain_db']    = getid3_lib::RGADadjustmentLookup($thisfile_mpeg_audio_lame_RGAD_album['raw']['gain_adjust'], $thisfile_mpeg_audio_lame_RGAD_album['raw']['sign_bit']);

							if (!empty($thisfile_mpeg_audio_lame_RGAD['peak_amplitude'])) {
								$ThisFileInfo['replay_gain']['album']['peak']   = $thisfile_mpeg_audio_lame_RGAD['peak_amplitude'];
							}
							$ThisFileInfo['replay_gain']['album']['originator'] = $thisfile_mpeg_audio_lame_RGAD_album['originator'];
							$ThisFileInfo['replay_gain']['album']['adjustment'] = $thisfile_mpeg_audio_lame_RGAD_album['gain_db'];
						} else {
							unset($thisfile_mpeg_audio_lame_RGAD['album']);
						}
						if (empty($thisfile_mpeg_audio_lame_RGAD)) {
							unset($thisfile_mpeg_audio_lame['RGAD']);
						}


						// byte $AF  Encoding flags + ATH Type
						$EncodingFlagsATHtype = getid3_lib::BigEndian2Int(substr($headerstring, $LAMEtagOffsetContant + 0xAF, 1));
						$thisfile_mpeg_audio_lame['encoding_flags']['nspsytune']   = (bool) ($EncodingFlagsATHtype & 0x10);
						$thisfile_mpeg_audio_lame['encoding_flags']['nssafejoint'] = (bool) ($EncodingFlagsATHtype & 0x20);
						$thisfile_mpeg_audio_lame['encoding_flags']['nogap_next']  = (bool) ($EncodingFlagsATHtype & 0x40);
						$thisfile_mpeg_audio_lame['encoding_flags']['nogap_prev']  = (bool) ($EncodingFlagsATHtype & 0x80);
						$thisfile_mpeg_audio_lame['ath_type']                      =         $EncodingFlagsATHtype & 0x0F;

						// byte $B0  if ABR {specified bitrate} else {minimal bitrate}
						$thisfile_mpeg_audio_lame['raw']['abrbitrate_minbitrate'] = getid3_lib::BigEndian2Int(substr($headerstring, $LAMEtagOffsetContant + 0xB0, 1));
						if ($thisfile_mpeg_audio_lame_raw['vbr_method'] == 2) { // Average BitRate (ABR)
							$thisfile_mpeg_audio_lame['bitrate_abr'] = $thisfile_mpeg_audio_lame['raw']['abrbitrate_minbitrate'];
						} elseif ($thisfile_mpeg_audio_lame_raw['vbr_method'] == 1) { // Constant BitRate (CBR)
							// ignore
						} elseif ($thisfile_mpeg_audio_lame['raw']['abrbitrate_minbitrate'] > 0) { // Variable BitRate (VBR) - minimum bitrate
							$thisfile_mpeg_audio_lame['bitrate_min'] = $thisfile_mpeg_audio_lame['raw']['abrbitrate_minbitrate'];
						}

						// bytes $B1-$B3  Encoder delays
						$EncoderDelays = getid3_lib::BigEndian2Int(substr($headerstring, $LAMEtagOffsetContant + 0xB1, 3));
						$thisfile_mpeg_audio_lame['encoder_delay'] = ($EncoderDelays & 0xFFF000) >> 12;
						$thisfile_mpeg_audio_lame['end_padding']   =  $EncoderDelays & 0x000FFF;

						// byte $B4  Misc
						$MiscByte = getid3_lib::BigEndian2Int(substr($headerstring, $LAMEtagOffsetContant + 0xB4, 1));
						$thisfile_mpeg_audio_lame_raw['noise_shaping']       = ($MiscByte & 0x03);
						$thisfile_mpeg_audio_lame_raw['stereo_mode']         = ($MiscByte & 0x1C) >> 2;
						$thisfile_mpeg_audio_lame_raw['not_optimal_quality'] = ($MiscByte & 0x20) >> 5;
						$thisfile_mpeg_audio_lame_raw['source_sample_freq']  = ($MiscByte & 0xC0) >> 6;
						$thisfile_mpeg_audio_lame['noise_shaping']       = $thisfile_mpeg_audio_lame_raw['noise_shaping'];
						$thisfile_mpeg_audio_lame['stereo_mode']         = getid3_mp3::LAMEmiscStereoModeLookup($thisfile_mpeg_audio_lame_raw['stereo_mode']);
						$thisfile_mpeg_audio_lame['not_optimal_quality'] = (bool) $thisfile_mpeg_audio_lame_raw['not_optimal_quality'];
						$thisfile_mpeg_audio_lame['source_sample_freq']  = getid3_mp3::LAMEmiscSourceSampleFrequencyLookup($thisfile_mpeg_audio_lame_raw['source_sample_freq']);

						// byte $B5  MP3 Gain
						$thisfile_mpeg_audio_lame_raw['mp3_gain'] = getid3_lib::BigEndian2Int(substr($headerstring, $LAMEtagOffsetContant + 0xB5, 1), false, true);
						$thisfile_mpeg_audio_lame['mp3_gain_db']     = (getid3_lib::RGADamplitude2dB(2) / 4) * $thisfile_mpeg_audio_lame_raw['mp3_gain'];
						$thisfile_mpeg_audio_lame['mp3_gain_factor'] = pow(2, ($thisfile_mpeg_audio_lame['mp3_gain_db'] / 6));

						// bytes $B6-$B7  Preset and surround info
						$PresetSurroundBytes = getid3_lib::BigEndian2Int(substr($headerstring, $LAMEtagOffsetContant + 0xB6, 2));
						// Reserved                                                    = ($PresetSurroundBytes & 0xC000);
						$thisfile_mpeg_audio_lame_raw['surround_info'] = ($PresetSurroundBytes & 0x3800);
						$thisfile_mpeg_audio_lame['surround_info']     = getid3_mp3::LAMEsurroundInfoLookup($thisfile_mpeg_audio_lame_raw['surround_info']);
						$thisfile_mpeg_audio_lame['preset_used_id']    = ($PresetSurroundBytes & 0x07FF);
						$thisfile_mpeg_audio_lame['preset_used']       = getid3_mp3::LAMEpresetUsedLookup($thisfile_mpeg_audio_lame);
						if (!empty($thisfile_mpeg_audio_lame['preset_used_id']) && empty($thisfile_mpeg_audio_lame['preset_used'])) {
							$ThisFileInfo['warning'][] = 'Unknown LAME preset used ('.$thisfile_mpeg_audio_lame['preset_used_id'].') - please report to info@getid3.org';
						}
						if (($thisfile_mpeg_audio_lame['short_version'] == 'LAME3.90.') && !empty($thisfile_mpeg_audio_lame['preset_used_id'])) {
							// this may change if 3.90.4 ever comes out
							$thisfile_mpeg_audio_lame['short_version'] = 'LAME3.90.3';
						}

						// bytes $B8-$BB  MusicLength
						$thisfile_mpeg_audio_lame['audio_bytes'] = getid3_lib::BigEndian2Int(substr($headerstring, $LAMEtagOffsetContant + 0xB8, 4));
						$ExpectedNumberOfAudioBytes = (($thisfile_mpeg_audio_lame['audio_bytes'] > 0) ? $thisfile_mpeg_audio_lame['audio_bytes'] : $thisfile_mpeg_audio['VBR_bytes']);

						// bytes $BC-$BD  MusicCRC
						$thisfile_mpeg_audio_lame['music_crc']    = getid3_lib::BigEndian2Int(substr($headerstring, $LAMEtagOffsetContant + 0xBC, 2));

						// bytes $BE-$BF  CRC-16 of Info Tag
						$thisfile_mpeg_audio_lame['lame_tag_crc'] = getid3_lib::BigEndian2Int(substr($headerstring, $LAMEtagOffsetContant + 0xBE, 2));


						// LAME CBR
						if ($thisfile_mpeg_audio_lame_raw['vbr_method'] == 1) {

							$thisfile_mpeg_audio['bitrate_mode'] = 'cbr';
							$thisfile_mpeg_audio['bitrate'] = getid3_mp3::ClosestStandardMP3Bitrate($thisfile_mpeg_audio['bitrate']);
							$ThisFileInfo['audio']['bitrate'] = $thisfile_mpeg_audio['bitrate'];
							//if (empty($thisfile_mpeg_audio['bitrate']) || (!empty($thisfile_mpeg_audio_lame['bitrate_min']) && ($thisfile_mpeg_audio_lame['bitrate_min'] != 255))) {
							//	$thisfile_mpeg_audio['bitrate'] = $thisfile_mpeg_audio_lame['bitrate_min'];
							//}

						}

					}
				}

			} else {

				// not Fraunhofer or Xing VBR methods, most likely CBR (but could be VBR with no header)
				$thisfile_mpeg_audio['bitrate_mode'] = 'cbr';
				if ($recursivesearch) {
					$thisfile_mpeg_audio['bitrate_mode'] = 'vbr';
					if (getid3_mp3::RecursiveFrameScanning($fd, $ThisFileInfo, $offset, $nextframetestoffset, true)) {
						$recursivesearch = false;
						$thisfile_mpeg_audio['bitrate_mode'] = 'cbr';
					}
					if ($thisfile_mpeg_audio['bitrate_mode'] == 'vbr') {
						$ThisFileInfo['warning'][] = 'VBR file with no VBR header. Bitrate values calculated from actual frame bitrates.';
					}
				}

			}

		}

		if (($ExpectedNumberOfAudioBytes > 0) && ($ExpectedNumberOfAudioBytes != ($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']))) {
			if ($ExpectedNumberOfAudioBytes > ($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset'])) {
				if (@$ThisFileInfo['fileformat'] == 'riff') {
					// ignore, audio data is broken into chunks so will always be data "missing"
				} elseif (($ExpectedNumberOfAudioBytes - ($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset'])) == 1) {
					$ThisFileInfo['warning'][] = 'Last byte of data truncated (this is a known bug in Meracl ID3 Tag Writer before v1.3.5)';
				} else {
					$ThisFileInfo['warning'][] = 'Probable truncated file: expecting '.$ExpectedNumberOfAudioBytes.' bytes of audio data, only found '.($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']).' (short by '.($ExpectedNumberOfAudioBytes - ($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset'])).' bytes)';
				}
			} else {
				if ((($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']) - $ExpectedNumberOfAudioBytes) == 1) {
				//	$prenullbytefileoffset = ftell($fd);
				//	fseek($fd, $ThisFileInfo['avdataend'], SEEK_SET);
				//	$PossibleNullByte = fread($fd, 1);
				//	fseek($fd, $prenullbytefileoffset, SEEK_SET);
				//	if ($PossibleNullByte === "\x00") {
						$ThisFileInfo['avdataend']--;
				//		$ThisFileInfo['warning'][] = 'Extra null byte at end of MP3 data assumed to be RIFF padding and therefore ignored';
				//	} else {
				//		$ThisFileInfo['warning'][] = 'Too much data in file: expecting '.$ExpectedNumberOfAudioBytes.' bytes of audio data, found '.($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']).' ('.(($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']) - $ExpectedNumberOfAudioBytes).' bytes too many)';
				//	}
				} else {
					$ThisFileInfo['warning'][] = 'Too much data in file: expecting '.$ExpectedNumberOfAudioBytes.' bytes of audio data, found '.($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']).' ('.(($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']) - $ExpectedNumberOfAudioBytes).' bytes too many)';
				}
			}
		}

		if (($thisfile_mpeg_audio['bitrate'] == 'free') && empty($ThisFileInfo['audio']['bitrate'])) {
			if (($offset == $ThisFileInfo['avdataoffset']) && empty($thisfile_mpeg_audio['VBR_frames'])) {
				$framebytelength = getid3_mp3::FreeFormatFrameLength($fd, $offset, $ThisFileInfo, true);
				if ($framebytelength > 0) {
					$thisfile_mpeg_audio['framelength'] = $framebytelength;
					if ($thisfile_mpeg_audio['layer'] == '1') {
						// BitRate = (((FrameLengthInBytes / 4) - Padding) * SampleRate) / 12
						$ThisFileInfo['audio']['bitrate'] = ((($framebytelength / 4) - intval($thisfile_mpeg_audio['padding'])) * $thisfile_mpeg_audio['sample_rate']) / 12;
					} else {
						// Bitrate = ((FrameLengthInBytes - Padding) * SampleRate) / 144
						$ThisFileInfo['audio']['bitrate'] = (($framebytelength - intval($thisfile_mpeg_audio['padding'])) * $thisfile_mpeg_audio['sample_rate']) / 144;
					}
				} else {
					$ThisFileInfo['error'][] = 'Error calculating frame length of free-format MP3 without Xing/LAME header';
				}
			}
		}

		if (@$thisfile_mpeg_audio['VBR_frames']) {
			switch ($thisfile_mpeg_audio['bitrate_mode']) {
				case 'vbr':
				case 'abr':
					if (($thisfile_mpeg_audio['version'] == '1') && ($thisfile_mpeg_audio['layer'] == 1)) {
						$thisfile_mpeg_audio['VBR_bitrate'] = ((@$thisfile_mpeg_audio['VBR_bytes'] / $thisfile_mpeg_audio['VBR_frames']) * 8) * ($ThisFileInfo['audio']['sample_rate'] / 384);
					} elseif ((($thisfile_mpeg_audio['version'] == '2') || ($thisfile_mpeg_audio['version'] == '2.5')) && ($thisfile_mpeg_audio['layer'] == 3)) {
						$thisfile_mpeg_audio['VBR_bitrate'] = ((@$thisfile_mpeg_audio['VBR_bytes'] / $thisfile_mpeg_audio['VBR_frames']) * 8) * ($ThisFileInfo['audio']['sample_rate'] / 576);
					} else {
						$thisfile_mpeg_audio['VBR_bitrate'] = ((@$thisfile_mpeg_audio['VBR_bytes'] / $thisfile_mpeg_audio['VBR_frames']) * 8) * ($ThisFileInfo['audio']['sample_rate'] / 1152);
					}
					if ($thisfile_mpeg_audio['VBR_bitrate'] > 0) {
						$ThisFileInfo['audio']['bitrate']         = $thisfile_mpeg_audio['VBR_bitrate'];
						$thisfile_mpeg_audio['bitrate'] = $thisfile_mpeg_audio['VBR_bitrate']; // to avoid confusion
					}
					break;
			}
		}

		// End variable-bitrate headers
		////////////////////////////////////////////////////////////////////////////////////

		if ($recursivesearch) {

			if (!getid3_mp3::RecursiveFrameScanning($fd, $ThisFileInfo, $offset, $nextframetestoffset, $ScanAsCBR)) {
				return false;
			}

		}


		//if (false) {
		//    // experimental side info parsing section - not returning anything useful yet
		//
		//    $SideInfoBitstream = getid3_lib::BigEndian2Bin($SideInfoData);
		//    $SideInfoOffset = 0;
		//
		//    if ($thisfile_mpeg_audio['version'] == '1') {
		//        if ($thisfile_mpeg_audio['channelmode'] == 'mono') {
		//            // MPEG-1 (mono)
		//            $thisfile_mpeg_audio['side_info']['main_data_begin'] = substr($SideInfoBitstream, $SideInfoOffset, 9);
		//            $SideInfoOffset += 9;
		//            $SideInfoOffset += 5;
		//        } else {
		//            // MPEG-1 (stereo, joint-stereo, dual-channel)
		//            $thisfile_mpeg_audio['side_info']['main_data_begin'] = substr($SideInfoBitstream, $SideInfoOffset, 9);
		//            $SideInfoOffset += 9;
		//            $SideInfoOffset += 3;
		//        }
		//    } else { // 2 or 2.5
		//        if ($thisfile_mpeg_audio['channelmode'] == 'mono') {
		//            // MPEG-2, MPEG-2.5 (mono)
		//            $thisfile_mpeg_audio['side_info']['main_data_begin'] = substr($SideInfoBitstream, $SideInfoOffset, 8);
		//            $SideInfoOffset += 8;
		//            $SideInfoOffset += 1;
		//        } else {
		//            // MPEG-2, MPEG-2.5 (stereo, joint-stereo, dual-channel)
		//            $thisfile_mpeg_audio['side_info']['main_data_begin'] = substr($SideInfoBitstream, $SideInfoOffset, 8);
		//            $SideInfoOffset += 8;
		//            $SideInfoOffset += 2;
		//        }
		//    }
		//
		//    if ($thisfile_mpeg_audio['version'] == '1') {
		//        for ($channel = 0; $channel < $ThisFileInfo['audio']['channels']; $channel++) {
		//            for ($scfsi_band = 0; $scfsi_band < 4; $scfsi_band++) {
		//                $thisfile_mpeg_audio['scfsi'][$channel][$scfsi_band] = substr($SideInfoBitstream, $SideInfoOffset, 1);
		//                $SideInfoOffset += 2;
		//            }
		//        }
		//    }
		//    for ($granule = 0; $granule < (($thisfile_mpeg_audio['version'] == '1') ? 2 : 1); $granule++) {
		//        for ($channel = 0; $channel < $ThisFileInfo['audio']['channels']; $channel++) {
		//            $thisfile_mpeg_audio['part2_3_length'][$granule][$channel] = substr($SideInfoBitstream, $SideInfoOffset, 12);
		//            $SideInfoOffset += 12;
		//            $thisfile_mpeg_audio['big_values'][$granule][$channel] = substr($SideInfoBitstream, $SideInfoOffset, 9);
		//            $SideInfoOffset += 9;
		//            $thisfile_mpeg_audio['global_gain'][$granule][$channel] = substr($SideInfoBitstream, $SideInfoOffset, 8);
		//            $SideInfoOffset += 8;
		//            if ($thisfile_mpeg_audio['version'] == '1') {
		//                $thisfile_mpeg_audio['scalefac_compress'][$granule][$channel] = substr($SideInfoBitstream, $SideInfoOffset, 4);
		//                $SideInfoOffset += 4;
		//            } else {
		//                $thisfile_mpeg_audio['scalefac_compress'][$granule][$channel] = substr($SideInfoBitstream, $SideInfoOffset, 9);
		//                $SideInfoOffset += 9;
		//            }
		//            $thisfile_mpeg_audio['window_switching_flag'][$granule][$channel] = substr($SideInfoBitstream, $SideInfoOffset, 1);
		//            $SideInfoOffset += 1;
		//
		//            if ($thisfile_mpeg_audio['window_switching_flag'][$granule][$channel] == '1') {
		//
		//                $thisfile_mpeg_audio['block_type'][$granule][$channel] = substr($SideInfoBitstream, $SideInfoOffset, 2);
		//                $SideInfoOffset += 2;
		//                $thisfile_mpeg_audio['mixed_block_flag'][$granule][$channel] = substr($SideInfoBitstream, $SideInfoOffset, 1);
		//                $SideInfoOffset += 1;
		//
		//                for ($region = 0; $region < 2; $region++) {
		//                    $thisfile_mpeg_audio['table_select'][$granule][$channel][$region] = substr($SideInfoBitstream, $SideInfoOffset, 5);
		//                    $SideInfoOffset += 5;
		//                }
		//                $thisfile_mpeg_audio['table_select'][$granule][$channel][2] = 0;
		//
		//                for ($window = 0; $window < 3; $window++) {
		//                    $thisfile_mpeg_audio['subblock_gain'][$granule][$channel][$window] = substr($SideInfoBitstream, $SideInfoOffset, 3);
		//                    $SideInfoOffset += 3;
		//                }
		//
		//            } else {
		//
		//                for ($region = 0; $region < 3; $region++) {
		//                    $thisfile_mpeg_audio['table_select'][$granule][$channel][$region] = substr($SideInfoBitstream, $SideInfoOffset, 5);
		//                    $SideInfoOffset += 5;
		//                }
		//
		//                $thisfile_mpeg_audio['region0_count'][$granule][$channel] = substr($SideInfoBitstream, $SideInfoOffset, 4);
		//                $SideInfoOffset += 4;
		//                $thisfile_mpeg_audio['region1_count'][$granule][$channel] = substr($SideInfoBitstream, $SideInfoOffset, 3);
		//                $SideInfoOffset += 3;
		//                $thisfile_mpeg_audio['block_type'][$granule][$channel] = 0;
		//            }
		//
		//            if ($thisfile_mpeg_audio['version'] == '1') {
		//                $thisfile_mpeg_audio['preflag'][$granule][$channel] = substr($SideInfoBitstream, $SideInfoOffset, 1);
		//                $SideInfoOffset += 1;
		//            }
		//            $thisfile_mpeg_audio['scalefac_scale'][$granule][$channel] = substr($SideInfoBitstream, $SideInfoOffset, 1);
		//            $SideInfoOffset += 1;
		//            $thisfile_mpeg_audio['count1table_select'][$granule][$channel] = substr($SideInfoBitstream, $SideInfoOffset, 1);
		//            $SideInfoOffset += 1;
		//        }
		//    }
		//}

		return true;
	}

	function RecursiveFrameScanning(&$fd, &$ThisFileInfo, &$offset, &$nextframetestoffset, $ScanAsCBR) {
		for ($i = 0; $i < GETID3_MP3_VALID_CHECK_FRAMES; $i++) {
			// check next GETID3_MP3_VALID_CHECK_FRAMES frames for validity, to make sure we haven't run across a false synch
			if (($nextframetestoffset + 4) >= $ThisFileInfo['avdataend']) {
				// end of file
				return true;
			}

			$nextframetestarray = array('error'=>'', 'warning'=>'', 'avdataend'=>$ThisFileInfo['avdataend'], 'avdataoffset'=>$ThisFileInfo['avdataoffset']);
			if (getid3_mp3::decodeMPEGaudioHeader($fd, $nextframetestoffset, $nextframetestarray, false)) {
				if ($ScanAsCBR) {
					// force CBR mode, used for trying to pick out invalid audio streams with
					// valid(?) VBR headers, or VBR streams with no VBR header
					if (!isset($nextframetestarray['mpeg']['audio']['bitrate']) || !isset($ThisFileInfo['mpeg']['audio']['bitrate']) || ($nextframetestarray['mpeg']['audio']['bitrate'] != $ThisFileInfo['mpeg']['audio']['bitrate'])) {
						return false;
					}
				}


				// next frame is OK, get ready to check the one after that
				if (isset($nextframetestarray['mpeg']['audio']['framelength']) && ($nextframetestarray['mpeg']['audio']['framelength'] > 0)) {
					$nextframetestoffset += $nextframetestarray['mpeg']['audio']['framelength'];
				} else {
					$ThisFileInfo['error'][] = 'Frame at offset ('.$offset.') is has an invalid frame length.';
					return false;
				}

			} else {

				// next frame is not valid, note the error and fail, so scanning can contiue for a valid frame sequence
				$ThisFileInfo['error'][] = 'Frame at offset ('.$offset.') is valid, but the next one at ('.$nextframetestoffset.') is not.';

				return false;
			}
		}
		return true;
	}

	function FreeFormatFrameLength($fd, $offset, &$ThisFileInfo, $deepscan=false) {
		fseek($fd, $offset, SEEK_SET);
		$MPEGaudioData = fread($fd, 32768);

		$SyncPattern1 = substr($MPEGaudioData, 0, 4);
		// may be different pattern due to padding
		$SyncPattern2 = $SyncPattern1{0}.$SyncPattern1{1}.chr(ord($SyncPattern1{2}) | 0x02).$SyncPattern1{3};
		if ($SyncPattern2 === $SyncPattern1) {
			$SyncPattern2 = $SyncPattern1{0}.$SyncPattern1{1}.chr(ord($SyncPattern1{2}) & 0xFD).$SyncPattern1{3};
		}

		$framelength = false;
		$framelength1 = strpos($MPEGaudioData, $SyncPattern1, 4);
		$framelength2 = strpos($MPEGaudioData, $SyncPattern2, 4);
		if ($framelength1 > 4) {
			$framelength = $framelength1;
		}
		if (($framelength2 > 4) && ($framelength2 < $framelength1)) {
			$framelength = $framelength2;
		}
		if (!$framelength) {

			// LAME 3.88 has a different value for modeextension on the first frame vs the rest
			$framelength1 = strpos($MPEGaudioData, substr($SyncPattern1, 0, 3), 4);
			$framelength2 = strpos($MPEGaudioData, substr($SyncPattern2, 0, 3), 4);

			if ($framelength1 > 4) {
				$framelength = $framelength1;
			}
			if (($framelength2 > 4) && ($framelength2 < $framelength1)) {
				$framelength = $framelength2;
			}
			if (!$framelength) {
				$ThisFileInfo['error'][] = 'Cannot find next free-format synch pattern ('.getid3_lib::PrintHexBytes($SyncPattern1).' or '.getid3_lib::PrintHexBytes($SyncPattern2).') after offset '.$offset;
				return false;
			} else {
				$ThisFileInfo['warning'][] = 'ModeExtension varies between first frame and other frames (known free-format issue in LAME 3.88)';
				$ThisFileInfo['audio']['codec']   = 'LAME';
				$ThisFileInfo['audio']['encoder'] = 'LAME3.88';
				$SyncPattern1 = substr($SyncPattern1, 0, 3);
				$SyncPattern2 = substr($SyncPattern2, 0, 3);
			}
		}

		if ($deepscan) {

			$ActualFrameLengthValues = array();
			$nextoffset = $offset + $framelength;
			while ($nextoffset < ($ThisFileInfo['avdataend'] - 6)) {
				fseek($fd, $nextoffset - 1, SEEK_SET);
				$NextSyncPattern = fread($fd, 6);
				if ((substr($NextSyncPattern, 1, strlen($SyncPattern1)) == $SyncPattern1) || (substr($NextSyncPattern, 1, strlen($SyncPattern2)) == $SyncPattern2)) {
					// good - found where expected
					$ActualFrameLengthValues[] = $framelength;
				} elseif ((substr($NextSyncPattern, 0, strlen($SyncPattern1)) == $SyncPattern1) || (substr($NextSyncPattern, 0, strlen($SyncPattern2)) == $SyncPattern2)) {
					// ok - found one byte earlier than expected (last frame wasn't padded, first frame was)
					$ActualFrameLengthValues[] = ($framelength - 1);
					$nextoffset--;
				} elseif ((substr($NextSyncPattern, 2, strlen($SyncPattern1)) == $SyncPattern1) || (substr($NextSyncPattern, 2, strlen($SyncPattern2)) == $SyncPattern2)) {
					// ok - found one byte later than expected (last frame was padded, first frame wasn't)
					$ActualFrameLengthValues[] = ($framelength + 1);
					$nextoffset++;
				} else {
					$ThisFileInfo['error'][] = 'Did not find expected free-format sync pattern at offset '.$nextoffset;
					return false;
				}
				$nextoffset += $framelength;
			}
			if (count($ActualFrameLengthValues) > 0) {
				$framelength = intval(round(array_sum($ActualFrameLengthValues) / count($ActualFrameLengthValues)));
			}
		}
		return $framelength;
	}

	function getOnlyMPEGaudioInfoBruteForce($fd, &$ThisFileInfo) {

		$MPEGaudioHeaderDecodeCache = array();
		$MPEGaudioHeaderValidCache  = array();
		$MPEGaudioHeaderLengthCache = array();
		$MPEGaudioVersionLookup       = getid3_mp3::MPEGaudioVersionArray();
		$MPEGaudioLayerLookup         = getid3_mp3::MPEGaudioLayerArray();
		$MPEGaudioBitrateLookup       = getid3_mp3::MPEGaudioBitrateArray();
		$MPEGaudioFrequencyLookup     = getid3_mp3::MPEGaudioFrequencyArray();
		$MPEGaudioChannelModeLookup   = getid3_mp3::MPEGaudioChannelModeArray();
		$MPEGaudioModeExtensionLookup = getid3_mp3::MPEGaudioModeExtensionArray();
		$MPEGaudioEmphasisLookup      = getid3_mp3::MPEGaudioEmphasisArray();
		$LongMPEGversionLookup   = array();
		$LongMPEGlayerLookup     = array();
		$LongMPEGbitrateLookup   = array();
		$LongMPEGpaddingLookup   = array();
		$LongMPEGfrequencyLookup = array();

		$Distribution['bitrate']   = array();
		$Distribution['frequency'] = array();
		$Distribution['layer']     = array();
		$Distribution['version']   = array();
		$Distribution['padding']   = array();

		fseek($fd, $ThisFileInfo['avdataoffset'], SEEK_SET);

		$max_frames_scan = 5000;
		$frames_scanned  = 0;

		$previousvalidframe = $ThisFileInfo['avdataoffset'];
		while (ftell($fd) < $ThisFileInfo['avdataend']) {
			set_time_limit(30);
			$head4 = fread($fd, 4);
			if (strlen($head4) < 4) {
				break;
			}
			if ($head4{0} != "\xFF") {
				for ($i = 1; $i < 4; $i++) {
					if ($head4{$i} == "\xFF") {
						fseek($fd, $i - 4, SEEK_CUR);
						continue 2;
					}
				}
				continue;
			}
			if (!isset($MPEGaudioHeaderDecodeCache[$head4])) {
				$MPEGaudioHeaderDecodeCache[$head4] = getid3_mp3::MPEGaudioHeaderDecode($head4);
			}
			if (!isset($MPEGaudioHeaderValidCache[$head4])) {
				$MPEGaudioHeaderValidCache[$head4] = getid3_mp3::MPEGaudioHeaderValid($MPEGaudioHeaderDecodeCache[$head4], false, false);
			}
			if ($MPEGaudioHeaderValidCache[$head4]) {

				if (!isset($MPEGaudioHeaderLengthCache[$head4])) {
					$LongMPEGversionLookup[$head4]   = $MPEGaudioVersionLookup[$MPEGaudioHeaderDecodeCache[$head4]['version']];
					$LongMPEGlayerLookup[$head4]     = $MPEGaudioLayerLookup[$MPEGaudioHeaderDecodeCache[$head4]['layer']];
					$LongMPEGbitrateLookup[$head4]   = $MPEGaudioBitrateLookup[$LongMPEGversionLookup[$head4]][$LongMPEGlayerLookup[$head4]][$MPEGaudioHeaderDecodeCache[$head4]['bitrate']];
					$LongMPEGpaddingLookup[$head4]   = (bool) $MPEGaudioHeaderDecodeCache[$head4]['padding'];
					$LongMPEGfrequencyLookup[$head4] = $MPEGaudioFrequencyLookup[$LongMPEGversionLookup[$head4]][$MPEGaudioHeaderDecodeCache[$head4]['sample_rate']];
					$MPEGaudioHeaderLengthCache[$head4] = getid3_mp3::MPEGaudioFrameLength(
						$LongMPEGbitrateLookup[$head4],
						$LongMPEGversionLookup[$head4],
						$LongMPEGlayerLookup[$head4],
						$LongMPEGpaddingLookup[$head4],
						$LongMPEGfrequencyLookup[$head4]);
				}
				if ($MPEGaudioHeaderLengthCache[$head4] > 4) {
					$WhereWeWere = ftell($fd);
					fseek($fd, $MPEGaudioHeaderLengthCache[$head4] - 4, SEEK_CUR);
					$next4 = fread($fd, 4);
					if ($next4{0} == "\xFF") {
						if (!isset($MPEGaudioHeaderDecodeCache[$next4])) {
							$MPEGaudioHeaderDecodeCache[$next4] = getid3_mp3::MPEGaudioHeaderDecode($next4);
						}
						if (!isset($MPEGaudioHeaderValidCache[$next4])) {
							$MPEGaudioHeaderValidCache[$next4] = getid3_mp3::MPEGaudioHeaderValid($MPEGaudioHeaderDecodeCache[$next4], false, false);
						}
						if ($MPEGaudioHeaderValidCache[$next4]) {
							fseek($fd, -4, SEEK_CUR);

							@$Distribution['bitrate'][$LongMPEGbitrateLookup[$head4]]++;
							@$Distribution['layer'][$LongMPEGlayerLookup[$head4]]++;
							@$Distribution['version'][$LongMPEGversionLookup[$head4]]++;
							@$Distribution['padding'][intval($LongMPEGpaddingLookup[$head4])]++;
							@$Distribution['frequency'][$LongMPEGfrequencyLookup[$head4]]++;
							if ($max_frames_scan && (++$frames_scanned >= $max_frames_scan)) {
								$pct_data_scanned = (ftell($fd) - $ThisFileInfo['avdataoffset']) / ($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']);
								$ThisFileInfo['warning'][] = 'too many MPEG audio frames to scan, only scanned first '.$max_frames_scan.' frames ('.number_format($pct_data_scanned * 100, 1).'% of file) and extrapolated distribution, playtime and bitrate may be incorrect.';
								foreach ($Distribution as $key1 => $value1) {
									foreach ($value1 as $key2 => $value2) {
										$Distribution[$key1][$key2] = round($value2 / $pct_data_scanned);
									}
								}
								break;
							}
							continue;
						}
					}
					unset($next4);
					fseek($fd, $WhereWeWere - 3, SEEK_SET);
				}

			}
		}
		foreach ($Distribution as $key => $value) {
			ksort($Distribution[$key], SORT_NUMERIC);
		}
		ksort($Distribution['version'], SORT_STRING);
		$ThisFileInfo['mpeg']['audio']['bitrate_distribution']   = $Distribution['bitrate'];
		$ThisFileInfo['mpeg']['audio']['frequency_distribution'] = $Distribution['frequency'];
		$ThisFileInfo['mpeg']['audio']['layer_distribution']     = $Distribution['layer'];
		$ThisFileInfo['mpeg']['audio']['version_distribution']   = $Distribution['version'];
		$ThisFileInfo['mpeg']['audio']['padding_distribution']   = $Distribution['padding'];
		if (count($Distribution['version']) > 1) {
			$ThisFileInfo['error'][] = 'Corrupt file - more than one MPEG version detected';
		}
		if (count($Distribution['layer']) > 1) {
			$ThisFileInfo['error'][] = 'Corrupt file - more than one MPEG layer detected';
		}
		if (count($Distribution['frequency']) > 1) {
			$ThisFileInfo['error'][] = 'Corrupt file - more than one MPEG sample rate detected';
		}


		$bittotal = 0;
		foreach ($Distribution['bitrate'] as $bitratevalue => $bitratecount) {
			if ($bitratevalue != 'free') {
				$bittotal += ($bitratevalue * $bitratecount);
			}
		}
		$ThisFileInfo['mpeg']['audio']['frame_count']  = array_sum($Distribution['bitrate']);
		if ($ThisFileInfo['mpeg']['audio']['frame_count'] == 0) {
			$ThisFileInfo['error'][] = 'no MPEG audio frames found';
			return false;
		}
		$ThisFileInfo['mpeg']['audio']['bitrate']      = ($bittotal / $ThisFileInfo['mpeg']['audio']['frame_count']);
		$ThisFileInfo['mpeg']['audio']['bitrate_mode'] = ((count($Distribution['bitrate']) > 0) ? 'vbr' : 'cbr');
		$ThisFileInfo['mpeg']['audio']['sample_rate']  = getid3_lib::array_max($Distribution['frequency'], true);

		$ThisFileInfo['audio']['bitrate']      = $ThisFileInfo['mpeg']['audio']['bitrate'];
		$ThisFileInfo['audio']['bitrate_mode'] = $ThisFileInfo['mpeg']['audio']['bitrate_mode'];
		$ThisFileInfo['audio']['sample_rate']  = $ThisFileInfo['mpeg']['audio']['sample_rate'];
		$ThisFileInfo['audio']['dataformat']   = 'mp'.getid3_lib::array_max($Distribution['layer'], true);
		$ThisFileInfo['fileformat']            = $ThisFileInfo['audio']['dataformat'];

		return true;
	}


	function getOnlyMPEGaudioInfo($fd, &$ThisFileInfo, $avdataoffset, $BitrateHistogram=false) {

		// looks for synch, decodes MPEG audio header

		static $MPEGaudioVersionLookup;
		static $MPEGaudioLayerLookup;
		static $MPEGaudioBitrateLookup;
		if (empty($MPEGaudioVersionLookup)) {
		   $MPEGaudioVersionLookup = getid3_mp3::MPEGaudioVersionArray();
		   $MPEGaudioLayerLookup   = getid3_mp3::MPEGaudioLayerArray();
		   $MPEGaudioBitrateLookup = getid3_mp3::MPEGaudioBitrateArray();

		}

		fseek($fd, $avdataoffset, SEEK_SET);
		$sync_seek_buffer_size = min(128 * 1024, $ThisFileInfo['avdataend'] - $avdataoffset);
		if ($sync_seek_buffer_size <= 0) {
			$ThisFileInfo['error'][] = 'Invalid $sync_seek_buffer_size at offset '.$avdataoffset;
			return false;
		}
		$header = fread($fd, $sync_seek_buffer_size);
		$sync_seek_buffer_size = strlen($header);
		$SynchSeekOffset = 0;
		while ($SynchSeekOffset < $sync_seek_buffer_size) {

			if ((($avdataoffset + $SynchSeekOffset)  < $ThisFileInfo['avdataend']) && !feof($fd)) {

				if ($SynchSeekOffset > $sync_seek_buffer_size) {
					// if a synch's not found within the first 128k bytes, then give up
					$ThisFileInfo['error'][] = 'Could not find valid MPEG audio synch within the first '.round($sync_seek_buffer_size / 1024).'kB';
					if (isset($ThisFileInfo['audio']['bitrate'])) {
						unset($ThisFileInfo['audio']['bitrate']);
					}
					if (isset($ThisFileInfo['mpeg']['audio'])) {
						unset($ThisFileInfo['mpeg']['audio']);
					}
					if (empty($ThisFileInfo['mpeg'])) {
						unset($ThisFileInfo['mpeg']);
					}
					return false;

				} elseif (feof($fd)) {

					$ThisFileInfo['error'][] = 'Could not find valid MPEG audio synch before end of file';
					if (isset($ThisFileInfo['audio']['bitrate'])) {
						unset($ThisFileInfo['audio']['bitrate']);
					}
					if (isset($ThisFileInfo['mpeg']['audio'])) {
						unset($ThisFileInfo['mpeg']['audio']);
					}
					if (isset($ThisFileInfo['mpeg']) && (!is_array($ThisFileInfo['mpeg']) || (count($ThisFileInfo['mpeg']) == 0))) {
						unset($ThisFileInfo['mpeg']);
					}
					return false;
				}
			}

			if (($SynchSeekOffset + 1) >= strlen($header)) {
				$ThisFileInfo['error'][] = 'Could not find valid MPEG synch before end of file';
				return false;
			}

			if (($header{$SynchSeekOffset} == "\xFF") && ($header{($SynchSeekOffset + 1)} > "\xE0")) { // synch detected

				if (!isset($FirstFrameThisfileInfo) && !isset($ThisFileInfo['mpeg']['audio'])) {
					$FirstFrameThisfileInfo = $ThisFileInfo;
					$FirstFrameAVDataOffset = $avdataoffset + $SynchSeekOffset;
					if (!getid3_mp3::decodeMPEGaudioHeader($fd, $avdataoffset + $SynchSeekOffset, $FirstFrameThisfileInfo, false)) {
						// if this is the first valid MPEG-audio frame, save it in case it's a VBR header frame and there's
						// garbage between this frame and a valid sequence of MPEG-audio frames, to be restored below
						unset($FirstFrameThisfileInfo);
					}
				}

				$dummy = $ThisFileInfo; // only overwrite real data if valid header found
				if (getid3_mp3::decodeMPEGaudioHeader($fd, $avdataoffset + $SynchSeekOffset, $dummy, true)) {
					$ThisFileInfo = $dummy;
					$ThisFileInfo['avdataoffset'] = $avdataoffset + $SynchSeekOffset;
					switch (@$ThisFileInfo['fileformat']) {
						case '':
						case 'id3':
						case 'ape':
						case 'mp3':
							$ThisFileInfo['fileformat']          = 'mp3';
							$ThisFileInfo['audio']['dataformat'] = 'mp3';
							break;
					}
					if (isset($FirstFrameThisfileInfo['mpeg']['audio']['bitrate_mode']) && ($FirstFrameThisfileInfo['mpeg']['audio']['bitrate_mode'] == 'vbr')) {
						if (!(abs($ThisFileInfo['audio']['bitrate'] - $FirstFrameThisfileInfo['audio']['bitrate']) <= 1)) {
							// If there is garbage data between a valid VBR header frame and a sequence
							// of valid MPEG-audio frames the VBR data is no longer discarded.
							$ThisFileInfo = $FirstFrameThisfileInfo;
							$ThisFileInfo['avdataoffset']        = $FirstFrameAVDataOffset;
							$ThisFileInfo['fileformat']          = 'mp3';
							$ThisFileInfo['audio']['dataformat'] = 'mp3';
							$dummy                               = $ThisFileInfo;
							unset($dummy['mpeg']['audio']);
							$GarbageOffsetStart = $FirstFrameAVDataOffset + $FirstFrameThisfileInfo['mpeg']['audio']['framelength'];
							$GarbageOffsetEnd   = $avdataoffset + $SynchSeekOffset;
							if (getid3_mp3::decodeMPEGaudioHeader($fd, $GarbageOffsetEnd, $dummy, true, true)) {

								$ThisFileInfo = $dummy;
								$ThisFileInfo['avdataoffset'] = $GarbageOffsetEnd;
								$ThisFileInfo['warning'][] = 'apparently-valid VBR header not used because could not find '.GETID3_MP3_VALID_CHECK_FRAMES.' consecutive MPEG-audio frames immediately after VBR header (garbage data for '.($GarbageOffsetEnd - $GarbageOffsetStart).' bytes between '.$GarbageOffsetStart.' and '.$GarbageOffsetEnd.'), but did find valid CBR stream starting at '.$GarbageOffsetEnd;

							} else {

								$ThisFileInfo['warning'][] = 'using data from VBR header even though could not find '.GETID3_MP3_VALID_CHECK_FRAMES.' consecutive MPEG-audio frames immediately after VBR header (garbage data for '.($GarbageOffsetEnd - $GarbageOffsetStart).' bytes between '.$GarbageOffsetStart.' and '.$GarbageOffsetEnd.')';

							}
						}
					}
					if (isset($ThisFileInfo['mpeg']['audio']['bitrate_mode']) && ($ThisFileInfo['mpeg']['audio']['bitrate_mode'] == 'vbr') && !isset($ThisFileInfo['mpeg']['audio']['VBR_method'])) {
						// VBR file with no VBR header
						$BitrateHistogram = true;
					}

					if ($BitrateHistogram) {

						$ThisFileInfo['mpeg']['audio']['stereo_distribution']  = array('stereo'=>0, 'joint stereo'=>0, 'dual channel'=>0, 'mono'=>0);
						$ThisFileInfo['mpeg']['audio']['version_distribution'] = array('1'=>0, '2'=>0, '2.5'=>0);

						if ($ThisFileInfo['mpeg']['audio']['version'] == '1') {
							if ($ThisFileInfo['mpeg']['audio']['layer'] == 3) {
								$ThisFileInfo['mpeg']['audio']['bitrate_distribution'] = array('free'=>0, 32000=>0, 40000=>0, 48000=>0, 56000=>0, 64000=>0, 80000=>0, 96000=>0, 112000=>0, 128000=>0, 160000=>0, 192000=>0, 224000=>0, 256000=>0, 320000=>0);
							} elseif ($ThisFileInfo['mpeg']['audio']['layer'] == 2) {
								$ThisFileInfo['mpeg']['audio']['bitrate_distribution'] = array('free'=>0, 32000=>0, 48000=>0, 56000=>0, 64000=>0, 80000=>0, 96000=>0, 112000=>0, 128000=>0, 160000=>0, 192000=>0, 224000=>0, 256000=>0, 320000=>0, 384000=>0);
							} elseif ($ThisFileInfo['mpeg']['audio']['layer'] == 1) {
								$ThisFileInfo['mpeg']['audio']['bitrate_distribution'] = array('free'=>0, 32000=>0, 64000=>0, 96000=>0, 128000=>0, 160000=>0, 192000=>0, 224000=>0, 256000=>0, 288000=>0, 320000=>0, 352000=>0, 384000=>0, 416000=>0, 448000=>0);
							}
						} elseif ($ThisFileInfo['mpeg']['audio']['layer'] == 1) {
							$ThisFileInfo['mpeg']['audio']['bitrate_distribution'] = array('free'=>0, 32000=>0, 48000=>0, 56000=>0, 64000=>0, 80000=>0, 96000=>0, 112000=>0, 128000=>0, 144000=>0, 160000=>0, 176000=>0, 192000=>0, 224000=>0, 256000=>0);
						} else {
							$ThisFileInfo['mpeg']['audio']['bitrate_distribution'] = array('free'=>0, 8000=>0, 16000=>0, 24000=>0, 32000=>0, 40000=>0, 48000=>0, 56000=>0, 64000=>0, 80000=>0, 96000=>0, 112000=>0, 128000=>0, 144000=>0, 160000=>0);
						}

						$dummy = array('error'=>$ThisFileInfo['error'], 'warning'=>$ThisFileInfo['warning'], 'avdataend'=>$ThisFileInfo['avdataend'], 'avdataoffset'=>$ThisFileInfo['avdataoffset']);
						$synchstartoffset = $ThisFileInfo['avdataoffset'];
						fseek($fd, $ThisFileInfo['avdataoffset'], SEEK_SET);

						// you can play with these numbers:
						$max_frames_scan  = 50000;
						$max_scan_segments = 10;

						// don't play with these numbers:
						$FastMode = false;
						$SynchErrorsFound = 0;
						$frames_scanned   = 0;
						$this_scan_segment = 0;
						$frames_scan_per_segment = ceil($max_frames_scan / $max_scan_segments);
						$pct_data_scanned = 0;
						for ($current_segment = 0; $current_segment < $max_scan_segments; $current_segment++) {
//echo 'was at '.ftell($fd).'<br>';
							$frames_scanned_this_segment = 0;
							if (ftell($fd) >= $ThisFileInfo['avdataend']) {
//echo 'breaking because current position ('.ftell($fd).') is >= $ThisFileInfo[avdataend] ('.$ThisFileInfo['avdataend'].')<br>';
								break;
							}
							$scan_start_offset[$current_segment] = max(ftell($fd), $ThisFileInfo['avdataoffset'] + round($current_segment * (($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']) / $max_scan_segments)));
//echo 'start at '.$scan_start_offset[$current_segment].'<br>';
							if ($current_segment > 0) {
								fseek($fd, $scan_start_offset[$current_segment], SEEK_SET);
								$buffer_4k = fread($fd, 4096);
								for ($j = 0; $j < (strlen($buffer_4k) - 4); $j++) {
									if (($buffer_4k{$j} == "\xFF") && ($buffer_4k{($j + 1)} > "\xE0")) { // synch detected
										if (getid3_mp3::decodeMPEGaudioHeader($fd, $scan_start_offset[$current_segment] + $j, $dummy, false, false, $FastMode)) {
											$calculated_next_offset = $scan_start_offset[$current_segment] + $j + $dummy['mpeg']['audio']['framelength'];
											if (getid3_mp3::decodeMPEGaudioHeader($fd, $calculated_next_offset, $dummy, false, false, $FastMode)) {
												$scan_start_offset[$current_segment] += $j;
												break;
											} else {
//echo 'header['.__LINE__.'] at '.($calculated_next_offset).' invalid<br>';
											}
										} else {
//echo 'header['.__LINE__.'] at '.($scan_start_offset[$current_segment] + $j).' invalid<br>';
										}
									}
								}
							}
//echo 'actually start at '.$scan_start_offset[$current_segment].'<br>';
							$synchstartoffset = $scan_start_offset[$current_segment];
							while (getid3_mp3::decodeMPEGaudioHeader($fd, $synchstartoffset, $dummy, false, false, $FastMode)) {
								$FastMode = true;
								$thisframebitrate = $MPEGaudioBitrateLookup[$MPEGaudioVersionLookup[$dummy['mpeg']['audio']['raw']['version']]][$MPEGaudioLayerLookup[$dummy['mpeg']['audio']['raw']['layer']]][$dummy['mpeg']['audio']['raw']['bitrate']];

								if (empty($dummy['mpeg']['audio']['framelength'])) {
									$SynchErrorsFound++;
									$synchstartoffset++;
//echo ' [Ø] ';
								} else {
//echo ' . ';
									@$ThisFileInfo['mpeg']['audio']['bitrate_distribution'][$thisframebitrate]++;
									@$ThisFileInfo['mpeg']['audio']['stereo_distribution'][$dummy['mpeg']['audio']['channelmode']]++;
									@$ThisFileInfo['mpeg']['audio']['version_distribution'][$dummy['mpeg']['audio']['version']]++;

									$synchstartoffset += $dummy['mpeg']['audio']['framelength'];
								}
								$frames_scanned++;
								if ($frames_scan_per_segment && (++$frames_scanned_this_segment >= $frames_scan_per_segment)) {
									$this_pct_scanned = (ftell($fd) - $scan_start_offset[$current_segment]) / ($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']);
									if (($current_segment == 0) && (($this_pct_scanned * $max_scan_segments) >= 1)) {
										// file likely contains < $max_frames_scan, just scan as one segment
										$max_scan_segments = 1;
										$frames_scan_per_segment = $max_frames_scan;
									} else {
										$pct_data_scanned += $this_pct_scanned;
//var_dump($pct_data_scanned);
//exit;
										break;
									}
								}
							}
//echo '<hr>';
						}
						if ($pct_data_scanned > 0) {
							$ThisFileInfo['warning'][] = 'too many MPEG audio frames to scan, only scanned '.$frames_scanned.' frames in '.$max_scan_segments.' segments ('.number_format($pct_data_scanned * 100, 1).'% of file) and extrapolated distribution, playtime and bitrate may be incorrect.';
							foreach ($ThisFileInfo['mpeg']['audio'] as $key1 => $value1) {
								if (!eregi('_distribution$', $key1)) {
									continue;
								}
								foreach ($value1 as $key2 => $value2) {
									$ThisFileInfo['mpeg']['audio'][$key1][$key2] = round($value2 / $pct_data_scanned);
								}
							}
						}

						if ($SynchErrorsFound > 0) {
							$ThisFileInfo['warning'][] = 'Found '.$SynchErrorsFound.' synch errors in histogram analysis';
							//return false;
						}

						$bittotal     = 0;
						$framecounter = 0;
						foreach ($ThisFileInfo['mpeg']['audio']['bitrate_distribution'] as $bitratevalue => $bitratecount) {
							$framecounter += $bitratecount;
							if ($bitratevalue != 'free') {
								$bittotal += ($bitratevalue * $bitratecount);
							}
						}
						if ($framecounter == 0) {
							$ThisFileInfo['error'][] = 'Corrupt MP3 file: framecounter == zero';
							return false;
						}
						$ThisFileInfo['mpeg']['audio']['frame_count'] = getid3_lib::CastAsInt($framecounter);
						$ThisFileInfo['mpeg']['audio']['bitrate']     = ($bittotal / $framecounter);

						$ThisFileInfo['audio']['bitrate'] = $ThisFileInfo['mpeg']['audio']['bitrate'];


						// Definitively set VBR vs CBR, even if the Xing/LAME/VBRI header says differently
						$distinct_bitrates = 0;
						foreach ($ThisFileInfo['mpeg']['audio']['bitrate_distribution'] as $bitrate_value => $bitrate_count) {
							if ($bitrate_count > 0) {
								$distinct_bitrates++;
							}
						}
						if ($distinct_bitrates > 1) {
							$ThisFileInfo['mpeg']['audio']['bitrate_mode'] = 'vbr';
						} else {
							$ThisFileInfo['mpeg']['audio']['bitrate_mode'] = 'cbr';
						}
						$ThisFileInfo['audio']['bitrate_mode'] = $ThisFileInfo['mpeg']['audio']['bitrate_mode'];

					}

					break; // exit while()
				}
			}

			$SynchSeekOffset++;
			if (($avdataoffset + $SynchSeekOffset) >= $ThisFileInfo['avdataend']) {
				// end of file/data

				if (empty($ThisFileInfo['mpeg']['audio'])) {

					$ThisFileInfo['error'][] = 'could not find valid MPEG synch before end of file';
					if (isset($ThisFileInfo['audio']['bitrate'])) {
						unset($ThisFileInfo['audio']['bitrate']);
					}
					if (isset($ThisFileInfo['mpeg']['audio'])) {
						unset($ThisFileInfo['mpeg']['audio']);
					}
					if (isset($ThisFileInfo['mpeg']) && (!is_array($ThisFileInfo['mpeg']) || empty($ThisFileInfo['mpeg']))) {
						unset($ThisFileInfo['mpeg']);
					}
					return false;

				}
				break;
			}

		}
		$ThisFileInfo['audio']['channels']        = $ThisFileInfo['mpeg']['audio']['channels'];
		$ThisFileInfo['audio']['channelmode']     = $ThisFileInfo['mpeg']['audio']['channelmode'];
		$ThisFileInfo['audio']['sample_rate']     = $ThisFileInfo['mpeg']['audio']['sample_rate'];
		return true;
	}


	function MPEGaudioVersionArray() {
		static $MPEGaudioVersion = array('2.5', false, '2', '1');
		return $MPEGaudioVersion;
	}

	function MPEGaudioLayerArray() {
		static $MPEGaudioLayer = array(false, 3, 2, 1);
		return $MPEGaudioLayer;
	}

	function MPEGaudioBitrateArray() {
		static $MPEGaudioBitrate;
		if (empty($MPEGaudioBitrate)) {
			$MPEGaudioBitrate = array (
				'1'  =>  array (1 => array('free', 32000, 64000, 96000, 128000, 160000, 192000, 224000, 256000, 288000, 320000, 352000, 384000, 416000, 448000),
								2 => array('free', 32000, 48000, 56000,  64000,  80000,  96000, 112000, 128000, 160000, 192000, 224000, 256000, 320000, 384000),
								3 => array('free', 32000, 40000, 48000,  56000,  64000,  80000,  96000, 112000, 128000, 160000, 192000, 224000, 256000, 320000)
							   ),

				'2'  =>  array (1 => array('free', 32000, 48000, 56000,  64000,  80000,  96000, 112000, 128000, 144000, 160000, 176000, 192000, 224000, 256000),
								2 => array('free',  8000, 16000, 24000,  32000,  40000,  48000,  56000,  64000,  80000,  96000, 112000, 128000, 144000, 160000),
							   )
			);
			$MPEGaudioBitrate['2'][3] = $MPEGaudioBitrate['2'][2];
			$MPEGaudioBitrate['2.5']  = $MPEGaudioBitrate['2'];
		}
		return $MPEGaudioBitrate;
	}

	function MPEGaudioFrequencyArray() {
		static $MPEGaudioFrequency;
		if (empty($MPEGaudioFrequency)) {
			$MPEGaudioFrequency = array (
				'1'   => array(44100, 48000, 32000),
				'2'   => array(22050, 24000, 16000),
				'2.5' => array(11025, 12000,  8000)
			);
		}
		return $MPEGaudioFrequency;
	}

	function MPEGaudioChannelModeArray() {
		static $MPEGaudioChannelMode = array('stereo', 'joint stereo', 'dual channel', 'mono');
		return $MPEGaudioChannelMode;
	}

	function MPEGaudioModeExtensionArray() {
		static $MPEGaudioModeExtension;
		if (empty($MPEGaudioModeExtension)) {
			$MPEGaudioModeExtension = array (
				1 => array('4-31', '8-31', '12-31', '16-31'),
				2 => array('4-31', '8-31', '12-31', '16-31'),
				3 => array('', 'IS', 'MS', 'IS+MS')
			);
		}
		return $MPEGaudioModeExtension;
	}

	function MPEGaudioEmphasisArray() {
		static $MPEGaudioEmphasis = array('none', '50/15ms', false, 'CCIT J.17');
		return $MPEGaudioEmphasis;
	}

	function MPEGaudioHeaderBytesValid($head4, $allowBitrate15=false) {
		return getid3_mp3::MPEGaudioHeaderValid(getid3_mp3::MPEGaudioHeaderDecode($head4), false, $allowBitrate15);
	}

	function MPEGaudioHeaderValid($rawarray, $echoerrors=false, $allowBitrate15=false) {
		if (($rawarray['synch'] & 0x0FFE) != 0x0FFE) {
			return false;
		}

		static $MPEGaudioVersionLookup;
		static $MPEGaudioLayerLookup;
		static $MPEGaudioBitrateLookup;
		static $MPEGaudioFrequencyLookup;
		static $MPEGaudioChannelModeLookup;
		static $MPEGaudioModeExtensionLookup;
		static $MPEGaudioEmphasisLookup;
		if (empty($MPEGaudioVersionLookup)) {
			$MPEGaudioVersionLookup       = getid3_mp3::MPEGaudioVersionArray();
			$MPEGaudioLayerLookup         = getid3_mp3::MPEGaudioLayerArray();
			$MPEGaudioBitrateLookup       = getid3_mp3::MPEGaudioBitrateArray();
			$MPEGaudioFrequencyLookup     = getid3_mp3::MPEGaudioFrequencyArray();
			$MPEGaudioChannelModeLookup   = getid3_mp3::MPEGaudioChannelModeArray();
			$MPEGaudioModeExtensionLookup = getid3_mp3::MPEGaudioModeExtensionArray();
			$MPEGaudioEmphasisLookup      = getid3_mp3::MPEGaudioEmphasisArray();
		}

		if (isset($MPEGaudioVersionLookup[$rawarray['version']])) {
			$decodedVersion = $MPEGaudioVersionLookup[$rawarray['version']];
		} else {
			echo ($echoerrors ? "\n".'invalid Version ('.$rawarray['version'].')' : '');
			return false;
		}
		if (isset($MPEGaudioLayerLookup[$rawarray['layer']])) {
			$decodedLayer = $MPEGaudioLayerLookup[$rawarray['layer']];
		} else {
			echo ($echoerrors ? "\n".'invalid Layer ('.$rawarray['layer'].')' : '');
			return false;
		}
		if (!isset($MPEGaudioBitrateLookup[$decodedVersion][$decodedLayer][$rawarray['bitrate']])) {
			echo ($echoerrors ? "\n".'invalid Bitrate ('.$rawarray['bitrate'].')' : '');
			if ($rawarray['bitrate'] == 15) {
				// known issue in LAME 3.90 - 3.93.1 where free-format has bitrate ID of 15 instead of 0
				// let it go through here otherwise file will not be identified
				if (!$allowBitrate15) {
					return false;
				}
			} else {
				return false;
			}
		}
		if (!isset($MPEGaudioFrequencyLookup[$decodedVersion][$rawarray['sample_rate']])) {
			echo ($echoerrors ? "\n".'invalid Frequency ('.$rawarray['sample_rate'].')' : '');
			return false;
		}
		if (!isset($MPEGaudioChannelModeLookup[$rawarray['channelmode']])) {
			echo ($echoerrors ? "\n".'invalid ChannelMode ('.$rawarray['channelmode'].')' : '');
			return false;
		}
		if (!isset($MPEGaudioModeExtensionLookup[$decodedLayer][$rawarray['modeextension']])) {
			echo ($echoerrors ? "\n".'invalid Mode Extension ('.$rawarray['modeextension'].')' : '');
			return false;
		}
		if (!isset($MPEGaudioEmphasisLookup[$rawarray['emphasis']])) {
			echo ($echoerrors ? "\n".'invalid Emphasis ('.$rawarray['emphasis'].')' : '');
			return false;
		}
		// These are just either set or not set, you can't mess that up :)
		// $rawarray['protection'];
		// $rawarray['padding'];
		// $rawarray['private'];
		// $rawarray['copyright'];
		// $rawarray['original'];

		return true;
	}

	function MPEGaudioHeaderDecode($Header4Bytes) {
		// AAAA AAAA  AAAB BCCD  EEEE FFGH  IIJJ KLMM
		// A - Frame sync (all bits set)
		// B - MPEG Audio version ID
		// C - Layer description
		// D - Protection bit
		// E - Bitrate index
		// F - Sampling rate frequency index
		// G - Padding bit
		// H - Private bit
		// I - Channel Mode
		// J - Mode extension (Only if Joint stereo)
		// K - Copyright
		// L - Original
		// M - Emphasis

		if (strlen($Header4Bytes) != 4) {
			return false;
		}

		$MPEGrawHeader['synch']         = (getid3_lib::BigEndian2Int(substr($Header4Bytes, 0, 2)) & 0xFFE0) >> 4;
		$MPEGrawHeader['version']       = (ord($Header4Bytes{1}) & 0x18) >> 3; //    BB
		$MPEGrawHeader['layer']         = (ord($Header4Bytes{1}) & 0x06) >> 1; //      CC
		$MPEGrawHeader['protection']    = (ord($Header4Bytes{1}) & 0x01);      //        D
		$MPEGrawHeader['bitrate']       = (ord($Header4Bytes{2}) & 0xF0) >> 4; // EEEE
		$MPEGrawHeader['sample_rate']   = (ord($Header4Bytes{2}) & 0x0C) >> 2; //     FF
		$MPEGrawHeader['padding']       = (ord($Header4Bytes{2}) & 0x02) >> 1; //       G
		$MPEGrawHeader['private']       = (ord($Header4Bytes{2}) & 0x01);      //        H
		$MPEGrawHeader['channelmode']   = (ord($Header4Bytes{3}) & 0xC0) >> 6; // II
		$MPEGrawHeader['modeextension'] = (ord($Header4Bytes{3}) & 0x30) >> 4; //   JJ
		$MPEGrawHeader['copyright']     = (ord($Header4Bytes{3}) & 0x08) >> 3; //     K
		$MPEGrawHeader['original']      = (ord($Header4Bytes{3}) & 0x04) >> 2; //      L
		$MPEGrawHeader['emphasis']      = (ord($Header4Bytes{3}) & 0x03);      //       MM

		return $MPEGrawHeader;
	}

	function MPEGaudioFrameLength(&$bitrate, &$version, &$layer, $padding, &$samplerate) {
		static $AudioFrameLengthCache = array();

		if (!isset($AudioFrameLengthCache[$bitrate][$version][$layer][$padding][$samplerate])) {
			$AudioFrameLengthCache[$bitrate][$version][$layer][$padding][$samplerate] = false;
			if ($bitrate != 'free') {

				if ($version == '1') {

					if ($layer == '1') {

						// For Layer I slot is 32 bits long
						$FrameLengthCoefficient = 48;
						$SlotLength = 4;

					} else { // Layer 2 / 3

						// for Layer 2 and Layer 3 slot is 8 bits long.
						$FrameLengthCoefficient = 144;
						$SlotLength = 1;

					}

				} else { // MPEG-2 / MPEG-2.5

					if ($layer == '1') {

						// For Layer I slot is 32 bits long
						$FrameLengthCoefficient = 24;
						$SlotLength = 4;

					} elseif ($layer == '2') {

						// for Layer 2 and Layer 3 slot is 8 bits long.
						$FrameLengthCoefficient = 144;
						$SlotLength = 1;

					} else { // layer 3

						// for Layer 2 and Layer 3 slot is 8 bits long.
						$FrameLengthCoefficient = 72;
						$SlotLength = 1;

					}

				}

				// FrameLengthInBytes = ((Coefficient * BitRate) / SampleRate) + Padding
				if ($samplerate > 0) {
					$NewFramelength  = ($FrameLengthCoefficient * $bitrate) / $samplerate;
					$NewFramelength  = floor($NewFramelength / $SlotLength) * $SlotLength; // round to next-lower multiple of SlotLength (1 byte for Layer 2/3, 4 bytes for Layer I)
					if ($padding) {
						$NewFramelength += $SlotLength;
					}
					$AudioFrameLengthCache[$bitrate][$version][$layer][$padding][$samplerate] = (int) $NewFramelength;
				}
			}
		}
		return $AudioFrameLengthCache[$bitrate][$version][$layer][$padding][$samplerate];
	}

	function ClosestStandardMP3Bitrate($bitrate) {
		static $StandardBitrates = array(320000, 256000, 224000, 192000, 160000, 128000, 112000, 96000, 80000, 64000, 56000, 48000, 40000, 32000, 24000, 16000, 8000);
		static $BitrateTable = array(0=>'-');
		$roundbitrate = intval(round($bitrate, -3));
		if (!isset($BitrateTable[$roundbitrate])) {
			if ($roundbitrate > 320000) {
				$BitrateTable[$roundbitrate] = round($bitrate, -4);
			} else {
				$LastBitrate = 320000;
				foreach ($StandardBitrates as $StandardBitrate) {
					$BitrateTable[$roundbitrate] = $StandardBitrate;
					if ($roundbitrate >= $StandardBitrate - (($LastBitrate - $StandardBitrate) / 2)) {
						break;
					}
					$LastBitrate = $StandardBitrate;
				}
			}
		}
		return $BitrateTable[$roundbitrate];
	}

	function XingVBRidOffset($version, $channelmode) {
		static $XingVBRidOffsetCache = array();
		if (empty($XingVBRidOffset)) {
			$XingVBRidOffset = array (
				'1'   => array ('mono'          => 0x15, // 4 + 17 = 21
								'stereo'        => 0x24, // 4 + 32 = 36
								'joint stereo'  => 0x24,
								'dual channel'  => 0x24
							   ),

				'2'   => array ('mono'          => 0x0D, // 4 +  9 = 13
								'stereo'        => 0x15, // 4 + 17 = 21
								'joint stereo'  => 0x15,
								'dual channel'  => 0x15
							   ),

				'2.5' => array ('mono'          => 0x15,
								'stereo'        => 0x15,
								'joint stereo'  => 0x15,
								'dual channel'  => 0x15
							   )
			);
		}
		return $XingVBRidOffset[$version][$channelmode];
	}

	function LAMEvbrMethodLookup($VBRmethodID) {
		static $LAMEvbrMethodLookup = array(
			0x00 => 'unknown',
			0x01 => 'cbr',
			0x02 => 'abr',
			0x03 => 'vbr-old / vbr-rh',
			0x04 => 'vbr-new / vbr-mtrh',
			0x05 => 'vbr-mt',
			0x06 => 'vbr (full vbr method 4)',
			0x08 => 'cbr (constant bitrate 2 pass)',
			0x09 => 'abr (2 pass)',
			0x0F => 'reserved'
		);
		return (isset($LAMEvbrMethodLookup[$VBRmethodID]) ? $LAMEvbrMethodLookup[$VBRmethodID] : '');
	}

	function LAMEmiscStereoModeLookup($StereoModeID) {
		static $LAMEmiscStereoModeLookup = array(
			0 => 'mono',
			1 => 'stereo',
			2 => 'dual mono',
			3 => 'joint stereo',
			4 => 'forced stereo',
			5 => 'auto',
			6 => 'intensity stereo',
			7 => 'other'
		);
		return (isset($LAMEmiscStereoModeLookup[$StereoModeID]) ? $LAMEmiscStereoModeLookup[$StereoModeID] : '');
	}

	function LAMEmiscSourceSampleFrequencyLookup($SourceSampleFrequencyID) {
		static $LAMEmiscSourceSampleFrequencyLookup = array(
			0 => '<= 32 kHz',
			1 => '44.1 kHz',
			2 => '48 kHz',
			3 => '> 48kHz'
		);
		return (isset($LAMEmiscSourceSampleFrequencyLookup[$SourceSampleFrequencyID]) ? $LAMEmiscSourceSampleFrequencyLookup[$SourceSampleFrequencyID] : '');
	}

	function LAMEsurroundInfoLookup($SurroundInfoID) {
		static $LAMEsurroundInfoLookup = array(
			0 => 'no surround info',
			1 => 'DPL encoding',
			2 => 'DPL2 encoding',
			3 => 'Ambisonic encoding'
		);
		return (isset($LAMEsurroundInfoLookup[$SurroundInfoID]) ? $LAMEsurroundInfoLookup[$SurroundInfoID] : 'reserved');
	}

	function LAMEpresetUsedLookup($LAMEtag) {

		if ($LAMEtag['preset_used_id'] == 0) {
			// no preset used (LAME >=3.93)
			// no preset recorded (LAME <3.93)
			return '';
		}
		$LAMEpresetUsedLookup = array();

		/////  THIS PART CANNOT BE STATIC .
		for ($i = 8; $i <= 320; $i++) {
			switch ($LAMEtag['vbr_method']) {
				case 'cbr':
					$LAMEpresetUsedLookup[$i] = '--alt-preset '.$LAMEtag['vbr_method'].' '.$i;
					break;
				case 'abr':
				default: // other VBR modes shouldn't be here(?)
					$LAMEpresetUsedLookup[$i] = '--alt-preset '.$i;
					break;
			}
		}

		// named old-style presets (studio, phone, voice, etc) are handled in GuessEncoderOptions()

		// named alt-presets
		$LAMEpresetUsedLookup[1000] = '--r3mix';
		$LAMEpresetUsedLookup[1001] = '--alt-preset standard';
		$LAMEpresetUsedLookup[1002] = '--alt-preset extreme';
		$LAMEpresetUsedLookup[1003] = '--alt-preset insane';
		$LAMEpresetUsedLookup[1004] = '--alt-preset fast standard';
		$LAMEpresetUsedLookup[1005] = '--alt-preset fast extreme';
		$LAMEpresetUsedLookup[1006] = '--alt-preset medium';
		$LAMEpresetUsedLookup[1007] = '--alt-preset fast medium';

		// LAME 3.94 additions/changes
		$LAMEpresetUsedLookup[1010] = '--preset portable';                                                           // 3.94a15 Oct 21 2003
		$LAMEpresetUsedLookup[1015] = '--preset radio';                                                              // 3.94a15 Oct 21 2003

		$LAMEpresetUsedLookup[320]  = '--preset insane';                                                             // 3.94a15 Nov 12 2003
		$LAMEpresetUsedLookup[410]  = '-V9';
		$LAMEpresetUsedLookup[420]  = '-V8';
		$LAMEpresetUsedLookup[440]  = '-V6';
		$LAMEpresetUsedLookup[430]  = '--preset radio';                                                              // 3.94a15 Nov 12 2003
		$LAMEpresetUsedLookup[450]  = '--preset '.(($LAMEtag['raw']['vbr_method'] == 4) ? 'fast ' : '').'portable';  // 3.94a15 Nov 12 2003
		$LAMEpresetUsedLookup[460]  = '--preset '.(($LAMEtag['raw']['vbr_method'] == 4) ? 'fast ' : '').'medium';    // 3.94a15 Nov 12 2003
		$LAMEpresetUsedLookup[470]  = '--r3mix';                                                                     // 3.94b1  Dec 18 2003
		$LAMEpresetUsedLookup[480]  = '--preset '.(($LAMEtag['raw']['vbr_method'] == 4) ? 'fast ' : '').'standard';  // 3.94a15 Nov 12 2003
		$LAMEpresetUsedLookup[490]  = '-V1';
		$LAMEpresetUsedLookup[500]  = '--preset '.(($LAMEtag['raw']['vbr_method'] == 4) ? 'fast ' : '').'extreme';   // 3.94a15 Nov 12 2003

		return (isset($LAMEpresetUsedLookup[$LAMEtag['preset_used_id']]) ? $LAMEpresetUsedLookup[$LAMEtag['preset_used_id']] : 'new/unknown preset: '.$LAMEtag['preset_used_id'].' - report to info@getid3.org');
	}

}

?>