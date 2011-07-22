<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.audio.wavpack.php                                    //
// module for analyzing WavPack v4.0+ Audio files              //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////


class getid3_wavpack
{

	function getid3_wavpack(&$fd, &$ThisFileInfo) {

		fseek($fd, $ThisFileInfo['avdataoffset'], SEEK_SET);

		while (true) {

			$wavpackheader = fread($fd, 32);

			if (ftell($fd) >= $ThisFileInfo['avdataend']) {
				break;
			} elseif (feof($fd)) {
				break;
			} elseif (
				(@$ThisFileInfo['wavpack']['blockheader']['total_samples'] > 0) &&
				(@$ThisFileInfo['wavpack']['blockheader']['block_samples'] > 0) &&
				(!isset($ThisFileInfo['wavpack']['riff_trailer_size']) || ($ThisFileInfo['wavpack']['riff_trailer_size'] <= 0)) &&
				((@$ThisFileInfo['wavpack']['config_flags']['md5_checksum'] === false) || !empty($ThisFileInfo['md5_data_source']))) {
					break;
			}

			$blockheader_offset = ftell($fd) - 32;
			$blockheader_magic  =                              substr($wavpackheader,  0,  4);
			$blockheader_size   = getid3_lib::LittleEndian2Int(substr($wavpackheader,  4,  4));

			if ($blockheader_magic != 'wvpk') {
				$ThisFileInfo['error'][] = 'Expecting "wvpk" at offset '.$blockheader_offset.', found "'.$blockheader_magic.'"';
				if ((@$ThisFileInfo['audio']['dataformat'] != 'wavpack') && (@$ThisFileInfo['audio']['dataformat'] != 'wvc')) {
					unset($ThisFileInfo['fileformat']);
					unset($ThisFileInfo['audio']);
					unset($ThisFileInfo['wavpack']);
				}
				return false;
			}


			if ((@$ThisFileInfo['wavpack']['blockheader']['block_samples'] <= 0) ||
				(@$ThisFileInfo['wavpack']['blockheader']['total_samples'] <= 0)) {
				// Also, it is possible that the first block might not have
				// any samples (block_samples == 0) and in this case you should skip blocks
				// until you find one with samples because the other information (like
				// total_samples) are not guaranteed to be correct until (block_samples > 0)

				// Finally, I have defined a format for files in which the length is not known
				// (for example when raw files are created using pipes). In these cases
				// total_samples will be -1 and you must seek to the final block to determine
				// the total number of samples.


				$ThisFileInfo['audio']['dataformat']   = 'wavpack';
				$ThisFileInfo['fileformat']            = 'wavpack';
				$ThisFileInfo['audio']['lossless']     = true;
				$ThisFileInfo['audio']['bitrate_mode'] = 'vbr';

				$ThisFileInfo['wavpack']['blockheader']['offset'] = $blockheader_offset;
				$ThisFileInfo['wavpack']['blockheader']['magic']  = $blockheader_magic;
				$ThisFileInfo['wavpack']['blockheader']['size']   = $blockheader_size;

				if ($ThisFileInfo['wavpack']['blockheader']['size'] >= 0x100000) {
					$ThisFileInfo['error'][] = 'Expecting WavPack block size less than "0x100000", found "'.$ThisFileInfo['wavpack']['blockheader']['size'].'" at offset '.$ThisFileInfo['wavpack']['blockheader']['offset'];
					if ((@$ThisFileInfo['audio']['dataformat'] != 'wavpack') && (@$ThisFileInfo['audio']['dataformat'] != 'wvc')) {
						unset($ThisFileInfo['fileformat']);
						unset($ThisFileInfo['audio']);
						unset($ThisFileInfo['wavpack']);
					}
					return false;
				}

				$ThisFileInfo['wavpack']['blockheader']['minor_version'] = ord($wavpackheader{8});
				$ThisFileInfo['wavpack']['blockheader']['major_version'] = ord($wavpackheader{9});

				if (($ThisFileInfo['wavpack']['blockheader']['major_version'] != 4) ||
					(($ThisFileInfo['wavpack']['blockheader']['minor_version'] < 4) &&
					($ThisFileInfo['wavpack']['blockheader']['minor_version'] > 16))) {
						$ThisFileInfo['error'][] = 'Expecting WavPack version between "4.2" and "4.16", found version "'.$ThisFileInfo['wavpack']['blockheader']['major_version'].'.'.$ThisFileInfo['wavpack']['blockheader']['minor_version'].'" at offset '.$ThisFileInfo['wavpack']['blockheader']['offset'];
						if ((@$ThisFileInfo['audio']['dataformat'] != 'wavpack') && (@$ThisFileInfo['audio']['dataformat'] != 'wvc')) {
							unset($ThisFileInfo['fileformat']);
							unset($ThisFileInfo['audio']);
							unset($ThisFileInfo['wavpack']);
						}
						return false;
				}

				$ThisFileInfo['wavpack']['blockheader']['track_number']  = ord($wavpackheader{10}); // unused
				$ThisFileInfo['wavpack']['blockheader']['index_number']  = ord($wavpackheader{11}); // unused
				$ThisFileInfo['wavpack']['blockheader']['total_samples'] = getid3_lib::LittleEndian2Int(substr($wavpackheader, 12,  4));
				$ThisFileInfo['wavpack']['blockheader']['block_index']   = getid3_lib::LittleEndian2Int(substr($wavpackheader, 16,  4));
				$ThisFileInfo['wavpack']['blockheader']['block_samples'] = getid3_lib::LittleEndian2Int(substr($wavpackheader, 20,  4));
				$ThisFileInfo['wavpack']['blockheader']['flags_raw']     = getid3_lib::LittleEndian2Int(substr($wavpackheader, 24,  4));
				$ThisFileInfo['wavpack']['blockheader']['crc']           = getid3_lib::LittleEndian2Int(substr($wavpackheader, 28,  4));

				$ThisFileInfo['wavpack']['blockheader']['flags']['bytes_per_sample']     =    1 + ($ThisFileInfo['wavpack']['blockheader']['flags_raw'] & 0x00000003);
				$ThisFileInfo['wavpack']['blockheader']['flags']['mono']                 = (bool) ($ThisFileInfo['wavpack']['blockheader']['flags_raw'] & 0x00000004);
				$ThisFileInfo['wavpack']['blockheader']['flags']['hybrid']               = (bool) ($ThisFileInfo['wavpack']['blockheader']['flags_raw'] & 0x00000008);
				$ThisFileInfo['wavpack']['blockheader']['flags']['joint_stereo']         = (bool) ($ThisFileInfo['wavpack']['blockheader']['flags_raw'] & 0x00000010);
				$ThisFileInfo['wavpack']['blockheader']['flags']['cross_decorrelation']  = (bool) ($ThisFileInfo['wavpack']['blockheader']['flags_raw'] & 0x00000020);
				$ThisFileInfo['wavpack']['blockheader']['flags']['hybrid_noiseshape']    = (bool) ($ThisFileInfo['wavpack']['blockheader']['flags_raw'] & 0x00000040);
				$ThisFileInfo['wavpack']['blockheader']['flags']['ieee_32bit_float']     = (bool) ($ThisFileInfo['wavpack']['blockheader']['flags_raw'] & 0x00000080);
				$ThisFileInfo['wavpack']['blockheader']['flags']['int_32bit']            = (bool) ($ThisFileInfo['wavpack']['blockheader']['flags_raw'] & 0x00000100);
				$ThisFileInfo['wavpack']['blockheader']['flags']['hybrid_bitrate_noise'] = (bool) ($ThisFileInfo['wavpack']['blockheader']['flags_raw'] & 0x00000200);
				$ThisFileInfo['wavpack']['blockheader']['flags']['hybrid_balance_noise'] = (bool) ($ThisFileInfo['wavpack']['blockheader']['flags_raw'] & 0x00000400);
				$ThisFileInfo['wavpack']['blockheader']['flags']['multichannel_initial'] = (bool) ($ThisFileInfo['wavpack']['blockheader']['flags_raw'] & 0x00000800);
				$ThisFileInfo['wavpack']['blockheader']['flags']['multichannel_final']   = (bool) ($ThisFileInfo['wavpack']['blockheader']['flags_raw'] & 0x00001000);

				$ThisFileInfo['audio']['lossless'] = !$ThisFileInfo['wavpack']['blockheader']['flags']['hybrid'];
			}

			while (!feof($fd) && (ftell($fd) < ($blockheader_offset + $blockheader_size + 8))) {

				$metablock = array('offset'=>ftell($fd));
				$metablockheader = fread($fd, 2);
				if (feof($fd)) {
					break;
				}
				$metablock['id'] = ord($metablockheader{0});
				$metablock['function_id'] = ($metablock['id'] & 0x3F);
				$metablock['function_name'] = $this->WavPackMetablockNameLookup($metablock['function_id']);

				// The 0x20 bit in the id of the meta subblocks (which is defined as
				// ID_OPTIONAL_DATA) is a permanent part of the id. The idea is that
				// if a decoder encounters an id that it does not know about, it uses
				// that "ID_OPTIONAL_DATA" flag to determine what to do. If it is set
				// then the decoder simply ignores the metadata, but if it is zero
				// then the decoder should quit because it means that an understanding
				// of the metadata is required to correctly decode the audio.
				$metablock['non_decoder'] = (bool) ($metablock['id'] & 0x20);

				$metablock['padded_data'] = (bool) ($metablock['id'] & 0x40);
				$metablock['large_block'] = (bool) ($metablock['id'] & 0x80);
				if ($metablock['large_block']) {
					$metablockheader .= fread($fd, 2);
				}
				$metablock['size'] = getid3_lib::LittleEndian2Int(substr($metablockheader, 1)) * 2; // size is stored in words
				$metablock['data'] = null;

				if ($metablock['size'] > 0) {

					switch ($metablock['function_id']) {
						case 0x21: // ID_RIFF_HEADER
						case 0x22: // ID_RIFF_TRAILER
						case 0x23: // ID_REPLAY_GAIN
						case 0x24: // ID_CUESHEET
						case 0x25: // ID_CONFIG_BLOCK
						case 0x26: // ID_MD5_CHECKSUM
							$metablock['data'] = fread($fd, $metablock['size']);

							if ($metablock['padded_data']) {
								// padded to the nearest even byte
								$metablock['size']--;
								$metablock['data'] = substr($metablock['data'], 0, -1);
							}
							break;

						case 0x00: // ID_DUMMY
						case 0x01: // ID_ENCODER_INFO
						case 0x02: // ID_DECORR_TERMS
						case 0x03: // ID_DECORR_WEIGHTS
						case 0x04: // ID_DECORR_SAMPLES
						case 0x05: // ID_ENTROPY_VARS
						case 0x06: // ID_HYBRID_PROFILE
						case 0x07: // ID_SHAPING_WEIGHTS
						case 0x08: // ID_FLOAT_INFO
						case 0x09: // ID_INT32_INFO
						case 0x0A: // ID_WV_BITSTREAM
						case 0x0B: // ID_WVC_BITSTREAM
						case 0x0C: // ID_WVX_BITSTREAM
						case 0x0D: // ID_CHANNEL_INFO
							fseek($fd, $metablock['offset'] + ($metablock['large_block'] ? 4 : 2) + $metablock['size'], SEEK_SET);
							break;

						default:
							$ThisFileInfo['warning'][] = 'Unexpected metablock type "0x'.str_pad(dechex($metablock['function_id']), 2, '0', STR_PAD_LEFT).'" at offset '.$metablock['offset'];
							fseek($fd, $metablock['offset'] + ($metablock['large_block'] ? 4 : 2) + $metablock['size'], SEEK_SET);
							break;
					}

					switch ($metablock['function_id']) {
						case 0x21: // ID_RIFF_HEADER
							getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.audio-video.riff.php', __FILE__, true);
							$original_wav_filesize = getid3_lib::LittleEndian2Int(substr($metablock['data'], 4, 4));
							getid3_riff::ParseRIFFdata($metablock['data'], $ParsedRIFFheader);
							$metablock['riff'] = $ParsedRIFFheader['riff'];
							$metablock['riff']['original_filesize'] = $original_wav_filesize;
							$ThisFileInfo['wavpack']['riff_trailer_size'] = $original_wav_filesize - $metablock['riff']['WAVE']['data'][0]['size'] - $metablock['riff']['header_size'];

							$ThisFileInfo['audio']['sample_rate'] = $ParsedRIFFheader['riff']['raw']['fmt ']['nSamplesPerSec'];
							$ThisFileInfo['playtime_seconds']     = $ThisFileInfo['wavpack']['blockheader']['total_samples'] / $ThisFileInfo['audio']['sample_rate'];

							// Safe RIFF header in case there's a RIFF footer later
							$metablockRIFFheader = $metablock['data'];
							break;


						case 0x22: // ID_RIFF_TRAILER
							$metablockRIFFfooter = $metablockRIFFheader.$metablock['data'];
							getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.audio-video.riff.php', __FILE__, true);

							$ftell_old = ftell($fd);
							$startoffset = $metablock['offset'] + ($metablock['large_block'] ? 4 : 2);
							$ParsedRIFFfooter = array('avdataend'=>$ThisFileInfo['avdataend'], 'fileformat'=>'riff', 'error'=>array(), 'warning'=>array());
							$metablock['riff'] = getid3_riff::ParseRIFF($fd, $startoffset, $startoffset + $metablock['size'], $ParsedRIFFfooter);
							fseek($fd, $ftell_old, SEEK_SET);

							if (!empty($metablock['riff']['INFO'])) {
								getid3_riff::RIFFcommentsParse($metablock['riff']['INFO'], $metablock['comments']);
								$ThisFileInfo['tags']['riff'] = $metablock['comments'];
							}
							break;


						case 0x23: // ID_REPLAY_GAIN
							$ThisFileInfo['warning'][] = 'WavPack "Replay Gain" contents not yet handled by getID3() in metablock at offset '.$metablock['offset'];
							break;


						case 0x24: // ID_CUESHEET
							$ThisFileInfo['warning'][] = 'WavPack "Cuesheet" contents not yet handled by getID3() in metablock at offset '.$metablock['offset'];
							break;


						case 0x25: // ID_CONFIG_BLOCK
							$metablock['flags_raw'] = getid3_lib::LittleEndian2Int(substr($metablock['data'], 0, 3));

							$metablock['flags']['adobe_mode']     = (bool) ($metablock['flags_raw'] & 0x000001); // "adobe" mode for 32-bit floats
							$metablock['flags']['fast_flag']      = (bool) ($metablock['flags_raw'] & 0x000002); // fast mode
							$metablock['flags']['very_fast_flag'] = (bool) ($metablock['flags_raw'] & 0x000004); // double fast
							$metablock['flags']['high_flag']      = (bool) ($metablock['flags_raw'] & 0x000008); // high quality mode
							$metablock['flags']['very_high_flag'] = (bool) ($metablock['flags_raw'] & 0x000010); // double high (not used yet)
							$metablock['flags']['bitrate_kbps']   = (bool) ($metablock['flags_raw'] & 0x000020); // bitrate is kbps, not bits / sample
							$metablock['flags']['auto_shaping']   = (bool) ($metablock['flags_raw'] & 0x000040); // automatic noise shaping
							$metablock['flags']['shape_override'] = (bool) ($metablock['flags_raw'] & 0x000080); // shaping mode specified
							$metablock['flags']['joint_override'] = (bool) ($metablock['flags_raw'] & 0x000100); // joint-stereo mode specified
							$metablock['flags']['copy_time']      = (bool) ($metablock['flags_raw'] & 0x000200); // copy file-time from source
							$metablock['flags']['create_exe']     = (bool) ($metablock['flags_raw'] & 0x000400); // create executable
							$metablock['flags']['create_wvc']     = (bool) ($metablock['flags_raw'] & 0x000800); // create correction file
							$metablock['flags']['optimize_wvc']   = (bool) ($metablock['flags_raw'] & 0x001000); // maximize bybrid compression
							$metablock['flags']['quality_mode']   = (bool) ($metablock['flags_raw'] & 0x002000); // psychoacoustic quality mode
							$metablock['flags']['raw_flag']       = (bool) ($metablock['flags_raw'] & 0x004000); // raw mode (not implemented yet)
							$metablock['flags']['calc_noise']     = (bool) ($metablock['flags_raw'] & 0x008000); // calc noise in hybrid mode
							$metablock['flags']['lossy_mode']     = (bool) ($metablock['flags_raw'] & 0x010000); // obsolete (for information)
							$metablock['flags']['extra_mode']     = (bool) ($metablock['flags_raw'] & 0x020000); // extra processing mode
							$metablock['flags']['skip_wvx']       = (bool) ($metablock['flags_raw'] & 0x040000); // no wvx stream w/ floats & big ints
							$metablock['flags']['md5_checksum']   = (bool) ($metablock['flags_raw'] & 0x080000); // compute & store MD5 signature
							$metablock['flags']['quiet_mode']     = (bool) ($metablock['flags_raw'] & 0x100000); // don't report progress %

							$ThisFileInfo['wavpack']['config_flags'] = $metablock['flags'];


							if ($ThisFileInfo['wavpack']['blockheader']['flags']['hybrid']) {
								@$ThisFileInfo['audio']['encoder_options'] .= ' -b???';
							}
							@$ThisFileInfo['audio']['encoder_options'] .= ($metablock['flags']['adobe_mode']     ? ' -a' : '');
							@$ThisFileInfo['audio']['encoder_options'] .= ($metablock['flags']['optimize_wvc']   ? ' -cc' : '');
							@$ThisFileInfo['audio']['encoder_options'] .= ($metablock['flags']['create_exe']     ? ' -e' : '');
							@$ThisFileInfo['audio']['encoder_options'] .= ($metablock['flags']['fast_flag']      ? ' -f' : '');
							@$ThisFileInfo['audio']['encoder_options'] .= ($metablock['flags']['joint_override'] ? ' -j?' : '');
							@$ThisFileInfo['audio']['encoder_options'] .= ($metablock['flags']['high_flag']      ? ' -h' : '');
							@$ThisFileInfo['audio']['encoder_options'] .= ($metablock['flags']['md5_checksum']   ? ' -m' : '');
							@$ThisFileInfo['audio']['encoder_options'] .= ($metablock['flags']['calc_noise']     ? ' -n' : '');
							@$ThisFileInfo['audio']['encoder_options'] .= ($metablock['flags']['shape_override'] ? ' -s?' : '');
							@$ThisFileInfo['audio']['encoder_options'] .= ($metablock['flags']['extra_mode']     ? ' -x?' : '');
							if (@$ThisFileInfo['audio']['encoder_options']) {
							    $ThisFileInfo['audio']['encoder_options'] = trim(@$ThisFileInfo['audio']['encoder_options']);
							}
							elseif (isset($ThisFileInfo['audio']['encoder_options'])) {
							    unset($ThisFileInfo['audio']['encoder_options']);
							}
							break;


						case 0x26: // ID_MD5_CHECKSUM
							if (strlen($metablock['data']) == 16) {
								$ThisFileInfo['md5_data_source'] = strtolower(getid3_lib::PrintHexBytes($metablock['data'], true, false, false));
							} else {
								$ThisFileInfo['warning'][] = 'Expecting 16 bytes of WavPack "MD5 Checksum" in metablock at offset '.$metablock['offset'].', but found '.strlen($metablock['data']).' bytes';
							}
							break;


						case 0x00: // ID_DUMMY
						case 0x01: // ID_ENCODER_INFO
						case 0x02: // ID_DECORR_TERMS
						case 0x03: // ID_DECORR_WEIGHTS
						case 0x04: // ID_DECORR_SAMPLES
						case 0x05: // ID_ENTROPY_VARS
						case 0x06: // ID_HYBRID_PROFILE
						case 0x07: // ID_SHAPING_WEIGHTS
						case 0x08: // ID_FLOAT_INFO
						case 0x09: // ID_INT32_INFO
						case 0x0A: // ID_WV_BITSTREAM
						case 0x0B: // ID_WVC_BITSTREAM
						case 0x0C: // ID_WVX_BITSTREAM
						case 0x0D: // ID_CHANNEL_INFO
							unset($metablock);
							break;
					}

				}
				if (!empty($metablock)) {
					$ThisFileInfo['wavpack']['metablocks'][] = $metablock;
				}

			}

		}

		$ThisFileInfo['audio']['encoder']         = 'WavPack v'.$ThisFileInfo['wavpack']['blockheader']['major_version'].'.'.str_pad($ThisFileInfo['wavpack']['blockheader']['minor_version'], 2, '0', STR_PAD_LEFT);
		$ThisFileInfo['audio']['bits_per_sample'] = $ThisFileInfo['wavpack']['blockheader']['flags']['bytes_per_sample'] * 8;
		$ThisFileInfo['audio']['channels']        = ($ThisFileInfo['wavpack']['blockheader']['flags']['mono'] ? 1 : 2);

		if (@$ThisFileInfo['playtime_seconds']) {

			$ThisFileInfo['audio']['bitrate']     = (($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']) * 8) / $ThisFileInfo['playtime_seconds'];

		} else {

			$ThisFileInfo['audio']['dataformat']  = 'wvc';

		}

		return true;
	}


	function WavPackMetablockNameLookup(&$id) {
		static $WavPackMetablockNameLookup = array(
			0x00 => 'Dummy',
			0x01 => 'Encoder Info',
			0x02 => 'Decorrelation Terms',
			0x03 => 'Decorrelation Weights',
			0x04 => 'Decorrelation Samples',
			0x05 => 'Entropy Variables',
			0x06 => 'Hybrid Profile',
			0x07 => 'Shaping Weights',
			0x08 => 'Float Info',
			0x09 => 'Int32 Info',
			0x0A => 'WV Bitstream',
			0x0B => 'WVC Bitstream',
			0x0C => 'WVX Bitstream',
			0x0D => 'Channel Info',
			0x21 => 'RIFF header',
			0x22 => 'RIFF trailer',
			0x23 => 'Replay Gain',
			0x24 => 'Cuesheet',
			0x25 => 'Config Block',
			0x26 => 'MD5 Checksum',
		);
		return (@$WavPackMetablockNameLookup[$id]);
	}

}


?>