<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.audio.mpc.php                                        //
// module for analyzing Musepack/MPEG+ Audio files             //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////


class getid3_mpc
{

	function getid3_mpc(&$fd, &$ThisFileInfo) {
		$ThisFileInfo['mpc']['header'] = array();
		$thisfile_mpc_header           = &$ThisFileInfo['mpc']['header'];

		$ThisFileInfo['fileformat']               = 'mpc';
		$ThisFileInfo['audio']['dataformat']      = 'mpc';
		$ThisFileInfo['audio']['bitrate_mode']    = 'vbr';
		$ThisFileInfo['audio']['channels']        = 2;  // up to SV7 the format appears to have been hardcoded for stereo only
		$ThisFileInfo['audio']['lossless']        = false;

		fseek($fd, $ThisFileInfo['avdataoffset'], SEEK_SET);
		$MPCheaderData = fread($fd, 4);
		$ThisFileInfo['mpc']['header']['preamble'] = substr($MPCheaderData, 0, 4); // should be 'MPCK' (SV8) or 'MP+' (SV7), otherwise possible stream data (SV4-SV6)
		if (ereg('^MPCK', $ThisFileInfo['mpc']['header']['preamble'])) {

			// this is SV8
			return $this->ParseMPCsv8($fd, $ThisFileInfo);

		} elseif (ereg('^MP\+', $ThisFileInfo['mpc']['header']['preamble'])) {

			// this is SV7
			return $this->ParseMPCsv7($fd, $ThisFileInfo);

		} elseif (preg_match('/^[\x00\x01\x10\x11\x40\x41\x50\x51\x80\x81\x90\x91\xC0\xC1\xD0\xD1][\x20-37][\x00\x20\x40\x60\x80\xA0\xC0\xE0]/s', $MPCheaderData)) {

			// this is SV4 - SV6, handle seperately
			return $this->ParseMPCsv6($fd, $ThisFileInfo);

		} else {

			$ThisFileInfo['error'][] = 'Expecting "MP+" or "MPCK" at offset '.$ThisFileInfo['avdataoffset'].', found "'.substr($MPCheaderData, 0, 4).'"';
			unset($ThisFileInfo['fileformat']);
			unset($ThisFileInfo['mpc']);
			return false;

		}
		return false;
	}


