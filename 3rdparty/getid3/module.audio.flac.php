<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.audio.flac.php                                       //
// module for analyzing FLAC and OggFLAC audio files           //
// dependencies: module.audio.ogg.php                          //
//                                                            ///
/////////////////////////////////////////////////////////////////


getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.audio.ogg.php', __FILE__, true);

class getid3_flac extends getid3_handler
{
	var $inline_attachments = true; // true: return full data for all attachments; false: return no data for all attachments; integer: return data for attachments <= than this; string: save as file to this directory

	function Analyze() {
		$info = &$this->getid3->info;

		// http://flac.sourceforge.net/format.html

		$this->fseek($info['avdataoffset'], SEEK_SET);
		$StreamMarker = $this->fread(4);
		$magic = 'fLaC';
		if ($StreamMarker != $magic) {
			$info['error'][] = 'Expecting "'.getid3_lib::PrintHexBytes($magic).'" at offset '.$info['avdataoffset'].', found "'.getid3_lib::PrintHexBytes($StreamMarker).'"';
			return false;
		}
		$info['fileformat']            = 'flac';
		$info['audio']['dataformat']   = 'flac';
		$info['audio']['bitrate_mode'] = 'vbr';
		$info['audio']['lossless']     = true;

		return $this->FLACparseMETAdata();
	}


