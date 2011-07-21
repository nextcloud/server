<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.audio-video.real.php                                 //
// module for analyzing Real Audio/Video files                 //
// dependencies: module.audio-video.riff.php                   //
//                                                            ///
/////////////////////////////////////////////////////////////////

getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.audio-video.riff.php', __FILE__, true);

class getid3_real
{

	function getid3_real(&$fd, &$ThisFileInfo) {
		$ThisFileInfo['fileformat']       = 'real';
		$ThisFileInfo['bitrate']          = 0;
		$ThisFileInfo['playtime_seconds'] = 0;

		fseek($fd, $ThisFileInfo['avdataoffset'], SEEK_SET);
		$ChunkCounter = 0;
		while (ftell($fd) < $ThisFileInfo['avdataend']) {
			$ChunkData  = fread($fd, 8);
			$ChunkName  =                           substr($ChunkData, 0, 4);
			$ChunkSize  = getid3_lib::BigEndian2Int(substr($ChunkData, 4, 4));

			if ($ChunkName == '.ra'."\xFD") {
				$ChunkData .= fread($fd, $ChunkSize - 8);
				if ($this->ParseOldRAheader(substr($ChunkData, 0, 128), $ThisFileInfo['real']['old_ra_header'])) {
					$ThisFileInfo['audio']['dataformat']      = 'real';
					$ThisFileInfo['audio']['lossless']        = false;
					$ThisFileInfo['audio']['sample_rate']     = $ThisFileInfo['real']['old_ra_header']['sample_rate'];
					$ThisFileInfo['audio']['bits_per_sample'] = $ThisFileInfo['real']['old_ra_header']['bits_per_sample'];
					$ThisFileInfo['audio']['channels']        = $ThisFileInfo['real']['old_ra_header']['channels'];

					$ThisFileInfo['playtime_seconds']         = 60 * ($ThisFileInfo['real']['old_ra_header']['audio_bytes'] / $ThisFileInfo['real']['old_ra_header']['bytes_per_minute']);
					$ThisFileInfo['audio']['bitrate']         =  8 * ($ThisFileInfo['real']['old_ra_header']['audio_bytes'] / $ThisFileInfo['playtime_seconds']);
					$ThisFileInfo['audio']['codec']           = $this->RealAudioCodecFourCClookup($ThisFileInfo['real']['old_ra_header']['fourcc'], $ThisFileInfo['audio']['bitrate']);

					foreach ($ThisFileInfo['real']['old_ra_header']['comments'] as $key => $valuearray) {
						if (strlen(trim($valuearray[0])) > 0) {
							$ThisFileInfo['real']['comments'][$key][] = trim($valuearray[0]);
						}
					}
					return true;
				}
				$ThisFileInfo['error'][] = 'There was a problem parsing this RealAudio file. Please submit it for analysis to http://www.getid3.org/upload/ or info@getid3.org';
				unset($ThisFileInfo['bitrate']);
				unset($ThisFileInfo['playtime_seconds']);
				return false;
			}

			// shortcut
			$ThisFileInfo['real']['chunks'][$ChunkCounter] = array();
			$thisfile_real_chunks_currentchunk = &$ThisFileInfo['real']['chunks'][$ChunkCounter];

			$thisfile_real_chunks_currentchunk['name']   = $ChunkName;
			$thisfile_real_chunks_currentchunk['offset'] = ftell($fd) - 8;
			$thisfile_real_chunks_currentchunk['length'] = $ChunkSize;
			if (($thisfile_real_chunks_currentchunk['offset'] + $thisfile_real_chunks_currentchunk['length']) > $ThisFileInfo['avdataend']) {
				$ThisFileInfo['warning'][] = 'Chunk "'.$thisfile_real_chunks_currentchunk['name'].'" at offset '.$thisfile_real_chunks_currentchunk['offset'].' claims to be '.$thisfile_real_chunks_currentchunk['length'].' bytes long, which is beyond end of file';
				return false;
			}

			if ($ChunkSize > (GETID3_FREAD_BUFFER_SIZE + 8)) {

				$ChunkData .= fread($fd, GETID3_FREAD_BUFFER_SIZE - 8);
				fseek($fd, $thisfile_real_chunks_currentchunk['offset'] + $ChunkSize, SEEK_SET);

			} elseif(($ChunkSize - 8) > 0) {
                
				$ChunkData .= fread($fd, $ChunkSize - 8);

			}
			$offset = 8;

			switch ($ChunkName) {

				case '.RMF': // RealMedia File Header
					$thisfile_real_chunks_currentchunk['object_version'] = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 2));
					$offset += 2;
					switch ($thisfile_real_chunks_currentchunk['object_version']) {

						case 0:
							$thisfile_real_chunks_currentchunk['file_version']  = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 4));
							$offset += 4;
							$thisfile_real_chunks_currentchunk['headers_count'] = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 4));
							$offset += 4;
							break;

						default:
							//$ThisFileInfo['warning'][] = 'Expected .RMF-object_version to be "0", actual value is "'.$thisfile_real_chunks_currentchunk['object_version'].'" (should not be a problem)';
							break;

					}
					break;


				case 'PROP': // Properties Header
					$thisfile_real_chunks_currentchunk['object_version']      = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 2));
					$offset += 2;
					if ($thisfile_real_chunks_currentchunk['object_version'] == 0) {
						$thisfile_real_chunks_currentchunk['max_bit_rate']    = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 4));
						$offset += 4;
						$thisfile_real_chunks_currentchunk['avg_bit_rate']    = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 4));
						$offset += 4;
						$thisfile_real_chunks_currentchunk['max_packet_size'] = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 4));
						$offset += 4;
						$thisfile_real_chunks_currentchunk['avg_packet_size'] = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 4));
						$offset += 4;
						$thisfile_real_chunks_currentchunk['num_packets']     = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 4));
						$offset += 4;
						$thisfile_real_chunks_currentchunk['duration']        = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 4));
						$offset += 4;
						$thisfile_real_chunks_currentchunk['preroll']         = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 4));
						$offset += 4;
						$thisfile_real_chunks_currentchunk['index_offset']    = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 4));
						$offset += 4;
						$thisfile_real_chunks_currentchunk['data_offset']     = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 4));
						$offset += 4;
						$thisfile_real_chunks_currentchunk['num_streams']     = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 2));
						$offset += 2;
						$thisfile_real_chunks_currentchunk['flags_raw']       = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 2));
						$offset += 2;
						$ThisFileInfo['playtime_seconds'] = $thisfile_real_chunks_currentchunk['duration'] / 1000;
						if ($thisfile_real_chunks_currentchunk['duration'] > 0) {
							$ThisFileInfo['bitrate'] += $thisfile_real_chunks_currentchunk['avg_bit_rate'];
						}
						$thisfile_real_chunks_currentchunk['flags']['save_enabled']   = (bool) ($thisfile_real_chunks_currentchunk['flags_raw'] & 0x0001);
						$thisfile_real_chunks_currentchunk['flags']['perfect_play']   = (bool) ($thisfile_real_chunks_currentchunk['flags_raw'] & 0x0002);
						$thisfile_real_chunks_currentchunk['flags']['live_broadcast'] = (bool) ($thisfile_real_chunks_currentchunk['flags_raw'] & 0x0004);
					}
					break;

				case 'MDPR': // Media Properties Header
					$thisfile_real_chunks_currentchunk['object_version']         = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 2));
					$offset += 2;
					if ($thisfile_real_chunks_currentchunk['object_version'] == 0) {
						$thisfile_real_chunks_currentchunk['stream_number']      = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 2));
						$offset += 2;
						$thisfile_real_chunks_currentchunk['max_bit_rate']       = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 4));
						$offset += 4;
						$thisfile_real_chunks_currentchunk['avg_bit_rate']       = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 4));
						$offset += 4;
						$thisfile_real_chunks_currentchunk['max_packet_size']    = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 4));
						$offset += 4;
						$thisfile_real_chunks_currentchunk['avg_packet_size']    = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 4));
						$offset += 4;
						$thisfile_real_chunks_currentchunk['start_time']         = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 4));
						$offset += 4;
						$thisfile_real_chunks_currentchunk['preroll']            = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 4));
						$offset += 4;
						$thisfile_real_chunks_currentchunk['duration']           = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 4));
						$offset += 4;
						$thisfile_real_chunks_currentchunk['stream_name_size']   = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 1));
						$offset += 1;
						$thisfile_real_chunks_currentchunk['stream_name']        = substr($ChunkData, $offset, $thisfile_real_chunks_currentchunk['stream_name_size']);
						$offset += $thisfile_real_chunks_currentchunk['stream_name_size'];
						$thisfile_real_chunks_currentchunk['mime_type_size']     = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 1));
						$offset += 1;
						$thisfile_real_chunks_currentchunk['mime_type']          = substr($ChunkData, $offset, $thisfile_real_chunks_currentchunk['mime_type_size']);
						$offset += $thisfile_real_chunks_currentchunk['mime_type_size'];
						$thisfile_real_chunks_currentchunk['type_specific_len']  = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 4));
						$offset += 4;
						$thisfile_real_chunks_currentchunk['type_specific_data'] = substr($ChunkData, $offset, $thisfile_real_chunks_currentchunk['type_specific_len']);
						$offset += $thisfile_real_chunks_currentchunk['type_specific_len'];

						// shortcut
						$thisfile_real_chunks_currentchunk_typespecificdata = &$thisfile_real_chunks_currentchunk['type_specific_data'];

						switch ($thisfile_real_chunks_currentchunk['mime_type']) {
							case 'video/x-pn-realvideo':
							case 'video/x-pn-multirate-realvideo':
								// http://www.freelists.org/archives/matroska-devel/07-2003/msg00010.html

								// shortcut
								$thisfile_real_chunks_currentchunk['video_info'] = array();
								$thisfile_real_chunks_currentchunk_videoinfo     = &$thisfile_real_chunks_currentchunk['video_info'];

								$thisfile_real_chunks_currentchunk_videoinfo['dwSize']            = getid3_lib::BigEndian2Int(substr($thisfile_real_chunks_currentchunk_typespecificdata,  0, 4));
								$thisfile_real_chunks_currentchunk_videoinfo['fourcc1']           =                           substr($thisfile_real_chunks_currentchunk_typespecificdata,  4, 4);
								$thisfile_real_chunks_currentchunk_videoinfo['fourcc2']           =                           substr($thisfile_real_chunks_currentchunk_typespecificdata,  8, 4);
								$thisfile_real_chunks_currentchunk_videoinfo['width']             = getid3_lib::BigEndian2Int(substr($thisfile_real_chunks_currentchunk_typespecificdata, 12, 2));
								$thisfile_real_chunks_currentchunk_videoinfo['height']            = getid3_lib::BigEndian2Int(substr($thisfile_real_chunks_currentchunk_typespecificdata, 14, 2));
								$thisfile_real_chunks_currentchunk_videoinfo['bits_per_sample']   = getid3_lib::BigEndian2Int(substr($thisfile_real_chunks_currentchunk_typespecificdata, 16, 2));
								//$thisfile_real_chunks_currentchunk_videoinfo['unknown1']          = getid3_lib::BigEndian2Int(substr($thisfile_real_chunks_currentchunk_typespecificdata, 18, 2));
								//$thisfile_real_chunks_currentchunk_videoinfo['unknown2']          = getid3_lib::BigEndian2Int(substr($thisfile_real_chunks_currentchunk_typespecificdata, 20, 2));
								$thisfile_real_chunks_currentchunk_videoinfo['frames_per_second'] = getid3_lib::BigEndian2Int(substr($thisfile_real_chunks_currentchunk_typespecificdata, 22, 2));
								//$thisfile_real_chunks_currentchunk_videoinfo['unknown3']          = getid3_lib::BigEndian2Int(substr($thisfile_real_chunks_currentchunk_typespecificdata, 24, 2));
								//$thisfile_real_chunks_currentchunk_videoinfo['unknown4']          = getid3_lib::BigEndian2Int(substr($thisfile_real_chunks_currentchunk_typespecificdata, 26, 2));
								//$thisfile_real_chunks_currentchunk_videoinfo['unknown5']          = getid3_lib::BigEndian2Int(substr($thisfile_real_chunks_currentchunk_typespecificdata, 28, 2));
								//$thisfile_real_chunks_currentchunk_videoinfo['unknown6']          = getid3_lib::BigEndian2Int(substr($thisfile_real_chunks_currentchunk_typespecificdata, 30, 2));
								//$thisfile_real_chunks_currentchunk_videoinfo['unknown7']          = getid3_lib::BigEndian2Int(substr($thisfile_real_chunks_currentchunk_typespecificdata, 32, 2));
								//$thisfile_real_chunks_currentchunk_videoinfo['unknown8']          = getid3_lib::BigEndian2Int(substr($thisfile_real_chunks_currentchunk_typespecificdata, 34, 2));
								//$thisfile_real_chunks_currentchunk_videoinfo['unknown9']          = getid3_lib::BigEndian2Int(substr($thisfile_real_chunks_currentchunk_typespecificdata, 36, 2));

								$thisfile_real_chunks_currentchunk_videoinfo['codec'] = getid3_riff::RIFFfourccLookup($thisfile_real_chunks_currentchunk_videoinfo['fourcc2']);

								$ThisFileInfo['video']['resolution_x']    =         $thisfile_real_chunks_currentchunk_videoinfo['width'];
								$ThisFileInfo['video']['resolution_y']    =         $thisfile_real_chunks_currentchunk_videoinfo['height'];
								$ThisFileInfo['video']['frame_rate']      = (float) $thisfile_real_chunks_currentchunk_videoinfo['frames_per_second'];
								$ThisFileInfo['video']['codec']           =         $thisfile_real_chunks_currentchunk_videoinfo['codec'];
								$ThisFileInfo['video']['bits_per_sample'] =         $thisfile_real_chunks_currentchunk_videoinfo['bits_per_sample'];
								break;

							case 'audio/x-pn-realaudio':
							case 'audio/x-pn-multirate-realaudio':
								$this->ParseOldRAheader($thisfile_real_chunks_currentchunk_typespecificdata, $thisfile_real_chunks_currentchunk['parsed_audio_data']);

								$ThisFileInfo['audio']['sample_rate']     = $thisfile_real_chunks_currentchunk['parsed_audio_data']['sample_rate'];
								$ThisFileInfo['audio']['bits_per_sample'] = $thisfile_real_chunks_currentchunk['parsed_audio_data']['bits_per_sample'];
								$ThisFileInfo['audio']['channels']        = $thisfile_real_chunks_currentchunk['parsed_audio_data']['channels'];
								if (!empty($ThisFileInfo['audio']['dataformat'])) {
									foreach ($ThisFileInfo['audio'] as $key => $value) {
										if ($key != 'streams') {
											$ThisFileInfo['audio']['streams'][$thisfile_real_chunks_currentchunk['stream_number']][$key] = $value;
										}
									}
								}
								break;

							case 'logical-fileinfo':
								// shortcut
								$thisfile_real_chunks_currentchunk['logical_fileinfo'] = array();
								$thisfile_real_chunks_currentchunk_logicalfileinfo     = &$thisfile_real_chunks_currentchunk['logical_fileinfo'];

								$thisfile_real_chunks_currentchunk_logicalfileinfo_offset = 0;
								$thisfile_real_chunks_currentchunk_logicalfileinfo['logical_fileinfo_length'] = getid3_lib::BigEndian2Int(substr($thisfile_real_chunks_currentchunk_typespecificdata, $thisfile_real_chunks_currentchunk_logicalfileinfo_offset, 4));
								$thisfile_real_chunks_currentchunk_logicalfileinfo_offset += 4;

								//$thisfile_real_chunks_currentchunk_logicalfileinfo['unknown1']                = getid3_lib::BigEndian2Int(substr($thisfile_real_chunks_currentchunk_typespecificdata, $thisfile_real_chunks_currentchunk_logicalfileinfo_offset, 4));
								$thisfile_real_chunks_currentchunk_logicalfileinfo_offset += 4;

								$thisfile_real_chunks_currentchunk_logicalfileinfo['num_tags']                = getid3_lib::BigEndian2Int(substr($thisfile_real_chunks_currentchunk_typespecificdata, $thisfile_real_chunks_currentchunk_logicalfileinfo_offset, 4));
								$thisfile_real_chunks_currentchunk_logicalfileinfo_offset += 4;

								//$thisfile_real_chunks_currentchunk_logicalfileinfo['unknown2']                = getid3_lib::BigEndian2Int(substr($thisfile_real_chunks_currentchunk_typespecificdata, $thisfile_real_chunks_currentchunk_logicalfileinfo_offset, 4));
								$thisfile_real_chunks_currentchunk_logicalfileinfo_offset += 4;

								//$thisfile_real_chunks_currentchunk_logicalfileinfo['d']                       = getid3_lib::BigEndian2Int(substr($thisfile_real_chunks_currentchunk_typespecificdata, $thisfile_real_chunks_currentchunk_logicalfileinfo_offset, 1));

								//$thisfile_real_chunks_currentchunk_logicalfileinfo['one_type'] = getid3_lib::BigEndian2Int(substr($thisfile_real_chunks_currentchunk_typespecificdata,     $thisfile_real_chunks_currentchunk_logicalfileinfo_offset, 4));
								//$thisfile_real_chunks_currentchunk_logicalfileinfo_thislength  = getid3_lib::BigEndian2Int(substr($thisfile_real_chunks_currentchunk_typespecificdata, 4 + $thisfile_real_chunks_currentchunk_logicalfileinfo_offset, 2));
								//$thisfile_real_chunks_currentchunk_logicalfileinfo['one']      =                           substr($thisfile_real_chunks_currentchunk_typespecificdata, 6 + $thisfile_real_chunks_currentchunk_logicalfileinfo_offset, $thisfile_real_chunks_currentchunk_logicalfileinfo_thislength);
								//$thisfile_real_chunks_currentchunk_logicalfileinfo_offset += (6 + $thisfile_real_chunks_currentchunk_logicalfileinfo_thislength);

								break;

						}


						if (empty($ThisFileInfo['playtime_seconds'])) {
							$ThisFileInfo['playtime_seconds'] = max($ThisFileInfo['playtime_seconds'], ($thisfile_real_chunks_currentchunk['duration'] + $thisfile_real_chunks_currentchunk['start_time']) / 1000);
						}
						if ($thisfile_real_chunks_currentchunk['duration'] > 0) {
							switch ($thisfile_real_chunks_currentchunk['mime_type']) {
								case 'audio/x-pn-realaudio':
								case 'audio/x-pn-multirate-realaudio':
									$ThisFileInfo['audio']['bitrate']    = (isset($ThisFileInfo['audio']['bitrate']) ? $ThisFileInfo['audio']['bitrate'] : 0) + $thisfile_real_chunks_currentchunk['avg_bit_rate'];
									$ThisFileInfo['audio']['codec']      = $this->RealAudioCodecFourCClookup($thisfile_real_chunks_currentchunk['parsed_audio_data']['fourcc'], $ThisFileInfo['audio']['bitrate']);
									$ThisFileInfo['audio']['dataformat'] = 'real';
									$ThisFileInfo['audio']['lossless']   = false;
									break;

								case 'video/x-pn-realvideo':
								case 'video/x-pn-multirate-realvideo':
									$ThisFileInfo['video']['bitrate']            = (isset($ThisFileInfo['video']['bitrate']) ? $ThisFileInfo['video']['bitrate'] : 0) + $thisfile_real_chunks_currentchunk['avg_bit_rate'];
									$ThisFileInfo['video']['bitrate_mode']       = 'cbr';
									$ThisFileInfo['video']['dataformat']         = 'real';
									$ThisFileInfo['video']['lossless']           = false;
									$ThisFileInfo['video']['pixel_aspect_ratio'] = (float) 1;
									break;

								case 'audio/x-ralf-mpeg4-generic':
									$ThisFileInfo['audio']['bitrate']    = (isset($ThisFileInfo['audio']['bitrate']) ? $ThisFileInfo['audio']['bitrate'] : 0) + $thisfile_real_chunks_currentchunk['avg_bit_rate'];
									$ThisFileInfo['audio']['codec']      = 'RealAudio Lossless';
									$ThisFileInfo['audio']['dataformat'] = 'real';
									$ThisFileInfo['audio']['lossless']   = true;
									break;
							}
							$ThisFileInfo['bitrate'] = (isset($ThisFileInfo['video']['bitrate']) ? $ThisFileInfo['video']['bitrate'] : 0) + (isset($ThisFileInfo['audio']['bitrate']) ? $ThisFileInfo['audio']['bitrate'] : 0);
						}
					}
					break;

				case 'CONT': // Content Description Header (text comments)
					$thisfile_real_chunks_currentchunk['object_version'] = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 2));
					$offset += 2;
					if ($thisfile_real_chunks_currentchunk['object_version'] == 0) {
						$thisfile_real_chunks_currentchunk['title_len'] = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 2));
						$offset += 2;
						$thisfile_real_chunks_currentchunk['title'] = (string) substr($ChunkData, $offset, $thisfile_real_chunks_currentchunk['title_len']);
						$offset += $thisfile_real_chunks_currentchunk['title_len'];

						$thisfile_real_chunks_currentchunk['artist_len'] = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 2));
						$offset += 2;
						$thisfile_real_chunks_currentchunk['artist'] = (string) substr($ChunkData, $offset, $thisfile_real_chunks_currentchunk['artist_len']);
						$offset += $thisfile_real_chunks_currentchunk['artist_len'];

						$thisfile_real_chunks_currentchunk['copyright_len'] = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 2));
						$offset += 2;
						$thisfile_real_chunks_currentchunk['copyright'] = (string) substr($ChunkData, $offset, $thisfile_real_chunks_currentchunk['copyright_len']);
						$offset += $thisfile_real_chunks_currentchunk['copyright_len'];

						$thisfile_real_chunks_currentchunk['comment_len'] = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 2));
						$offset += 2;
						$thisfile_real_chunks_currentchunk['comment'] = (string) substr($ChunkData, $offset, $thisfile_real_chunks_currentchunk['comment_len']);
						$offset += $thisfile_real_chunks_currentchunk['comment_len'];


						$commentkeystocopy = array('title'=>'title', 'artist'=>'artist', 'copyright'=>'copyright', 'comment'=>'comment');
						foreach ($commentkeystocopy as $key => $val) {
							if ($thisfile_real_chunks_currentchunk[$key]) {
								$ThisFileInfo['real']['comments'][$val][] = trim($thisfile_real_chunks_currentchunk[$key]);
							}
						}

					}
					break;


				case 'DATA': // Data Chunk Header
					// do nothing
					break;

				case 'INDX': // Index Section Header
					$thisfile_real_chunks_currentchunk['object_version']        = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 2));
					$offset += 2;
					if ($thisfile_real_chunks_currentchunk['object_version'] == 0) {
						$thisfile_real_chunks_currentchunk['num_indices']       = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 4));
						$offset += 4;
						$thisfile_real_chunks_currentchunk['stream_number']     = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 2));
						$offset += 2;
						$thisfile_real_chunks_currentchunk['next_index_header'] = getid3_lib::BigEndian2Int(substr($ChunkData, $offset, 4));
						$offset += 4;

						if ($thisfile_real_chunks_currentchunk['next_index_header'] == 0) {
							// last index chunk found, ignore rest of file
							break 2;
						} else {
							// non-last index chunk, seek to next index chunk (skipping actual index data)
							fseek($fd, $thisfile_real_chunks_currentchunk['next_index_header'], SEEK_SET);
						}
					}
					break;

				default:
					$ThisFileInfo['warning'][] = 'Unhandled RealMedia chunk "'.$ChunkName.'" at offset '.$thisfile_real_chunks_currentchunk['offset'];
					break;
			}
			$ChunkCounter++;
		}

		if (!empty($ThisFileInfo['audio']['streams'])) {
			$ThisFileInfo['audio']['bitrate'] = 0;
			foreach ($ThisFileInfo['audio']['streams'] as $key => $valuearray) {
				$ThisFileInfo['audio']['bitrate'] += $valuearray['bitrate'];
			}
		}

		return true;
	}


	function ParseOldRAheader($OldRAheaderData, &$ParsedArray) {
		// http://www.freelists.org/archives/matroska-devel/07-2003/msg00010.html

		$ParsedArray = array();
		$ParsedArray['magic'] = substr($OldRAheaderData, 0, 4);
		if ($ParsedArray['magic'] != '.ra'."\xFD") {
			return false;
		}
		$ParsedArray['version1']         = getid3_lib::BigEndian2Int(substr($OldRAheaderData,  4, 2));

		if ($ParsedArray['version1'] < 3) {

			return false;

		} elseif ($ParsedArray['version1'] == 3) {

			$ParsedArray['fourcc1']          = '.ra3';
			$ParsedArray['bits_per_sample']  = 16;   // hard-coded for old versions?
			$ParsedArray['sample_rate']      = 8000; // hard-coded for old versions?

			$ParsedArray['header_size']      = getid3_lib::BigEndian2Int(substr($OldRAheaderData,  6, 2));
			$ParsedArray['channels']         = getid3_lib::BigEndian2Int(substr($OldRAheaderData,  8, 2)); // always 1 (?)
			//$ParsedArray['unknown1']         = getid3_lib::BigEndian2Int(substr($OldRAheaderData, 10, 2));
			//$ParsedArray['unknown2']         = getid3_lib::BigEndian2Int(substr($OldRAheaderData, 12, 2));
			//$ParsedArray['unknown3']         = getid3_lib::BigEndian2Int(substr($OldRAheaderData, 14, 2));
			$ParsedArray['bytes_per_minute'] = getid3_lib::BigEndian2Int(substr($OldRAheaderData, 16, 2));
			$ParsedArray['audio_bytes']      = getid3_lib::BigEndian2Int(substr($OldRAheaderData, 18, 4));
			$ParsedArray['comments_raw']     =                           substr($OldRAheaderData, 22, $ParsedArray['header_size'] - 22 + 1); // not including null terminator

			$commentoffset = 0;
			$commentlength = getid3_lib::BigEndian2Int(substr($ParsedArray['comments_raw'], $commentoffset++, 1));
			$ParsedArray['comments']['title'][]     = substr($ParsedArray['comments_raw'], $commentoffset, $commentlength);
			$commentoffset += $commentlength;

			$commentlength = getid3_lib::BigEndian2Int(substr($ParsedArray['comments_raw'], $commentoffset++, 1));
			$ParsedArray['comments']['artist'][]    = substr($ParsedArray['comments_raw'], $commentoffset, $commentlength);
			$commentoffset += $commentlength;

			$commentlength = getid3_lib::BigEndian2Int(substr($ParsedArray['comments_raw'], $commentoffset++, 1));
			$ParsedArray['comments']['copyright'][] = substr($ParsedArray['comments_raw'], $commentoffset, $commentlength);
			$commentoffset += $commentlength;

			$commentoffset++; // final null terminator (?)
			$commentoffset++; // fourcc length (?) should be 4
			$ParsedArray['fourcc']           =                           substr($OldRAheaderData, 23 + $commentoffset, 4);

		} elseif ($ParsedArray['version1'] <= 5) {

			//$ParsedArray['unknown1']         = getid3_lib::BigEndian2Int(substr($OldRAheaderData,  6, 2));
			$ParsedArray['fourcc1']          =                           substr($OldRAheaderData,  8, 4);
			$ParsedArray['file_size']        = getid3_lib::BigEndian2Int(substr($OldRAheaderData, 12, 4));
			$ParsedArray['version2']         = getid3_lib::BigEndian2Int(substr($OldRAheaderData, 16, 2));
			$ParsedArray['header_size']      = getid3_lib::BigEndian2Int(substr($OldRAheaderData, 18, 4));
			$ParsedArray['codec_flavor_id']  = getid3_lib::BigEndian2Int(substr($OldRAheaderData, 22, 2));
			$ParsedArray['coded_frame_size'] = getid3_lib::BigEndian2Int(substr($OldRAheaderData, 24, 4));
			$ParsedArray['audio_bytes']      = getid3_lib::BigEndian2Int(substr($OldRAheaderData, 28, 4));
			$ParsedArray['bytes_per_minute'] = getid3_lib::BigEndian2Int(substr($OldRAheaderData, 32, 4));
			//$ParsedArray['unknown5']         = getid3_lib::BigEndian2Int(substr($OldRAheaderData, 36, 4));
			$ParsedArray['sub_packet_h']     = getid3_lib::BigEndian2Int(substr($OldRAheaderData, 40, 2));
			$ParsedArray['frame_size']       = getid3_lib::BigEndian2Int(substr($OldRAheaderData, 42, 2));
			$ParsedArray['sub_packet_size']  = getid3_lib::BigEndian2Int(substr($OldRAheaderData, 44, 2));
			//$ParsedArray['unknown6']         = getid3_lib::BigEndian2Int(substr($OldRAheaderData, 46, 2));

			switch ($ParsedArray['version1']) {

				case 4:
					$ParsedArray['sample_rate']      = getid3_lib::BigEndian2Int(substr($OldRAheaderData, 48, 2));
					//$ParsedArray['unknown8']         = getid3_lib::BigEndian2Int(substr($OldRAheaderData, 50, 2));
					$ParsedArray['bits_per_sample']  = getid3_lib::BigEndian2Int(substr($OldRAheaderData, 52, 2));
					$ParsedArray['channels']         = getid3_lib::BigEndian2Int(substr($OldRAheaderData, 54, 2));
					$ParsedArray['length_fourcc2']   = getid3_lib::BigEndian2Int(substr($OldRAheaderData, 56, 1));
					$ParsedArray['fourcc2']          =                           substr($OldRAheaderData, 57, 4);
					$ParsedArray['length_fourcc3']   = getid3_lib::BigEndian2Int(substr($OldRAheaderData, 61, 1));
					$ParsedArray['fourcc3']          =                           substr($OldRAheaderData, 62, 4);
					//$ParsedArray['unknown9']         = getid3_lib::BigEndian2Int(substr($OldRAheaderData, 66, 1));
					//$ParsedArray['unknown10']        = getid3_lib::BigEndian2Int(substr($OldRAheaderData, 67, 2));
					$ParsedArray['comments_raw']     =                           substr($OldRAheaderData, 69, $ParsedArray['header_size'] - 69 + 16);

					$commentoffset = 0;
					$commentlength = getid3_lib::BigEndian2Int(substr($ParsedArray['comments_raw'], $commentoffset++, 1));
					$ParsedArray['comments']['title'][]     = substr($ParsedArray['comments_raw'], $commentoffset, $commentlength);
					$commentoffset += $commentlength;

					$commentlength = getid3_lib::BigEndian2Int(substr($ParsedArray['comments_raw'], $commentoffset++, 1));
					$ParsedArray['comments']['artist'][]    = substr($ParsedArray['comments_raw'], $commentoffset, $commentlength);
					$commentoffset += $commentlength;

					$commentlength = getid3_lib::BigEndian2Int(substr($ParsedArray['comments_raw'], $commentoffset++, 1));
					$ParsedArray['comments']['copyright'][] = substr($ParsedArray['comments_raw'], $commentoffset, $commentlength);
					$commentoffset += $commentlength;
					break;

				case 5:
					$ParsedArray['sample_rate']      = getid3_lib::BigEndian2Int(substr($OldRAheaderData, 48, 4));
					$ParsedArray['sample_rate2']     = getid3_lib::BigEndian2Int(substr($OldRAheaderData, 52, 4));
					$ParsedArray['bits_per_sample']  = getid3_lib::BigEndian2Int(substr($OldRAheaderData, 56, 4));
					$ParsedArray['channels']         = getid3_lib::BigEndian2Int(substr($OldRAheaderData, 60, 2));
					$ParsedArray['genr']             =                           substr($OldRAheaderData, 62, 4);
					$ParsedArray['fourcc3']          =                           substr($OldRAheaderData, 66, 4);
					$ParsedArray['comments']         = array();
					break;
			}
			$ParsedArray['fourcc'] = $ParsedArray['fourcc3'];

		}
		foreach ($ParsedArray['comments'] as $key => $value) {
			if ($ParsedArray['comments'][$key][0] === false) {
				$ParsedArray['comments'][$key][0] = '';
			}
		}

		return true;
	}

	function RealAudioCodecFourCClookup($fourcc, $bitrate) {
		static $RealAudioCodecFourCClookup = array();
		if (empty($RealAudioCodecFourCClookup)) {
			// http://www.its.msstate.edu/net/real/reports/config/tags.stats
			// http://www.freelists.org/archives/matroska-devel/06-2003/fullthread18.html

			$RealAudioCodecFourCClookup['14_4'][8000]  = 'RealAudio v2 (14.4kbps)';
			$RealAudioCodecFourCClookup['14.4'][8000]  = 'RealAudio v2 (14.4kbps)';
			$RealAudioCodecFourCClookup['lpcJ'][8000]  = 'RealAudio v2 (14.4kbps)';
			$RealAudioCodecFourCClookup['28_8'][15200] = 'RealAudio v2 (28.8kbps)';
			$RealAudioCodecFourCClookup['28.8'][15200] = 'RealAudio v2 (28.8kbps)';
			$RealAudioCodecFourCClookup['sipr'][4933]  = 'RealAudio v4 (5kbps Voice)';
			$RealAudioCodecFourCClookup['sipr'][6444]  = 'RealAudio v4 (6.5kbps Voice)';
			$RealAudioCodecFourCClookup['sipr'][8444]  = 'RealAudio v4 (8.5kbps Voice)';
			$RealAudioCodecFourCClookup['sipr'][16000] = 'RealAudio v4 (16kbps Wideband)';
			$RealAudioCodecFourCClookup['dnet'][8000]  = 'RealAudio v3 (8kbps Music)';
			$RealAudioCodecFourCClookup['dnet'][16000] = 'RealAudio v3 (16kbps Music Low Response)';
			$RealAudioCodecFourCClookup['dnet'][15963] = 'RealAudio v3 (16kbps Music Mid/High Response)';
			$RealAudioCodecFourCClookup['dnet'][20000] = 'RealAudio v3 (20kbps Music Stereo)';
			$RealAudioCodecFourCClookup['dnet'][32000] = 'RealAudio v3 (32kbps Music Mono)';
			$RealAudioCodecFourCClookup['dnet'][31951] = 'RealAudio v3 (32kbps Music Stereo)';
			$RealAudioCodecFourCClookup['dnet'][39965] = 'RealAudio v3 (40kbps Music Mono)';
			$RealAudioCodecFourCClookup['dnet'][40000] = 'RealAudio v3 (40kbps Music Stereo)';
			$RealAudioCodecFourCClookup['dnet'][79947] = 'RealAudio v3 (80kbps Music Mono)';
			$RealAudioCodecFourCClookup['dnet'][80000] = 'RealAudio v3 (80kbps Music Stereo)';

			$RealAudioCodecFourCClookup['dnet'][0] = 'RealAudio v3';
			$RealAudioCodecFourCClookup['sipr'][0] = 'RealAudio v4';
			$RealAudioCodecFourCClookup['cook'][0] = 'RealAudio G2';
			$RealAudioCodecFourCClookup['atrc'][0] = 'RealAudio 8';
		}
		$roundbitrate = intval(round($bitrate));
		if (isset($RealAudioCodecFourCClookup[$fourcc][$roundbitrate])) {
			return $RealAudioCodecFourCClookup[$fourcc][$roundbitrate];
		} elseif (isset($RealAudioCodecFourCClookup[$fourcc][0])) {
			return $RealAudioCodecFourCClookup[$fourcc][0];
		}
		return $fourcc;
	}

}


?>