	function ParseMPCsv8(&$fd, &$ThisFileInfo) {
		// this is SV8
		// http://trac.musepack.net/trac/wiki/SV8Specification

		$thisfile_mpc_header = &$ThisFileInfo['mpc']['header'];

		$keyNameSize            = 2;
		$maxHandledPacketLength = 9; // specs say: "n*8; 0 < n < 10"

		$offset = ftell($fd);
		while ($offset < $ThisFileInfo['avdataend']) {
			$thisPacket = array();
			$thisPacket['offset'] = $offset;
			$packet_offset = 0;

			// Size is a variable-size field, could be 1-4 bytes (possibly more?)
			// read enough data in and figure out the exact size later
			$MPCheaderData = fread($fd, $keyNameSize + $maxHandledPacketLength);
			$packet_offset += $keyNameSize;
			$thisPacket['key']      = substr($MPCheaderData, 0, $keyNameSize);
			$thisPacket['key_name'] = $this->MPCsv8PacketName($thisPacket['key']);
			if ($thisPacket['key'] == $thisPacket['key_name']) {
				$ThisFileInfo['error'][] = 'Found unexpected key value "'.$thisPacket['key'].'" at offset '.$thisPacket['offset'];
				return false;
			}
			$packetLength = 0;
			$thisPacket['packet_size'] = $this->SV8variableLengthInteger(substr($MPCheaderData, $keyNameSize), $packetLength); // includes keyname and packet_size field
			if ($thisPacket['packet_size'] === false) {
				$ThisFileInfo['error'][] = 'Did not find expected packet length within '.$maxHandledPacketLength.' bytes at offset '.($thisPacket['offset'] + $keyNameSize);
				return false;
			}
			$packet_offset += $packetLength;
			$offset += $thisPacket['packet_size'];

			switch ($thisPacket['key']) {
				case 'SH': // Stream Header
					$moreBytesToRead = $thisPacket['packet_size'] - $keyNameSize - $maxHandledPacketLength;
					if ($moreBytesToRead > 0) {
						$MPCheaderData .= fread($fd, $moreBytesToRead);
					}
					$thisPacket['crc']               =       getid3_lib::BigEndian2Int(substr($MPCheaderData, $packet_offset, 4));
					$packet_offset += 4;
					$thisPacket['stream_version']    =       getid3_lib::BigEndian2Int(substr($MPCheaderData, $packet_offset, 1));
					$packet_offset += 1;

					$packetLength = 0;
					$thisPacket['sample_count']      = $this->SV8variableLengthInteger(substr($MPCheaderData, $packet_offset, $maxHandledPacketLength), $packetLength);
					$packet_offset += $packetLength;

					$packetLength = 0;
					$thisPacket['beginning_silence'] = $this->SV8variableLengthInteger(substr($MPCheaderData, $packet_offset, $maxHandledPacketLength), $packetLength);
					$packet_offset += $packetLength;

					$otherUsefulData                 =       getid3_lib::BigEndian2Int(substr($MPCheaderData, $packet_offset, 2));
					$packet_offset += 2;
					$thisPacket['sample_frequency_raw'] =        (($otherUsefulData & 0xE000) >> 13);
					$thisPacket['max_bands_used']       =        (($otherUsefulData & 0x1F00) >>  8);
					$thisPacket['channels']             =        (($otherUsefulData & 0x00F0) >>  4) + 1;
					$thisPacket['ms_used']              = (bool) (($otherUsefulData & 0x0008) >>  3);
					$thisPacket['audio_block_frames']   =        (($otherUsefulData & 0x0007) >>  0);
					$thisPacket['sample_frequency']     = $this->MPCfrequencyLookup($thisPacket['sample_frequency_raw']);

					$thisfile_mpc_header['mid_side_stereo']      = $thisPacket['ms_used'];
					$thisfile_mpc_header['sample_rate']          = $thisPacket['sample_frequency'];
					$thisfile_mpc_header['samples']              = $thisPacket['sample_count'];
					$thisfile_mpc_header['stream_version_major'] = $thisPacket['stream_version'];

					$ThisFileInfo['audio']['channels']    = $thisPacket['channels'];
					$ThisFileInfo['audio']['sample_rate'] = $thisPacket['sample_frequency'];
					$ThisFileInfo['playtime_seconds'] = $thisPacket['sample_count'] / $thisPacket['sample_frequency'];
					$ThisFileInfo['audio']['bitrate'] = (($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']) * 8) / $ThisFileInfo['playtime_seconds'];
					break;

				case 'RG': // Replay Gain
					$moreBytesToRead = $thisPacket['packet_size'] - $keyNameSize - $maxHandledPacketLength;
					if ($moreBytesToRead > 0) {
						$MPCheaderData .= fread($fd, $moreBytesToRead);
					}
					$thisPacket['replaygain_version']     =       getid3_lib::BigEndian2Int(substr($MPCheaderData, $packet_offset, 1));
					$packet_offset += 1;
					$thisPacket['replaygain_title_gain']  =       getid3_lib::BigEndian2Int(substr($MPCheaderData, $packet_offset, 2));
					$packet_offset += 2;
					$thisPacket['replaygain_title_peak']  =       getid3_lib::BigEndian2Int(substr($MPCheaderData, $packet_offset, 2));
					$packet_offset += 2;
					$thisPacket['replaygain_album_gain']  =       getid3_lib::BigEndian2Int(substr($MPCheaderData, $packet_offset, 2));
					$packet_offset += 2;
					$thisPacket['replaygain_album_peak']  =       getid3_lib::BigEndian2Int(substr($MPCheaderData, $packet_offset, 2));
					$packet_offset += 2;

					if ($thisPacket['replaygain_title_gain']) { $ThisFileInfo['replay_gain']['title']['gain'] = $thisPacket['replaygain_title_gain']; }
					if ($thisPacket['replaygain_title_peak']) { $ThisFileInfo['replay_gain']['title']['peak'] = $thisPacket['replaygain_title_peak']; }
					if ($thisPacket['replaygain_album_gain']) { $ThisFileInfo['replay_gain']['album']['gain'] = $thisPacket['replaygain_album_gain']; }
					if ($thisPacket['replaygain_album_peak']) { $ThisFileInfo['replay_gain']['album']['peak'] = $thisPacket['replaygain_album_peak']; }
					break;

				case 'EI': // Encoder Info
					$moreBytesToRead = $thisPacket['packet_size'] - $keyNameSize - $maxHandledPacketLength;
					if ($moreBytesToRead > 0) {
						$MPCheaderData .= fread($fd, $moreBytesToRead);
					}
					$profile_pns                 = getid3_lib::BigEndian2Int(substr($MPCheaderData, $packet_offset, 1));
					$packet_offset += 1;
					$quality_int =                   (($profile_pns & 0xF0) >> 4);
					$quality_dec =                   (($profile_pns & 0x0E) >> 3);
					$thisPacket['quality'] = (float) $quality_int + ($quality_dec / 8);
					$thisPacket['pns_tool'] = (bool) (($profile_pns & 0x01) >> 0);
					$thisPacket['version_major'] = getid3_lib::BigEndian2Int(substr($MPCheaderData, $packet_offset, 1));
					$packet_offset += 1;
					$thisPacket['version_minor'] = getid3_lib::BigEndian2Int(substr($MPCheaderData, $packet_offset, 1));
					$packet_offset += 1;
					$thisPacket['version_build'] = getid3_lib::BigEndian2Int(substr($MPCheaderData, $packet_offset, 1));
					$packet_offset += 1;
					$thisPacket['version'] = $thisPacket['version_major'].'.'.$thisPacket['version_minor'].'.'.$thisPacket['version_build'];

					$ThisFileInfo['audio']['encoder'] = 'MPC v'.$thisPacket['version'].' ('.(($thisPacket['version_minor'] % 2) ? 'unstable' : 'stable').')';
					$thisfile_mpc_header['encoder_version'] = $ThisFileInfo['audio']['encoder'];
					//$thisfile_mpc_header['quality']         = (float) ($thisPacket['quality'] / 1.5875); // values can range from 0.000 to 15.875, mapped to qualities of 0.0 to 10.0
					$thisfile_mpc_header['quality']         = (float) ($thisPacket['quality'] - 5); // values can range from 0.000 to 15.875, of which 0..4 are "reserved/experimental", and 5..15 are mapped to qualities of 0.0 to 10.0
					break;

				case 'SO': // Seek Table Offset
					$packetLength = 0;
					$thisPacket['seek_table_offset'] = $thisPacket['offset'] + $this->SV8variableLengthInteger(substr($MPCheaderData, $packet_offset, $maxHandledPacketLength), $packetLength);
					$packet_offset += $packetLength;
					break;

				case 'ST': // Seek Table
				case 'SE': // Stream End
				case 'AP': // Audio Data
					// nothing useful here, just skip this packet
					$thisPacket = array();
					break;

				default:
					$ThisFileInfo['error'][] = 'Found unhandled key type "'.$thisPacket['key'].'" at offset '.$thisPacket['offset'];
					return false;
					break;
			}
			if (!empty($thisPacket)) {
				$ThisFileInfo['mpc']['packets'][] = $thisPacket;
			}
			fseek($fd, $offset);
		}
		$thisfile_mpc_header['size'] = $offset;
		return true;
	}

	function ParseMPCsv7(&$fd, &$ThisFileInfo) {
		// this is SV7
		// http://www.uni-jena.de/~pfk/mpp/sv8/header.html
		$thisfile_mpc_header = &$ThisFileInfo['mpc']['header'];
		$offset = 0;

		$thisfile_mpc_header['size'] = 28;
		$MPCheaderData  = $ThisFileInfo['mpc']['header']['preamble'];
		$MPCheaderData .= fread($fd, $thisfile_mpc_header['size'] - strlen($ThisFileInfo['mpc']['header']['preamble']));
		$offset = strlen('MP+');

		$StreamVersionByte                           = getid3_lib::LittleEndian2Int(substr($MPCheaderData, $offset, 1));
		$offset += 1;
		$thisfile_mpc_header['stream_version_major'] = ($StreamVersionByte & 0x0F) >> 0;
		$thisfile_mpc_header['stream_version_minor'] = ($StreamVersionByte & 0xF0) >> 4; // should always be 0, subversions no longer exist in SV8
		$thisfile_mpc_header['frame_count']          = getid3_lib::LittleEndian2Int(substr($MPCheaderData, $offset, 4));
		$offset += 4;

		if ($thisfile_mpc_header['stream_version_major'] != 7) {
			$ThisFileInfo['error'][] = 'Only Musepack SV7 supported (this file claims to be v'.$thisfile_mpc_header['stream_version_major'].')';
			return false;
		}

		$FlagsDWORD1                                   = getid3_lib::LittleEndian2Int(substr($MPCheaderData, $offset, 4));
		$offset += 4;
		$thisfile_mpc_header['intensity_stereo']       = (bool) (($FlagsDWORD1 & 0x80000000) >> 31);
		$thisfile_mpc_header['mid_side_stereo']        = (bool) (($FlagsDWORD1 & 0x40000000) >> 30);
		$thisfile_mpc_header['max_subband']            =         ($FlagsDWORD1 & 0x3F000000) >> 24;
		$thisfile_mpc_header['raw']['profile']         =         ($FlagsDWORD1 & 0x00F00000) >> 20;
		$thisfile_mpc_header['begin_loud']             = (bool) (($FlagsDWORD1 & 0x00080000) >> 19);
		$thisfile_mpc_header['end_loud']               = (bool) (($FlagsDWORD1 & 0x00040000) >> 18);
		$thisfile_mpc_header['raw']['sample_rate']     =         ($FlagsDWORD1 & 0x00030000) >> 16;
		$thisfile_mpc_header['max_level']              =         ($FlagsDWORD1 & 0x0000FFFF);

		$thisfile_mpc_header['raw']['title_peak']      = getid3_lib::LittleEndian2Int(substr($MPCheaderData, $offset, 2));
		$offset += 2;
		$thisfile_mpc_header['raw']['title_gain']      = getid3_lib::LittleEndian2Int(substr($MPCheaderData, $offset, 2), true);
		$offset += 2;

		$thisfile_mpc_header['raw']['album_peak']      = getid3_lib::LittleEndian2Int(substr($MPCheaderData, $offset, 2));
		$offset += 2;
		$thisfile_mpc_header['raw']['album_gain']      = getid3_lib::LittleEndian2Int(substr($MPCheaderData, $offset, 2), true);
		$offset += 2;

		$FlagsDWORD2                                   = getid3_lib::LittleEndian2Int(substr($MPCheaderData, $offset, 4));
		$offset += 4;
		$thisfile_mpc_header['true_gapless']           = (bool) (($FlagsDWORD2 & 0x80000000) >> 31);
		$thisfile_mpc_header['last_frame_length']      =         ($FlagsDWORD2 & 0x7FF00000) >> 20;


		$thisfile_mpc_header['raw']['not_sure_what']   = getid3_lib::LittleEndian2Int(substr($MPCheaderData, $offset, 3));
		$offset += 3;
		$thisfile_mpc_header['raw']['encoder_version'] = getid3_lib::LittleEndian2Int(substr($MPCheaderData, $offset, 1));
		$offset += 1;

		$thisfile_mpc_header['profile']     = $this->MPCprofileNameLookup($thisfile_mpc_header['raw']['profile']);
		$thisfile_mpc_header['sample_rate'] = $this->MPCfrequencyLookup($thisfile_mpc_header['raw']['sample_rate']);
		if ($thisfile_mpc_header['sample_rate'] == 0) {
			$ThisFileInfo['error'][] = 'Corrupt MPC file: frequency == zero';
			return false;
		}
		$ThisFileInfo['audio']['sample_rate'] = $thisfile_mpc_header['sample_rate'];
		$thisfile_mpc_header['samples']       = ((($thisfile_mpc_header['frame_count'] - 1) * 1152) + $thisfile_mpc_header['last_frame_length']) * $ThisFileInfo['audio']['channels'];

		$ThisFileInfo['playtime_seconds']     = ($thisfile_mpc_header['samples'] / $ThisFileInfo['audio']['channels']) / $ThisFileInfo['audio']['sample_rate'];
		if ($ThisFileInfo['playtime_seconds'] == 0) {
			$ThisFileInfo['error'][] = 'Corrupt MPC file: playtime_seconds == zero';
			return false;
		}

		// add size of file header to avdataoffset - calc bitrate correctly + MD5 data
		$ThisFileInfo['avdataoffset'] += $thisfile_mpc_header['size'];

		$ThisFileInfo['audio']['bitrate'] = (($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']) * 8) / $ThisFileInfo['playtime_seconds'];

		$thisfile_mpc_header['title_peak']        = $thisfile_mpc_header['raw']['title_peak'];
		$thisfile_mpc_header['title_peak_db']     = $this->MPCpeakDBLookup($thisfile_mpc_header['title_peak']);
		if ($thisfile_mpc_header['raw']['title_gain'] < 0) {
			$thisfile_mpc_header['title_gain_db'] = (float) (32768 + $thisfile_mpc_header['raw']['title_gain']) / -100;
		} else {
			$thisfile_mpc_header['title_gain_db'] = (float) $thisfile_mpc_header['raw']['title_gain'] / 100;
		}

		$thisfile_mpc_header['album_peak']        = $thisfile_mpc_header['raw']['album_peak'];
		$thisfile_mpc_header['album_peak_db']     = $this->MPCpeakDBLookup($thisfile_mpc_header['album_peak']);
		if ($thisfile_mpc_header['raw']['album_gain'] < 0) {
			$thisfile_mpc_header['album_gain_db'] = (float) (32768 + $thisfile_mpc_header['raw']['album_gain']) / -100;
		} else {
			$thisfile_mpc_header['album_gain_db'] = (float) $thisfile_mpc_header['raw']['album_gain'] / 100;;
		}
		$thisfile_mpc_header['encoder_version']   = $this->MPCencoderVersionLookup($thisfile_mpc_header['raw']['encoder_version']);

		$ThisFileInfo['replay_gain']['track']['adjustment'] = $thisfile_mpc_header['title_gain_db'];
		$ThisFileInfo['replay_gain']['album']['adjustment'] = $thisfile_mpc_header['album_gain_db'];

		if ($thisfile_mpc_header['title_peak'] > 0) {
			$ThisFileInfo['replay_gain']['track']['peak'] = $thisfile_mpc_header['title_peak'];
		} elseif (round($thisfile_mpc_header['max_level'] * 1.18) > 0) {
			$ThisFileInfo['replay_gain']['track']['peak'] = getid3_lib::CastAsInt(round($thisfile_mpc_header['max_level'] * 1.18)); // why? I don't know - see mppdec.c
		}
		if ($thisfile_mpc_header['album_peak'] > 0) {
			$ThisFileInfo['replay_gain']['album']['peak'] = $thisfile_mpc_header['album_peak'];
		}

		//$ThisFileInfo['audio']['encoder'] = 'SV'.$thisfile_mpc_header['stream_version_major'].'.'.$thisfile_mpc_header['stream_version_minor'].', '.$thisfile_mpc_header['encoder_version'];
		$ThisFileInfo['audio']['encoder'] = $thisfile_mpc_header['encoder_version'];
		$ThisFileInfo['audio']['encoder_options'] = $thisfile_mpc_header['profile'];
		$thisfile_mpc_header['quality'] = (float) ($thisfile_mpc_header['raw']['profile'] - 5); // values can range from 0 to 15, of which 0..4 are "reserved/experimental", and 5..15 are mapped to qualities of 0.0 to 10.0

		return true;
	}

	function ParseMPCsv6(&$fd, &$ThisFileInfo) {
		// this is SV4 - SV6
		$thisfile_mpc_header = &$ThisFileInfo['mpc']['header'];
		$offset = 0;

        $thisfile_mpc_header['size'] = 8;
		fseek($fd, $ThisFileInfo['avdataoffset'], SEEK_SET);
		$MPCheaderData = fread($fd, $thisfile_mpc_header['size']);

        // add size of file header to avdataoffset - calc bitrate correctly + MD5 data
	    $ThisFileInfo['avdataoffset'] += $thisfile_mpc_header['size'];

		// Most of this code adapted from Jurgen Faul's MPEGplus source code - thanks Jurgen! :)
		$HeaderDWORD[0] = getid3_lib::LittleEndian2Int(substr($MPCheaderData, 0, 4));
		$HeaderDWORD[1] = getid3_lib::LittleEndian2Int(substr($MPCheaderData, 4, 4));


		// DDDD DDDD  CCCC CCCC  BBBB BBBB  AAAA AAAA
		// aaaa aaaa  abcd dddd  dddd deee  eeff ffff
		//
		// a = bitrate       = anything
		// b = IS            = anything
		// c = MS            = anything
		// d = streamversion = 0000000004 or 0000000005 or 0000000006
		// e = maxband       = anything
		// f = blocksize     = 000001 for SV5+, anything(?) for SV4

		$thisfile_mpc_header['target_bitrate']       =        (($HeaderDWORD[0] & 0xFF800000) >> 23);
		$thisfile_mpc_header['intensity_stereo']     = (bool) (($HeaderDWORD[0] & 0x00400000) >> 22);
		$thisfile_mpc_header['mid_side_stereo']      = (bool) (($HeaderDWORD[0] & 0x00200000) >> 21);
		$thisfile_mpc_header['stream_version_major'] =         ($HeaderDWORD[0] & 0x001FF800) >> 11;
		$thisfile_mpc_header['stream_version_minor'] = 0; // no sub-version numbers before SV7
		$thisfile_mpc_header['max_band']             =         ($HeaderDWORD[0] & 0x000007C0) >>  6;  // related to lowpass frequency, not sure how it translates exactly
		$thisfile_mpc_header['block_size']           =         ($HeaderDWORD[0] & 0x0000003F);

		switch ($thisfile_mpc_header['stream_version_major']) {
			case 4:
				$thisfile_mpc_header['frame_count'] = ($HeaderDWORD[1] >> 16);
				break;

			case 5:
			case 6:
				$thisfile_mpc_header['frame_count'] =  $HeaderDWORD[1];
				break;

			default:
				$ThisFileInfo['error'] = 'Expecting 4, 5 or 6 in version field, found '.$thisfile_mpc_header['stream_version_major'].' instead';
				unset($ThisFileInfo['mpc']);
				return false;
				break;
		}

		if (($thisfile_mpc_header['stream_version_major'] > 4) && ($thisfile_mpc_header['block_size'] != 1)) {
			$ThisFileInfo['warning'][] = 'Block size expected to be 1, actual value found: '.$thisfile_mpc_header['block_size'];
		}

		$thisfile_mpc_header['sample_rate']   = 44100; // AB: used by all files up to SV7
		$ThisFileInfo['audio']['sample_rate'] = $thisfile_mpc_header['sample_rate'];
		$thisfile_mpc_header['samples']       = $thisfile_mpc_header['frame_count'] * 1152 * $ThisFileInfo['audio']['channels'];

		if ($thisfile_mpc_header['target_bitrate'] == 0) {
			$ThisFileInfo['audio']['bitrate_mode'] = 'vbr';
		} else {
			$ThisFileInfo['audio']['bitrate_mode'] = 'cbr';
		}

		$ThisFileInfo['mpc']['bitrate']   = ($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']) * 8 * 44100 / $thisfile_mpc_header['frame_count'] / 1152;
		$ThisFileInfo['audio']['bitrate'] = $ThisFileInfo['mpc']['bitrate'];
		$ThisFileInfo['audio']['encoder'] = 'SV'.$thisfile_mpc_header['stream_version_major'];

		return true;
	}


	function MPCprofileNameLookup($profileid) {
		static $MPCprofileNameLookup = array(
			0  => 'no profile',
			1  => 'Experimental',
			2  => 'unused',
			3  => 'unused',
			4  => 'unused',
			5  => 'below Telephone (q = 0.0)',
			6  => 'below Telephone (q = 1.0)',
			7  => 'Telephone (q = 2.0)',
			8  => 'Thumb (q = 3.0)',
			9  => 'Radio (q = 4.0)',
			10 => 'Standard (q = 5.0)',
			11 => 'Extreme (q = 6.0)',
			12 => 'Insane (q = 7.0)',
			13 => 'BrainDead (q = 8.0)',
			14 => 'above BrainDead (q = 9.0)',
			15 => 'above BrainDead (q = 10.0)'
		);
		return (isset($MPCprofileNameLookup[$profileid]) ? $MPCprofileNameLookup[$profileid] : 'invalid');
	}

	function MPCfrequencyLookup($frequencyid) {
		static $MPCfrequencyLookup = array(
			0 => 44100,
			1 => 48000,
			2 => 37800,
			3 => 32000
		);
		return (isset($MPCfrequencyLookup[$frequencyid]) ? $MPCfrequencyLookup[$frequencyid] : 'invalid');
	}

	function MPCpeakDBLookup($intvalue) {
		if ($intvalue > 0) {
			return ((log10($intvalue) / log10(2)) - 15) * 6;
		}
		return false;
	}

	function MPCencoderVersionLookup($encoderversion) {
		//Encoder version * 100  (106 = 1.06)
		//EncoderVersion % 10 == 0        Release (1.0)
		//EncoderVersion %  2 == 0        Beta (1.06)
		//EncoderVersion %  2 == 1        Alpha (1.05a...z)

		if ($encoderversion == 0) {
			// very old version, not known exactly which
			return 'Buschmann v1.7.0-v1.7.9 or Klemm v0.90-v1.05';
		}

		if (($encoderversion % 10) == 0) {

			// release version
			return number_format($encoderversion / 100, 2);

		} elseif (($encoderversion % 2) == 0) {

			// beta version
			return number_format($encoderversion / 100, 2).' beta';

		}

		// alpha version
		return number_format($encoderversion / 100, 2).' alpha';
	}

	function SV8variableLengthInteger($data, &$packetLength, $maxHandledPacketLength=9) {
		$packet_size = 0;
		for ($packetLength = 1; $packetLength <= $maxHandledPacketLength; $packetLength++) {
			// variable-length size field:
			//  bits, big-endian
			//  0xxx xxxx                                           - value 0 to  2^7-1
			//  1xxx xxxx  0xxx xxxx                                - value 0 to 2^14-1
			//  1xxx xxxx  1xxx xxxx  0xxx xxxx                     - value 0 to 2^21-1
			//  1xxx xxxx  1xxx xxxx  1xxx xxxx  0xxx xxxx          - value 0 to 2^28-1
			//  ...
			$thisbyte = ord(substr($data, ($packetLength - 1), 1));
			// look through bytes until find a byte with MSB==0
			$packet_size = ($packet_size << 7);
			$packet_size = ($packet_size | ($thisbyte & 0x7F));
			if (($thisbyte & 0x80) === 0) {
				break;
			}
			if ($packetLength >= $maxHandledPacketLength) {
				return false;
			}
		}
		return $packet_size;
	}

	function MPCsv8PacketName($packetKey) {
		static $MPCsv8PacketName = array();
		if (empty($MPCsv8PacketName)) {
			$MPCsv8PacketName = array(
				'AP' => 'Audio Packet',
				'CT' => 'Chapter Tag',
				'EI' => 'Encoder Info',
				'RG' => 'Replay Gain',
				'SE' => 'Stream End',
				'SH' => 'Stream Header',
				'SO' => 'Seek Table Offset',
				'ST' => 'Seek Table',
			);
		}
		return (isset($MPCsv8PacketName[$packetKey]) ? $MPCsv8PacketName[$packetKey] : $packetKey);
	}
}


?>