	function FLACparseMETAdata() {
		$info = &$this->getid3->info;
		do {
			$METAdataBlockOffset          = $this->ftell();
			$METAdataBlockHeader          = $this->fread(4);
			$METAdataLastBlockFlag        = (bool) (getid3_lib::BigEndian2Int(substr($METAdataBlockHeader, 0, 1)) & 0x80);
			$METAdataBlockType            = getid3_lib::BigEndian2Int(substr($METAdataBlockHeader, 0, 1)) & 0x7F;
			$METAdataBlockLength          = getid3_lib::BigEndian2Int(substr($METAdataBlockHeader, 1, 3));
			$METAdataBlockTypeText        = getid3_flac::FLACmetaBlockTypeLookup($METAdataBlockType);

			if ($METAdataBlockLength < 0) {
				$info['error'][] = 'corrupt or invalid METADATA_BLOCK_HEADER.BLOCK_TYPE ('.$METAdataBlockType.') at offset '.$METAdataBlockOffset;
				break;
			}

			$info['flac'][$METAdataBlockTypeText]['raw'] = array();
			$ThisFileInfo_flac_METAdataBlockTypeText_raw = &$info['flac'][$METAdataBlockTypeText]['raw'];

			$ThisFileInfo_flac_METAdataBlockTypeText_raw['offset']          = $METAdataBlockOffset;
			$ThisFileInfo_flac_METAdataBlockTypeText_raw['last_meta_block'] = $METAdataLastBlockFlag;
			$ThisFileInfo_flac_METAdataBlockTypeText_raw['block_type']      = $METAdataBlockType;
			$ThisFileInfo_flac_METAdataBlockTypeText_raw['block_type_text'] = $METAdataBlockTypeText;
			$ThisFileInfo_flac_METAdataBlockTypeText_raw['block_length']    = $METAdataBlockLength;
			if (($METAdataBlockOffset + 4 + $METAdataBlockLength) > $info['avdataend']) {
				$info['error'][] = 'METADATA_BLOCK_HEADER.BLOCK_TYPE ('.$METAdataBlockType.') at offset '.$METAdataBlockOffset.' extends beyond end of file';
				break;
			}
			if ($METAdataBlockLength < 1) {
				$info['error'][] = 'METADATA_BLOCK_HEADER.BLOCK_LENGTH ('.$METAdataBlockLength.') at offset '.$METAdataBlockOffset.' is invalid';
				break;
			}
			$ThisFileInfo_flac_METAdataBlockTypeText_raw['block_data'] = $this->fread($METAdataBlockLength);
			$info['avdataoffset'] = $this->ftell();

			switch ($METAdataBlockTypeText) {
				case 'STREAMINFO':     // 0x00
					if (!$this->FLACparseSTREAMINFO($ThisFileInfo_flac_METAdataBlockTypeText_raw['block_data'])) {
						return false;
					}
					break;

				case 'PADDING':        // 0x01
					// ignore
					break;

				case 'APPLICATION':    // 0x02
					if (!$this->FLACparseAPPLICATION($ThisFileInfo_flac_METAdataBlockTypeText_raw['block_data'])) {
						return false;
					}
					break;

				case 'SEEKTABLE':      // 0x03
					if (!$this->FLACparseSEEKTABLE($ThisFileInfo_flac_METAdataBlockTypeText_raw['block_data'])) {
						return false;
					}
					break;

				case 'VORBIS_COMMENT': // 0x04
					$getid3_temp = new getID3();
					$getid3_temp->openfile($this->getid3->filename);
					$getid3_temp->info['avdataoffset'] = $this->ftell() - $METAdataBlockLength;
					$getid3_temp->info['audio']['dataformat'] = 'flac';
					$getid3_temp->info['flac'] = $info['flac'];
					$getid3_ogg = new getid3_ogg($getid3_temp);
					$getid3_ogg->ParseVorbisCommentsFilepointer();
					$maybe_copy_keys = array('vendor', 'comments_raw', 'comments', 'replay_gain');
					foreach ($maybe_copy_keys as $maybe_copy_key) {
						if (!empty($getid3_temp->info['ogg'][$maybe_copy_key])) {
							$info['ogg'][$maybe_copy_key] = $getid3_temp->info['ogg'][$maybe_copy_key];
						}
					}
					if (!empty($getid3_temp->info['replay_gain'])) {
						$info['replay_gain'] = $getid3_temp->info['replay_gain'];
					}
					unset($getid3_temp, $getid3_ogg);
					break;

				case 'CUESHEET':       // 0x05
					if (!getid3_flac::FLACparseCUESHEET($ThisFileInfo_flac_METAdataBlockTypeText_raw['block_data'])) {
						return false;
					}
					break;

				case 'PICTURE':        // 0x06
					if (!getid3_flac::FLACparsePICTURE($ThisFileInfo_flac_METAdataBlockTypeText_raw['block_data'])) {
						return false;
					}
					break;

				default:
					$info['warning'][] = 'Unhandled METADATA_BLOCK_HEADER.BLOCK_TYPE ('.$METAdataBlockType.') at offset '.$METAdataBlockOffset;
					break;
			}

		} while ($METAdataLastBlockFlag === false);

		if (isset($info['flac']['PICTURE'])) {
			foreach ($info['flac']['PICTURE'] as $key => $valuearray) {
				if (!empty($valuearray['image_mime']) && !empty($valuearray['data'])) {
					$info['ogg']['comments']['picture'][] = array('image_mime'=>$valuearray['image_mime'], 'data'=>$valuearray['data']);
				}
			}
		}

		if (isset($info['flac']['STREAMINFO'])) {
			$info['flac']['compressed_audio_bytes']   = $info['avdataend'] - $info['avdataoffset'];
			$info['flac']['uncompressed_audio_bytes'] = $info['flac']['STREAMINFO']['samples_stream'] * $info['flac']['STREAMINFO']['channels'] * ($info['flac']['STREAMINFO']['bits_per_sample'] / 8);
			if ($info['flac']['uncompressed_audio_bytes'] == 0) {
				$info['error'][] = 'Corrupt FLAC file: uncompressed_audio_bytes == zero';
				return false;
			}
			$info['flac']['compression_ratio'] = $info['flac']['compressed_audio_bytes'] / $info['flac']['uncompressed_audio_bytes'];
		}

		// set md5_data_source - built into flac 0.5+
		if (isset($info['flac']['STREAMINFO']['audio_signature'])) {

			if ($info['flac']['STREAMINFO']['audio_signature'] === str_repeat("\x00", 16)) {

				$info['warning'][] = 'FLAC STREAMINFO.audio_signature is null (known issue with libOggFLAC)';

			} else {

				$info['md5_data_source'] = '';
				$md5 = $info['flac']['STREAMINFO']['audio_signature'];
				for ($i = 0; $i < strlen($md5); $i++) {
					$info['md5_data_source'] .= str_pad(dechex(ord($md5{$i})), 2, '00', STR_PAD_LEFT);
				}
				if (!preg_match('/^[0-9a-f]{32}$/', $info['md5_data_source'])) {
					unset($info['md5_data_source']);
				}

			}

		}

		$info['audio']['bits_per_sample'] = $info['flac']['STREAMINFO']['bits_per_sample'];
		if ($info['audio']['bits_per_sample'] == 8) {
			// special case
			// must invert sign bit on all data bytes before MD5'ing to match FLAC's calculated value
			// MD5sum calculates on unsigned bytes, but FLAC calculated MD5 on 8-bit audio data as signed
			$info['warning'][] = 'FLAC calculates MD5 data strangely on 8-bit audio, so the stored md5_data_source value will not match the decoded WAV file';
		}
		if (!empty($info['ogg']['vendor'])) {
			$info['audio']['encoder'] = $info['ogg']['vendor'];
		}

		return true;
	}

