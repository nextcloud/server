<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.audio-video.riff.php                                 //
// module for analyzing RIFF files                             //
// multiple formats supported by this module:                  //
//    Wave, AVI, AIFF/AIFC, (MP3,AC3)/RIFF, Wavpack v3, 8SVX   //
// dependencies: module.audio.mp3.php                          //
//               module.audio.ac3.php (optional)               //
//               module.audio.dts.php (optional)               //
//                                                            ///
/////////////////////////////////////////////////////////////////

getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.audio.mp3.php', __FILE__, true);

class getid3_riff
{

	function getid3_riff(&$fd, &$ThisFileInfo) {

		// initialize these values to an empty array, otherwise they default to NULL
		// and you can't append array values to a NULL value
		$ThisFileInfo['riff'] = array('raw'=>array());

		// Shortcuts
		$thisfile_riff             = &$ThisFileInfo['riff'];
		$thisfile_riff_raw         = &$thisfile_riff['raw'];
		$thisfile_audio            = &$ThisFileInfo['audio'];
		$thisfile_video            = &$ThisFileInfo['video'];
		$thisfile_avdataoffset     = &$ThisFileInfo['avdataoffset'];
		$thisfile_avdataend        = &$ThisFileInfo['avdataend'];
		$thisfile_audio_dataformat = &$thisfile_audio['dataformat'];
		$thisfile_riff_audio       = &$thisfile_riff['audio'];
		$thisfile_riff_video       = &$thisfile_riff['video'];


		$Original['avdataoffset'] = $thisfile_avdataoffset;
		$Original['avdataend']    = $thisfile_avdataend;

		fseek($fd, $thisfile_avdataoffset, SEEK_SET);
		$RIFFheader = fread($fd, 12);
		$RIFFsubtype = substr($RIFFheader, 8, 4);
		switch (substr($RIFFheader, 0, 4)) {
			case 'FORM':
				$ThisFileInfo['fileformat']   = 'aiff';
				$RIFFheaderSize               = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($RIFFheader, 4, 4));
				$thisfile_riff[$RIFFsubtype]  = getid3_riff::ParseRIFF($fd, $thisfile_avdataoffset + 12, $thisfile_avdataoffset + $RIFFheaderSize, $ThisFileInfo);
				$thisfile_riff['header_size'] = $RIFFheaderSize;
				break;

			case 'RIFF':
			case 'SDSS':  // SDSS is identical to RIFF, just renamed. Used by SmartSound QuickTracks (www.smartsound.com)
			case 'RMP3':  // RMP3 is identical to RIFF, just renamed. Used by [unknown program] when creating RIFF-MP3s
				if ($RIFFsubtype == 'RMP3') {
					// RMP3 is identical to WAVE, just renamed. Used by [unknown program] when creating RIFF-MP3s
					$RIFFsubtype = 'WAVE';
				}

				$ThisFileInfo['fileformat']   = 'riff';
				$RIFFheaderSize               = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($RIFFheader, 4, 4));
				$thisfile_riff['header_size'] = $RIFFheaderSize;
				$thisfile_riff[$RIFFsubtype]  = getid3_riff::ParseRIFF($fd, $thisfile_avdataoffset + 12, $thisfile_avdataoffset + $RIFFheaderSize, $ThisFileInfo);

