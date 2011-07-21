<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.audio.dts.php                                        //
// module for analyzing DTS Audio files                        //
// dependencies: NONE                                          //
//                                                             //
/////////////////////////////////////////////////////////////////


class getid3_dts
{

    function getid3_dts(&$fd, &$ThisFileInfo) {
        // Specs taken from "DTS Coherent Acoustics;Core and Extensions,  ETSI TS 102 114 V1.2.1 (2002-12)"
        // (http://pda.etsi.org/pda/queryform.asp)
        // With thanks to Gambit <macteam@users.sourceforge.net> http://mac.sourceforge.net/atl/

        $ThisFileInfo['fileformat'] = 'dts';

        fseek($fd, $ThisFileInfo['avdataoffset'], SEEK_SET);
        $DTSheader = fread($fd, 16);

        $ThisFileInfo['dts']['raw']['magic'] = getid3_lib::BigEndian2Int(substr($DTSheader,  0,  4));
        if ($ThisFileInfo['dts']['raw']['magic'] != 0x7FFE8001) {
            $ThisFileInfo['error'][] = 'Expecting "0x7FFE8001" at offset '.$ThisFileInfo['avdataoffset'].', found "0x'.str_pad(strtoupper(dechex($ThisFileInfo['dts']['raw']['magic'])), 8, '0', STR_PAD_LEFT).'"';
            unset($ThisFileInfo['fileformat']);
            unset($ThisFileInfo['dts']);
            return false;
        }

        $fhBS = getid3_lib::BigEndian2Bin(substr($DTSheader,  4,  12));
        $bsOffset = 0;
        $ThisFileInfo['dts']['raw']['frame_type']             =        bindec(substr($fhBS, $bsOffset,  1)); $bsOffset +=  1;
        $ThisFileInfo['dts']['raw']['deficit_samples']        =        bindec(substr($fhBS, $bsOffset,  5)); $bsOffset +=  5;
        $ThisFileInfo['dts']['flags']['crc_present']          = (bool) bindec(substr($fhBS, $bsOffset,  1)); $bsOffset +=  1;
        $ThisFileInfo['dts']['raw']['pcm_sample_blocks']      =        bindec(substr($fhBS, $bsOffset,  7)); $bsOffset +=  7;
        $ThisFileInfo['dts']['raw']['frame_byte_size']        =        bindec(substr($fhBS, $bsOffset, 14)); $bsOffset += 14;
        $ThisFileInfo['dts']['raw']['channel_arrangement']    =        bindec(substr($fhBS, $bsOffset,  6)); $bsOffset +=  6;
        $ThisFileInfo['dts']['raw']['sample_frequency']       =        bindec(substr($fhBS, $bsOffset,  4)); $bsOffset +=  4;
        $ThisFileInfo['dts']['raw']['bitrate']                =        bindec(substr($fhBS, $bsOffset,  5)); $bsOffset +=  5;
        $ThisFileInfo['dts']['flags']['embedded_downmix']     = (bool) bindec(substr($fhBS, $bsOffset,  1)); $bsOffset +=  1;
        $ThisFileInfo['dts']['flags']['dynamicrange']         = (bool) bindec(substr($fhBS, $bsOffset,  1)); $bsOffset +=  1;
        $ThisFileInfo['dts']['flags']['timestamp']            = (bool) bindec(substr($fhBS, $bsOffset,  1)); $bsOffset +=  1;
        $ThisFileInfo['dts']['flags']['auxdata']              = (bool) bindec(substr($fhBS, $bsOffset,  1)); $bsOffset +=  1;
        $ThisFileInfo['dts']['flags']['hdcd']                 = (bool) bindec(substr($fhBS, $bsOffset,  1)); $bsOffset +=  1;
        $ThisFileInfo['dts']['raw']['extension_audio']        =        bindec(substr($fhBS, $bsOffset,  3)); $bsOffset +=  3;
        $ThisFileInfo['dts']['flags']['extended_coding']      = (bool) bindec(substr($fhBS, $bsOffset,  1)); $bsOffset +=  1;
        $ThisFileInfo['dts']['flags']['audio_sync_insertion'] = (bool) bindec(substr($fhBS, $bsOffset,  1)); $bsOffset +=  1;
        $ThisFileInfo['dts']['raw']['lfe_effects']            =        bindec(substr($fhBS, $bsOffset,  2)); $bsOffset +=  2;
        $ThisFileInfo['dts']['flags']['predictor_history']    = (bool) bindec(substr($fhBS, $bsOffset,  1)); $bsOffset +=  1;
        if ($ThisFileInfo['dts']['flags']['crc_present']) {
            $ThisFileInfo['dts']['raw']['crc16']              =        bindec(substr($fhBS, $bsOffset, 16)); $bsOffset += 16;
        }
        $ThisFileInfo['dts']['flags']['mri_perfect_reconst']  = (bool) bindec(substr($fhBS, $bsOffset,  1)); $bsOffset +=  1;
        $ThisFileInfo['dts']['raw']['encoder_soft_version']   =        bindec(substr($fhBS, $bsOffset,  4)); $bsOffset +=  4;
        $ThisFileInfo['dts']['raw']['copy_history']           =        bindec(substr($fhBS, $bsOffset,  2)); $bsOffset +=  2;
        $ThisFileInfo['dts']['raw']['bits_per_sample']        =        bindec(substr($fhBS, $bsOffset,  2)); $bsOffset +=  2;
        $ThisFileInfo['dts']['flags']['surround_es']          = (bool) bindec(substr($fhBS, $bsOffset,  1)); $bsOffset +=  1;
        $ThisFileInfo['dts']['flags']['front_sum_diff']       = (bool) bindec(substr($fhBS, $bsOffset,  1)); $bsOffset +=  1;
        $ThisFileInfo['dts']['flags']['surround_sum_diff']    = (bool) bindec(substr($fhBS, $bsOffset,  1)); $bsOffset +=  1;
        $ThisFileInfo['dts']['raw']['dialog_normalization']   =        bindec(substr($fhBS, $bsOffset,  4)); $bsOffset +=  4;


        $ThisFileInfo['dts']['bitrate']              = $this->DTSbitrateLookup($ThisFileInfo['dts']['raw']['bitrate']);
        $ThisFileInfo['dts']['bits_per_sample']      = $this->DTSbitPerSampleLookup($ThisFileInfo['dts']['raw']['bits_per_sample']);
        $ThisFileInfo['dts']['sample_rate']          = $this->DTSsampleRateLookup($ThisFileInfo['dts']['raw']['sample_frequency']);
        $ThisFileInfo['dts']['dialog_normalization'] = $this->DTSdialogNormalization($ThisFileInfo['dts']['raw']['dialog_normalization'], $ThisFileInfo['dts']['raw']['encoder_soft_version']);
        $ThisFileInfo['dts']['flags']['lossless']    = (($ThisFileInfo['dts']['raw']['bitrate'] == 31) ? true  : false);
        $ThisFileInfo['dts']['bitrate_mode']         = (($ThisFileInfo['dts']['raw']['bitrate'] == 30) ? 'vbr' : 'cbr');
        $ThisFileInfo['dts']['channels']             = $this->DTSnumChannelsLookup($ThisFileInfo['dts']['raw']['channel_arrangement']);
        $ThisFileInfo['dts']['channel_arrangement']  = $this->DTSchannelArrangementLookup($ThisFileInfo['dts']['raw']['channel_arrangement']);

        $ThisFileInfo['audio']['dataformat']          = 'dts';
        $ThisFileInfo['audio']['lossless']            = $ThisFileInfo['dts']['flags']['lossless'];
        $ThisFileInfo['audio']['bitrate_mode']        = $ThisFileInfo['dts']['bitrate_mode'];
        $ThisFileInfo['audio']['bits_per_sample']     = $ThisFileInfo['dts']['bits_per_sample'];
        $ThisFileInfo['audio']['sample_rate']         = $ThisFileInfo['dts']['sample_rate'];
        $ThisFileInfo['audio']['channels']            = $ThisFileInfo['dts']['channels'];
        $ThisFileInfo['audio']['bitrate']             = $ThisFileInfo['dts']['bitrate'];
        if (isset($ThisFileInfo['avdataend'])) {
        	$ThisFileInfo['playtime_seconds']         = ($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']) / ($ThisFileInfo['dts']['bitrate'] / 8);
        }

        return true;
    }

    function DTSbitrateLookup($index) {
        $DTSbitrateLookup = array(
            0  => 32000,
            1  => 56000,
            2  => 64000,
            3  => 96000,
            4  => 112000,
            5  => 128000,
            6  => 192000,
            7  => 224000,
            8  => 256000,
            9  => 320000,
            10 => 384000,
            11 => 448000,
            12 => 512000,
            13 => 576000,
            14 => 640000,
            15 => 768000,
            16 => 960000,
            17 => 1024000,
            18 => 1152000,
            19 => 1280000,
            20 => 1344000,
            21 => 1408000,
            22 => 1411200,
            23 => 1472000,
            24 => 1536000,
            25 => 1920000,
            26 => 2048000,
            27 => 3072000,
            28 => 3840000,
            29 => 'open',
            30 => 'variable',
            31 => 'lossless'
        );
        return @$DTSbitrateLookup[$index];
    }

    function DTSsampleRateLookup($index) {
        $DTSsampleRateLookup = array(
            0  => 'invalid',
            1  => 8000,
            2  => 16000,
            3  => 32000,
            4  => 'invalid',
            5  => 'invalid',
            6  => 11025,
            7  => 22050,
            8  => 44100,
            9  => 'invalid',
            10 => 'invalid',
            11 => 12000,
            12 => 24000,
            13 => 48000,
            14 => 'invalid',
            15 => 'invalid'
        );
        return @$DTSsampleRateLookup[$index];
    }

    function DTSbitPerSampleLookup($index) {
        $DTSbitPerSampleLookup = array(
            0  => 16,
            1  => 20,
            2  => 24,
            3  => 24,
        );
        return @$DTSbitPerSampleLookup[$index];
    }

    function DTSnumChannelsLookup($index) {
        switch ($index) {
            case 0:
                return 1;
                break;
            case 1:
            case 2:
            case 3:
            case 4:
                return 2;
                break;
            case 5:
            case 6:
                return 3;
                break;
            case 7:
            case 8:
                return 4;
                break;
            case 9:
                return 5;
                break;
            case 10:
            case 11:
            case 12:
                return 6;
                break;
            case 13:
                return 7;
                break;
            case 14:
            case 15:
                return 8;
                break;
        }
        return false;
    }

    function DTSchannelArrangementLookup($index) {
        $DTSchannelArrangementLookup = array(
            0  => 'A',
            1  => 'A + B (dual mono)',
            2  => 'L + R (stereo)',
            3  => '(L+R) + (L-R) (sum-difference)',
            4  => 'LT + RT (left and right total)',
            5  => 'C + L + R',
            6  => 'L + R + S',
            7  => 'C + L + R + S',
            8  => 'L + R + SL + SR',
            9  => 'C + L + R + SL + SR',
            10 => 'CL + CR + L + R + SL + SR',
            11 => 'C + L + R+ LR + RR + OV',
            12 => 'CF + CR + LF + RF + LR + RR',
            13 => 'CL + C + CR + L + R + SL + SR',
            14 => 'CL + CR + L + R + SL1 + SL2 + SR1 + SR2',
            15 => 'CL + C+ CR + L + R + SL + S + SR',
        );
        return (@$DTSchannelArrangementLookup[$index] ? @$DTSchannelArrangementLookup[$index] : 'user-defined');
    }

    function DTSdialogNormalization($index, $version) {
        switch ($version) {
            case 7:
                return 0 - $index;
                break;
            case 6:
                return 0 - 16 - $index;
                break;
        }
        return false;
    }

}


?>