	static function FLACmetaBlockTypeLookup($blocktype) {
		static $FLACmetaBlockTypeLookup = array();
		if (empty($FLACmetaBlockTypeLookup)) {
			$FLACmetaBlockTypeLookup[0] = 'STREAMINFO';
			$FLACmetaBlockTypeLookup[1] = 'PADDING';
			$FLACmetaBlockTypeLookup[2] = 'APPLICATION';
			$FLACmetaBlockTypeLookup[3] = 'SEEKTABLE';
			$FLACmetaBlockTypeLookup[4] = 'VORBIS_COMMENT';
			$FLACmetaBlockTypeLookup[5] = 'CUESHEET';
			$FLACmetaBlockTypeLookup[6] = 'PICTURE';
		}
		return (isset($FLACmetaBlockTypeLookup[$blocktype]) ? $FLACmetaBlockTypeLookup[$blocktype] : 'reserved');
	}

	static function FLACapplicationIDLookup($applicationid) {
		static $FLACapplicationIDLookup = array();
		if (empty($FLACapplicationIDLookup)) {
			// http://flac.sourceforge.net/id.html
			$FLACapplicationIDLookup[0x46746F6C] = 'flac-tools';      // 'Ftol'
			$FLACapplicationIDLookup[0x46746F6C] = 'Sound Font FLAC'; // 'SFFL'
		}
		return (isset($FLACapplicationIDLookup[$applicationid]) ? $FLACapplicationIDLookup[$applicationid] : 'reserved');
	}

	static function FLACpictureTypeLookup($type_id) {
		static $lookup = array (
			 0 => 'Other',
			 1 => '32x32 pixels \'file icon\' (PNG only)',
			 2 => 'Other file icon',
			 3 => 'Cover (front)',
			 4 => 'Cover (back)',
			 5 => 'Leaflet page',
			 6 => 'Media (e.g. label side of CD)',
			 7 => 'Lead artist/lead performer/soloist',
			 8 => 'Artist/performer',
			 9 => 'Conductor',
			10 => 'Band/Orchestra',
			11 => 'Composer',
			12 => 'Lyricist/text writer',
			13 => 'Recording Location',
			14 => 'During recording',
			15 => 'During performance',
			16 => 'Movie/video screen capture',
			17 => 'A bright coloured fish',
			18 => 'Illustration',
			19 => 'Band/artist logotype',
			20 => 'Publisher/Studio logotype',
		);
		return (isset($lookup[$type_id]) ? $lookup[$type_id] : 'reserved');
	}

	function FLACparseSTREAMINFO($METAdataBlockData) {
		$info = &$this->getid3->info;

		$offset = 0;
		$info['flac']['STREAMINFO']['min_block_size']  = getid3_lib::BigEndian2Int(substr($METAdataBlockData, $offset, 2));
		$offset += 2;
		$info['flac']['STREAMINFO']['max_block_size']  = getid3_lib::BigEndian2Int(substr($METAdataBlockData, $offset, 2));
		$offset += 2;
		$info['flac']['STREAMINFO']['min_frame_size']  = getid3_lib::BigEndian2Int(substr($METAdataBlockData, $offset, 3));
		$offset += 3;
		$info['flac']['STREAMINFO']['max_frame_size']  = getid3_lib::BigEndian2Int(substr($METAdataBlockData, $offset, 3));
		$offset += 3;

		$SampleRateChannelsSampleBitsStreamSamples             = getid3_lib::BigEndian2Bin(substr($METAdataBlockData, $offset, 8));
		$info['flac']['STREAMINFO']['sample_rate']     = getid3_lib::Bin2Dec(substr($SampleRateChannelsSampleBitsStreamSamples,  0, 20));
		$info['flac']['STREAMINFO']['channels']        = getid3_lib::Bin2Dec(substr($SampleRateChannelsSampleBitsStreamSamples, 20,  3)) + 1;
		$info['flac']['STREAMINFO']['bits_per_sample'] = getid3_lib::Bin2Dec(substr($SampleRateChannelsSampleBitsStreamSamples, 23,  5)) + 1;
		$info['flac']['STREAMINFO']['samples_stream']  = getid3_lib::Bin2Dec(substr($SampleRateChannelsSampleBitsStreamSamples, 28, 36));
		$offset += 8;

		$info['flac']['STREAMINFO']['audio_signature'] =               substr($METAdataBlockData, $offset, 16);
		$offset += 16;

		if (!empty($info['flac']['STREAMINFO']['sample_rate'])) {

			$info['audio']['bitrate_mode']     = 'vbr';
			$info['audio']['sample_rate']      = $info['flac']['STREAMINFO']['sample_rate'];
			$info['audio']['channels']         = $info['flac']['STREAMINFO']['channels'];
			$info['audio']['bits_per_sample']  = $info['flac']['STREAMINFO']['bits_per_sample'];
			$info['playtime_seconds']          = $info['flac']['STREAMINFO']['samples_stream'] / $info['flac']['STREAMINFO']['sample_rate'];
			if ($info['playtime_seconds'] > 0) {
				$info['audio']['bitrate']      = (($info['avdataend'] - $info['avdataoffset']) * 8) / $info['playtime_seconds'];
			}

		} else {
			$info['error'][] = 'Corrupt METAdata block: STREAMINFO';
			return false;
		}
		unset($info['flac']['STREAMINFO']['raw']);
		return true;
	}