				fseek($fd, $thisfile_avdataoffset + $RIFFheaderSize);
				$nextRIFFheader = fread($fd, 20);
				if (substr($nextRIFFheader, 8, 4) == 'RIFF') {
					$nextRIFFsize = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($nextRIFFheader, 12, 4));
					$nextRIFFtype = substr($nextRIFFheader, 16, 4).'<br>';
					$thisfile_riff[$nextRIFFtype]['offset'] = ftell($fd) - 4;
					$thisfile_riff[$nextRIFFtype]['size']   = $nextRIFFsize;
					$ThisFileInfo['avdataend'] = $thisfile_riff[$nextRIFFtype]['offset'] + $thisfile_riff[$nextRIFFtype]['size'];
					$ThisFileInfo['error'][]   = 'AVI extends beyond 2GB and PHP filesystem functions cannot read that far, playtime is probably wrong';
					$ThisFileInfo['warning'][] = '[avdataend] value may be incorrect, multiple AVIX chunks may be present';
					$thisfile_riff[$nextRIFFtype]  = getid3_riff::ParseRIFF($fd, $thisfile_riff[$nextRIFFtype]['offset'] + 4, $thisfile_riff[$nextRIFFtype]['offset'] + $thisfile_riff[$nextRIFFtype]['size'], $ThisFileInfo);
				}
				if ($RIFFsubtype == 'WAVE') {
					$thisfile_riff_WAVE = &$thisfile_riff['WAVE'];
				}
				break;

			default:
				$ThisFileInfo['error'][] = 'Cannot parse RIFF (this is maybe not a RIFF / WAV / AVI file?) - expecting "FORM|RIFF|SDSS|RMP3" found "'.$RIFFsubtype.'" instead';
				unset($ThisFileInfo['fileformat']);
				return false;
				break;
		}

		$streamindex = 0;
		switch ($RIFFsubtype) {
			case 'WAVE':
				if (empty($thisfile_audio['bitrate_mode'])) {
					$thisfile_audio['bitrate_mode'] = 'cbr';
				}
				if (empty($thisfile_audio_dataformat)) {
					$thisfile_audio_dataformat = 'wav';
				}

				if (isset($thisfile_riff_WAVE['data'][0]['offset'])) {
					$thisfile_avdataoffset = $thisfile_riff_WAVE['data'][0]['offset'] + 8;
					$thisfile_avdataend    = $thisfile_avdataoffset + $thisfile_riff_WAVE['data'][0]['size'];
				}
				if (isset($thisfile_riff_WAVE['fmt '][0]['data'])) {

					$thisfile_riff_audio[$streamindex] = getid3_riff::RIFFparseWAVEFORMATex($thisfile_riff_WAVE['fmt '][0]['data']);
					$thisfile_audio['wformattag'] = $thisfile_riff_audio[$streamindex]['raw']['wFormatTag'];
					if (@$thisfile_riff_audio[$streamindex]['bitrate'] == 0) {
						$ThisFileInfo['error'][] = 'Corrupt RIFF file: bitrate_audio == zero';
						return false;
					}
					$thisfile_riff_raw['fmt '] = $thisfile_riff_audio[$streamindex]['raw'];
					unset($thisfile_riff_audio[$streamindex]['raw']);
					$thisfile_audio['streams'][$streamindex] = $thisfile_riff_audio[$streamindex];

					$thisfile_audio = getid3_lib::array_merge_noclobber($thisfile_audio, $thisfile_riff_audio[$streamindex]);
					if (substr($thisfile_audio['codec'], 0, strlen('unknown: 0x')) == 'unknown: 0x') {
						$ThisFileInfo['warning'][] = 'Audio codec = '.$thisfile_audio['codec'];
					}
					$thisfile_audio['bitrate'] = $thisfile_riff_audio[$streamindex]['bitrate'];

					$ThisFileInfo['playtime_seconds'] = (float) ((($thisfile_avdataend - $thisfile_avdataoffset) * 8) / $thisfile_audio['bitrate']);

					$thisfile_audio['lossless'] = false;
					if (isset($thisfile_riff_WAVE['data'][0]['offset']) && isset($thisfile_riff_raw['fmt ']['wFormatTag'])) {
						switch ($thisfile_riff_raw['fmt ']['wFormatTag']) {

							case 0x0001:  // PCM
								$thisfile_audio['lossless'] = true;
								break;

							case 0x2000:  // AC-3
								$thisfile_audio_dataformat = 'ac3';
								break;

							default:
								// do nothing
								break;

						}
					}
					$thisfile_audio['streams'][$streamindex]['wformattag']   = $thisfile_audio['wformattag'];
					$thisfile_audio['streams'][$streamindex]['bitrate_mode'] = $thisfile_audio['bitrate_mode'];
					$thisfile_audio['streams'][$streamindex]['lossless']     = $thisfile_audio['lossless'];
					$thisfile_audio['streams'][$streamindex]['dataformat']   = $thisfile_audio_dataformat;
				}

				if (isset($thisfile_riff_WAVE['rgad'][0]['data'])) {

					// shortcuts
					$rgadData = &$thisfile_riff_WAVE['rgad'][0]['data'];
					$thisfile_riff_raw['rgad']    = array('track'=>array(), 'album'=>array());
					$thisfile_riff_raw_rgad       = &$thisfile_riff_raw['rgad'];
					$thisfile_riff_raw_rgad_track = &$thisfile_riff_raw_rgad['track'];
					$thisfile_riff_raw_rgad_album = &$thisfile_riff_raw_rgad['album'];

					$thisfile_riff_raw_rgad['fPeakAmplitude']      =               getid3_lib::LittleEndian2Float(substr($rgadData, 0, 4));
					$thisfile_riff_raw_rgad['nRadioRgAdjust']      = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($rgadData, 4, 2));
					$thisfile_riff_raw_rgad['nAudiophileRgAdjust'] = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($rgadData, 6, 2));

					$nRadioRgAdjustBitstring      = str_pad(getid3_lib::Dec2Bin($thisfile_riff_raw_rgad['nRadioRgAdjust']), 16, '0', STR_PAD_LEFT);
					$nAudiophileRgAdjustBitstring = str_pad(getid3_lib::Dec2Bin($thisfile_riff_raw_rgad['nAudiophileRgAdjust']), 16, '0', STR_PAD_LEFT);
					$thisfile_riff_raw_rgad_track['name']       = getid3_lib::Bin2Dec(substr($nRadioRgAdjustBitstring, 0, 3));
					$thisfile_riff_raw_rgad_track['originator'] = getid3_lib::Bin2Dec(substr($nRadioRgAdjustBitstring, 3, 3));
					$thisfile_riff_raw_rgad_track['signbit']    = getid3_lib::Bin2Dec(substr($nRadioRgAdjustBitstring, 6, 1));
					$thisfile_riff_raw_rgad_track['adjustment'] = getid3_lib::Bin2Dec(substr($nRadioRgAdjustBitstring, 7, 9));
					$thisfile_riff_raw_rgad_album['name']       = getid3_lib::Bin2Dec(substr($nAudiophileRgAdjustBitstring, 0, 3));
					$thisfile_riff_raw_rgad_album['originator'] = getid3_lib::Bin2Dec(substr($nAudiophileRgAdjustBitstring, 3, 3));
					$thisfile_riff_raw_rgad_album['signbit']    = getid3_lib::Bin2Dec(substr($nAudiophileRgAdjustBitstring, 6, 1));
					$thisfile_riff_raw_rgad_album['adjustment'] = getid3_lib::Bin2Dec(substr($nAudiophileRgAdjustBitstring, 7, 9));

					$thisfile_riff['rgad']['peakamplitude'] = $thisfile_riff_raw_rgad['fPeakAmplitude'];
					if (($thisfile_riff_raw_rgad_track['name'] != 0) && ($thisfile_riff_raw_rgad_track['originator'] != 0)) {
						$thisfile_riff['rgad']['track']['name']            = getid3_lib::RGADnameLookup($thisfile_riff_raw_rgad_track['name']);
						$thisfile_riff['rgad']['track']['originator']      = getid3_lib::RGADoriginatorLookup($thisfile_riff_raw_rgad_track['originator']);
						$thisfile_riff['rgad']['track']['adjustment']      = getid3_lib::RGADadjustmentLookup($thisfile_riff_raw_rgad_track['adjustment'], $thisfile_riff_raw_rgad_track['signbit']);
					}
					if (($thisfile_riff_raw_rgad_album['name'] != 0) && ($thisfile_riff_raw_rgad_album['originator'] != 0)) {
						$thisfile_riff['rgad']['album']['name']       = getid3_lib::RGADnameLookup($thisfile_riff_raw_rgad_album['name']);
						$thisfile_riff['rgad']['album']['originator'] = getid3_lib::RGADoriginatorLookup($thisfile_riff_raw_rgad_album['originator']);
						$thisfile_riff['rgad']['album']['adjustment'] = getid3_lib::RGADadjustmentLookup($thisfile_riff_raw_rgad_album['adjustment'], $thisfile_riff_raw_rgad_album['signbit']);
					}
				}

				if (isset($thisfile_riff_WAVE['fact'][0]['data'])) {
					$thisfile_riff_raw['fact']['NumberOfSamples'] = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($thisfile_riff_WAVE['fact'][0]['data'], 0, 4));

					// This should be a good way of calculating exact playtime,
					// but some sample files have had incorrect number of samples,
					// so cannot use this method

					// if (!empty($thisfile_riff_raw['fmt ']['nSamplesPerSec'])) {
					//     $ThisFileInfo['playtime_seconds'] = (float) $thisfile_riff_raw['fact']['NumberOfSamples'] / $thisfile_riff_raw['fmt ']['nSamplesPerSec'];
					// }
				}
				if (!empty($thisfile_riff_raw['fmt ']['nAvgBytesPerSec'])) {
					$thisfile_audio['bitrate'] = getid3_lib::CastAsInt($thisfile_riff_raw['fmt ']['nAvgBytesPerSec'] * 8);
				}

				if (isset($thisfile_riff_WAVE['bext'][0]['data'])) {
					// shortcut
					$thisfile_riff_WAVE_bext_0 = &$thisfile_riff_WAVE['bext'][0];

					$thisfile_riff_WAVE_bext_0['title']          =                         trim(substr($thisfile_riff_WAVE_bext_0['data'],   0, 256));
					$thisfile_riff_WAVE_bext_0['author']         =                         trim(substr($thisfile_riff_WAVE_bext_0['data'], 256,  32));
					$thisfile_riff_WAVE_bext_0['reference']      =                         trim(substr($thisfile_riff_WAVE_bext_0['data'], 288,  32));
					$thisfile_riff_WAVE_bext_0['origin_date']    =                              substr($thisfile_riff_WAVE_bext_0['data'], 320,  10);
					$thisfile_riff_WAVE_bext_0['origin_time']    =                              substr($thisfile_riff_WAVE_bext_0['data'], 330,   8);
					$thisfile_riff_WAVE_bext_0['time_reference'] = getid3_lib::LittleEndian2Int(substr($thisfile_riff_WAVE_bext_0['data'], 338,   8));
					$thisfile_riff_WAVE_bext_0['bwf_version']    = getid3_lib::LittleEndian2Int(substr($thisfile_riff_WAVE_bext_0['data'], 346,   1));
					$thisfile_riff_WAVE_bext_0['reserved']       = getid3_lib::LittleEndian2Int(substr($thisfile_riff_WAVE_bext_0['data'], 347, 254));
					$thisfile_riff_WAVE_bext_0['coding_history'] =         explode("\r\n", trim(substr($thisfile_riff_WAVE_bext_0['data'], 601)));

					$thisfile_riff_WAVE_bext_0['origin_date_unix'] = gmmktime(
																				substr($thisfile_riff_WAVE_bext_0['origin_time'], 0, 2),
																				substr($thisfile_riff_WAVE_bext_0['origin_time'], 3, 2),
																				substr($thisfile_riff_WAVE_bext_0['origin_time'], 6, 2),
																				substr($thisfile_riff_WAVE_bext_0['origin_date'], 5, 2),
																				substr($thisfile_riff_WAVE_bext_0['origin_date'], 8, 2),
																				substr($thisfile_riff_WAVE_bext_0['origin_date'], 0, 4));

					$thisfile_riff['comments']['author'][] = $thisfile_riff_WAVE_bext_0['author'];
					$thisfile_riff['comments']['title'][]  = $thisfile_riff_WAVE_bext_0['title'];
				}

				if (isset($thisfile_riff_WAVE['MEXT'][0]['data'])) {
					// shortcut
					$thisfile_riff_WAVE_MEXT_0 = &$thisfile_riff_WAVE['MEXT'][0];

					$thisfile_riff_WAVE_MEXT_0['raw']['sound_information']      = getid3_lib::LittleEndian2Int(substr($thisfile_riff_WAVE_MEXT_0['data'], 0, 2));
					$thisfile_riff_WAVE_MEXT_0['flags']['homogenous']           = (bool) ($thisfile_riff_WAVE_MEXT_0['raw']['sound_information'] & 0x0001);
					if ($thisfile_riff_WAVE_MEXT_0['flags']['homogenous']) {
						$thisfile_riff_WAVE_MEXT_0['flags']['padding']          = ($thisfile_riff_WAVE_MEXT_0['raw']['sound_information'] & 0x0002) ? false : true;
						$thisfile_riff_WAVE_MEXT_0['flags']['22_or_44']         =        (bool) ($thisfile_riff_WAVE_MEXT_0['raw']['sound_information'] & 0x0004);
						$thisfile_riff_WAVE_MEXT_0['flags']['free_format']      =        (bool) ($thisfile_riff_WAVE_MEXT_0['raw']['sound_information'] & 0x0008);

						$thisfile_riff_WAVE_MEXT_0['nominal_frame_size']        = getid3_lib::LittleEndian2Int(substr($thisfile_riff_WAVE_MEXT_0['data'], 2, 2));
					}
					$thisfile_riff_WAVE_MEXT_0['anciliary_data_length']         = getid3_lib::LittleEndian2Int(substr($thisfile_riff_WAVE_MEXT_0['data'], 6, 2));
					$thisfile_riff_WAVE_MEXT_0['raw']['anciliary_data_def']     = getid3_lib::LittleEndian2Int(substr($thisfile_riff_WAVE_MEXT_0['data'], 8, 2));
					$thisfile_riff_WAVE_MEXT_0['flags']['anciliary_data_left']  = (bool) ($thisfile_riff_WAVE_MEXT_0['raw']['anciliary_data_def'] & 0x0001);
					$thisfile_riff_WAVE_MEXT_0['flags']['anciliary_data_free']  = (bool) ($thisfile_riff_WAVE_MEXT_0['raw']['anciliary_data_def'] & 0x0002);
					$thisfile_riff_WAVE_MEXT_0['flags']['anciliary_data_right'] = (bool) ($thisfile_riff_WAVE_MEXT_0['raw']['anciliary_data_def'] & 0x0004);
				}

				if (isset($thisfile_riff_WAVE['cart'][0]['data'])) {
					// shortcut
					$thisfile_riff_WAVE_cart_0 = &$thisfile_riff_WAVE['cart'][0];

					$thisfile_riff_WAVE_cart_0['version']              =                  substr($thisfile_riff_WAVE_cart_0['data'],    0,    4);
					$thisfile_riff_WAVE_cart_0['title']                =             trim(substr($thisfile_riff_WAVE_cart_0['data'],    4,   64));
					$thisfile_riff_WAVE_cart_0['artist']               =             trim(substr($thisfile_riff_WAVE_cart_0['data'],   68,   64));
					$thisfile_riff_WAVE_cart_0['cut_id']               =             trim(substr($thisfile_riff_WAVE_cart_0['data'],  132,   64));
					$thisfile_riff_WAVE_cart_0['client_id']            =             trim(substr($thisfile_riff_WAVE_cart_0['data'],  196,   64));
					$thisfile_riff_WAVE_cart_0['category']             =             trim(substr($thisfile_riff_WAVE_cart_0['data'],  260,   64));
					$thisfile_riff_WAVE_cart_0['classification']       =             trim(substr($thisfile_riff_WAVE_cart_0['data'],  324,   64));
					$thisfile_riff_WAVE_cart_0['out_cue']              =             trim(substr($thisfile_riff_WAVE_cart_0['data'],  388,   64));
					$thisfile_riff_WAVE_cart_0['start_date']           =             trim(substr($thisfile_riff_WAVE_cart_0['data'],  452,   10));
					$thisfile_riff_WAVE_cart_0['start_time']           =             trim(substr($thisfile_riff_WAVE_cart_0['data'],  462,    8));
					$thisfile_riff_WAVE_cart_0['end_date']             =             trim(substr($thisfile_riff_WAVE_cart_0['data'],  470,   10));
					$thisfile_riff_WAVE_cart_0['end_time']             =             trim(substr($thisfile_riff_WAVE_cart_0['data'],  480,    8));
					$thisfile_riff_WAVE_cart_0['producer_app_id']      =             trim(substr($thisfile_riff_WAVE_cart_0['data'],  488,   64));
					$thisfile_riff_WAVE_cart_0['producer_app_version'] =             trim(substr($thisfile_riff_WAVE_cart_0['data'],  552,   64));
					$thisfile_riff_WAVE_cart_0['user_defined_text']    =             trim(substr($thisfile_riff_WAVE_cart_0['data'],  616,   64));
					$thisfile_riff_WAVE_cart_0['zero_db_reference']    = getid3_lib::LittleEndian2Int(substr($thisfile_riff_WAVE_cart_0['data'],  680,    4), true);
					for ($i = 0; $i < 8; $i++) {
						$thisfile_riff_WAVE_cart_0['post_time'][$i]['usage_fourcc'] =                  substr($thisfile_riff_WAVE_cart_0['data'], 684 + ($i * 8), 4);
						$thisfile_riff_WAVE_cart_0['post_time'][$i]['timer_value']  = getid3_lib::LittleEndian2Int(substr($thisfile_riff_WAVE_cart_0['data'], 684 + ($i * 8) + 4, 4));
					}
					$thisfile_riff_WAVE_cart_0['url']              =                 trim(substr($thisfile_riff_WAVE_cart_0['data'],  748, 1024));
					$thisfile_riff_WAVE_cart_0['tag_text']         = explode("\r\n", trim(substr($thisfile_riff_WAVE_cart_0['data'], 1772)));

					$thisfile_riff['comments']['artist'][] = $thisfile_riff_WAVE_cart_0['artist'];
					$thisfile_riff['comments']['title'][]  = $thisfile_riff_WAVE_cart_0['title'];
				}

				if (!isset($thisfile_audio['bitrate']) && isset($thisfile_riff_audio[$streamindex]['bitrate'])) {
					$thisfile_audio['bitrate'] = $thisfile_riff_audio[$streamindex]['bitrate'];
					$ThisFileInfo['playtime_seconds'] = (float) ((($thisfile_avdataend - $thisfile_avdataoffset) * 8) / $thisfile_audio['bitrate']);
				}

				if (!empty($ThisFileInfo['wavpack'])) {
					$thisfile_audio_dataformat = 'wavpack';
					$thisfile_audio['bitrate_mode'] = 'vbr';
					$thisfile_audio['encoder']      = 'WavPack v'.$ThisFileInfo['wavpack']['version'];

					// Reset to the way it was - RIFF parsing will have messed this up
					$thisfile_avdataend        = $Original['avdataend'];
					$thisfile_audio['bitrate'] = (($thisfile_avdataend - $thisfile_avdataoffset) * 8) / $ThisFileInfo['playtime_seconds'];

					fseek($fd, $thisfile_avdataoffset - 44, SEEK_SET);
					$RIFFdata = fread($fd, 44);
					$OrignalRIFFheaderSize = getid3_lib::LittleEndian2Int(substr($RIFFdata,  4, 4)) +  8;
					$OrignalRIFFdataSize   = getid3_lib::LittleEndian2Int(substr($RIFFdata, 40, 4)) + 44;

					if ($OrignalRIFFheaderSize > $OrignalRIFFdataSize) {
						$thisfile_avdataend -= ($OrignalRIFFheaderSize - $OrignalRIFFdataSize);
						fseek($fd, $thisfile_avdataend, SEEK_SET);
						$RIFFdata .= fread($fd, $OrignalRIFFheaderSize - $OrignalRIFFdataSize);
					}

					// move the data chunk after all other chunks (if any)
					// so that the RIFF parser doesn't see EOF when trying
					// to skip over the data chunk
					$RIFFdata = substr($RIFFdata, 0, 36).substr($RIFFdata, 44).substr($RIFFdata, 36, 8);
					getid3_riff::ParseRIFFdata($RIFFdata, $ThisFileInfo);
				}

				if (isset($thisfile_riff_raw['fmt ']['wFormatTag'])) {
					switch ($thisfile_riff_raw['fmt ']['wFormatTag']) {
						case 0x08AE: // ClearJump LiteWave
							$thisfile_audio['bitrate_mode'] = 'vbr';
							$thisfile_audio_dataformat   = 'litewave';

							//typedef struct tagSLwFormat {
							//  WORD    m_wCompFormat;     // low byte defines compression method, high byte is compression flags
							//  DWORD   m_dwScale;         // scale factor for lossy compression
							//  DWORD   m_dwBlockSize;     // number of samples in encoded blocks
							//  WORD    m_wQuality;        // alias for the scale factor
							//  WORD    m_wMarkDistance;   // distance between marks in bytes
							//  WORD    m_wReserved;
							//
							//  //following paramters are ignored if CF_FILESRC is not set
							//  DWORD   m_dwOrgSize;       // original file size in bytes
							//  WORD    m_bFactExists;     // indicates if 'fact' chunk exists in the original file
							//  DWORD   m_dwRiffChunkSize; // riff chunk size in the original file
							//
							//  PCMWAVEFORMAT m_OrgWf;     // original wave format
							// }SLwFormat, *PSLwFormat;

							// shortcut
							$thisfile_riff['litewave']['raw'] = array();
							$thisfile_riff_litewave     = &$thisfile_riff['litewave'];
							$thisfile_riff_litewave_raw = &$thisfile_riff_litewave['raw'];

							$thisfile_riff_litewave_raw['compression_method'] = getid3_lib::LittleEndian2Int(substr($thisfile_riff_WAVE['fmt '][0]['data'], 18, 1));
							$thisfile_riff_litewave_raw['compression_flags']  = getid3_lib::LittleEndian2Int(substr($thisfile_riff_WAVE['fmt '][0]['data'], 19, 1));
							$thisfile_riff_litewave_raw['m_dwScale']          = getid3_lib::LittleEndian2Int(substr($thisfile_riff_WAVE['fmt '][0]['data'], 20, 4));
							$thisfile_riff_litewave_raw['m_dwBlockSize']      = getid3_lib::LittleEndian2Int(substr($thisfile_riff_WAVE['fmt '][0]['data'], 24, 4));
							$thisfile_riff_litewave_raw['m_wQuality']         = getid3_lib::LittleEndian2Int(substr($thisfile_riff_WAVE['fmt '][0]['data'], 28, 2));
							$thisfile_riff_litewave_raw['m_wMarkDistance']    = getid3_lib::LittleEndian2Int(substr($thisfile_riff_WAVE['fmt '][0]['data'], 30, 2));
							$thisfile_riff_litewave_raw['m_wReserved']        = getid3_lib::LittleEndian2Int(substr($thisfile_riff_WAVE['fmt '][0]['data'], 32, 2));
							$thisfile_riff_litewave_raw['m_dwOrgSize']        = getid3_lib::LittleEndian2Int(substr($thisfile_riff_WAVE['fmt '][0]['data'], 34, 4));
							$thisfile_riff_litewave_raw['m_bFactExists']      = getid3_lib::LittleEndian2Int(substr($thisfile_riff_WAVE['fmt '][0]['data'], 38, 2));
							$thisfile_riff_litewave_raw['m_dwRiffChunkSize']  = getid3_lib::LittleEndian2Int(substr($thisfile_riff_WAVE['fmt '][0]['data'], 40, 4));

							//$thisfile_riff_litewave['quality_factor'] = intval(round((2000 - $thisfile_riff_litewave_raw['m_dwScale']) / 20));
							$thisfile_riff_litewave['quality_factor'] = $thisfile_riff_litewave_raw['m_wQuality'];

							$thisfile_riff_litewave['flags']['raw_source']    = ($thisfile_riff_litewave_raw['compression_flags'] & 0x01) ? false : true;
							$thisfile_riff_litewave['flags']['vbr_blocksize'] = ($thisfile_riff_litewave_raw['compression_flags'] & 0x02) ? false : true;
							$thisfile_riff_litewave['flags']['seekpoints']    =        (bool) ($thisfile_riff_litewave_raw['compression_flags'] & 0x04);

							$thisfile_audio['lossless']        = (($thisfile_riff_litewave_raw['m_wQuality'] == 100) ? true : false);
							$thisfile_audio['encoder_options'] = '-q'.$thisfile_riff_litewave['quality_factor'];
							break;

						default:
							break;
					}
				}
				if ($thisfile_avdataend > $ThisFileInfo['filesize']) {
					switch (@$thisfile_audio_dataformat) {
						case 'wavpack': // WavPack
						case 'lpac':    // LPAC
						case 'ofr':     // OptimFROG
						case 'ofs':     // OptimFROG DualStream
							// lossless compressed audio formats that keep original RIFF headers - skip warning
							break;

						case 'litewave':
							if (($thisfile_avdataend - $ThisFileInfo['filesize']) == 1) {
								// LiteWave appears to incorrectly *not* pad actual output file
								// to nearest WORD boundary so may appear to be short by one
								// byte, in which case - skip warning
							} else {
								// Short by more than one byte, throw warning
								$ThisFileInfo['warning'][] = 'Probably truncated file - expecting '.$thisfile_riff[$RIFFsubtype]['data'][0]['size'].' bytes of data, only found '.($ThisFileInfo['filesize'] - $thisfile_avdataoffset).' (short by '.($thisfile_riff[$RIFFsubtype]['data'][0]['size'] - ($ThisFileInfo['filesize'] - $thisfile_avdataoffset)).' bytes)';
								$thisfile_avdataend = $ThisFileInfo['filesize'];
							}
							break;

						default:
							if ((($thisfile_avdataend - $ThisFileInfo['filesize']) == 1) && (($thisfile_riff[$RIFFsubtype]['data'][0]['size'] % 2) == 0) && ((($ThisFileInfo['filesize'] - $thisfile_avdataoffset) % 2) == 1)) {
								// output file appears to be incorrectly *not* padded to nearest WORD boundary
								// Output less severe warning
								$ThisFileInfo['warning'][] = 'File should probably be padded to nearest WORD boundary, but it is not (expecting '.$thisfile_riff[$RIFFsubtype]['data'][0]['size'].' bytes of data, only found '.($ThisFileInfo['filesize'] - $thisfile_avdataoffset).' therefore short by '.($thisfile_riff[$RIFFsubtype]['data'][0]['size'] - ($ThisFileInfo['filesize'] - $thisfile_avdataoffset)).' bytes)';
								$thisfile_avdataend = $ThisFileInfo['filesize'];
								break;
							}
							// Short by more than one byte, throw warning
							$ThisFileInfo['warning'][] = 'Probably truncated file - expecting '.$thisfile_riff[$RIFFsubtype]['data'][0]['size'].' bytes of data, only found '.($ThisFileInfo['filesize'] - $thisfile_avdataoffset).' (short by '.($thisfile_riff[$RIFFsubtype]['data'][0]['size'] - ($ThisFileInfo['filesize'] - $thisfile_avdataoffset)).' bytes)';
							$thisfile_avdataend = $ThisFileInfo['filesize'];
							break;
					}
				}
				if (!empty($ThisFileInfo['mpeg']['audio']['LAME']['audio_bytes'])) {
					if ((($thisfile_avdataend - $thisfile_avdataoffset) - $ThisFileInfo['mpeg']['audio']['LAME']['audio_bytes']) == 1) {
						$thisfile_avdataend--;
						$ThisFileInfo['warning'][] = 'Extra null byte at end of MP3 data assumed to be RIFF padding and therefore ignored';
					}
				}
				if (@$thisfile_audio_dataformat == 'ac3') {
					unset($thisfile_audio['bits_per_sample']);
					if (!empty($ThisFileInfo['ac3']['bitrate']) && ($ThisFileInfo['ac3']['bitrate'] != $thisfile_audio['bitrate'])) {
						$thisfile_audio['bitrate'] = $ThisFileInfo['ac3']['bitrate'];
					}
				}
				break;

			case 'AVI ':
				$thisfile_video['bitrate_mode'] = 'vbr'; // maybe not, but probably
				$thisfile_video['dataformat']   = 'avi';
				$ThisFileInfo['mime_type']      = 'video/avi';

				if (isset($thisfile_riff[$RIFFsubtype]['movi']['offset'])) {
					$thisfile_avdataoffset = $thisfile_riff[$RIFFsubtype]['movi']['offset'] + 8;
					$thisfile_avdataend    = $thisfile_avdataoffset + $thisfile_riff[$RIFFsubtype]['movi']['size'];
					if ($thisfile_avdataend > $ThisFileInfo['filesize']) {
						$ThisFileInfo['warning'][] = 'Probably truncated file - expecting '.$thisfile_riff[$RIFFsubtype]['movi']['size'].' bytes of data, only found '.($ThisFileInfo['filesize'] - $thisfile_avdataoffset).' (short by '.($thisfile_riff[$RIFFsubtype]['movi']['size'] - ($ThisFileInfo['filesize'] - $thisfile_avdataoffset)).' bytes)';
						$thisfile_avdataend = $ThisFileInfo['filesize'];
					}
				}

				if (isset($thisfile_riff['AVI ']['hdrl']['strl']['indx'])) {
					//$bIndexType = array(
					//	0x00 => 'AVI_INDEX_OF_INDEXES',
					//	0x01 => 'AVI_INDEX_OF_CHUNKS',
					//	0x80 => 'AVI_INDEX_IS_DATA',
					//);
					//$bIndexSubtype = array(
					//	0x01 => array(
					//		0x01 => 'AVI_INDEX_2FIELD',
					//	),
					//);
					foreach ($thisfile_riff['AVI ']['hdrl']['strl']['indx'] as $streamnumber => $steamdataarray) {
						$thisfile_riff_avi_hdrl_strl_indx_stream_data = &$thisfile_riff['AVI ']['hdrl']['strl']['indx'][$streamnumber]['data'];

						$thisfile_riff_raw['indx'][$streamnumber]['wLongsPerEntry'] = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($thisfile_riff_avi_hdrl_strl_indx_stream_data,  0, 2));
						$thisfile_riff_raw['indx'][$streamnumber]['bIndexSubType']  = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($thisfile_riff_avi_hdrl_strl_indx_stream_data,  2, 1));
						$thisfile_riff_raw['indx'][$streamnumber]['bIndexType']     = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($thisfile_riff_avi_hdrl_strl_indx_stream_data,  3, 1));
						$thisfile_riff_raw['indx'][$streamnumber]['nEntriesInUse']  = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($thisfile_riff_avi_hdrl_strl_indx_stream_data,  4, 4));
						$thisfile_riff_raw['indx'][$streamnumber]['dwChunkId']      =                                              substr($thisfile_riff_avi_hdrl_strl_indx_stream_data,  8, 4);
						$thisfile_riff_raw['indx'][$streamnumber]['dwReserved']     = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($thisfile_riff_avi_hdrl_strl_indx_stream_data, 12, 4));

						//$thisfile_riff_raw['indx'][$streamnumber]['bIndexType_name']    =    @$bIndexType[$thisfile_riff_raw['indx'][$streamnumber]['bIndexType']];
						//$thisfile_riff_raw['indx'][$streamnumber]['bIndexSubType_name'] = @$bIndexSubtype[$thisfile_riff_raw['indx'][$streamnumber]['bIndexType']][$thisfile_riff_raw['indx'][$streamnumber]['bIndexSubType']];

						unset($thisfile_riff_avi_hdrl_strl_indx_stream_data);
					}
				}
				if (isset($thisfile_riff['AVI ']['hdrl']['avih'][$streamindex]['data'])) {
					$avihData = $thisfile_riff['AVI ']['hdrl']['avih'][$streamindex]['data'];

					// shortcut
					$thisfile_riff_raw['avih'] = array();
					$thisfile_riff_raw_avih = &$thisfile_riff_raw['avih'];

					$thisfile_riff_raw_avih['dwMicroSecPerFrame']    = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($avihData,  0, 4)); // frame display rate (or 0L)
					if ($thisfile_riff_raw_avih['dwMicroSecPerFrame'] == 0) {
						$ThisFileInfo['error'][] = 'Corrupt RIFF file: avih.dwMicroSecPerFrame == zero';
						return false;
					}
					$thisfile_riff_raw_avih['dwMaxBytesPerSec']      = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($avihData,  4, 4)); // max. transfer rate
					$thisfile_riff_raw_avih['dwPaddingGranularity']  = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($avihData,  8, 4)); // pad to multiples of this size; normally 2K.
					$thisfile_riff_raw_avih['dwFlags']               = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($avihData, 12, 4)); // the ever-present flags
					$thisfile_riff_raw_avih['dwTotalFrames']         = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($avihData, 16, 4)); // # frames in file
					$thisfile_riff_raw_avih['dwInitialFrames']       = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($avihData, 20, 4));
					$thisfile_riff_raw_avih['dwStreams']             = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($avihData, 24, 4));
					$thisfile_riff_raw_avih['dwSuggestedBufferSize'] = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($avihData, 28, 4));
					$thisfile_riff_raw_avih['dwWidth']               = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($avihData, 32, 4));
					$thisfile_riff_raw_avih['dwHeight']              = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($avihData, 36, 4));
					$thisfile_riff_raw_avih['dwScale']               = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($avihData, 40, 4));
					$thisfile_riff_raw_avih['dwRate']                = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($avihData, 44, 4));
					$thisfile_riff_raw_avih['dwStart']               = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($avihData, 48, 4));
					$thisfile_riff_raw_avih['dwLength']              = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($avihData, 52, 4));

					$thisfile_riff_raw_avih['flags']['hasindex']     = (bool) ($thisfile_riff_raw_avih['dwFlags'] & 0x00000010);
					$thisfile_riff_raw_avih['flags']['mustuseindex'] = (bool) ($thisfile_riff_raw_avih['dwFlags'] & 0x00000020);
					$thisfile_riff_raw_avih['flags']['interleaved']  = (bool) ($thisfile_riff_raw_avih['dwFlags'] & 0x00000100);
					$thisfile_riff_raw_avih['flags']['trustcktype']  = (bool) ($thisfile_riff_raw_avih['dwFlags'] & 0x00000800);
					$thisfile_riff_raw_avih['flags']['capturedfile'] = (bool) ($thisfile_riff_raw_avih['dwFlags'] & 0x00010000);
					$thisfile_riff_raw_avih['flags']['copyrighted']  = (bool) ($thisfile_riff_raw_avih['dwFlags'] & 0x00020010);

					// shortcut
					$thisfile_riff_video[$streamindex] = array();
					$thisfile_riff_video_current = &$thisfile_riff_video[$streamindex];

					if ($thisfile_riff_raw_avih['dwWidth'] > 0) {
						$thisfile_riff_video_current['frame_width'] = $thisfile_riff_raw_avih['dwWidth'];
						$thisfile_video['resolution_x']             = $thisfile_riff_video_current['frame_width'];
					}
					if ($thisfile_riff_raw_avih['dwHeight'] > 0) {
						$thisfile_riff_video_current['frame_height'] = $thisfile_riff_raw_avih['dwHeight'];
						$thisfile_video['resolution_y']              = $thisfile_riff_video_current['frame_height'];
					}
					if ($thisfile_riff_raw_avih['dwTotalFrames'] > 0) {
						$thisfile_riff_video_current['total_frames'] = $thisfile_riff_raw_avih['dwTotalFrames'];
						$thisfile_video['total_frames']              = $thisfile_riff_video_current['total_frames'];
					}

					$thisfile_riff_video_current['frame_rate'] = round(1000000 / $thisfile_riff_raw_avih['dwMicroSecPerFrame'], 3);
					$thisfile_video['frame_rate'] = $thisfile_riff_video_current['frame_rate'];
				}
				if (isset($thisfile_riff['AVI ']['hdrl']['strl']['strh'][0]['data'])) {
					if (is_array($thisfile_riff['AVI ']['hdrl']['strl']['strh'])) {
						for ($i = 0; $i < count($thisfile_riff['AVI ']['hdrl']['strl']['strh']); $i++) {
							if (isset($thisfile_riff['AVI ']['hdrl']['strl']['strh'][$i]['data'])) {
								$strhData = $thisfile_riff['AVI ']['hdrl']['strl']['strh'][$i]['data'];
								$strhfccType = substr($strhData,  0, 4);

								if (isset($thisfile_riff['AVI ']['hdrl']['strl']['strf'][$i]['data'])) {
									$strfData = $thisfile_riff['AVI ']['hdrl']['strl']['strf'][$i]['data'];

									// shortcut
									$thisfile_riff_raw_strf_strhfccType_streamindex = &$thisfile_riff_raw['strf'][$strhfccType][$streamindex];

									switch ($strhfccType) {
										case 'auds':
											$thisfile_audio['bitrate_mode'] = 'cbr';
											$thisfile_audio_dataformat      = 'wav';
											if (isset($thisfile_riff_audio) && is_array($thisfile_riff_audio)) {
												$streamindex = count($thisfile_riff_audio);
											}

											$thisfile_riff_audio[$streamindex] = getid3_riff::RIFFparseWAVEFORMATex($strfData);
											$thisfile_audio['wformattag'] = $thisfile_riff_audio[$streamindex]['raw']['wFormatTag'];

											// shortcut
											$thisfile_audio['streams'][$streamindex] = $thisfile_riff_audio[$streamindex];
											$thisfile_audio_streams_currentstream = &$thisfile_audio['streams'][$streamindex];

											if ($thisfile_audio_streams_currentstream['bits_per_sample'] == 0) {
												unset($thisfile_audio_streams_currentstream['bits_per_sample']);
											}
											$thisfile_audio_streams_currentstream['wformattag'] = $thisfile_audio_streams_currentstream['raw']['wFormatTag'];
											unset($thisfile_audio_streams_currentstream['raw']);

											// shortcut
											$thisfile_riff_raw['strf'][$strhfccType][$streamindex] = $thisfile_riff_audio[$streamindex]['raw'];

											unset($thisfile_riff_audio[$streamindex]['raw']);
											$thisfile_audio = getid3_lib::array_merge_noclobber($thisfile_audio, $thisfile_riff_audio[$streamindex]);

											$thisfile_audio['lossless'] = false;
											switch ($thisfile_riff_raw_strf_strhfccType_streamindex['wFormatTag']) {
												case 0x0001:  // PCM
													$thisfile_audio_dataformat  = 'wav';
													$thisfile_audio['lossless'] = true;
													break;

												case 0x0050: // MPEG Layer 2 or Layer 1
													$thisfile_audio_dataformat = 'mp2'; // Assume Layer-2
													break;

												case 0x0055: // MPEG Layer 3
													$thisfile_audio_dataformat = 'mp3';
													break;

												case 0x00FF: // AAC
													$thisfile_audio_dataformat = 'aac';
													break;

												case 0x0161: // Windows Media v7 / v8 / v9
												case 0x0162: // Windows Media Professional v9
												case 0x0163: // Windows Media Lossess v9
													$thisfile_audio_dataformat = 'wma';
													break;

												case 0x2000: // AC-3
													$thisfile_audio_dataformat = 'ac3';
													break;

												case 0x2001: // DTS
													$thisfile_audio_dataformat = 'dts';
													break;

												default:
													$thisfile_audio_dataformat = 'wav';
													break;
											}
											$thisfile_audio_streams_currentstream['dataformat']   = $thisfile_audio_dataformat;
											$thisfile_audio_streams_currentstream['lossless']     = $thisfile_audio['lossless'];
											$thisfile_audio_streams_currentstream['bitrate_mode'] = $thisfile_audio['bitrate_mode'];
											break;


										case 'iavs':
										case 'vids':
											// shortcut
											$thisfile_riff_raw['strh'][$i]                  = array();
											$thisfile_riff_raw_strh_current                 = &$thisfile_riff_raw['strh'][$i];

											$thisfile_riff_raw_strh_current['fccType']               =                  substr($strhData,  0, 4);  // same as $strhfccType;
											$thisfile_riff_raw_strh_current['fccHandler']            =                  substr($strhData,  4, 4);
											$thisfile_riff_raw_strh_current['dwFlags']               = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($strhData,  8, 4)); // Contains AVITF_* flags
											$thisfile_riff_raw_strh_current['wPriority']             = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($strhData, 12, 2));
											$thisfile_riff_raw_strh_current['wLanguage']             = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($strhData, 14, 2));
											$thisfile_riff_raw_strh_current['dwInitialFrames']       = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($strhData, 16, 4));
											$thisfile_riff_raw_strh_current['dwScale']               = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($strhData, 20, 4));
											$thisfile_riff_raw_strh_current['dwRate']                = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($strhData, 24, 4));
											$thisfile_riff_raw_strh_current['dwStart']               = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($strhData, 28, 4));
											$thisfile_riff_raw_strh_current['dwLength']              = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($strhData, 32, 4));
											$thisfile_riff_raw_strh_current['dwSuggestedBufferSize'] = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($strhData, 36, 4));
											$thisfile_riff_raw_strh_current['dwQuality']             = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($strhData, 40, 4));
											$thisfile_riff_raw_strh_current['dwSampleSize']          = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($strhData, 44, 4));
											$thisfile_riff_raw_strh_current['rcFrame']               = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($strhData, 48, 4));

											$thisfile_riff_video_current['codec'] = getid3_riff::RIFFfourccLookup($thisfile_riff_raw_strh_current['fccHandler']);
											$thisfile_video['fourcc']             = $thisfile_riff_raw_strh_current['fccHandler'];
											if (!$thisfile_riff_video_current['codec'] && isset($thisfile_riff_raw_strf_strhfccType_streamindex['fourcc']) && getid3_riff::RIFFfourccLookup($thisfile_riff_raw_strf_strhfccType_streamindex['fourcc'])) {
												$thisfile_riff_video_current['codec'] = getid3_riff::RIFFfourccLookup($thisfile_riff_raw_strf_strhfccType_streamindex['fourcc']);
												$thisfile_video['fourcc']             = $thisfile_riff_raw_strf_strhfccType_streamindex['fourcc'];
											}
											$thisfile_video['codec']              = $thisfile_riff_video_current['codec'];
											$thisfile_video['pixel_aspect_ratio'] = (float) 1;
											switch ($thisfile_riff_raw_strh_current['fccHandler']) {
												case 'HFYU': // Huffman Lossless Codec
												case 'IRAW': // Intel YUV Uncompressed
												case 'YUY2': // Uncompressed YUV 4:2:2
													$thisfile_video['lossless'] = true;
													break;

												default:
													$thisfile_video['lossless'] = false;
													break;
											}

											switch ($strhfccType) {
												case 'vids':
													$thisfile_riff_raw_strf_strhfccType_streamindex = getid3_riff::ParseBITMAPINFOHEADER(substr($strfData, 0, 40), ($ThisFileInfo['fileformat'] == 'riff'));
													//$thisfile_riff_raw_strf_strhfccType_streamindex['biSize']          = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($strfData,  0, 4)); // number of bytes required by the BITMAPINFOHEADER structure
													//$thisfile_riff_raw_strf_strhfccType_streamindex['biWidth']         = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($strfData,  4, 4)); // width of the bitmap in pixels
													//$thisfile_riff_raw_strf_strhfccType_streamindex['biHeight']        = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($strfData,  8, 4)); // height of the bitmap in pixels. If biHeight is positive, the bitmap is a 'bottom-up' DIB and its origin is the lower left corner. If biHeight is negative, the bitmap is a 'top-down' DIB and its origin is the upper left corner
													//$thisfile_riff_raw_strf_strhfccType_streamindex['biPlanes']        = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($strfData, 12, 2)); // number of color planes on the target device. In most cases this value must be set to 1
													//$thisfile_riff_raw_strf_strhfccType_streamindex['biBitCount']      = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($strfData, 14, 2)); // Specifies the number of bits per pixels
													//$thisfile_riff_raw_strf_strhfccType_streamindex['fourcc']          =                                              substr($strfData, 16, 4);  //
													//$thisfile_riff_raw_strf_strhfccType_streamindex['biSizeImage']     = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($strfData, 20, 4)); // size of the bitmap data section of the image (the actual pixel data, excluding BITMAPINFOHEADER and RGBQUAD structures)
													//$thisfile_riff_raw_strf_strhfccType_streamindex['biXPelsPerMeter'] = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($strfData, 24, 4)); // horizontal resolution, in pixels per metre, of the target device
													//$thisfile_riff_raw_strf_strhfccType_streamindex['biYPelsPerMeter'] = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($strfData, 28, 4)); // vertical resolution, in pixels per metre, of the target device
													//$thisfile_riff_raw_strf_strhfccType_streamindex['biClrUsed']       = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($strfData, 32, 4)); // actual number of color indices in the color table used by the bitmap. If this value is zero, the bitmap uses the maximum number of colors corresponding to the value of the biBitCount member for the compression mode specified by biCompression
													//$thisfile_riff_raw_strf_strhfccType_streamindex['biClrImportant']  = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($strfData, 36, 4)); // number of color indices that are considered important for displaying the bitmap. If this value is zero, all colors are important

													$thisfile_video['bits_per_sample'] = $thisfile_riff_raw_strf_strhfccType_streamindex['biBitCount'];

													if ($thisfile_riff_video_current['codec'] == 'DV') {
														$thisfile_riff_video_current['dv_type'] = 2;
													}
													break;

												case 'iavs':
													$thisfile_riff_video_current['dv_type'] = 1;
													break;
											}
											break;

										default:
											$ThisFileInfo['warning'][] = 'Unhandled fccType for stream ('.$i.'): "'.$strhfccType.'"';
											break;

									}
								}
							}

							if (isset($thisfile_riff_raw_strf_strhfccType_streamindex['fourcc']) && getid3_riff::RIFFfourccLookup($thisfile_riff_raw_strf_strhfccType_streamindex['fourcc'])) {

								$thisfile_riff_video_current['codec'] = getid3_riff::RIFFfourccLookup($thisfile_riff_raw_strf_strhfccType_streamindex['fourcc']);
								$thisfile_video['codec']              = $thisfile_riff_video_current['codec'];
								$thisfile_video['fourcc']             = $thisfile_riff_raw_strf_strhfccType_streamindex['fourcc'];

								switch ($thisfile_riff_raw_strf_strhfccType_streamindex['fourcc']) {
									case 'HFYU': // Huffman Lossless Codec
									case 'IRAW': // Intel YUV Uncompressed
									case 'YUY2': // Uncompressed YUV 4:2:2
										$thisfile_video['lossless']        = true;
										$thisfile_video['bits_per_sample'] = 24;
										break;

									default:
										$thisfile_video['lossless']        = false;
										$thisfile_video['bits_per_sample'] = 24;
										break;
								}

							}
						}
					}
				}
				break;

			case 'CDDA':
				$thisfile_audio['bitrate_mode'] = 'cbr';
				$thisfile_audio_dataformat      = 'cda';
				$thisfile_audio['lossless']     = true;
				unset($ThisFileInfo['mime_type']);

				$thisfile_avdataoffset = 44;

				if (isset($thisfile_riff['CDDA']['fmt '][0]['data'])) {
					// shortcut
					$thisfile_riff_CDDA_fmt_0 = &$thisfile_riff['CDDA']['fmt '][0];

					$thisfile_riff_CDDA_fmt_0['unknown1']           = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($thisfile_riff_CDDA_fmt_0['data'],  0, 2));
					$thisfile_riff_CDDA_fmt_0['track_num']          = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($thisfile_riff_CDDA_fmt_0['data'],  2, 2));
					$thisfile_riff_CDDA_fmt_0['disc_id']            = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($thisfile_riff_CDDA_fmt_0['data'],  4, 4));
					$thisfile_riff_CDDA_fmt_0['start_offset_frame'] = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($thisfile_riff_CDDA_fmt_0['data'],  8, 4));
					$thisfile_riff_CDDA_fmt_0['playtime_frames']    = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($thisfile_riff_CDDA_fmt_0['data'], 12, 4));
					$thisfile_riff_CDDA_fmt_0['unknown6']           = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($thisfile_riff_CDDA_fmt_0['data'], 16, 4));
					$thisfile_riff_CDDA_fmt_0['unknown7']           = getid3_riff::EitherEndian2Int($ThisFileInfo, substr($thisfile_riff_CDDA_fmt_0['data'], 20, 4));

					$thisfile_riff_CDDA_fmt_0['start_offset_seconds'] = (float) $thisfile_riff_CDDA_fmt_0['start_offset_frame'] / 75;
					$thisfile_riff_CDDA_fmt_0['playtime_seconds']     = (float) $thisfile_riff_CDDA_fmt_0['playtime_frames'] / 75;
					$ThisFileInfo['comments']['track']                = $thisfile_riff_CDDA_fmt_0['track_num'];
					$ThisFileInfo['playtime_seconds']                 = $thisfile_riff_CDDA_fmt_0['playtime_seconds'];

					// hardcoded data for CD-audio
					$thisfile_audio['sample_rate']     = 44100;
					$thisfile_audio['channels']        = 2;
					$thisfile_audio['bits_per_sample'] = 16;
					$thisfile_audio['bitrate']         = $thisfile_audio['sample_rate'] * $thisfile_audio['channels'] * $thisfile_audio['bits_per_sample'];
					$thisfile_audio['bitrate_mode']    = 'cbr';
				}
				break;


			case 'AIFF':
			case 'AIFC':
				$thisfile_audio['bitrate_mode'] = 'cbr';
				$thisfile_audio_dataformat      = 'aiff';
				$thisfile_audio['lossless']     = true;
				$ThisFileInfo['mime_type']      = 'audio/x-aiff';

				if (isset($thisfile_riff[$RIFFsubtype]['SSND'][0]['offset'])) {
					$thisfile_avdataoffset = $thisfile_riff[$RIFFsubtype]['SSND'][0]['offset'] + 8;
					$thisfile_avdataend    = $thisfile_avdataoffset + $thisfile_riff[$RIFFsubtype]['SSND'][0]['size'];
					if ($thisfile_avdataend > $ThisFileInfo['filesize']) {
						if (($thisfile_avdataend == ($ThisFileInfo['filesize'] + 1)) && (($ThisFileInfo['filesize'] % 2) == 1)) {
							// structures rounded to 2-byte boundary, but dumb encoders
							// forget to pad end of file to make this actually work
						} else {
							$ThisFileInfo['warning'][] = 'Probable truncated AIFF file: expecting '.$thisfile_riff[$RIFFsubtype]['SSND'][0]['size'].' bytes of audio data, only '.($ThisFileInfo['filesize'] - $thisfile_avdataoffset).' bytes found';
						}
						$thisfile_avdataend = $ThisFileInfo['filesize'];
					}
				}

				if (isset($thisfile_riff[$RIFFsubtype]['COMM'][0]['data'])) {

					// shortcut
					$thisfile_riff_RIFFsubtype_COMM_0_data = &$thisfile_riff[$RIFFsubtype]['COMM'][0]['data'];

					$thisfile_riff_audio['channels']         =         getid3_lib::BigEndian2Int(substr($thisfile_riff_RIFFsubtype_COMM_0_data,  0,  2), true);
					$thisfile_riff_audio['total_samples']    =         getid3_lib::BigEndian2Int(substr($thisfile_riff_RIFFsubtype_COMM_0_data,  2,  4), false);
					$thisfile_riff_audio['bits_per_sample']  =         getid3_lib::BigEndian2Int(substr($thisfile_riff_RIFFsubtype_COMM_0_data,  6,  2), true);
					$thisfile_riff_audio['sample_rate']      = (int) getid3_lib::BigEndian2Float(substr($thisfile_riff_RIFFsubtype_COMM_0_data,  8, 10));

					if ($thisfile_riff[$RIFFsubtype]['COMM'][0]['size'] > 18) {
						$thisfile_riff_audio['codec_fourcc'] =                                   substr($thisfile_riff_RIFFsubtype_COMM_0_data, 18,  4);
						$CodecNameSize                       =         getid3_lib::BigEndian2Int(substr($thisfile_riff_RIFFsubtype_COMM_0_data, 22,  1), false);
						$thisfile_riff_audio['codec_name']   =                                   substr($thisfile_riff_RIFFsubtype_COMM_0_data, 23,  $CodecNameSize);
						switch ($thisfile_riff_audio['codec_name']) {
							case 'NONE':
								$thisfile_audio['codec']    = 'Pulse Code Modulation (PCM)';
								$thisfile_audio['lossless'] = true;
								break;

							case '':
								switch ($thisfile_riff_audio['codec_fourcc']) {
									// http://developer.apple.com/qa/snd/snd07.html
									case 'sowt':
										$thisfile_riff_audio['codec_name'] = 'Two\'s Compliment Little-Endian PCM';
										$thisfile_audio['lossless'] = true;
										break;

									case 'twos':
										$thisfile_riff_audio['codec_name'] = 'Two\'s Compliment Big-Endian PCM';
										$thisfile_audio['lossless'] = true;
										break;

									default:
										break;
								}
								break;

							default:
								$thisfile_audio['codec']    = $thisfile_riff_audio['codec_name'];
								$thisfile_audio['lossless'] = false;
								break;
						}
					}

					$thisfile_audio['channels']        = $thisfile_riff_audio['channels'];
					if ($thisfile_riff_audio['bits_per_sample'] > 0) {
						$thisfile_audio['bits_per_sample'] = $thisfile_riff_audio['bits_per_sample'];
					}
					$thisfile_audio['sample_rate']     = $thisfile_riff_audio['sample_rate'];
					if ($thisfile_audio['sample_rate'] == 0) {
						$ThisFileInfo['error'][] = 'Corrupted AIFF file: sample_rate == zero';
						return false;
					}
					$ThisFileInfo['playtime_seconds'] = $thisfile_riff_audio['total_samples'] / $thisfile_audio['sample_rate'];
				}

				if (isset($thisfile_riff[$RIFFsubtype]['COMT'])) {
					$offset = 0;
					$CommentCount                                           = getid3_lib::BigEndian2Int(substr($thisfile_riff[$RIFFsubtype]['COMT'][0]['data'], $offset, 2), false);
					$offset += 2;
					for ($i = 0; $i < $CommentCount; $i++) {
						$ThisFileInfo['comments_raw'][$i]['timestamp']      = getid3_lib::BigEndian2Int(substr($thisfile_riff[$RIFFsubtype]['COMT'][0]['data'], $offset, 4), false);
						$offset += 4;
						$ThisFileInfo['comments_raw'][$i]['marker_id']      = getid3_lib::BigEndian2Int(substr($thisfile_riff[$RIFFsubtype]['COMT'][0]['data'], $offset, 2), true);
						$offset += 2;
						$CommentLength                                      = getid3_lib::BigEndian2Int(substr($thisfile_riff[$RIFFsubtype]['COMT'][0]['data'], $offset, 2), false);
						$offset += 2;
						$ThisFileInfo['comments_raw'][$i]['comment']        =                           substr($thisfile_riff[$RIFFsubtype]['COMT'][0]['data'], $offset, $CommentLength);
						$offset += $CommentLength;

						$ThisFileInfo['comments_raw'][$i]['timestamp_unix'] = getid3_lib::DateMac2Unix($ThisFileInfo['comments_raw'][$i]['timestamp']);
						$thisfile_riff['comments']['comment'][] = $ThisFileInfo['comments_raw'][$i]['comment'];
					}
				}

				$CommentsChunkNames = array('NAME'=>'title', 'author'=>'artist', '(c) '=>'copyright', 'ANNO'=>'comment');
				foreach ($CommentsChunkNames as $key => $value) {
					if (isset($thisfile_riff[$RIFFsubtype][$key][0]['data'])) {
						$thisfile_riff['comments'][$value][] = $thisfile_riff[$RIFFsubtype][$key][0]['data'];
					}
				}
				break;

			case '8SVX':
				$thisfile_audio['bitrate_mode']    = 'cbr';
				$thisfile_audio_dataformat         = '8svx';
				$thisfile_audio['bits_per_sample'] = 8;
				$thisfile_audio['channels']        = 1; // overridden below, if need be
				$ThisFileInfo['mime_type']                = 'audio/x-aiff';

				if (isset($thisfile_riff[$RIFFsubtype]['BODY'][0]['offset'])) {
					$thisfile_avdataoffset = $thisfile_riff[$RIFFsubtype]['BODY'][0]['offset'] + 8;
					$thisfile_avdataend    = $thisfile_avdataoffset + $thisfile_riff[$RIFFsubtype]['BODY'][0]['size'];
					if ($thisfile_avdataend > $ThisFileInfo['filesize']) {
						$ThisFileInfo['warning'][] = 'Probable truncated AIFF file: expecting '.$thisfile_riff[$RIFFsubtype]['BODY'][0]['size'].' bytes of audio data, only '.($ThisFileInfo['filesize'] - $thisfile_avdataoffset).' bytes found';
					}
				}

				if (isset($thisfile_riff[$RIFFsubtype]['VHDR'][0]['offset'])) {
					// shortcut
					$thisfile_riff_RIFFsubtype_VHDR_0 = &$thisfile_riff[$RIFFsubtype]['VHDR'][0];

					$thisfile_riff_RIFFsubtype_VHDR_0['oneShotHiSamples']  =   getid3_lib::BigEndian2Int(substr($thisfile_riff_RIFFsubtype_VHDR_0['data'],  0, 4));
					$thisfile_riff_RIFFsubtype_VHDR_0['repeatHiSamples']   =   getid3_lib::BigEndian2Int(substr($thisfile_riff_RIFFsubtype_VHDR_0['data'],  4, 4));
					$thisfile_riff_RIFFsubtype_VHDR_0['samplesPerHiCycle'] =   getid3_lib::BigEndian2Int(substr($thisfile_riff_RIFFsubtype_VHDR_0['data'],  8, 4));
					$thisfile_riff_RIFFsubtype_VHDR_0['samplesPerSec']     =   getid3_lib::BigEndian2Int(substr($thisfile_riff_RIFFsubtype_VHDR_0['data'], 12, 2));
					$thisfile_riff_RIFFsubtype_VHDR_0['ctOctave']          =   getid3_lib::BigEndian2Int(substr($thisfile_riff_RIFFsubtype_VHDR_0['data'], 14, 1));
					$thisfile_riff_RIFFsubtype_VHDR_0['sCompression']      =   getid3_lib::BigEndian2Int(substr($thisfile_riff_RIFFsubtype_VHDR_0['data'], 15, 1));
					$thisfile_riff_RIFFsubtype_VHDR_0['Volume']            = getid3_lib::FixedPoint16_16(substr($thisfile_riff_RIFFsubtype_VHDR_0['data'], 16, 4));

					$thisfile_audio['sample_rate'] = $thisfile_riff_RIFFsubtype_VHDR_0['samplesPerSec'];

					switch ($thisfile_riff_RIFFsubtype_VHDR_0['sCompression']) {
						case 0:
							$thisfile_audio['codec']    = 'Pulse Code Modulation (PCM)';
							$thisfile_audio['lossless'] = true;
							$ActualBitsPerSample               = 8;
							break;

						case 1:
							$thisfile_audio['codec']    = 'Fibonacci-delta encoding';
							$thisfile_audio['lossless'] = false;
							$ActualBitsPerSample               = 4;
							break;

						default:
							$ThisFileInfo['warning'][] = 'Unexpected sCompression value in 8SVX.VHDR chunk - expecting 0 or 1, found "'.sCompression.'"';
							break;
					}
				}

				if (isset($thisfile_riff[$RIFFsubtype]['CHAN'][0]['data'])) {
					$ChannelsIndex = getid3_lib::BigEndian2Int(substr($thisfile_riff[$RIFFsubtype]['CHAN'][0]['data'], 0, 4));
					switch ($ChannelsIndex) {
						case 6: // Stereo
							$thisfile_audio['channels'] = 2;
							break;

						case 2: // Left channel only
						case 4: // Right channel only
							$thisfile_audio['channels'] = 1;
							break;

						default:
							$ThisFileInfo['warning'][] = 'Unexpected value in 8SVX.CHAN chunk - expecting 2 or 4 or 6, found "'.$ChannelsIndex.'"';
							break;
					}

				}

				$CommentsChunkNames = array('NAME'=>'title', 'author'=>'artist', '(c) '=>'copyright', 'ANNO'=>'comment');
				foreach ($CommentsChunkNames as $key => $value) {
					if (isset($thisfile_riff[$RIFFsubtype][$key][0]['data'])) {
						$thisfile_riff['comments'][$value][] = $thisfile_riff[$RIFFsubtype][$key][0]['data'];
					}
				}

				$thisfile_audio['bitrate'] = $thisfile_audio['sample_rate'] * $ActualBitsPerSample * $thisfile_audio['channels'];
				if (!empty($thisfile_audio['bitrate'])) {
					$ThisFileInfo['playtime_seconds'] = ($thisfile_avdataend - $thisfile_avdataoffset) / ($thisfile_audio['bitrate'] / 8);
				}
				break;


			case 'CDXA':
				$ThisFileInfo['mime_type']      = 'video/mpeg';
				if (!empty($thisfile_riff['CDXA']['data'][0]['size'])) {
					$GETID3_ERRORARRAY = &$ThisFileInfo['warning'];
					if (getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.audio-video.mpeg.php', __FILE__, false)) {
						$dummy = $ThisFileInfo;
						$dummy['error'] = array();
						$mpeg_scanner = new getid3_mpeg($fd, $dummy);
						if (empty($dummy['error'])) {
							$ThisFileInfo['audio']   = $dummy['audio'];
							$ThisFileInfo['video']   = $dummy['video'];
							$ThisFileInfo['mpeg']    = $dummy['mpeg'];
							$ThisFileInfo['warning'] = $dummy['warning'];
						}
						unset($mpeg_scanner);
					}
				}
				break;


			default:
				$ThisFileInfo['error'][] = 'Unknown RIFF type: expecting one of (WAVE|RMP3|AVI |CDDA|AIFF|AIFC|8SVX|CDXA), found "'.$RIFFsubtype.'" instead';
				unset($ThisFileInfo['fileformat']);
				break;
		}

		if (@$thisfile_riff_raw['fmt ']['wFormatTag'] == 1) {
			// http://www.mega-nerd.com/erikd/Blog/Windiots/dts.html
			fseek($fd, $ThisFileInfo['avdataoffset'], SEEK_SET);
			$FirstFourBytes = fread($fd, 4);
			if (preg_match('/^\xFF\x1F\x00\xE8/s', $FirstFourBytes)) {
				// DTSWAV
				$thisfile_audio_dataformat = 'dts';
			} elseif (preg_match('/^\x7F\xFF\x80\x01/s', $FirstFourBytes)) {
				// DTS, but this probably shouldn't happen
				$thisfile_audio_dataformat = 'dts';
			}
		}


		if (isset($thisfile_riff_WAVE['DISP']) && is_array($thisfile_riff_WAVE['DISP'])) {
			$thisfile_riff['comments']['title'][] = trim(substr($thisfile_riff_WAVE['DISP'][count($thisfile_riff_WAVE['DISP']) - 1]['data'], 4));
		}
		if (isset($thisfile_riff_WAVE['INFO']) && is_array($thisfile_riff_WAVE['INFO'])) {
			$this->RIFFcommentsParse($thisfile_riff_WAVE['INFO'], $thisfile_riff['comments']);
		}

		if (empty($thisfile_audio['encoder']) && !empty($ThisFileInfo['mpeg']['audio']['LAME']['short_version'])) {
			$thisfile_audio['encoder'] = $ThisFileInfo['mpeg']['audio']['LAME']['short_version'];
		}

		if (!isset($ThisFileInfo['playtime_seconds'])) {
			$ThisFileInfo['playtime_seconds'] = 0;
		}
		if (isset($thisfile_riff_raw['avih']['dwTotalFrames']) && isset($thisfile_riff_raw['avih']['dwMicroSecPerFrame'])) {
			$ThisFileInfo['playtime_seconds'] = $thisfile_riff_raw['avih']['dwTotalFrames'] * ($thisfile_riff_raw['avih']['dwMicroSecPerFrame'] / 1000000);
		}

		if ($ThisFileInfo['playtime_seconds'] > 0) {
			if (isset($thisfile_riff_audio) && isset($thisfile_riff_video)) {

				if (!isset($ThisFileInfo['bitrate'])) {
					$ThisFileInfo['bitrate'] = ((($thisfile_avdataend - $thisfile_avdataoffset) / $ThisFileInfo['playtime_seconds']) * 8);
				}

			} elseif (isset($thisfile_riff_audio) && !isset($thisfile_riff_video)) {

				if (!isset($thisfile_audio['bitrate'])) {
					$thisfile_audio['bitrate'] = ((($thisfile_avdataend - $thisfile_avdataoffset) / $ThisFileInfo['playtime_seconds']) * 8);
				}

			} elseif (!isset($thisfile_riff_audio) && isset($thisfile_riff_video)) {

				if (!isset($thisfile_video['bitrate'])) {
					$thisfile_video['bitrate'] = ((($thisfile_avdataend - $thisfile_avdataoffset) / $ThisFileInfo['playtime_seconds']) * 8);
				}

			}
		}


		if (isset($thisfile_riff_video) && isset($thisfile_audio['bitrate']) && ($thisfile_audio['bitrate'] > 0) && ($ThisFileInfo['playtime_seconds'] > 0)) {

			$ThisFileInfo['bitrate'] = ((($thisfile_avdataend - $thisfile_avdataoffset) / $ThisFileInfo['playtime_seconds']) * 8);
			$thisfile_audio['bitrate'] = 0;
			$thisfile_video['bitrate'] = $ThisFileInfo['bitrate'];
			foreach ($thisfile_riff_audio as $channelnumber => $audioinfoarray) {
				$thisfile_video['bitrate'] -= $audioinfoarray['bitrate'];
				$thisfile_audio['bitrate'] += $audioinfoarray['bitrate'];
			}
			if ($thisfile_video['bitrate'] <= 0) {
				unset($thisfile_video['bitrate']);
			}
			if ($thisfile_audio['bitrate'] <= 0) {
				unset($thisfile_audio['bitrate']);
			}
		}

		if (isset($ThisFileInfo['mpeg']['audio'])) {
			$thisfile_audio_dataformat      = 'mp'.$ThisFileInfo['mpeg']['audio']['layer'];
			$thisfile_audio['sample_rate']  = $ThisFileInfo['mpeg']['audio']['sample_rate'];
			$thisfile_audio['channels']     = $ThisFileInfo['mpeg']['audio']['channels'];
			$thisfile_audio['bitrate']      = $ThisFileInfo['mpeg']['audio']['bitrate'];
			$thisfile_audio['bitrate_mode'] = strtolower($ThisFileInfo['mpeg']['audio']['bitrate_mode']);
			if (!empty($ThisFileInfo['mpeg']['audio']['codec'])) {
				$thisfile_audio['codec'] = $ThisFileInfo['mpeg']['audio']['codec'].' '.$thisfile_audio['codec'];
			}
			if (!empty($thisfile_audio['streams'])) {
				foreach ($thisfile_audio['streams'] as $streamnumber => $streamdata) {
					if ($streamdata['dataformat'] == $thisfile_audio_dataformat) {
						$thisfile_audio['streams'][$streamnumber]['sample_rate']  = $thisfile_audio['sample_rate'];
						$thisfile_audio['streams'][$streamnumber]['channels']     = $thisfile_audio['channels'];
						$thisfile_audio['streams'][$streamnumber]['bitrate']      = $thisfile_audio['bitrate'];
						$thisfile_audio['streams'][$streamnumber]['bitrate_mode'] = $thisfile_audio['bitrate_mode'];
						$thisfile_audio['streams'][$streamnumber]['codec']        = $thisfile_audio['codec'];
					}
				}
			}
			$thisfile_audio['encoder_options'] = getid3_mp3::GuessEncoderOptions($ThisFileInfo);
		}


		if (!empty($thisfile_riff_raw['fmt ']['wBitsPerSample']) && ($thisfile_riff_raw['fmt ']['wBitsPerSample'] > 0)) {
			switch ($thisfile_audio_dataformat) {
				case 'ac3':
					// ignore bits_per_sample
					break;

				default:
					$thisfile_audio['bits_per_sample'] = $thisfile_riff_raw['fmt ']['wBitsPerSample'];
					break;
			}
		}


		if (empty($thisfile_riff_raw)) {
			unset($thisfile_riff['raw']);
		}
		if (empty($thisfile_riff_audio)) {
			unset($thisfile_riff['audio']);
		}
		if (empty($thisfile_riff_video)) {
			unset($thisfile_riff['video']);
		}

		return true;
	}


	function RIFFcommentsParse(&$RIFFinfoArray, &$CommentsTargetArray) {
		$RIFFinfoKeyLookup = array(
			'IARL'=>'archivallocation',
			'IART'=>'artist',
			'ICDS'=>'costumedesigner',
			'ICMS'=>'commissionedby',
			'ICMT'=>'comment',
			'ICNT'=>'country',
			'ICOP'=>'copyright',
			'ICRD'=>'creationdate',
			'IDIM'=>'dimensions',
			'IDIT'=>'digitizationdate',
			'IDPI'=>'resolution',
			'IDST'=>'distributor',
			'IEDT'=>'editor',
			'IENG'=>'engineers',
			'IFRM'=>'accountofparts',
			'IGNR'=>'genre',
			'IKEY'=>'keywords',
			'ILGT'=>'lightness',
			'ILNG'=>'language',
			'IMED'=>'orignalmedium',
			'IMUS'=>'composer',
			'INAM'=>'title',
			'IPDS'=>'productiondesigner',
			'IPLT'=>'palette',
			'IPRD'=>'product',
			'IPRO'=>'producer',
			'IPRT'=>'part',
			'IRTD'=>'rating',
			'ISBJ'=>'subject',
			'ISFT'=>'software',
			'ISGN'=>'secondarygenre',
			'ISHP'=>'sharpness',
			'ISRC'=>'sourcesupplier',
			'ISRF'=>'digitizationsource',
			'ISTD'=>'productionstudio',
			'ISTR'=>'starring',
			'ITCH'=>'encoded_by',
			'IWEB'=>'url',
			'IWRI'=>'writer'
		);
		foreach ($RIFFinfoKeyLookup as $key => $value) {
			if (isset($RIFFinfoArray[$key])) {
				foreach ($RIFFinfoArray[$key] as $commentid => $commentdata) {
					if (trim($commentdata['data']) != '') {
						@$CommentsTargetArray[$value][] = trim($commentdata['data']);
					}
				}
			}
		}
		return true;
	}

	function ParseRIFF(&$fd, $startoffset, $maxoffset, &$ThisFileInfo) {
		$maxoffset = min($maxoffset, $ThisFileInfo['avdataend']);

		$RIFFchunk = false;
		$FoundAllChunksWeNeed = false;

		if (($startoffset < 0) || ($startoffset >= pow(2, 31))) {
			$ThisFileInfo['warning'][] = 'Unable to ParseRIFF() at '.$startoffset.' because beyond 2GB limit of PHP filesystem functions';
			return false;
		}
		fseek($fd, $startoffset, SEEK_SET);

		while (ftell($fd) < $maxoffset) {
			$chunkname = fread($fd, 4);
			if (strlen($chunkname) < 4) {
				$ThisFileInfo['error'][] = 'Expecting chunk name at offset '.(ftell($fd) - 4).' but found nothing. Aborting RIFF parsing.';
				break;
			}

			$chunksize = getid3_riff::EitherEndian2Int($ThisFileInfo, fread($fd, 4));
			if ($chunksize == 0) {
				$ThisFileInfo['warning'][] = 'Chunk size at offset '.(ftell($fd) - 4).' is zero. Aborting RIFF parsing.';
				continue;
			}
			if (($chunksize % 2) != 0) {
				// all structures are packed on word boundaries
				$chunksize++;
			}

			switch ($chunkname) {
				case 'LIST':
					$listname = fread($fd, 4);
					if (eregi('^(movi|rec )$', $listname)) {
						$RIFFchunk[$listname]['offset'] = ftell($fd) - 4;
						$RIFFchunk[$listname]['size']   = $chunksize;

						if ($FoundAllChunksWeNeed) {

							// skip over

						} else {

							$WhereWeWere = ftell($fd);
							$AudioChunkHeader = fread($fd, 12);
							$AudioChunkStreamNum  =                              substr($AudioChunkHeader, 0, 2);
							$AudioChunkStreamType =                              substr($AudioChunkHeader, 2, 2);
							$AudioChunkSize       = getid3_lib::LittleEndian2Int(substr($AudioChunkHeader, 4, 4));

							if ($AudioChunkStreamType == 'wb') {
								$FirstFourBytes = substr($AudioChunkHeader, 8, 4);
								if (preg_match('/^\xFF[\xE2-\xE7\xF2-\xF7\xFA-\xFF][\x00-\xEB]/s', $FirstFourBytes)) {
									// MP3
									if (getid3_mp3::MPEGaudioHeaderBytesValid($FirstFourBytes)) {
										$dummy = $ThisFileInfo;
										$dummy['avdataoffset'] = ftell($fd) - 4;
										$dummy['avdataend']    = ftell($fd) + $AudioChunkSize;
										getid3_mp3::getOnlyMPEGaudioInfo($fd, $dummy, $dummy['avdataoffset'], false);
										if (isset($dummy['mpeg']['audio'])) {
											$ThisFileInfo = $dummy;
											$ThisFileInfo['audio']['dataformat']   = 'mp'.$ThisFileInfo['mpeg']['audio']['layer'];
											$ThisFileInfo['audio']['sample_rate']  = $ThisFileInfo['mpeg']['audio']['sample_rate'];
											$ThisFileInfo['audio']['channels']     = $ThisFileInfo['mpeg']['audio']['channels'];
											$ThisFileInfo['audio']['bitrate']      = $ThisFileInfo['mpeg']['audio']['bitrate'];
											$ThisFileInfo['bitrate']               = $ThisFileInfo['audio']['bitrate'];
											$ThisFileInfo['audio']['bitrate_mode'] = strtolower($ThisFileInfo['mpeg']['audio']['bitrate_mode']);
										}
										unset($dummy);
									}

								} elseif (preg_match('/^\x0B\x77/s', $FirstFourBytes)) {

									// AC3
									$GETID3_ERRORARRAY = &$ThisFileInfo['warning'];
									if (getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.audio.ac3.php', __FILE__, false)) {

										$dummy = $ThisFileInfo;
										$dummy['avdataoffset'] = ftell($fd) - 4;
										$dummy['avdataend']    = ftell($fd) + $AudioChunkSize;
										$dummy['error']        = array();
										$ac3_tag = new getid3_ac3($fd, $dummy);
										if (empty($dummy['error'])) {
											$ThisFileInfo['audio']   = $dummy['audio'];
											$ThisFileInfo['ac3']     = $dummy['ac3'];
											$ThisFileInfo['warning'] = $dummy['warning'];
										}
										unset($ac3_tag);

									}

								}

							}

							$FoundAllChunksWeNeed = true;
							fseek($fd, $WhereWeWere, SEEK_SET);

						}
						fseek($fd, $chunksize - 4, SEEK_CUR);

					//} elseif (ereg('^[0-9]{2}(wb|pc|dc|db)$', $listname)) {
                    //
					//	// data chunk, ignore
                    //
					} else {

						if (!isset($RIFFchunk[$listname])) {
							$RIFFchunk[$listname] = array();
						}
						$LISTchunkParent    = $listname;
						$LISTchunkMaxOffset = ftell($fd) - 4 + $chunksize;
						if ($parsedChunk = getid3_riff::ParseRIFF($fd, ftell($fd), ftell($fd) + $chunksize - 4, $ThisFileInfo)) {
							$RIFFchunk[$listname] = array_merge_recursive($RIFFchunk[$listname], $parsedChunk);
						}

					}
					break;

				default:
					if (eregi('^[0-9]{2}(wb|pc|dc|db)$', $chunkname)) {
						$nextoffset = ftell($fd) + $chunksize;
						if (($nextoffset < 0) || ($nextoffset >= pow(2, 31))) {
							$ThisFileInfo['warning'][] = 'Unable to parse chunk at offset '.$nextoffset.' because beyond 2GB limit of PHP filesystem functions';
							break 2;
						}
						fseek($fd, $nextoffset, SEEK_SET);
						break;
					}
					$thisindex = 0;
					if (isset($RIFFchunk[$chunkname]) && is_array($RIFFchunk[$chunkname])) {
						$thisindex = count($RIFFchunk[$chunkname]);
					}
					$RIFFchunk[$chunkname][$thisindex]['offset'] = ftell($fd) - 8;
					$RIFFchunk[$chunkname][$thisindex]['size']   = $chunksize;
					switch ($chunkname) {
						case 'data':
							$ThisFileInfo['avdataoffset'] = ftell($fd);
							$ThisFileInfo['avdataend']    = $ThisFileInfo['avdataoffset'] + $chunksize;

							$RIFFdataChunkContentsTest = fread($fd, 36);

							if ((strlen($RIFFdataChunkContentsTest) > 0) && preg_match('/^\xFF[\xE2-\xE7\xF2-\xF7\xFA-\xFF][\x00-\xEB]/s', substr($RIFFdataChunkContentsTest, 0, 4))) {

								// Probably is MP3 data
								if (getid3_mp3::MPEGaudioHeaderBytesValid(substr($RIFFdataChunkContentsTest, 0, 4))) {

									// copy info array
									$dummy = $ThisFileInfo;

									getid3_mp3::getOnlyMPEGaudioInfo($fd, $dummy, $RIFFchunk[$chunkname][$thisindex]['offset'], false);

                                    // use dummy array unless error
									if (empty($dummy['error'])) {
									    $ThisFileInfo = $dummy;
									}
								}

							} elseif ((strlen($RIFFdataChunkContentsTest) > 0) && (substr($RIFFdataChunkContentsTest, 0, 2) == "\x0B\x77")) {

								// This is probably AC-3 data
								$GETID3_ERRORARRAY = &$ThisFileInfo['warning'];
								if (getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.audio.ac3.php', __FILE__, false)) {

									$dummy = $ThisFileInfo;
									$dummy['avdataoffset'] = $RIFFchunk[$chunkname][$thisindex]['offset'];
									$dummy['avdataend']    = $dummy['avdataoffset'] + $RIFFchunk[$chunkname][$thisindex]['size'];
									$dummy['error']        = array();

									$ac3_tag = new getid3_ac3($fd, $dummy);
									if (empty($dummy['error'])) {
										$ThisFileInfo['audio']   = $dummy['audio'];
										$ThisFileInfo['ac3']     = $dummy['ac3'];
										$ThisFileInfo['warning'] = $dummy['warning'];
									}
									unset($ac3_tag);

								}

							} elseif ((strlen($RIFFdataChunkContentsTest) > 0) && (substr($RIFFdataChunkContentsTest, 8, 2) == "\x77\x0B")) {

								// Dolby Digital WAV
								// AC-3 content, but not encoded in same format as normal AC-3 file
								// For one thing, byte order is swapped

								$GETID3_ERRORARRAY = &$ThisFileInfo['warning'];
								if (getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.audio.ac3.php', __FILE__, false)) {

									// ok to use tmpfile here - only 56 bytes
									if ($fd_temp = tmpfile()) {

										for ($i = 0; $i < 28; $i += 2) {
											// swap byte order
											fwrite($fd_temp, substr($RIFFdataChunkContentsTest, 8 + $i + 1, 1));
											fwrite($fd_temp, substr($RIFFdataChunkContentsTest, 8 + $i + 0, 1));
										}

										$dummy = $ThisFileInfo;
										$dummy['avdataoffset'] = 0;
										$dummy['avdataend']    = 20;
										$dummy['error']        = array();
										$ac3_tag = new getid3_ac3($fd_temp, $dummy);
										fclose($fd_temp);
										if (empty($dummy['error'])) {
											$ThisFileInfo['audio']   = $dummy['audio'];
											$ThisFileInfo['ac3']     = $dummy['ac3'];
											$ThisFileInfo['warning'] = $dummy['warning'];
										} else {
											$ThisFileInfo['error'][] = 'Errors parsing DolbyDigital WAV: '.explode(';', $dummy['error']);
										}
										unset($ac3_tag);

									} else {

										$ThisFileInfo['error'][] = 'Could not create temporary file to analyze DolbyDigital WAV';

									}

								}

							} elseif ((strlen($RIFFdataChunkContentsTest) > 0) && (substr($RIFFdataChunkContentsTest, 0, 4) == 'wvpk')) {

								// This is WavPack data
								$ThisFileInfo['wavpack']['offset'] = $RIFFchunk[$chunkname][$thisindex]['offset'];
								$ThisFileInfo['wavpack']['size']   = getid3_lib::LittleEndian2Int(substr($RIFFdataChunkContentsTest, 4, 4));
								getid3_riff::RIFFparseWavPackHeader(substr($RIFFdataChunkContentsTest, 8, 28), $ThisFileInfo);

							} else {

								// This is some other kind of data (quite possibly just PCM)
								// do nothing special, just skip it

							}
							$nextoffset = $RIFFchunk[$chunkname][$thisindex]['offset'] + 8 + $chunksize;
							if (($nextoffset < 0) || ($nextoffset >= pow(2, 31))) {
								$ThisFileInfo['warning'][] = 'Unable to parse chunk at offset '.$nextoffset.' because beyond 2GB limit of PHP filesystem functions';
								break 3;
							}
							fseek($fd, $RIFFchunk[$chunkname][$thisindex]['offset'] + 8 + $chunksize, SEEK_SET);
							break;

						case 'bext':
						case 'cart':
						case 'fmt ':
						case 'strh':
						case 'strf':
						case 'indx':
						case 'MEXT':
						case 'DISP':
							// always read data in
							$RIFFchunk[$chunkname][$thisindex]['data'] = fread($fd, $chunksize);
							break;

						default:
							if (!ereg('^[0-9]{2}(wb|pc|dc|db)$', $chunkname) && !empty($LISTchunkParent) && (($RIFFchunk[$chunkname][$thisindex]['offset'] + $RIFFchunk[$chunkname][$thisindex]['size']) <= $LISTchunkMaxOffset)) {
								$RIFFchunk[$LISTchunkParent][$chunkname][$thisindex]['offset'] = $RIFFchunk[$chunkname][$thisindex]['offset'];
								$RIFFchunk[$LISTchunkParent][$chunkname][$thisindex]['size']   = $RIFFchunk[$chunkname][$thisindex]['size'];
								unset($RIFFchunk[$chunkname][$thisindex]['offset']);
								unset($RIFFchunk[$chunkname][$thisindex]['size']);
								if (isset($RIFFchunk[$chunkname][$thisindex]) && empty($RIFFchunk[$chunkname][$thisindex])) {
									unset($RIFFchunk[$chunkname][$thisindex]);
								}
								if (isset($RIFFchunk[$chunkname]) && empty($RIFFchunk[$chunkname])) {
									unset($RIFFchunk[$chunkname]);
								}
								$RIFFchunk[$LISTchunkParent][$chunkname][$thisindex]['data'] = fread($fd, $chunksize);
							} elseif ($chunksize < 2048) {
								// only read data in if smaller than 2kB
								$RIFFchunk[$chunkname][$thisindex]['data'] = fread($fd, $chunksize);
							} else {
								$nextoffset = ftell($fd) + $chunksize;
								if (($nextoffset < 0) || ($nextoffset >= pow(2, 31))) {
									$ThisFileInfo['warning'][] = 'Unable to parse chunk at offset '.$nextoffset.' because beyond 2GB limit of PHP filesystem functions';
									break 3;
								}
								fseek($fd, $nextoffset, SEEK_SET);
							}
							break;
					}
					break;

			}

		}

		return $RIFFchunk;
	}


	function ParseRIFFdata(&$RIFFdata, &$ThisFileInfo) {
		if ($RIFFdata) {

		    $tempfile = tempnam('*', 'getID3');
            $fp_temp  = fopen($tempfile, "wb");
			$RIFFdataLength = strlen($RIFFdata);
			$NewLengthString = getid3_lib::LittleEndian2String($RIFFdataLength, 4);
			for ($i = 0; $i < 4; $i++) {
				$RIFFdata{$i + 4} = $NewLengthString{$i};
			}
			fwrite($fp_temp, $RIFFdata);
			fclose($fp_temp);

			$fp_temp  = fopen($tempfile, "rb");
			$dummy = array('filesize'=>$RIFFdataLength, 'filenamepath'=>$ThisFileInfo['filenamepath'], 'tags'=>$ThisFileInfo['tags'], 'avdataoffset'=>0, 'avdataend'=>$RIFFdataLength, 'warning'=>$ThisFileInfo['warning'], 'error'=>$ThisFileInfo['error'], 'comments'=>$ThisFileInfo['comments'], 'audio'=>(isset($ThisFileInfo['audio']) ? $ThisFileInfo['audio'] : array()), 'video'=>(isset($ThisFileInfo['video']) ? $ThisFileInfo['video'] : array()));
			$riff = new getid3_riff($fp_temp, $dummy);
			$ThisFileInfo['riff']     = $dummy['riff'];
			$ThisFileInfo['warning']  = $dummy['warning'];
			$ThisFileInfo['error']    = $dummy['error'];
			$ThisFileInfo['tags']     = $dummy['tags'];
			$ThisFileInfo['comments'] = $dummy['comments'];
			unset($riff);
			fclose($fp_temp);
			unlink($tempfile);
			return true;
		}
		return false;
	}


	function RIFFparseWAVEFORMATex($WaveFormatExData) {
		// shortcut
		$WaveFormatEx['raw'] = array();
		$WaveFormatEx_raw    = &$WaveFormatEx['raw'];

		$WaveFormatEx_raw['wFormatTag']      = getid3_lib::LittleEndian2Int(substr($WaveFormatExData,  0, 2));
		$WaveFormatEx_raw['nChannels']       = getid3_lib::LittleEndian2Int(substr($WaveFormatExData,  2, 2));
		$WaveFormatEx_raw['nSamplesPerSec']  = getid3_lib::LittleEndian2Int(substr($WaveFormatExData,  4, 4));
		$WaveFormatEx_raw['nAvgBytesPerSec'] = getid3_lib::LittleEndian2Int(substr($WaveFormatExData,  8, 4));
		$WaveFormatEx_raw['nBlockAlign']     = getid3_lib::LittleEndian2Int(substr($WaveFormatExData, 12, 2));
		$WaveFormatEx_raw['wBitsPerSample']  = getid3_lib::LittleEndian2Int(substr($WaveFormatExData, 14, 2));
		if (strlen($WaveFormatExData) > 16) {
			$WaveFormatEx_raw['cbSize']      = getid3_lib::LittleEndian2Int(substr($WaveFormatExData, 16, 2));
		}

		$WaveFormatEx['codec']           = getid3_riff::RIFFwFormatTagLookup($WaveFormatEx_raw['wFormatTag']);
		$WaveFormatEx['channels']        = $WaveFormatEx_raw['nChannels'];
		$WaveFormatEx['sample_rate']     = $WaveFormatEx_raw['nSamplesPerSec'];
		$WaveFormatEx['bitrate']         = $WaveFormatEx_raw['nAvgBytesPerSec'] * 8;
		$WaveFormatEx['bits_per_sample'] = $WaveFormatEx_raw['wBitsPerSample'];

		return $WaveFormatEx;
	}


	function RIFFparseWavPackHeader($WavPackChunkData, &$ThisFileInfo) {
		// typedef struct {
		//     char ckID [4];
		//     long ckSize;
		//     short version;
		//     short bits;                // added for version 2.00
		//     short flags, shift;        // added for version 3.00
		//     long total_samples, crc, crc2;
		//     char extension [4], extra_bc, extras [3];
		// } WavpackHeader;

		// shortcut
		$ThisFileInfo['wavpack'] = array();
		$thisfile_wavpack        = &$ThisFileInfo['wavpack'];

		$thisfile_wavpack['version']           = getid3_lib::LittleEndian2Int(substr($WavPackChunkData,  0, 2));
		if ($thisfile_wavpack['version'] >= 2) {
			$thisfile_wavpack['bits']          = getid3_lib::LittleEndian2Int(substr($WavPackChunkData,  2, 2));
		}
		if ($thisfile_wavpack['version'] >= 3) {
			$thisfile_wavpack['flags_raw']     = getid3_lib::LittleEndian2Int(substr($WavPackChunkData,  4, 2));
			$thisfile_wavpack['shift']         = getid3_lib::LittleEndian2Int(substr($WavPackChunkData,  6, 2));
			$thisfile_wavpack['total_samples'] = getid3_lib::LittleEndian2Int(substr($WavPackChunkData,  8, 4));
			$thisfile_wavpack['crc1']          = getid3_lib::LittleEndian2Int(substr($WavPackChunkData, 12, 4));
			$thisfile_wavpack['crc2']          = getid3_lib::LittleEndian2Int(substr($WavPackChunkData, 16, 4));
			$thisfile_wavpack['extension']     =                              substr($WavPackChunkData, 20, 4);
			$thisfile_wavpack['extra_bc']      = getid3_lib::LittleEndian2Int(substr($WavPackChunkData, 24, 1));
			for ($i = 0; $i <= 2; $i++) {
				$thisfile_wavpack['extras'][]  = getid3_lib::LittleEndian2Int(substr($WavPackChunkData, 25 + $i, 1));
			}

			// shortcut
			$thisfile_wavpack['flags'] = array();
			$thisfile_wavpack_flags = &$thisfile_wavpack['flags'];

			$thisfile_wavpack_flags['mono']                 = (bool) ($thisfile_wavpack['flags_raw'] & 0x000001);
			$thisfile_wavpack_flags['fast_mode']            = (bool) ($thisfile_wavpack['flags_raw'] & 0x000002);
			$thisfile_wavpack_flags['raw_mode']             = (bool) ($thisfile_wavpack['flags_raw'] & 0x000004);
			$thisfile_wavpack_flags['calc_noise']           = (bool) ($thisfile_wavpack['flags_raw'] & 0x000008);
			$thisfile_wavpack_flags['high_quality']         = (bool) ($thisfile_wavpack['flags_raw'] & 0x000010);
			$thisfile_wavpack_flags['3_byte_samples']       = (bool) ($thisfile_wavpack['flags_raw'] & 0x000020);
			$thisfile_wavpack_flags['over_20_bits']         = (bool) ($thisfile_wavpack['flags_raw'] & 0x000040);
			$thisfile_wavpack_flags['use_wvc']              = (bool) ($thisfile_wavpack['flags_raw'] & 0x000080);
			$thisfile_wavpack_flags['noiseshaping']         = (bool) ($thisfile_wavpack['flags_raw'] & 0x000100);
			$thisfile_wavpack_flags['very_fast_mode']       = (bool) ($thisfile_wavpack['flags_raw'] & 0x000200);
			$thisfile_wavpack_flags['new_high_quality']     = (bool) ($thisfile_wavpack['flags_raw'] & 0x000400);
			$thisfile_wavpack_flags['cancel_extreme']       = (bool) ($thisfile_wavpack['flags_raw'] & 0x000800);
			$thisfile_wavpack_flags['cross_decorrelation']  = (bool) ($thisfile_wavpack['flags_raw'] & 0x001000);
			$thisfile_wavpack_flags['new_decorrelation']    = (bool) ($thisfile_wavpack['flags_raw'] & 0x002000);
			$thisfile_wavpack_flags['joint_stereo']         = (bool) ($thisfile_wavpack['flags_raw'] & 0x004000);
			$thisfile_wavpack_flags['extra_decorrelation']  = (bool) ($thisfile_wavpack['flags_raw'] & 0x008000);
			$thisfile_wavpack_flags['override_noiseshape']  = (bool) ($thisfile_wavpack['flags_raw'] & 0x010000);
			$thisfile_wavpack_flags['override_jointstereo'] = (bool) ($thisfile_wavpack['flags_raw'] & 0x020000);
			$thisfile_wavpack_flags['copy_source_filetime'] = (bool) ($thisfile_wavpack['flags_raw'] & 0x040000);
			$thisfile_wavpack_flags['create_exe']           = (bool) ($thisfile_wavpack['flags_raw'] & 0x080000);
		}

		return true;
	}

	function ParseBITMAPINFOHEADER($BITMAPINFOHEADER, $littleEndian=true) {
		// yes it's ugly to instantiate a getid3_lib object here, suggested alternative please?
		$getid3_lib = new getid3_lib();
		$functionname = ($littleEndian ? 'LittleEndian2Int' : 'BigEndian2Int');
		$parsed['biSize']          = $getid3_lib->$functionname(substr($BITMAPINFOHEADER,  0, 4)); // number of bytes required by the BITMAPINFOHEADER structure
		$parsed['biWidth']         = $getid3_lib->$functionname(substr($BITMAPINFOHEADER,  4, 4)); // width of the bitmap in pixels
		$parsed['biHeight']        = $getid3_lib->$functionname(substr($BITMAPINFOHEADER,  8, 4)); // height of the bitmap in pixels. If biHeight is positive, the bitmap is a 'bottom-up' DIB and its origin is the lower left corner. If biHeight is negative, the bitmap is a 'top-down' DIB and its origin is the upper left corner
		$parsed['biPlanes']        = $getid3_lib->$functionname(substr($BITMAPINFOHEADER, 12, 2)); // number of color planes on the target device. In most cases this value must be set to 1
		$parsed['biBitCount']      = $getid3_lib->$functionname(substr($BITMAPINFOHEADER, 14, 2)); // Specifies the number of bits per pixels
		$parsed['fourcc']          =                            substr($BITMAPINFOHEADER, 16, 4);  // compression identifier
		$parsed['biSizeImage']     = $getid3_lib->$functionname(substr($BITMAPINFOHEADER, 20, 4)); // size of the bitmap data section of the image (the actual pixel data, excluding BITMAPINFOHEADER and RGBQUAD structures)
		$parsed['biXPelsPerMeter'] = $getid3_lib->$functionname(substr($BITMAPINFOHEADER, 24, 4)); // horizontal resolution, in pixels per metre, of the target device
		$parsed['biYPelsPerMeter'] = $getid3_lib->$functionname(substr($BITMAPINFOHEADER, 28, 4)); // vertical resolution, in pixels per metre, of the target device
		$parsed['biClrUsed']       = $getid3_lib->$functionname(substr($BITMAPINFOHEADER, 32, 4)); // actual number of color indices in the color table used by the bitmap. If this value is zero, the bitmap uses the maximum number of colors corresponding to the value of the biBitCount member for the compression mode specified by biCompression
		$parsed['biClrImportant']  = $getid3_lib->$functionname(substr($BITMAPINFOHEADER, 36, 4)); // number of color indices that are considered important for displaying the bitmap. If this value is zero, all colors are important
		return $parsed;
	}

	function RIFFwFormatTagLookup($wFormatTag) {

		$begin = __LINE__;

		/** This is not a comment!

			0x0000	Microsoft Unknown Wave Format
			0x0001	Pulse Code Modulation (PCM)
			0x0002	Microsoft ADPCM
			0x0003	IEEE Float
			0x0004	Compaq Computer VSELP
			0x0005	IBM CVSD
			0x0006	Microsoft A-Law
			0x0007	Microsoft mu-Law
			0x0008	Microsoft DTS
			0x0010	OKI ADPCM
			0x0011	Intel DVI/IMA ADPCM
			0x0012	Videologic MediaSpace ADPCM
			0x0013	Sierra Semiconductor ADPCM
			0x0014	Antex Electronics G.723 ADPCM
			0x0015	DSP Solutions DigiSTD
			0x0016	DSP Solutions DigiFIX
			0x0017	Dialogic OKI ADPCM
			0x0018	MediaVision ADPCM
			0x0019	Hewlett-Packard CU
			0x0020	Yamaha ADPCM
			0x0021	Speech Compression Sonarc
			0x0022	DSP Group TrueSpeech
			0x0023	Echo Speech EchoSC1
			0x0024	Audiofile AF36
			0x0025	Audio Processing Technology APTX
			0x0026	AudioFile AF10
			0x0027	Prosody 1612
			0x0028	LRC
			0x0030	Dolby AC2
			0x0031	Microsoft GSM 6.10
			0x0032	MSNAudio
			0x0033	Antex Electronics ADPCME
			0x0034	Control Resources VQLPC
			0x0035	DSP Solutions DigiREAL
			0x0036	DSP Solutions DigiADPCM
			0x0037	Control Resources CR10
			0x0038	Natural MicroSystems VBXADPCM
			0x0039	Crystal Semiconductor IMA ADPCM
			0x003A	EchoSC3
			0x003B	Rockwell ADPCM
			0x003C	Rockwell Digit LK
			0x003D	Xebec
			0x0040	Antex Electronics G.721 ADPCM
			0x0041	G.728 CELP
			0x0042	MSG723
			0x0050	MPEG Layer-2 or Layer-1
			0x0052	RT24
			0x0053	PAC
			0x0055	MPEG Layer-3
			0x0059	Lucent G.723
			0x0060	Cirrus
			0x0061	ESPCM
			0x0062	Voxware
			0x0063	Canopus Atrac
			0x0064	G.726 ADPCM
			0x0065	G.722 ADPCM
			0x0066	DSAT
			0x0067	DSAT Display
			0x0069	Voxware Byte Aligned
			0x0070	Voxware AC8
			0x0071	Voxware AC10
			0x0072	Voxware AC16
			0x0073	Voxware AC20
			0x0074	Voxware MetaVoice
			0x0075	Voxware MetaSound
			0x0076	Voxware RT29HW
			0x0077	Voxware VR12
			0x0078	Voxware VR18
			0x0079	Voxware TQ40
			0x0080	Softsound
			0x0081	Voxware TQ60
			0x0082	MSRT24
			0x0083	G.729A
			0x0084	MVI MV12
			0x0085	DF G.726
			0x0086	DF GSM610
			0x0088	ISIAudio
			0x0089	Onlive
			0x0091	SBC24
			0x0092	Dolby AC3 SPDIF
			0x0093	MediaSonic G.723
			0x0094	Aculab PLC    Prosody 8kbps
			0x0097	ZyXEL ADPCM
			0x0098	Philips LPCBB
			0x0099	Packed
			0x00FF	AAC
			0x0100	Rhetorex ADPCM
			0x0101	IBM mu-law
			0x0102	IBM A-law
			0x0103	IBM AVC Adaptive Differential Pulse Code Modulation (ADPCM)
			0x0111	Vivo G.723
			0x0112	Vivo Siren
			0x0123	Digital G.723
			0x0125	Sanyo LD ADPCM
			0x0130	Sipro Lab Telecom ACELP NET
			0x0131	Sipro Lab Telecom ACELP 4800
			0x0132	Sipro Lab Telecom ACELP 8V3
			0x0133	Sipro Lab Telecom G.729
			0x0134	Sipro Lab Telecom G.729A
			0x0135	Sipro Lab Telecom Kelvin
			0x0140	Windows Media Video V8
			0x0150	Qualcomm PureVoice
			0x0151	Qualcomm HalfRate
			0x0155	Ring Zero Systems TUB GSM
			0x0160	Microsoft Audio 1
			0x0161	Windows Media Audio V7 / V8 / V9
			0x0162	Windows Media Audio Professional V9
			0x0163	Windows Media Audio Lossless V9
			0x0200	Creative Labs ADPCM
			0x0202	Creative Labs Fastspeech8
			0x0203	Creative Labs Fastspeech10
			0x0210	UHER Informatic GmbH ADPCM
			0x0220	Quarterdeck
			0x0230	I-link Worldwide VC
			0x0240	Aureal RAW Sport
			0x0250	Interactive Products HSX
			0x0251	Interactive Products RPELP
			0x0260	Consistent Software CS2
			0x0270	Sony SCX
			0x0300	Fujitsu FM Towns Snd
			0x0400	BTV Digital
			0x0401	Intel Music Coder
			0x0450	QDesign Music
			0x0680	VME VMPCM
			0x0681	AT&T Labs TPC
			0x08AE	ClearJump LiteWave
			0x1000	Olivetti GSM
			0x1001	Olivetti ADPCM
			0x1002	Olivetti CELP
			0x1003	Olivetti SBC
			0x1004	Olivetti OPR
			0x1100	Lernout & Hauspie Codec (0x1100)
			0x1101	Lernout & Hauspie CELP Codec (0x1101)
			0x1102	Lernout & Hauspie SBC Codec (0x1102)
			0x1103	Lernout & Hauspie SBC Codec (0x1103)
			0x1104	Lernout & Hauspie SBC Codec (0x1104)
			0x1400	Norris
			0x1401	AT&T ISIAudio
			0x1500	Soundspace Music Compression
			0x181C	VoxWare RT24 Speech
			0x1FC4	NCT Soft ALF2CD (www.nctsoft.com)
			0x2000	Dolby AC3
			0x2001	Dolby DTS
			0x2002	WAVE_FORMAT_14_4
			0x2003	WAVE_FORMAT_28_8
			0x2004	WAVE_FORMAT_COOK
			0x2005	WAVE_FORMAT_DNET
			0x674F	Ogg Vorbis 1
			0x6750	Ogg Vorbis 2
			0x6751	Ogg Vorbis 3
			0x676F	Ogg Vorbis 1+
			0x6770	Ogg Vorbis 2+
			0x6771	Ogg Vorbis 3+
			0x7A21	GSM-AMR (CBR, no SID)
			0x7A22	GSM-AMR (VBR, including SID)
			0xFFFE	WAVE_FORMAT_EXTENSIBLE
			0xFFFF	WAVE_FORMAT_DEVELOPMENT

		*/

		return getid3_lib::EmbeddedLookup('0x'.str_pad(strtoupper(dechex($wFormatTag)), 4, '0', STR_PAD_LEFT), $begin, __LINE__, __FILE__, 'riff-wFormatTag');

	}


	function RIFFfourccLookup($fourcc) {

		$begin = __LINE__;

		/** This is not a comment!

			swot	http://developer.apple.com/qa/snd/snd07.html
			____	No Codec (____)
			_BIT	BI_BITFIELDS (Raw RGB)
			_JPG	JPEG compressed
			_PNG	PNG compressed W3C/ISO/IEC (RFC-2083)
			_RAW	Full Frames (Uncompressed)
			_RGB	Raw RGB Bitmap
			_RL4	RLE 4bpp RGB
			_RL8	RLE 8bpp RGB
			3IV1	3ivx MPEG-4 v1
			3IV2	3ivx MPEG-4 v2
			3IVX	3ivx MPEG-4
			AASC	Autodesk Animator
			ABYR	Kensington ?ABYR?
			AEMI	Array Microsystems VideoONE MPEG1-I Capture
			AFLC	Autodesk Animator FLC
			AFLI	Autodesk Animator FLI
			AMPG	Array Microsystems VideoONE MPEG
			ANIM	Intel RDX (ANIM)
			AP41	AngelPotion Definitive
			ASV1	Asus Video v1
			ASV2	Asus Video v2
			ASVX	Asus Video 2.0 (audio)
			AUR2	AuraVision Aura 2 Codec - YUV 4:2:2
			AURA	AuraVision Aura 1 Codec - YUV 4:1:1
			AVDJ	Independent JPEG Group\'s codec (AVDJ)
			AVRN	Independent JPEG Group\'s codec (AVRN)
			AYUV	4:4:4 YUV (AYUV)
			AZPR	Quicktime Apple Video (AZPR)
			BGR 	Raw RGB32
			BLZ0	Blizzard DivX MPEG-4
			BTVC	Conexant Composite Video
			BINK	RAD Game Tools Bink Video
			BT20	Conexant Prosumer Video
			BTCV	Conexant Composite Video Codec
			BW10	Data Translation Broadway MPEG Capture
			CC12	Intel YUV12
			CDVC	Canopus DV
			CFCC	Digital Processing Systems DPS Perception
			CGDI	Microsoft Office 97 Camcorder Video
			CHAM	Winnov Caviara Champagne
			CJPG	Creative WebCam JPEG
			CLJR	Cirrus Logic YUV 4:1:1
			CMYK	Common Data Format in Printing (Colorgraph)
			CPLA	Weitek 4:2:0 YUV Planar
			CRAM	Microsoft Video 1 (CRAM)
			cvid	Radius Cinepak
			CVID	Radius Cinepak
			CWLT	Microsoft Color WLT DIB
			CYUV	Creative Labs YUV
			CYUY	ATI YUV
			D261	H.261
			D263	H.263
			DIB 	Device Independent Bitmap
			DIV1	FFmpeg OpenDivX
			DIV2	Microsoft MPEG-4 v1/v2
			DIV3	DivX ;-) MPEG-4 v3.x Low-Motion
			DIV4	DivX ;-) MPEG-4 v3.x Fast-Motion
			DIV5	DivX MPEG-4 v5.x
			DIV6	DivX ;-) (MS MPEG-4 v3.x)
			DIVX	DivX MPEG-4 v4 (OpenDivX / Project Mayo)
			divx	DivX MPEG-4
			DMB1	Matrox Rainbow Runner hardware MJPEG
			DMB2	Paradigm MJPEG
			DSVD	?DSVD?
			DUCK	Duck TrueMotion 1.0
			DPS0	DPS/Leitch Reality Motion JPEG
			DPSC	DPS/Leitch PAR Motion JPEG
			DV25	Matrox DVCPRO codec
			DV50	Matrox DVCPRO50 codec
			DVC 	IEC 61834 and SMPTE 314M (DVC/DV Video)
			DVCP	IEC 61834 and SMPTE 314M (DVC/DV Video)
			DVHD	IEC Standard DV 1125 lines @ 30fps / 1250 lines @ 25fps
			DVMA	Darim Vision DVMPEG (dummy for MPEG compressor) (www.darvision.com)
			DVSL	IEC Standard DV compressed in SD (SDL)
			DVAN	?DVAN?
			DVE2	InSoft DVE-2 Videoconferencing
			dvsd	IEC 61834 and SMPTE 314M DVC/DV Video
			DVSD	IEC 61834 and SMPTE 314M DVC/DV Video
			DVX1	Lucent DVX1000SP Video Decoder
			DVX2	Lucent DVX2000S Video Decoder
			DVX3	Lucent DVX3000S Video Decoder
			DX50	DivX v5
			DXT1	Microsoft DirectX Compressed Texture (DXT1)
			DXT2	Microsoft DirectX Compressed Texture (DXT2)
			DXT3	Microsoft DirectX Compressed Texture (DXT3)
			DXT4	Microsoft DirectX Compressed Texture (DXT4)
			DXT5	Microsoft DirectX Compressed Texture (DXT5)
			DXTC	Microsoft DirectX Compressed Texture (DXTC)
			DXTn	Microsoft DirectX Compressed Texture (DXTn)
			EM2V	Etymonix MPEG-2 I-frame (www.etymonix.com)
			EKQ0	Elsa ?EKQ0?
			ELK0	Elsa ?ELK0?
			ESCP	Eidos Escape
			ETV1	eTreppid Video ETV1
			ETV2	eTreppid Video ETV2
			ETVC	eTreppid Video ETVC
			FLIC	Autodesk FLI/FLC Animation
			FRWT	Darim Vision Forward Motion JPEG (www.darvision.com)
			FRWU	Darim Vision Forward Uncompressed (www.darvision.com)
			FLJP	D-Vision Field Encoded Motion JPEG
			FRWA	SoftLab-Nsk Forward Motion JPEG w/ alpha channel
			FRWD	SoftLab-Nsk Forward Motion JPEG
			FVF1	Iterated Systems Fractal Video Frame
			GLZW	Motion LZW (gabest@freemail.hu)
			GPEG	Motion JPEG (gabest@freemail.hu)
			GWLT	Microsoft Greyscale WLT DIB
			H260	Intel ITU H.260 Videoconferencing
			H261	Intel ITU H.261 Videoconferencing
			H262	Intel ITU H.262 Videoconferencing
			H263	Intel ITU H.263 Videoconferencing
			H264	Intel ITU H.264 Videoconferencing
			H265	Intel ITU H.265 Videoconferencing
			H266	Intel ITU H.266 Videoconferencing
			H267	Intel ITU H.267 Videoconferencing
			H268	Intel ITU H.268 Videoconferencing
			H269	Intel ITU H.269 Videoconferencing
			HFYU	Huffman Lossless Codec
			HMCR	Rendition Motion Compensation Format (HMCR)
			HMRR	Rendition Motion Compensation Format (HMRR)
			I263	FFmpeg I263 decoder
			IF09	Indeo YVU9 ("YVU9 with additional delta-frame info after the U plane")
			IUYV	Interlaced version of UYVY (www.leadtools.com)
			IY41	Interlaced version of Y41P (www.leadtools.com)
			IYU1	12 bit format used in mode 2 of the IEEE 1394 Digital Camera 1.04 spec    IEEE standard
			IYU2	24 bit format used in mode 2 of the IEEE 1394 Digital Camera 1.04 spec    IEEE standard
			IYUV	Planar YUV format (8-bpp Y plane, followed by 8-bpp 2×2 U and V planes)
			i263	Intel ITU H.263 Videoconferencing (i263)
			I420	Intel Indeo 4
			IAN 	Intel Indeo 4 (RDX)
			ICLB	InSoft CellB Videoconferencing
			IGOR	Power DVD
			IJPG	Intergraph JPEG
			ILVC	Intel Layered Video
			ILVR	ITU-T H.263+
			IPDV	I-O Data Device Giga AVI DV Codec
			IR21	Intel Indeo 2.1
			IRAW	Intel YUV Uncompressed
			IV30	Intel Indeo 3.0
			IV31	Intel Indeo 3.1
			IV32	Ligos Indeo 3.2
			IV33	Ligos Indeo 3.3
			IV34	Ligos Indeo 3.4
			IV35	Ligos Indeo 3.5
			IV36	Ligos Indeo 3.6
			IV37	Ligos Indeo 3.7
			IV38	Ligos Indeo 3.8
			IV39	Ligos Indeo 3.9
			IV40	Ligos Indeo Interactive 4.0
			IV41	Ligos Indeo Interactive 4.1
			IV42	Ligos Indeo Interactive 4.2
			IV43	Ligos Indeo Interactive 4.3
			IV44	Ligos Indeo Interactive 4.4
			IV45	Ligos Indeo Interactive 4.5
			IV46	Ligos Indeo Interactive 4.6
			IV47	Ligos Indeo Interactive 4.7
			IV48	Ligos Indeo Interactive 4.8
			IV49	Ligos Indeo Interactive 4.9
			IV50	Ligos Indeo Interactive 5.0
			JBYR	Kensington ?JBYR?
			JPEG	Still Image JPEG DIB
			JPGL	Pegasus Lossless Motion JPEG
			KMVC	Team17 Software Karl Morton\'s Video Codec
			LSVM	Vianet Lighting Strike Vmail (Streaming) (www.vianet.com)
			LEAD	LEAD Video Codec
			Ljpg	LEAD MJPEG Codec
			MDVD	Alex MicroDVD Video (hacked MS MPEG-4) (www.tiasoft.de)
			MJPA	Morgan Motion JPEG (MJPA) (www.morgan-multimedia.com)
			MJPB	Morgan Motion JPEG (MJPB) (www.morgan-multimedia.com)
			MMES	Matrox MPEG-2 I-frame
			MP2v	Microsoft S-Mpeg 4 version 1 (MP2v)
			MP42	Microsoft S-Mpeg 4 version 2 (MP42)
			MP43	Microsoft S-Mpeg 4 version 3 (MP43)
			MP4S	Microsoft S-Mpeg 4 version 3 (MP4S)
			MP4V	FFmpeg MPEG-4
			MPG1	FFmpeg MPEG 1/2
			MPG2	FFmpeg MPEG 1/2
			MPG3	FFmpeg DivX ;-) (MS MPEG-4 v3)
			MPG4	Microsoft MPEG-4
			MPGI	Sigma Designs MPEG
			MPNG	PNG images decoder
			MSS1	Microsoft Windows Screen Video
			MSZH	LCL (Lossless Codec Library) (www.geocities.co.jp/Playtown-Denei/2837/LRC.htm)
			M261	Microsoft H.261
			M263	Microsoft H.263
			M4S2	Microsoft Fully Compliant MPEG-4 v2 simple profile (M4S2)
			m4s2	Microsoft Fully Compliant MPEG-4 v2 simple profile (m4s2)
			MC12	ATI Motion Compensation Format (MC12)
			MCAM	ATI Motion Compensation Format (MCAM)
			MJ2C	Morgan Multimedia Motion JPEG2000
			mJPG	IBM Motion JPEG w/ Huffman Tables
			MJPG	Microsoft Motion JPEG DIB
			MP42	Microsoft MPEG-4 (low-motion)
			MP43	Microsoft MPEG-4 (fast-motion)
			MP4S	Microsoft MPEG-4 (MP4S)
			mp4s	Microsoft MPEG-4 (mp4s)
			MPEG	Chromatic Research MPEG-1 Video I-Frame
			MPG4	Microsoft MPEG-4 Video High Speed Compressor
			MPGI	Sigma Designs MPEG
			MRCA	FAST Multimedia Martin Regen Codec
			MRLE	Microsoft Run Length Encoding
			MSVC	Microsoft Video 1
			MTX1	Matrox ?MTX1?
			MTX2	Matrox ?MTX2?
			MTX3	Matrox ?MTX3?
			MTX4	Matrox ?MTX4?
			MTX5	Matrox ?MTX5?
			MTX6	Matrox ?MTX6?
			MTX7	Matrox ?MTX7?
			MTX8	Matrox ?MTX8?
			MTX9	Matrox ?MTX9?
			MV12	Motion Pixels Codec (old)
			MWV1	Aware Motion Wavelets
			nAVI	SMR Codec (hack of Microsoft MPEG-4) (IRC #shadowrealm)
			NT00	NewTek LightWave HDTV YUV w/ Alpha (www.newtek.com)
			NUV1	NuppelVideo
			NTN1	Nogatech Video Compression 1
			NVS0	nVidia GeForce Texture (NVS0)
			NVS1	nVidia GeForce Texture (NVS1)
			NVS2	nVidia GeForce Texture (NVS2)
			NVS3	nVidia GeForce Texture (NVS3)
			NVS4	nVidia GeForce Texture (NVS4)
			NVS5	nVidia GeForce Texture (NVS5)
			NVT0	nVidia GeForce Texture (NVT0)
			NVT1	nVidia GeForce Texture (NVT1)
			NVT2	nVidia GeForce Texture (NVT2)
			NVT3	nVidia GeForce Texture (NVT3)
			NVT4	nVidia GeForce Texture (NVT4)
			NVT5	nVidia GeForce Texture (NVT5)
			PIXL	MiroXL, Pinnacle PCTV
			PDVC	I-O Data Device Digital Video Capture DV codec
			PGVV	Radius Video Vision
			PHMO	IBM Photomotion
			PIM1	MPEG Realtime (Pinnacle Cards)
			PIM2	Pegasus Imaging ?PIM2?
			PIMJ	Pegasus Imaging Lossless JPEG
			PVEZ	Horizons Technology PowerEZ
			PVMM	PacketVideo Corporation MPEG-4
			PVW2	Pegasus Imaging Wavelet Compression
			Q1.0	Q-Team\'s QPEG 1.0 (www.q-team.de)
			Q1.1	Q-Team\'s QPEG 1.1 (www.q-team.de)
			QPEG	Q-Team QPEG 1.0
			qpeq	Q-Team QPEG 1.1
			RGB 	Raw BGR32
			RGBA	Raw RGB w/ Alpha
			RMP4	REALmagic MPEG-4 (unauthorized XVID copy) (www.sigmadesigns.com)
			ROQV	Id RoQ File Video Decoder
			RPZA	Quicktime Apple Video (RPZA)
			RUD0	Rududu video codec (http://rududu.ifrance.com/rududu/)
			RV10	RealVideo 1.0 (aka RealVideo 5.0)
			RV13	RealVideo 1.0 (RV13)
			RV20	RealVideo G2
			RV30	RealVideo 8
			RV40	RealVideo 9
			RGBT	Raw RGB w/ Transparency
			RLE 	Microsoft Run Length Encoder
			RLE4	Run Length Encoded (4bpp, 16-color)
			RLE8	Run Length Encoded (8bpp, 256-color)
			RT21	Intel Indeo RealTime Video 2.1
			rv20	RealVideo G2
			rv30	RealVideo 8
			RVX 	Intel RDX (RVX )
			SMC 	Apple Graphics (SMC )
			SP54	Logitech Sunplus Sp54 Codec for Mustek GSmart Mini 2
			SPIG	Radius Spigot
			SVQ3	Sorenson Video 3 (Apple Quicktime 5)
			s422	Tekram VideoCap C210 YUV 4:2:2
			SDCC	Sun Communication Digital Camera Codec
			SFMC	CrystalNet Surface Fitting Method
			SMSC	Radius SMSC
			SMSD	Radius SMSD
			smsv	WorldConnect Wavelet Video
			SPIG	Radius Spigot
			SPLC	Splash Studios ACM Audio Codec (www.splashstudios.net)
			SQZ2	Microsoft VXTreme Video Codec V2
			STVA	ST Microelectronics CMOS Imager Data (Bayer)
			STVB	ST Microelectronics CMOS Imager Data (Nudged Bayer)
			STVC	ST Microelectronics CMOS Imager Data (Bunched)
			STVX	ST Microelectronics CMOS Imager Data (Extended CODEC Data Format)
			STVY	ST Microelectronics CMOS Imager Data (Extended CODEC Data Format with Correction Data)
			SV10	Sorenson Video R1
			SVQ1	Sorenson Video
			T420	Toshiba YUV 4:2:0
			TM2A	Duck TrueMotion Archiver 2.0 (www.duck.com)
			TVJP	Pinnacle/Truevision Targa 2000 board (TVJP)
			TVMJ	Pinnacle/Truevision Targa 2000 board (TVMJ)
			TY0N	Tecomac Low-Bit Rate Codec (www.tecomac.com)
			TY2C	Trident Decompression Driver
			TLMS	TeraLogic Motion Intraframe Codec (TLMS)
			TLST	TeraLogic Motion Intraframe Codec (TLST)
			TM20	Duck TrueMotion 2.0
			TM2X	Duck TrueMotion 2X
			TMIC	TeraLogic Motion Intraframe Codec (TMIC)
			TMOT	Horizons Technology TrueMotion S
			tmot	Horizons TrueMotion Video Compression
			TR20	Duck TrueMotion RealTime 2.0
			TSCC	TechSmith Screen Capture Codec
			TV10	Tecomac Low-Bit Rate Codec
			TY2N	Trident ?TY2N?
			U263	UB Video H.263/H.263+/H.263++ Decoder
			UMP4	UB Video MPEG 4 (www.ubvideo.com)
			UYNV	Nvidia UYVY packed 4:2:2
			UYVP	Evans & Sutherland YCbCr 4:2:2 extended precision
			UCOD	eMajix.com ClearVideo
			ULTI	IBM Ultimotion
			UYVY	UYVY packed 4:2:2
			V261	Lucent VX2000S
			VIFP	VFAPI Reader Codec (www.yks.ne.jp/~hori/)
			VIV1	FFmpeg H263+ decoder
			VIV2	Vivo H.263
			VQC2	Vector-quantised codec 2 (research) http://eprints.ecs.soton.ac.uk/archive/00001310/01/VTC97-js.pdf)
			VTLP	Alaris VideoGramPiX
			VYU9	ATI YUV (VYU9)
			VYUY	ATI YUV (VYUY)
			V261	Lucent VX2000S
			V422	Vitec Multimedia 24-bit YUV 4:2:2 Format
			V655	Vitec Multimedia 16-bit YUV 4:2:2 Format
			VCR1	ATI Video Codec 1
			VCR2	ATI Video Codec 2
			VCR3	ATI VCR 3.0
			VCR4	ATI VCR 4.0
			VCR5	ATI VCR 5.0
			VCR6	ATI VCR 6.0
			VCR7	ATI VCR 7.0
			VCR8	ATI VCR 8.0
			VCR9	ATI VCR 9.0
			VDCT	Vitec Multimedia Video Maker Pro DIB
			VDOM	VDOnet VDOWave
			VDOW	VDOnet VDOLive (H.263)
			VDTZ	Darim Vison VideoTizer YUV
			VGPX	Alaris VideoGramPiX
			VIDS	Vitec Multimedia YUV 4:2:2 CCIR 601 for V422
			VIVO	Vivo H.263 v2.00
			vivo	Vivo H.263
			VIXL	Miro/Pinnacle Video XL
			VLV1	VideoLogic/PURE Digital Videologic Capture
			VP30	On2 VP3.0
			VP31	On2 VP3.1
			VX1K	Lucent VX1000S Video Codec
			VX2K	Lucent VX2000S Video Codec
			VXSP	Lucent VX1000SP Video Codec
			WBVC	Winbond W9960
			WHAM	Microsoft Video 1 (WHAM)
			WINX	Winnov Software Compression
			WJPG	AverMedia Winbond JPEG
			WMV1	Windows Media Video V7
			WMV2	Windows Media Video V8
			WMV3	Windows Media Video V9
			WNV1	Winnov Hardware Compression
			XYZP	Extended PAL format XYZ palette (www.riff.org)
			x263	Xirlink H.263
			XLV0	NetXL Video Decoder
			XMPG	Xing MPEG (I-Frame only)
			XVID	XviD MPEG-4 (www.xvid.org)
			XXAN	?XXAN?
			YU92	Intel YUV (YU92)
			YUNV	Nvidia Uncompressed YUV 4:2:2
			YUVP	Extended PAL format YUV palette (www.riff.org)
			Y211	YUV 2:1:1 Packed
			Y411	YUV 4:1:1 Packed
			Y41B	Weitek YUV 4:1:1 Planar
			Y41P	Brooktree PC1 YUV 4:1:1 Packed
			Y41T	Brooktree PC1 YUV 4:1:1 with transparency
			Y42B	Weitek YUV 4:2:2 Planar
			Y42T	Brooktree UYUV 4:2:2 with transparency
			Y422	ADS Technologies Copy of UYVY used in Pyro WebCam firewire camera
			Y800	Simple, single Y plane for monochrome images
			Y8  	Grayscale video
			YC12	Intel YUV 12 codec
			YUV8	Winnov Caviar YUV8
			YUV9	Intel YUV9
			YUY2	Uncompressed YUV 4:2:2
			YUYV	Canopus YUV
			YV12	YVU12 Planar
			YVU9	Intel YVU9 Planar (8-bpp Y plane, followed by 8-bpp 4x4 U and V planes)
			YVYU	YVYU 4:2:2 Packed
			ZLIB	Lossless Codec Library zlib compression (www.geocities.co.jp/Playtown-Denei/2837/LRC.htm)
			ZPEG	Metheus Video Zipper

		*/

		return getid3_lib::EmbeddedLookup($fourcc, $begin, __LINE__, __FILE__, 'riff-fourcc');
	}


	function EitherEndian2Int(&$ThisFileInfo, $byteword, $signed=false) {
		if ($ThisFileInfo['fileformat'] == 'riff') {
			return getid3_lib::LittleEndian2Int($byteword, $signed);
		}
		return getid3_lib::BigEndian2Int($byteword, false, $signed);
	}

}

?>