	function FLACparseAPPLICATION($METAdataBlockData) {
		$info = &$this->getid3->info;

		$offset = 0;
		$ApplicationID = getid3_lib::BigEndian2Int(substr($METAdataBlockData, $offset, 4));
		$offset += 4;
		$info['flac']['APPLICATION'][$ApplicationID]['name'] = getid3_flac::FLACapplicationIDLookup($ApplicationID);
		$info['flac']['APPLICATION'][$ApplicationID]['data'] = substr($METAdataBlockData, $offset);
		$offset = $METAdataBlockLength;

		unset($info['flac']['APPLICATION']['raw']);
		return true;
	}


	function FLACparseSEEKTABLE($METAdataBlockData) {
		$info = &$this->getid3->info;

		$offset = 0;
		$METAdataBlockLength = strlen($METAdataBlockData);
		$placeholderpattern = str_repeat("\xFF", 8);
		while ($offset < $METAdataBlockLength) {
			$SampleNumberString = substr($METAdataBlockData, $offset, 8);
			$offset += 8;
			if ($SampleNumberString == $placeholderpattern) {

				// placeholder point
				getid3_lib::safe_inc($info['flac']['SEEKTABLE']['placeholders'], 1);
				$offset += 10;

			} else {

				$SampleNumber                                                = getid3_lib::BigEndian2Int($SampleNumberString);
				$info['flac']['SEEKTABLE'][$SampleNumber]['offset']  = getid3_lib::BigEndian2Int(substr($METAdataBlockData, $offset, 8));
				$offset += 8;
				$info['flac']['SEEKTABLE'][$SampleNumber]['samples'] = getid3_lib::BigEndian2Int(substr($METAdataBlockData, $offset, 2));
				$offset += 2;

			}
		}

		unset($info['flac']['SEEKTABLE']['raw']);

		return true;
	}

	function FLACparseCUESHEET($METAdataBlockData) {
		$info = &$this->getid3->info;
		$offset = 0;
		$info['flac']['CUESHEET']['media_catalog_number'] =                              trim(substr($METAdataBlockData, $offset, 128), "\0");
		$offset += 128;
		$info['flac']['CUESHEET']['lead_in_samples']      =         getid3_lib::BigEndian2Int(substr($METAdataBlockData, $offset, 8));
		$offset += 8;
		$info['flac']['CUESHEET']['flags']['is_cd']       = (bool) (getid3_lib::BigEndian2Int(substr($METAdataBlockData, $offset, 1)) & 0x80);
		$offset += 1;

		$offset += 258; // reserved

		$info['flac']['CUESHEET']['number_tracks']        =         getid3_lib::BigEndian2Int(substr($METAdataBlockData, $offset, 1));
		$offset += 1;

		for ($track = 0; $track < $info['flac']['CUESHEET']['number_tracks']; $track++) {
			$TrackSampleOffset = getid3_lib::BigEndian2Int(substr($METAdataBlockData, $offset, 8));
			$offset += 8;
			$TrackNumber       = getid3_lib::BigEndian2Int(substr($METAdataBlockData, $offset, 1));
			$offset += 1;

			$info['flac']['CUESHEET']['tracks'][$TrackNumber]['sample_offset']         = $TrackSampleOffset;

			$info['flac']['CUESHEET']['tracks'][$TrackNumber]['isrc']                  =                           substr($METAdataBlockData, $offset, 12);
			$offset += 12;

			$TrackFlagsRaw                                                             = getid3_lib::BigEndian2Int(substr($METAdataBlockData, $offset, 1));
			$offset += 1;
			$info['flac']['CUESHEET']['tracks'][$TrackNumber]['flags']['is_audio']     = (bool) ($TrackFlagsRaw & 0x80);
			$info['flac']['CUESHEET']['tracks'][$TrackNumber]['flags']['pre_emphasis'] = (bool) ($TrackFlagsRaw & 0x40);

			$offset += 13; // reserved

			$info['flac']['CUESHEET']['tracks'][$TrackNumber]['index_points']          = getid3_lib::BigEndian2Int(substr($METAdataBlockData, $offset, 1));
			$offset += 1;

			for ($index = 0; $index < $info['flac']['CUESHEET']['tracks'][$TrackNumber]['index_points']; $index++) {
				$IndexSampleOffset = getid3_lib::BigEndian2Int(substr($METAdataBlockData, $offset, 8));
				$offset += 8;
				$IndexNumber       = getid3_lib::BigEndian2Int(substr($METAdataBlockData, $offset, 1));
				$offset += 1;

				$offset += 3; // reserved

				$info['flac']['CUESHEET']['tracks'][$TrackNumber]['indexes'][$IndexNumber] = $IndexSampleOffset;
			}
		}

		unset($info['flac']['CUESHEET']['raw']);

		return true;
	}


	function FLACparsePICTURE($meta_data_block_data) {
		$info = &$this->getid3->info;
		$picture = &$info['flac']['PICTURE'][sizeof($info['flac']['PICTURE']) - 1];
		$picture['offset'] = $info['flac']['PICTURE']['raw']['offset'];
		unset($info['flac']['PICTURE']['raw']);

		$offset = 0;

		$picture['typeid'] = getid3_lib::BigEndian2Int(substr($meta_data_block_data, $offset, 4));
		$picture['type'] = getid3_flac::FLACpictureTypeLookup($picture['typeid']);
		$offset += 4;

		$length = getid3_lib::BigEndian2Int(substr($meta_data_block_data, $offset, 4));
		$offset += 4;

		$picture['image_mime'] = substr($meta_data_block_data, $offset, $length);
		$offset += $length;

		$length = getid3_lib::BigEndian2Int(substr($meta_data_block_data, $offset, 4));
		$offset += 4;

		$picture['description'] = substr($meta_data_block_data, $offset, $length);
		$offset += $length;

		$picture['width'] = getid3_lib::BigEndian2Int(substr($meta_data_block_data, $offset, 4));
		$offset += 4;

		$picture['height'] = getid3_lib::BigEndian2Int(substr($meta_data_block_data, $offset, 4));
		$offset += 4;

		$picture['color_depth'] = getid3_lib::BigEndian2Int(substr($meta_data_block_data, $offset, 4));
		$offset += 4;

		$picture['colors_indexed'] = getid3_lib::BigEndian2Int(substr($meta_data_block_data, $offset, 4));
		$offset += 4;

		$length = getid3_lib::BigEndian2Int(substr($meta_data_block_data, $offset, 4));
		$offset += 4;

		$picture['data'] = substr($meta_data_block_data, $offset, $length);
		$offset += $length;
		$picture['data_length'] = strlen($picture['data']);


		do {
			if ($this->inline_attachments === false) {
				// skip entirely
				unset($picture['data']);
				break;
			}
			if ($this->inline_attachments === true) {
				// great
			} elseif (is_int($this->inline_attachments)) {
				if ($this->inline_attachments < $picture['data_length']) {
					// too big, skip
					$info['warning'][] = 'attachment at '.$picture['offset'].' is too large to process inline ('.number_format($picture['data_length']).' bytes)';
					unset($picture['data']);
					break;
				}
			} elseif (is_string($this->inline_attachments)) {
				$this->inline_attachments = rtrim(str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $this->inline_attachments), DIRECTORY_SEPARATOR);
				if (!is_dir($this->inline_attachments) || !is_writable($this->inline_attachments)) {
					// cannot write, skip
					$info['warning'][] = 'attachment at '.$picture['offset'].' cannot be saved to "'.$this->inline_attachments.'" (not writable)';
					unset($picture['data']);
					break;
				}
			}
			// if we get this far, must be OK
			if (is_string($this->inline_attachments)) {
				$destination_filename = $this->inline_attachments.DIRECTORY_SEPARATOR.md5($info['filenamepath']).'_'.$picture['offset'];
				if (!file_exists($destination_filename) || is_writable($destination_filename)) {
					file_put_contents($destination_filename, $picture['data']);
				} else {
					$info['warning'][] = 'attachment at '.$picture['offset'].' cannot be saved to "'.$destination_filename.'" (not writable)';
				}
				$picture['data_filename'] = $destination_filename;
				unset($picture['data']);
			} else {
				if (!isset($info['flac']['comments']['picture'])) {
					$info['flac']['comments']['picture'] = array();
				}
				$info['flac']['comments']['picture'][] = array('data'=>$picture['data'], 'image_mime'=>$picture['image_mime']);
			}
		} while (false);



		return true;
	}
}

?>