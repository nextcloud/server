<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.audio-video.matriska.php                             //
// module for analyzing Matroska containers                    //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////


define('EBML_ID_CHAPTERS',                  0x0043A770); // [10][43][A7][70] -- A system to define basic menus and partition data. For more detailed information, look at the Chapters Explanation.
define('EBML_ID_SEEKHEAD',                  0x014D9B74); // [11][4D][9B][74] -- Contains the position of other level 1 elements.
define('EBML_ID_TAGS',                      0x0254C367); // [12][54][C3][67] -- Element containing elements specific to Tracks/Chapters. A list of valid tags can be found here.
define('EBML_ID_INFO',                      0x0549A966); // [15][49][A9][66] -- Contains miscellaneous general information and statistics on the file.
define('EBML_ID_TRACKS',                    0x0654AE6B); // [16][54][AE][6B] -- A top-level block of information with many tracks described.
define('EBML_ID_SEGMENT',                   0x08538067); // [18][53][80][67] -- This element contains all other top-level (level 1) elements. Typically a Matroska file is composed of 1 segment.
define('EBML_ID_ATTACHMENTS',               0x0941A469); // [19][41][A4][69] -- Contain attached files.
define('EBML_ID_EBML',                      0x0A45DFA3); // [1A][45][DF][A3] -- Set the EBML characteristics of the data to follow. Each EBML document has to start with this.
define('EBML_ID_CUES',                      0x0C53BB6B); // [1C][53][BB][6B] -- A top-level element to speed seeking access. All entries are local to the segment.
define('EBML_ID_CLUSTER',                   0x0F43B675); // [1F][43][B6][75] -- The lower level element containing the (monolithic) Block structure.
define('EBML_ID_LANGUAGE',                    0x02B59C); //     [22][B5][9C] -- Specifies the language of the track in the Matroska languages form.
define('EBML_ID_TRACKTIMECODESCALE',          0x03314F); //     [23][31][4F] -- The scale to apply on this track to work at normal speed in relation with other tracks (mostly used to adjust video speed when the audio length differs).
define('EBML_ID_DEFAULTDURATION',             0x03E383); //     [23][E3][83] -- Number of nanoseconds (i.e. not scaled) per frame.
define('EBML_ID_CODECNAME',                   0x058688); //     [25][86][88] -- A human-readable string specifying the codec.
define('EBML_ID_CODECDOWNLOADURL',            0x06B240); //     [26][B2][40] -- A URL to download about the codec used.
define('EBML_ID_TIMECODESCALE',               0x0AD7B1); //     [2A][D7][B1] -- Timecode scale in nanoseconds (1.000.000 means all timecodes in the segment are expressed in milliseconds).
define('EBML_ID_COLOURSPACE',                 0x0EB524); //     [2E][B5][24] -- Same value as in AVI (32 bits).
define('EBML_ID_GAMMAVALUE',                  0x0FB523); //     [2F][B5][23] -- Gamma Value.
define('EBML_ID_CODECSETTINGS',               0x1A9697); //     [3A][96][97] -- A string describing the encoding setting used.
define('EBML_ID_CODECINFOURL',                0x1B4040); //     [3B][40][40] -- A URL to find information about the codec used.
define('EBML_ID_PREVFILENAME',                0x1C83AB); //     [3C][83][AB] -- An escaped filename corresponding to the previous segment.
define('EBML_ID_PREVUID',                     0x1CB923); //     [3C][B9][23] -- A unique ID to identify the previous chained segment (128 bits).
define('EBML_ID_NEXTFILENAME',                0x1E83BB); //     [3E][83][BB] -- An escaped filename corresponding to the next segment.
define('EBML_ID_NEXTUID',                     0x1EB923); //     [3E][B9][23] -- A unique ID to identify the next chained segment (128 bits).
define('EBML_ID_CONTENTCOMPALGO',               0x0254); //         [42][54] -- The compression algorithm used. Algorithms that have been specified so far are:
define('EBML_ID_CONTENTCOMPSETTINGS',           0x0255); //         [42][55] -- Settings that might be needed by the decompressor. For Header Stripping (ContentCompAlgo=3), the bytes that were removed from the beggining of each frames of the track.
define('EBML_ID_DOCTYPE',                       0x0282); //         [42][82] -- A string that describes the type of document that follows this EBML header ('matroska' in our case).
define('EBML_ID_DOCTYPEREADVERSION',            0x0285); //         [42][85] -- The minimum DocType version an interpreter has to support to read this file.
define('EBML_ID_EBMLVERSION',                   0x0286); //         [42][86] -- The version of EBML parser used to create the file.
define('EBML_ID_DOCTYPEVERSION',                0x0287); //         [42][87] -- The version of DocType interpreter used to create the file.
define('EBML_ID_EBMLMAXIDLENGTH',               0x02F2); //         [42][F2] -- The maximum length of the IDs you'll find in this file (4 or less in Matroska).
define('EBML_ID_EBMLMAXSIZELENGTH',             0x02F3); //         [42][F3] -- The maximum length of the sizes you'll find in this file (8 or less in Matroska). This does not override the element size indicated at the beginning of an element. Elements that have an indicated size which is larger than what is allowed by EBMLMaxSizeLength shall be considered invalid.
define('EBML_ID_EBMLREADVERSION',               0x02F7); //         [42][F7] -- The minimum EBML version a parser has to support to read this file.
define('EBML_ID_CHAPLANGUAGE',                  0x037C); //         [43][7C] -- The languages corresponding to the string, in the bibliographic ISO-639-2 form.
define('EBML_ID_CHAPCOUNTRY',                   0x037E); //         [43][7E] -- The countries corresponding to the string, same 2 octets as in Internet domains.
define('EBML_ID_SEGMENTFAMILY',                 0x0444); //         [44][44] -- A randomly generated unique ID that all segments related to each other must use (128 bits).
define('EBML_ID_DATEUTC',                       0x0461); //         [44][61] -- Date of the origin of timecode (value 0), i.e. production date.
define('EBML_ID_TAGLANGUAGE',                   0x047A); //         [44][7A] -- Specifies the language of the tag specified, in the Matroska languages form.
define('EBML_ID_TAGDEFAULT',                    0x0484); //         [44][84] -- Indication to know if this is the default/original language to use for the given tag.
define('EBML_ID_TAGBINARY',                     0x0485); //         [44][85] -- The values of the Tag if it is binary. Note that this cannot be used in the same SimpleTag as TagString.
define('EBML_ID_TAGSTRING',                     0x0487); //         [44][87] -- The value of the Tag.
define('EBML_ID_DURATION',                      0x0489); //         [44][89] -- Duration of the segment (based on TimecodeScale).
define('EBML_ID_CHAPPROCESSPRIVATE',            0x050D); //         [45][0D] -- Some optional data attached to the ChapProcessCodecID information. For ChapProcessCodecID = 1, it is the "DVD level" equivalent.
define('EBML_ID_CHAPTERFLAGENABLED',            0x0598); //         [45][98] -- Specify wether the chapter is enabled. It can be enabled/disabled by a Control Track. When disabled, the movie should skip all the content between the TimeStart and TimeEnd of this chapter.
define('EBML_ID_TAGNAME',                       0x05A3); //         [45][A3] -- The name of the Tag that is going to be stored.
define('EBML_ID_EDITIONENTRY',                  0x05B9); //         [45][B9] -- Contains all information about a segment edition.
define('EBML_ID_EDITIONUID',                    0x05BC); //         [45][BC] -- A unique ID to identify the edition. It's useful for tagging an edition.
define('EBML_ID_EDITIONFLAGHIDDEN',             0x05BD); //         [45][BD] -- If an edition is hidden (1), it should not be available to the user interface (but still to Control Tracks).
define('EBML_ID_EDITIONFLAGDEFAULT',            0x05DB); //         [45][DB] -- If a flag is set (1) the edition should be used as the default one.
define('EBML_ID_EDITIONFLAGORDERED',            0x05DD); //         [45][DD] -- Specify if the chapters can be defined multiple times and the order to play them is enforced.
define('EBML_ID_FILEDATA',                      0x065C); //         [46][5C] -- The data of the file.
define('EBML_ID_FILEMIMETYPE',                  0x0660); //         [46][60] -- MIME type of the file.
define('EBML_ID_FILENAME',                      0x066E); //         [46][6E] -- Filename of the attached file.
define('EBML_ID_FILEREFERRAL',                  0x0675); //         [46][75] -- A binary value that a track/codec can refer to when the attachment is needed.
define('EBML_ID_FILEDESCRIPTION',               0x067E); //         [46][7E] -- A human-friendly name for the attached file.
define('EBML_ID_FILEUID',                       0x06AE); //         [46][AE] -- Unique ID representing the file, as random as possible.
define('EBML_ID_CONTENTENCALGO',                0x07E1); //         [47][E1] -- The encryption algorithm used. The value '0' means that the contents have not been encrypted but only signed. Predefined values:
define('EBML_ID_CONTENTENCKEYID',               0x07E2); //         [47][E2] -- For public key algorithms this is the ID of the public key the the data was encrypted with.
define('EBML_ID_CONTENTSIGNATURE',              0x07E3); //         [47][E3] -- A cryptographic signature of the contents.
define('EBML_ID_CONTENTSIGKEYID',               0x07E4); //         [47][E4] -- This is the ID of the private key the data was signed with.
define('EBML_ID_CONTENTSIGALGO',                0x07E5); //         [47][E5] -- The algorithm used for the signature. A value of '0' means that the contents have not been signed but only encrypted. Predefined values:
define('EBML_ID_CONTENTSIGHASHALGO',            0x07E6); //         [47][E6] -- The hash algorithm used for the signature. A value of '0' means that the contents have not been signed but only encrypted. Predefined values:
define('EBML_ID_MUXINGAPP',                     0x0D80); //         [4D][80] -- Muxing application or library ("libmatroska-0.4.3").
define('EBML_ID_SEEK',                          0x0DBB); //         [4D][BB] -- Contains a single seek entry to an EBML element.
define('EBML_ID_CONTENTENCODINGORDER',          0x1031); //         [50][31] -- Tells when this modification was used during encoding/muxing starting with 0 and counting upwards. The decoder/demuxer has to start with the highest order number it finds and work its way down. This value has to be unique over all ContentEncodingOrder elements in the segment.
define('EBML_ID_CONTENTENCODINGSCOPE',          0x1032); //         [50][32] -- A bit field that describes which elements have been modified in this way. Values (big endian) can be OR'ed. Possible values:
define('EBML_ID_CONTENTENCODINGTYPE',           0x1033); //         [50][33] -- A value describing what kind of transformation has been done. Possible values:
define('EBML_ID_CONTENTCOMPRESSION',            0x1034); //         [50][34] -- Settings describing the compression used. Must be present if the value of ContentEncodingType is 0 and absent otherwise. Each block must be decompressable even if no previous block is available in order not to prevent seeking.
define('EBML_ID_CONTENTENCRYPTION',             0x1035); //         [50][35] -- Settings describing the encryption used. Must be present if the value of ContentEncodingType is 1 and absent otherwise.
define('EBML_ID_CUEREFNUMBER',                  0x135F); //         [53][5F] -- Number of the referenced Block of Track X in the specified Cluster.
define('EBML_ID_NAME',                          0x136E); //         [53][6E] -- A human-readable track name.
define('EBML_ID_CUEBLOCKNUMBER',                0x1378); //         [53][78] -- Number of the Block in the specified Cluster.
define('EBML_ID_TRACKOFFSET',                   0x137F); //         [53][7F] -- A value to add to the Block's Timecode. This can be used to adjust the playback offset of a track.
define('EBML_ID_SEEKID',                        0x13AB); //         [53][AB] -- The binary ID corresponding to the element name.
define('EBML_ID_SEEKPOSITION',                  0x13AC); //         [53][AC] -- The position of the element in the segment in octets (0 = first level 1 element).
define('EBML_ID_STEREOMODE',                    0x13B8); //         [53][B8] -- Stereo-3D video mode on 2 bits (0: mono, 1: right eye, 2: left eye, 3: both eyes).
define('EBML_ID_PIXELCROPBOTTOM',               0x14AA); //         [54][AA] -- The number of video pixels to remove at the bottom of the image (for HDTV content).
define('EBML_ID_DISPLAYWIDTH',                  0x14B0); //         [54][B0] -- Width of the video frames to display.
define('EBML_ID_DISPLAYUNIT',                   0x14B2); //         [54][B2] -- Type of the unit for DisplayWidth/Height (0: pixels, 1: centimeters, 2: inches).
define('EBML_ID_ASPECTRATIOTYPE',               0x14B3); //         [54][B3] -- Specify the possible modifications to the aspect ratio (0: free resizing, 1: keep aspect ratio, 2: fixed).
define('EBML_ID_DISPLAYHEIGHT',                 0x14BA); //         [54][BA] -- Height of the video frames to display.
define('EBML_ID_PIXELCROPTOP',                  0x14BB); //         [54][BB] -- The number of video pixels to remove at the top of the image.
define('EBML_ID_PIXELCROPLEFT',                 0x14CC); //         [54][CC] -- The number of video pixels to remove on the left of the image.
define('EBML_ID_PIXELCROPRIGHT',                0x14DD); //         [54][DD] -- The number of video pixels to remove on the right of the image.
define('EBML_ID_FLAGFORCED',                    0x15AA); //         [55][AA] -- Set if that track MUST be used during playback. There can be many forced track for a kind (audio, video or subs), the player should select the one which language matches the user preference or the default + forced track. Overlay MAY happen between a forced and non-forced track of the same kind.
define('EBML_ID_MAXBLOCKADDITIONID',            0x15EE); //         [55][EE] -- The maximum value of BlockAddID. A value 0 means there is no BlockAdditions for this track.
define('EBML_ID_WRITINGAPP',                    0x1741); //         [57][41] -- Writing application ("mkvmerge-0.3.3").
define('EBML_ID_CLUSTERSILENTTRACKS',           0x1854); //         [58][54] -- The list of tracks that are not used in that part of the stream. It is useful when using overlay tracks on seeking. Then you should decide what track to use.
define('EBML_ID_CLUSTERSILENTTRACKNUMBER',      0x18D7); //         [58][D7] -- One of the track number that are not used from now on in the stream. It could change later if not specified as silent in a further Cluster.
define('EBML_ID_ATTACHEDFILE',                  0x21A7); //         [61][A7] -- An attached file.
define('EBML_ID_CONTENTENCODING',               0x2240); //         [62][40] -- Settings for one content encoding like compression or encryption.
define('EBML_ID_BITDEPTH',                      0x2264); //         [62][64] -- Bits per sample, mostly used for PCM.
define('EBML_ID_CODECPRIVATE',                  0x23A2); //         [63][A2] -- Private data only known to the codec.
define('EBML_ID_TARGETS',                       0x23C0); //         [63][C0] -- Contain all UIDs where the specified meta data apply. It is void to describe everything in the segment.
define('EBML_ID_CHAPTERPHYSICALEQUIV',          0x23C3); //         [63][C3] -- Specify the physical equivalent of this ChapterAtom like "DVD" (60) or "SIDE" (50), see complete list of values.
define('EBML_ID_TAGCHAPTERUID',                 0x23C4); //         [63][C4] -- A unique ID to identify the Chapter(s) the tags belong to. If the value is 0 at this level, the tags apply to all chapters in the Segment.
define('EBML_ID_TAGTRACKUID',                   0x23C5); //         [63][C5] -- A unique ID to identify the Track(s) the tags belong to. If the value is 0 at this level, the tags apply to all tracks in the Segment.
define('EBML_ID_ATTACHMENTUID',                 0x23C6); //         [63][C6] -- A unique ID to identify the Attachment(s) the tags belong to. If the value is 0 at this level, the tags apply to all the attachments in the Segment.
define('EBML_ID_TAGEDITIONUID',                 0x23C9); //         [63][C9] -- A unique ID to identify the EditionEntry(s) the tags belong to. If the value is 0 at this level, the tags apply to all editions in the Segment.
define('EBML_ID_TARGETTYPE',                    0x23CA); //         [63][CA] -- An informational string that can be used to display the logical level of the target like "ALBUM", "TRACK", "MOVIE", "CHAPTER", etc (see TargetType).
define('EBML_ID_TRACKTRANSLATE',                0x2624); //         [66][24] -- The track identification for the given Chapter Codec.
define('EBML_ID_TRACKTRANSLATETRACKID',         0x26A5); //         [66][A5] -- The binary value used to represent this track in the chapter codec data. The format depends on the ChapProcessCodecID used.
define('EBML_ID_TRACKTRANSLATECODEC',           0x26BF); //         [66][BF] -- The chapter codec using this ID (0: Matroska Script, 1: DVD-menu).
define('EBML_ID_TRACKTRANSLATEEDITIONUID',      0x26FC); //         [66][FC] -- Specify an edition UID on which this translation applies. When not specified, it means for all editions found in the segment.
define('EBML_ID_SIMPLETAG',                     0x27C8); //         [67][C8] -- Contains general information about the target.
define('EBML_ID_TARGETTYPEVALUE',               0x28CA); //         [68][CA] -- A number to indicate the logical level of the target (see TargetType).
define('EBML_ID_CHAPPROCESSCOMMAND',            0x2911); //         [69][11] -- Contains all the commands associated to the Atom.
define('EBML_ID_CHAPPROCESSTIME',               0x2922); //         [69][22] -- Defines when the process command should be handled (0: during the whole chapter, 1: before starting playback, 2: after playback of the chapter).
define('EBML_ID_CHAPTERTRANSLATE',              0x2924); //         [69][24] -- A tuple of corresponding ID used by chapter codecs to represent this segment.
define('EBML_ID_CHAPPROCESSDATA',               0x2933); //         [69][33] -- Contains the command information. The data should be interpreted depending on the ChapProcessCodecID value. For ChapProcessCodecID = 1, the data correspond to the binary DVD cell pre/post commands.
define('EBML_ID_CHAPPROCESS',                   0x2944); //         [69][44] -- Contains all the commands associated to the Atom.
define('EBML_ID_CHAPPROCESSCODECID',            0x2955); //         [69][55] -- Contains the type of the codec used for the processing. A value of 0 means native Matroska processing (to be defined), a value of 1 means the DVD command set is used. More codec IDs can be added later.
define('EBML_ID_CHAPTERTRANSLATEID',            0x29A5); //         [69][A5] -- The binary value used to represent this segment in the chapter codec data. The format depends on the ChapProcessCodecID used.
define('EBML_ID_CHAPTERTRANSLATECODEC',         0x29BF); //         [69][BF] -- The chapter codec using this ID (0: Matroska Script, 1: DVD-menu).
define('EBML_ID_CHAPTERTRANSLATEEDITIONUID',    0x29FC); //         [69][FC] -- Specify an edition UID on which this correspondance applies. When not specified, it means for all editions found in the segment.
define('EBML_ID_CONTENTENCODINGS',              0x2D80); //         [6D][80] -- Settings for several content encoding mechanisms like compression or encryption.
define('EBML_ID_MINCACHE',                      0x2DE7); //         [6D][E7] -- The minimum number of frames a player should be able to cache during playback. If set to 0, the reference pseudo-cache system is not used.
define('EBML_ID_MAXCACHE',                      0x2DF8); //         [6D][F8] -- The maximum cache size required to store referenced frames in and the current frame. 0 means no cache is needed.
define('EBML_ID_CHAPTERSEGMENTUID',             0x2E67); //         [6E][67] -- A segment to play in place of this chapter. Edition ChapterSegmentEditionUID should be used for this segment, otherwise no edition is used.
define('EBML_ID_CHAPTERSEGMENTEDITIONUID',      0x2EBC); //         [6E][BC] -- The edition to play from the segment linked in ChapterSegmentUID.
define('EBML_ID_TRACKOVERLAY',                  0x2FAB); //         [6F][AB] -- Specify that this track is an overlay track for the Track specified (in the u-integer). That means when this track has a gap (see SilentTracks) the overlay track should be used instead. The order of multiple TrackOverlay matters, the first one is the one that should be used. If not found it should be the second, etc.
define('EBML_ID_TAG',                           0x3373); //         [73][73] -- Element containing elements specific to Tracks/Chapters.
define('EBML_ID_SEGMENTFILENAME',               0x3384); //         [73][84] -- A filename corresponding to this segment.
define('EBML_ID_SEGMENTUID',                    0x33A4); //         [73][A4] -- A randomly generated unique ID to identify the current segment between many others (128 bits).
define('EBML_ID_CHAPTERUID',                    0x33C4); //         [73][C4] -- A unique ID to identify the Chapter.
define('EBML_ID_TRACKUID',                      0x33C5); //         [73][C5] -- A unique ID to identify the Track. This should be kept the same when making a direct stream copy of the Track to another file.
define('EBML_ID_ATTACHMENTLINK',                0x3446); //         [74][46] -- The UID of an attachment that is used by this codec.
define('EBML_ID_CLUSTERBLOCKADDITIONS',         0x35A1); //         [75][A1] -- Contain additional blocks to complete the main one. An EBML parser that has no knowledge of the Block structure could still see and use/skip these data.
define('EBML_ID_CHANNELPOSITIONS',              0x347B); //         [7D][7B] -- Table of horizontal angles for each successive channel, see appendix.
define('EBML_ID_OUTPUTSAMPLINGFREQUENCY',       0x38B5); //         [78][B5] -- Real output sampling frequency in Hz (used for SBR techniques).
define('EBML_ID_TITLE',                         0x3BA9); //         [7B][A9] -- General name of the segment.
define('EBML_ID_CHAPTERDISPLAY',                  0x00); //             [80] -- Contains all possible strings to use for the chapter display.
define('EBML_ID_TRACKTYPE',                       0x03); //             [83] -- A set of track types coded on 8 bits (1: video, 2: audio, 3: complex, 0x10: logo, 0x11: subtitle, 0x12: buttons, 0x20: control).
define('EBML_ID_CHAPSTRING',                      0x05); //             [85] -- Contains the string to use as the chapter atom.
define('EBML_ID_CODECID',                         0x06); //             [86] -- An ID corresponding to the codec, see the codec page for more info.
define('EBML_ID_FLAGDEFAULT',                     0x08); //             [88] -- Set if that track (audio, video or subs) SHOULD be used if no language found matches the user preference.
define('EBML_ID_CHAPTERTRACKNUMBER',              0x09); //             [89] -- UID of the Track to apply this chapter too. In the absense of a control track, choosing this chapter will select the listed Tracks and deselect unlisted tracks. Absense of this element indicates that the Chapter should be applied to any currently used Tracks.
define('EBML_ID_CLUSTERSLICES',                   0x0E); //             [8E] -- Contains slices description.
define('EBML_ID_CHAPTERTRACK',                    0x0F); //             [8F] -- List of tracks on which the chapter applies. If this element is not present, all tracks apply
define('EBML_ID_CHAPTERTIMESTART',                0x11); //             [91] -- Timecode of the start of Chapter (not scaled).
define('EBML_ID_CHAPTERTIMEEND',                  0x12); //             [92] -- Timecode of the end of Chapter (timecode excluded, not scaled).
define('EBML_ID_CUEREFTIME',                      0x16); //             [96] -- Timecode of the referenced Block.
define('EBML_ID_CUEREFCLUSTER',                   0x17); //             [97] -- Position of the Cluster containing the referenced Block.
define('EBML_ID_CHAPTERFLAGHIDDEN',               0x18); //             [98] -- If a chapter is hidden (1), it should not be available to the user interface (but still to Control Tracks).
define('EBML_ID_FLAGINTERLACED',                  0x1A); //             [9A] -- Set if the video is interlaced.
define('EBML_ID_CLUSTERBLOCKDURATION',            0x1B); //             [9B] -- The duration of the Block (based on TimecodeScale). This element is mandatory when DefaultDuration is set for the track. When not written and with no DefaultDuration, the value is assumed to be the difference between the timecode of this Block and the timecode of the next Block in "display" order (not coding order). This element can be useful at the end of a Track (as there is not other Block available), or when there is a break in a track like for subtitle tracks.
define('EBML_ID_FLAGLACING',                      0x1C); //             [9C] -- Set if the track may contain blocks using lacing.
define('EBML_ID_CHANNELS',                        0x1F); //             [9F] -- Numbers of channels in the track.
define('EBML_ID_CLUSTERBLOCKGROUP',               0x20); //             [A0] -- Basic container of information containing a single Block or BlockVirtual, and information specific to that Block/VirtualBlock.
define('EBML_ID_CLUSTERBLOCK',                    0x21); //             [A1] -- Block containing the actual data to be rendered and a timecode relative to the Cluster Timecode.
define('EBML_ID_CLUSTERBLOCKVIRTUAL',             0x22); //             [A2] -- A Block with no data. It must be stored in the stream at the place the real Block should be in display order.
define('EBML_ID_CLUSTERSIMPLEBLOCK',              0x23); //             [A3] -- Similar to Block but without all the extra information, mostly used to reduced overhead when no extra feature is needed.
define('EBML_ID_CLUSTERCODECSTATE',               0x24); //             [A4] -- The new codec state to use. Data interpretation is private to the codec. This information should always be referenced by a seek entry.
define('EBML_ID_CLUSTERBLOCKADDITIONAL',          0x25); //             [A5] -- Interpreted by the codec as it wishes (using the BlockAddID).
define('EBML_ID_CLUSTERBLOCKMORE',                0x26); //             [A6] -- Contain the BlockAdditional and some parameters.
define('EBML_ID_CLUSTERPOSITION',                 0x27); //             [A7] -- Position of the Cluster in the segment (0 in live broadcast streams). It might help to resynchronise offset on damaged streams.
define('EBML_ID_CODECDECODEALL',                  0x2A); //             [AA] -- The codec can decode potentially damaged data.
define('EBML_ID_CLUSTERPREVSIZE',                 0x2B); //             [AB] -- Size of the previous Cluster, in octets. Can be useful for backward playing.
define('EBML_ID_TRACKENTRY',                      0x2E); //             [AE] -- Describes a track with all elements.
define('EBML_ID_CLUSTERENCRYPTEDBLOCK',           0x2F); //             [AF] -- Similar to SimpleBlock but the data inside the Block are Transformed (encrypt and/or signed).
define('EBML_ID_PIXELWIDTH',                      0x30); //             [B0] -- Width of the encoded video frames in pixels.
define('EBML_ID_CUETIME',                         0x33); //             [B3] -- Absolute timecode according to the segment time base.
define('EBML_ID_SAMPLINGFREQUENCY',               0x35); //             [B5] -- Sampling frequency in Hz.
define('EBML_ID_CHAPTERATOM',                     0x36); //             [B6] -- Contains the atom information to use as the chapter atom (apply to all tracks).
define('EBML_ID_CUETRACKPOSITIONS',               0x37); //             [B7] -- Contain positions for different tracks corresponding to the timecode.
define('EBML_ID_FLAGENABLED',                     0x39); //             [B9] -- Set if the track is used.
define('EBML_ID_PIXELHEIGHT',                     0x3A); //             [BA] -- Height of the encoded video frames in pixels.
define('EBML_ID_CUEPOINT',                        0x3B); //             [BB] -- Contains all information relative to a seek point in the segment.
define('EBML_ID_CRC32',                           0x3F); //             [BF] -- The CRC is computed on all the data of the Master element it's in, regardless of its position. It's recommended to put the CRC value at the beggining of the Master element for easier reading. All level 1 elements should include a CRC-32.
define('EBML_ID_CLUSTERBLOCKADDITIONID',          0x4B); //             [CB] -- The ID of the BlockAdditional element (0 is the main Block).
define('EBML_ID_CLUSTERLACENUMBER',               0x4C); //             [CC] -- The reverse number of the frame in the lace (0 is the last frame, 1 is the next to last, etc). While there are a few files in the wild with this element, it is no longer in use and has been deprecated. Being able to interpret this element is not required for playback.
define('EBML_ID_CLUSTERFRAMENUMBER',              0x4D); //             [CD] -- The number of the frame to generate from this lace with this delay (allow you to generate many frames from the same Block/Frame).
define('EBML_ID_CLUSTERDELAY',                    0x4E); //             [CE] -- The (scaled) delay to apply to the element.
define('EBML_ID_CLUSTERDURATION',                 0x4F); //             [CF] -- The (scaled) duration to apply to the element.
define('EBML_ID_TRACKNUMBER',                     0x57); //             [D7] -- The track number as used in the Block Header (using more than 127 tracks is not encouraged, though the design allows an unlimited number).
define('EBML_ID_CUEREFERENCE',                    0x5B); //             [DB] -- The Clusters containing the required referenced Blocks.
define('EBML_ID_VIDEO',                           0x60); //             [E0] -- Video settings.
define('EBML_ID_AUDIO',                           0x61); //             [E1] -- Audio settings.
define('EBML_ID_CLUSTERTIMESLICE',                0x68); //             [E8] -- Contains extra time information about the data contained in the Block. While there are a few files in the wild with this element, it is no longer in use and has been deprecated. Being able to interpret this element is not required for playback.
define('EBML_ID_CUECODECSTATE',                   0x6A); //             [EA] -- The position of the Codec State corresponding to this Cue element. 0 means that the data is taken from the initial Track Entry.
define('EBML_ID_CUEREFCODECSTATE',                0x6B); //             [EB] -- The position of the Codec State corresponding to this referenced element. 0 means that the data is taken from the initial Track Entry.
define('EBML_ID_VOID',                            0x6C); //             [EC] -- Used to void damaged data, to avoid unexpected behaviors when using damaged data. The content is discarded. Also used to reserve space in a sub-element for later use.
define('EBML_ID_CLUSTERTIMECODE',                 0x67); //             [E7] -- Absolute timecode of the cluster (based on TimecodeScale).
define('EBML_ID_CLUSTERBLOCKADDID',               0x6E); //             [EE] -- An ID to identify the BlockAdditional level.
define('EBML_ID_CUECLUSTERPOSITION',              0x71); //             [F1] -- The position of the Cluster containing the required Block.
define('EBML_ID_CUETRACK',                        0x77); //             [F7] -- The track for which a position is given.
define('EBML_ID_CLUSTERREFERENCEPRIORITY',        0x7A); //             [FA] -- This frame is referenced and has the specified cache priority. In cache only a frame of the same or higher priority can replace this frame. A value of 0 means the frame is not referenced.
define('EBML_ID_CLUSTERREFERENCEBLOCK',           0x7B); //             [FB] -- Timecode of another frame used as a reference (ie: B or P frame). The timecode is relative to the block it's attached to.
define('EBML_ID_CLUSTERREFERENCEVIRTUAL',         0x7D); //             [FD] -- Relative position of the data that should be in position of the virtual block.


class getid3_matroska
{
	var $read_buffer_size = 32768; // size of read buffer, 32kB is default
	var $hide_clusters    = true;  // if true, do not return information about CLUSTER chunks, since there's a lot of them and they're not usually useful
	var $warnings         = array();

	function getid3_matroska(&$fd, &$ThisFileInfo) {

		// http://www.matroska.org/technical/specs/index.html#EBMLBasics
		$offset = $ThisFileInfo['avdataoffset'];
		$EBMLdata = '';
		$EBMLdata_offset = $offset;

		if ($ThisFileInfo['avdataend'] > 2147483648) {
			$this->warnings[] = 'This version of getID3() may or may not correctly handle Matroska files larger than 2GB';
		}

		while ($offset < $ThisFileInfo['avdataend']) {
			$this->EnsureBufferHasEnoughData($fd, $EBMLdata, $offset, $EBMLdata_offset);

			$top_element_offset    = $offset;
			$top_element_id        = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
			$top_element_length    = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
			if ($top_element_length === false) {
				$this->warnings[] = 'invalid chunk length at '.$top_element_offset;
				$offset = pow(2, 63);
				break;
			}
			$top_element_endoffset = $offset + $top_element_length;
			switch ($top_element_id) {
				case EBML_ID_EBML:
					$ThisFileInfo['fileformat'] = 'matroska';
					$ThisFileInfo['matroska']['header']['offset'] = $top_element_offset;
					$ThisFileInfo['matroska']['header']['length'] = $top_element_length;

					while ($offset < $top_element_endoffset) {
						$this->EnsureBufferHasEnoughData($fd, $EBMLdata, $offset, $EBMLdata_offset);
						$element_data = array();
						$element_data_offset     = $offset;
						$element_data['id']      = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
						$element_data['id_name'] = $this->EBMLidName($element_data['id']);
						$element_data['length']     = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
						$end_offset              = $offset + $element_data['length'];

						switch ($element_data['id']) {
							case EBML_ID_EBMLVERSION:
							case EBML_ID_EBMLREADVERSION:
							case EBML_ID_EBMLMAXIDLENGTH:
							case EBML_ID_EBMLMAXSIZELENGTH:
							case EBML_ID_DOCTYPEVERSION:
							case EBML_ID_DOCTYPEREADVERSION:
								$element_data['data'] = getid3_lib::BigEndian2Int(substr($EBMLdata, $offset - $EBMLdata_offset, $element_data['length']));
								break;
							case EBML_ID_DOCTYPE:
								$element_data['data'] =                      trim(substr($EBMLdata, $offset - $EBMLdata_offset, $element_data['length']), "\x00");
								break;
							default:
								$this->warnings[] = 'Unhandled track.video element['.__LINE__.'] ('.$element_data['id'].'::'.$element_data['id_name'].') at '.$element_data_offset;
								break;
						}
						$offset = $end_offset;
						$ThisFileInfo['matroska']['header']['elements'][] = $element_data;
					}
					break;


				case EBML_ID_SEGMENT:
					$ThisFileInfo['matroska']['segment'][0]['offset'] = $top_element_offset;
					$ThisFileInfo['matroska']['segment'][0]['length'] = $top_element_length;

					$segment_key = -1;
					while ($offset < $ThisFileInfo['avdataend']) {
						$this->EnsureBufferHasEnoughData($fd, $EBMLdata, $offset, $EBMLdata_offset);

						$element_data = array();
						$element_data['offset']  = $offset;
						$element_data['id']      = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
						$element_data['id_name'] = $this->EBMLidName($element_data['id']);
						$element_data['length']  = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
						if ($element_data['length'] === false) {
							$this->warnings[] = 'invalid chunk length at '.$element_data['offset'];
							//$offset = pow(2, 63);
							$offset = $ThisFileInfo['avdataend'];
							break;
						}
						$element_end             = $offset + $element_data['length'];
						switch ($element_data['id']) {
							//case EBML_ID_CLUSTER:
							//	// too many cluster entries, probably not useful
							//	break;
							case false:
								$this->warnings[] = 'invalid ID at '.$element_data['offset'];
								$offset = $element_end;
								continue 3;
							default:
								$ThisFileInfo['matroska']['segments'][] = $element_data;
								break;
						}
						$segment_key++;

						switch ($element_data['id']) {
							case EBML_ID_SEEKHEAD: // Contains the position of other level 1 elements
								while ($offset < $element_end) {
									$this->EnsureBufferHasEnoughData($fd, $EBMLdata, $offset, $EBMLdata_offset);
									$seek_entry = array();
									$seek_entry['offset']  = $offset;
									$seek_entry['id']      = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
									$seek_entry['id_name'] = $this->EBMLidName($seek_entry['id']);
									$seek_entry['length']  = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
									$seek_end_offset       = $offset + $seek_entry['length'];
									switch ($seek_entry['id']) {
										case EBML_ID_SEEK: // Contains a single seek entry to an EBML element
											while ($offset < $seek_end_offset) {
												$this->EnsureBufferHasEnoughData($fd, $EBMLdata, $offset, $EBMLdata_offset);
												$id     = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
												$length = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
												$value  =             substr($EBMLdata, $offset - $EBMLdata_offset, $length);
												$offset += $length;
												switch ($id) {
													case EBML_ID_SEEKID:
														$dummy = 0;
														$seek_entry['target_id']   = $this->readEBMLint($value, $dummy);
														$seek_entry['target_name'] = $this->EBMLidName($seek_entry['target_id']);
														break;
													case EBML_ID_SEEKPOSITION:
														$seek_entry['target_offset'] = $element_data['offset'] + getid3_lib::BigEndian2Int($value);
														break;
													default:
														$ThisFileInfo['error'][] = 'Unhandled segment['.__LINE__.'] ('.$id.') at '.$offset;
														break;
												}
											}
											$ThisFileInfo['matroska']['seek'][] = $seek_entry;
											//switch ($seek_entry['target_id']) {
											//	case EBML_ID_CLUSTER:
											//		// too many cluster seek points, probably not useful
											//		break;
											//	default:
											//		$ThisFileInfo['matroska']['seek'][] = $seek_entry;
											//		break;
											//}
											break;
										default:
											$this->warnings[] = 'Unhandled seekhead element['.__LINE__.'] ('.$seek_entry['id'].') at '.$offset;
											break;
									}
									$offset = $seek_end_offset;
								}
								break;

							case EBML_ID_TRACKS: // information about all tracks in segment
								$ThisFileInfo['matroska']['tracks'] = $element_data;
								while ($offset < $element_end) {
									$this->EnsureBufferHasEnoughData($fd, $EBMLdata, $offset, $EBMLdata_offset);
									$track_entry = array();
									$track_entry['offset']  = $offset;
									$track_entry['id']      = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
									$track_entry['id_name'] = $this->EBMLidName($track_entry['id']);
									$track_entry['length']  = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
									$track_entry_endoffset  = $offset + $track_entry['length'];
									switch ($track_entry['id']) {
										case EBML_ID_TRACKENTRY: //subelements: Describes a track with all elements.
											while ($offset < $track_entry_endoffset) {
												$this->EnsureBufferHasEnoughData($fd, $EBMLdata, $offset, $EBMLdata_offset);
												$subelement_offset = $offset;
												$subelement_id     = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
												$subelement_idname = $this->EBMLidName($subelement_id);
												$subelement_length = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
												$subelement_end    = $offset + $subelement_length;
												switch ($subelement_id) {
													case EBML_ID_TRACKNUMBER:
													case EBML_ID_TRACKUID:
													case EBML_ID_TRACKTYPE:
													case EBML_ID_MINCACHE:
													case EBML_ID_MAXCACHE:
													case EBML_ID_MAXBLOCKADDITIONID:
													case EBML_ID_DEFAULTDURATION: // nanoseconds per frame
														$track_entry[$subelement_idname] =        getid3_lib::BigEndian2Int(substr($EBMLdata, $offset - $EBMLdata_offset, $subelement_length));
														break;
													case EBML_ID_TRACKTIMECODESCALE:
														$track_entry[$subelement_idname] =      getid3_lib::BigEndian2Float(substr($EBMLdata, $offset - $EBMLdata_offset, $subelement_length));
														break;
													case EBML_ID_CODECID:
													case EBML_ID_LANGUAGE:
													case EBML_ID_NAME:
													case EBML_ID_CODECPRIVATE:
														$track_entry[$subelement_idname] =                             trim(substr($EBMLdata, $offset - $EBMLdata_offset, $subelement_length), "\x00");
														break;
													case EBML_ID_FLAGENABLED:
													case EBML_ID_FLAGDEFAULT:
													case EBML_ID_FLAGFORCED:
													case EBML_ID_FLAGLACING:
													case EBML_ID_CODECDECODEALL:
														$track_entry[$subelement_idname] = (bool) getid3_lib::BigEndian2Int(substr($EBMLdata, $offset - $EBMLdata_offset, $subelement_length));
														break;
													case EBML_ID_VIDEO:
														while ($offset < $subelement_end) {
															$this->EnsureBufferHasEnoughData($fd, $EBMLdata, $offset, $EBMLdata_offset);
															$sub_subelement_offset = $offset;
															$sub_subelement_id     = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
															$sub_subelement_idname = $this->EBMLidName($sub_subelement_id);
															$sub_subelement_length = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
															$sub_subelement_end    = $offset + $sub_subelement_length;
															switch ($sub_subelement_id) {
																case EBML_ID_PIXELWIDTH:
																case EBML_ID_PIXELHEIGHT:
																case EBML_ID_STEREOMODE:
																case EBML_ID_PIXELCROPBOTTOM:
																case EBML_ID_PIXELCROPTOP:
																case EBML_ID_PIXELCROPLEFT:
																case EBML_ID_PIXELCROPRIGHT:
																case EBML_ID_DISPLAYWIDTH:
																case EBML_ID_DISPLAYHEIGHT:
																case EBML_ID_DISPLAYUNIT:
																case EBML_ID_ASPECTRATIOTYPE:
																	$track_entry[$sub_subelement_idname] =        getid3_lib::BigEndian2Int(substr($EBMLdata, $offset - $EBMLdata_offset, $sub_subelement_length));
																	break;
																case EBML_ID_FLAGINTERLACED:
																	$track_entry[$sub_subelement_idname] = (bool) getid3_lib::BigEndian2Int(substr($EBMLdata, $offset - $EBMLdata_offset, $sub_subelement_length));
																	break;
																case EBML_ID_GAMMAVALUE:
																	$track_entry[$sub_subelement_idname] =      getid3_lib::BigEndian2Float(substr($EBMLdata, $offset - $EBMLdata_offset, $sub_subelement_length));
																	break;
																case EBML_ID_COLOURSPACE:
																	$track_entry[$sub_subelement_idname] =                             trim(substr($EBMLdata, $offset - $EBMLdata_offset, $sub_subelement_length), "\x00");
																	break;
																default:
																	$this->warnings[] = 'Unhandled track.video element['.__LINE__.'] ('.$sub_subelement_id.'::'.$sub_subelement_idname.') at '.$sub_subelement_offset;
																	break;
															}
															$offset = $sub_subelement_end;
														}

														if ((@$track_entry[$this->EBMLidName(EBML_ID_CODECID)] == 'V_MS/VFW/FOURCC') && isset($track_entry[$this->EBMLidName(EBML_ID_CODECPRIVATE)])) {
															if (getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.audio-video.riff.php', __FILE__, false)) {
																$track_entry['codec_private_parsed'] = getid3_riff::ParseBITMAPINFOHEADER($track_entry[$this->EBMLidName(EBML_ID_CODECPRIVATE)]);
															} else {
																$this->warnings[] = 'Unable to parse codec private data['.__LINE__.'] because cannot include "module.audio-video.riff.php"';
															}
														}
														break;
													case EBML_ID_AUDIO:
														while ($offset < $subelement_end) {
															$this->EnsureBufferHasEnoughData($fd, $EBMLdata, $offset, $EBMLdata_offset);
															$sub_subelement_offset = $offset;
															$sub_subelement_id     = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
															$sub_subelement_idname = $this->EBMLidName($sub_subelement_id);
															$sub_subelement_length = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
															$sub_subelement_end    = $offset + $sub_subelement_length;
															switch ($sub_subelement_id) {
																case EBML_ID_CHANNELS:
																case EBML_ID_BITDEPTH:
																	$track_entry[$sub_subelement_idname] =        getid3_lib::BigEndian2Int(substr($EBMLdata, $offset - $EBMLdata_offset, $sub_subelement_length));
																	break;
																case EBML_ID_SAMPLINGFREQUENCY:
																case EBML_ID_OUTPUTSAMPLINGFREQUENCY:
																	$track_entry[$sub_subelement_idname] =      getid3_lib::BigEndian2Float(substr($EBMLdata, $offset - $EBMLdata_offset, $sub_subelement_length));
																	break;
																case EBML_ID_CHANNELPOSITIONS:
																	$track_entry[$sub_subelement_idname] =                             trim(substr($EBMLdata, $offset - $EBMLdata_offset, $sub_subelement_length), "\x00");
																	break;
																default:
																	$this->warnings[] = 'Unhandled track.video element['.__LINE__.'] ('.$sub_subelement_id.'::'.$sub_subelement_idname.') at '.$sub_subelement_offset;
																	break;
															}
															$offset = $sub_subelement_end;
														}
														break;

													case EBML_ID_CONTENTENCODINGS:
														while ($offset < $subelement_end) {
															$this->EnsureBufferHasEnoughData($fd, $EBMLdata, $offset, $EBMLdata_offset);
															$sub_subelement_offset = $offset;
															$sub_subelement_id     = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
															$sub_subelement_idname = $this->EBMLidName($sub_subelement_id);
															$sub_subelement_length = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
															$sub_subelement_end    = $offset + $sub_subelement_length;
															switch ($sub_subelement_id) {
																case EBML_ID_CONTENTENCODING:
																	while ($offset < $sub_subelement_end) {
																		$this->EnsureBufferHasEnoughData($fd, $EBMLdata, $offset, $EBMLdata_offset);
																		$sub_sub_subelement_offset = $offset;
																		$sub_sub_subelement_id     = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
																		$sub_sub_subelement_idname = $this->EBMLidName($sub_sub_subelement_id);
																		$sub_sub_subelement_length = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
																		$sub_sub_subelement_end    = $offset + $sub_sub_subelement_length;
																		switch ($sub_sub_subelement_id) {
																			case EBML_ID_CONTENTENCODINGORDER:
																			case EBML_ID_CONTENTENCODINGSCOPE:
																			case EBML_ID_CONTENTENCODINGTYPE:
																				$track_entry[$sub_subelement_idname][$sub_sub_subelement_idname] = getid3_lib::BigEndian2Int(substr($EBMLdata, $offset - $EBMLdata_offset, $sub_sub_subelement_length));
																				break;
																			case EBML_ID_CONTENTCOMPRESSION:
																				while ($offset < $sub_sub_subelement_end) {
																					$this->EnsureBufferHasEnoughData($fd, $EBMLdata, $offset, $EBMLdata_offset);
																					$sub_sub_sub_subelement_offset = $offset;
																					$sub_sub_sub_subelement_id     = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
																					$sub_sub_sub_subelement_idname = $this->EBMLidName($sub_sub_subelement_id);
																					$sub_sub_sub_subelement_length = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
																					$sub_sub_sub_subelement_end    = $offset + $sub_sub_sub_subelement_length;
																					switch ($sub_sub_sub_subelement_id) {
																						case EBML_ID_CONTENTCOMPALGO:
																							$track_entry[$sub_subelement_idname][$sub_sub_subelement_idname][$sub_sub_sub_subelement_idname] = getid3_lib::BigEndian2Int(substr($EBMLdata, $offset - $EBMLdata_offset, $sub_sub_sub_subelement_length));
																							break;
																						case EBML_ID_CONTENTCOMPSETTINGS:
																							$track_entry[$sub_subelement_idname][$sub_sub_subelement_idname][$sub_sub_sub_subelement_idname] =                           substr($EBMLdata, $offset - $EBMLdata_offset, $sub_sub_sub_subelement_length);
																							break;
																						default:
																							$this->warnings[] = 'Unhandled track.contentencodings.contentencoding.contentcompression element['.__LINE__.'] ('.$subelement_id.'::'.$subelement_idname.' ['.$subelement_length.' bytes]) at '.$subelement_offset;
																							break;
																					}
																					$offset = $sub_sub_sub_subelement_end;
																				}
																				break;

																			case EBML_ID_CONTENTENCRYPTION:
																				while ($offset < $sub_sub_subelement_end) {
																					$this->EnsureBufferHasEnoughData($fd, $EBMLdata, $offset, $EBMLdata_offset);
																					$sub_sub_sub_subelement_offset = $offset;
																					$sub_sub_sub_subelement_id     = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
																					$sub_sub_sub_subelement_idname = $this->EBMLidName($sub_sub_subelement_id);
																					$sub_sub_sub_subelement_length = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
																					$sub_sub_sub_subelement_end    = $offset + $sub_sub_sub_subelement_length;
																					switch ($sub_sub_sub_subelement_id) {
																						case EBML_ID_CONTENTENCALGO:
																						case EBML_ID_CONTENTSIGALGO:
																						case EBML_ID_CONTENTSIGHASHALGO:
																							$track_entry[$sub_subelement_idname][$sub_sub_subelement_idname][$sub_sub_sub_subelement_idname] = getid3_lib::BigEndian2Int(substr($EBMLdata, $offset - $EBMLdata_offset, $sub_sub_sub_subelement_length));
																							break;
																						case EBML_ID_CONTENTENCKEYID:
																						case EBML_ID_CONTENTSIGNATURE:
																						case EBML_ID_CONTENTSIGKEYID:
																							$track_entry[$sub_subelement_idname][$sub_sub_subelement_idname][$sub_sub_sub_subelement_idname] =                           substr($EBMLdata, $offset - $EBMLdata_offset, $sub_sub_sub_subelement_length);
																							break;
																						default:
																							$this->warnings[] = 'Unhandled track.contentencodings.contentencoding.contentcompression element['.__LINE__.'] ('.$subelement_id.'::'.$subelement_idname.' ['.$subelement_length.' bytes]) at '.$subelement_offset;
																							break;
																					}
																					$offset = $sub_sub_sub_subelement_end;
																				}
																				break;

																			default:
																				$this->warnings[] = 'Unhandled track.contentencodings.contentencoding element['.__LINE__.'] ('.$subelement_id.'::'.$subelement_idname.' ['.$subelement_length.' bytes]) at '.$subelement_offset;
																				break;
																		}
																		$offset = $sub_sub_subelement_end;
																	}
																	break;
																default:
																	$this->warnings[] = 'Unhandled track.contentencodings element['.__LINE__.'] ('.$subelement_id.'::'.$subelement_idname.' ['.$subelement_length.' bytes]) at '.$subelement_offset;
																	break;
															}
															$offset = $sub_subelement_end;
														}
														break;

													default:
														$this->warnings[] = 'Unhandled track element['.__LINE__.'] ('.$subelement_id.'::'.$subelement_idname.' ['.$subelement_length.' bytes]) at '.$subelement_offset;
														break;
												}
												$offset = $subelement_end;
											}
											break;
										default:
											$this->warnings[] = 'Unhandled track element['.__LINE__.'] ('.$track_entry['id'].'::'.$track_entry['id_name'].') at '.$track_entry['offset'];
											$offset = $track_entry_endoffset;
											break;
									}
									$ThisFileInfo['matroska']['tracks']['tracks'][] = $track_entry;
								}
								break;

							case EBML_ID_INFO: // Contains the position of other level 1 elements
								$info_entry = array();
								while ($offset < $element_end) {
									$this->EnsureBufferHasEnoughData($fd, $EBMLdata, $offset, $EBMLdata_offset);
									$subelement_offset = $offset;
									$subelement_id     = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
									$subelement_idname = $this->EBMLidName($subelement_id);
									$subelement_length = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
									$subelement_end    = $offset + $subelement_length;
									switch ($subelement_id) {
										case EBML_ID_CHAPTERTRANSLATEEDITIONUID:
										case EBML_ID_CHAPTERTRANSLATECODEC:
										case EBML_ID_TIMECODESCALE:
											$info_entry[$subelement_idname] =        getid3_lib::BigEndian2Int(substr($EBMLdata, $offset - $EBMLdata_offset, $subelement_length));
											break;
										case EBML_ID_DURATION:
											$info_entry[$subelement_idname] =      getid3_lib::BigEndian2Float(substr($EBMLdata, $offset - $EBMLdata_offset, $subelement_length));
											break;
										case EBML_ID_DATEUTC:
											$info_entry[$subelement_idname] =        getid3_lib::BigEndian2Int(substr($EBMLdata, $offset - $EBMLdata_offset, $subelement_length));
											$info_entry[$subelement_idname.'_unix'] = $this->EBMLdate2unix($info_entry[$subelement_idname]);
											break;
										case EBML_ID_SEGMENTUID:
										case EBML_ID_PREVUID:
										case EBML_ID_NEXTUID:
										case EBML_ID_SEGMENTFAMILY:
										case EBML_ID_CHAPTERTRANSLATEID:
											$info_entry[$subelement_idname] =                             trim(substr($EBMLdata, $offset - $EBMLdata_offset, $subelement_length), "\x00");
											break;
										case EBML_ID_SEGMENTFILENAME:
										case EBML_ID_PREVFILENAME:
										case EBML_ID_NEXTFILENAME:
										case EBML_ID_TITLE:
										case EBML_ID_MUXINGAPP:
										case EBML_ID_WRITINGAPP:
											$info_entry[$subelement_idname] =                             trim(substr($EBMLdata, $offset - $EBMLdata_offset, $subelement_length), "\x00");
											break;
										default:
											$this->warnings[] = 'Unhandled info element['.__LINE__.'] ('.$subelement_id.'::'.$subelement_idname.' ['.$subelement_length.' bytes]) at '.$subelement_offset;
											break;
									}
									$offset = $subelement_end;
								}
								$ThisFileInfo['matroska']['info'][] = $info_entry;
								break;

							case EBML_ID_CUES:
								$cues_entry = array();
								while ($offset < $element_end) {
									$this->EnsureBufferHasEnoughData($fd, $EBMLdata, $offset, $EBMLdata_offset);
									$subelement_offset = $offset;
									$subelement_id     = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
									$subelement_idname = $this->EBMLidName($subelement_id);
									$subelement_length = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
									$subelement_end    = $offset + $subelement_length;
									switch ($subelement_id) {
										case EBML_ID_CUEPOINT:
											$cuepoint_entry = array();
											while ($offset < $subelement_end) {
												$this->EnsureBufferHasEnoughData($fd, $EBMLdata, $offset, $EBMLdata_offset);
												$sub_subelement_offset = $offset;
												$sub_subelement_id     = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
												$sub_subelement_idname = $this->EBMLidName($sub_subelement_id);
												$sub_subelement_length = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
												$sub_subelement_end    = $offset + $sub_subelement_length;
												switch ($sub_subelement_id) {
													case EBML_ID_CUETRACKPOSITIONS:
														while ($offset < $sub_subelement_end) {
															$this->EnsureBufferHasEnoughData($fd, $EBMLdata, $offset, $EBMLdata_offset);
															$sub_sub_subelement_offset = $offset;
															$sub_sub_subelement_id     = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
															$sub_sub_subelement_idname = $this->EBMLidName($sub_sub_subelement_id);
															$sub_sub_subelement_length = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
															$sub_sub_subelement_end    = $offset + $sub_sub_subelement_length;
															switch ($sub_sub_subelement_id) {
																case EBML_ID_CUETRACK:
																	$cuepoint_entry[$sub_sub_subelement_idname] = getid3_lib::BigEndian2Int(substr($EBMLdata, $offset - $EBMLdata_offset, $sub_sub_subelement_length));
																	break;
																default:
																	$this->warnings[] = 'Unhandled cues.cuepoint.cuetrackpositions element['.__LINE__.'] ('.$sub_sub_subelement_id.'::'.$sub_sub_subelement_idname.') at '.$sub_sub_subelement_offset;
																	break;
															}
															$offset = $sub_subelement_end;
														}
														break;
													case EBML_ID_CUETIME:
														$cuepoint_entry[$subelement_idname] =        getid3_lib::BigEndian2Int(substr($EBMLdata, $offset - $EBMLdata_offset, $sub_subelement_length));
														break;
													default:
														$this->warnings[] = 'Unhandled cues.cuepoint element['.__LINE__.'] ('.$sub_subelement_id.'::'.$sub_subelement_idname.') at '.$sub_subelement_offset;
														break;
												}
												$offset = $sub_subelement_end;
											}
											$cues_entry[] = $cuepoint_entry;
											$offset = $sub_subelement_end;
											break;
										default:
											$this->warnings[] = 'Unhandled cues element['.__LINE__.'] ('.$subelement_id.'::'.$subelement_idname.' ['.$subelement_length.' bytes]) at '.$subelement_offset;
											break;
									}
									$offset = $subelement_end;
								}
								$ThisFileInfo['matroska']['cues'] = $cues_entry;
								break;

							case EBML_ID_TAGS:
								$tags_entry = array();
								while ($offset < $element_end) {
									$this->EnsureBufferHasEnoughData($fd, $EBMLdata, $offset, $EBMLdata_offset);
									$subelement_offset = $offset;
									$subelement_id     = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
									$subelement_idname = $this->EBMLidName($subelement_id);
									$subelement_length = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
									$subelement_end    = $offset + $subelement_length;
									switch ($subelement_id) {
										case EBML_ID_WRITINGAPP:
											$tags_entry[$subelement_idname] = substr($EBMLdata, $offset - $EBMLdata_offset, $subelement_length);
											break;
										case EBML_ID_TAG:
											$tag_entry = array();
											while ($offset < $subelement_end) {
												$this->EnsureBufferHasEnoughData($fd, $EBMLdata, $offset, $EBMLdata_offset);
												$sub_subelement_offset = $offset;
												$sub_subelement_id     = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
												$sub_subelement_idname = $this->EBMLidName($sub_subelement_id);
												$sub_subelement_length = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
												$sub_subelement_end    = $offset + $sub_subelement_length;
												switch ($sub_subelement_id) {
													case EBML_ID_TARGETS:
														$targets_entry = array();
														while ($offset < $sub_subelement_end) {
															$this->EnsureBufferHasEnoughData($fd, $EBMLdata, $offset, $EBMLdata_offset);
															$sub_sub_subelement_offset = $offset;
															$sub_sub_subelement_id     = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
															$sub_sub_subelement_idname = $this->EBMLidName($sub_sub_subelement_id);
															$sub_sub_subelement_length = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
															$sub_sub_subelement_end    = $offset + $sub_sub_subelement_length;
															switch ($sub_sub_subelement_id) {
																case EBML_ID_TARGETTYPEVALUE:
																case EBML_ID_EDITIONUID:
																case EBML_ID_CHAPTERUID:
																case EBML_ID_ATTACHMENTUID:
																case EBML_ID_TAGTRACKUID:
																case EBML_ID_TAGCHAPTERUID:
																	$targets_entry[$sub_sub_subelement_idname] = getid3_lib::BigEndian2Int(substr($EBMLdata, $offset - $EBMLdata_offset, $sub_sub_subelement_length));
																	break;
																default:
																	$this->warnings[] = 'Unhandled tag.targets element['.__LINE__.'] ('.$sub_sub_subelement_id.'::'.$sub_sub_subelement_idname.') at '.$sub_sub_subelement_offset;
																	break;
															}
															$offset = $sub_sub_subelement_end;
														}
														$tag_entry[$sub_subelement_idname][] = $targets_entry;
														break;
													case EBML_ID_SIMPLETAG:
														$simpletag_entry = array();
														while ($offset < $sub_subelement_end) {
															$this->EnsureBufferHasEnoughData($fd, $EBMLdata, $offset, $EBMLdata_offset);
															$sub_sub_subelement_offset = $offset;
															$sub_sub_subelement_id     = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
															$sub_sub_subelement_idname = $this->EBMLidName($sub_sub_subelement_id);
															$sub_sub_subelement_length = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
															$sub_sub_subelement_end    = $offset + $sub_sub_subelement_length;
															switch ($sub_sub_subelement_id) {
																case EBML_ID_TAGNAME:
																case EBML_ID_TAGLANGUAGE:
																case EBML_ID_TAGSTRING:
																case EBML_ID_TAGBINARY:
																	$simpletag_entry[$sub_sub_subelement_idname] =                                  substr($EBMLdata, $offset - $EBMLdata_offset, $sub_sub_subelement_length);
																	break;
																case EBML_ID_TAGDEFAULT:
																	$simpletag_entry[$sub_sub_subelement_idname] = (bool) getid3_lib::BigEndian2Int(substr($EBMLdata, $offset - $EBMLdata_offset, $sub_sub_subelement_length));
																	break;
																default:
																	$this->warnings[] = 'Unhandled tag.simpletag element['.__LINE__.'] ('.$sub_sub_subelement_id.'::'.$sub_sub_subelement_idname.') at '.$sub_sub_subelement_offset;
																	break;
															}
															$offset = $sub_sub_subelement_end;
														}
														$tag_entry[$sub_subelement_idname][] = $simpletag_entry;
														break;
													case EBML_ID_TARGETTYPE:
														$tag_entry[$sub_subelement_idname] =                           substr($EBMLdata, $offset - $EBMLdata_offset, $sub_subelement_length);
														break;
													case EBML_ID_TRACKUID:
														$tag_entry[$sub_subelement_idname] = getid3_lib::BigEndian2Int(substr($EBMLdata, $offset - $EBMLdata_offset, $sub_subelement_length));
														break;
													default:
														$this->warnings[] = 'Unhandled tags.tag element['.__LINE__.'] ('.$sub_subelement_id.'::'.$sub_subelement_idname.') at '.$sub_subelement_offset;
														break;
												}
												$offset = $sub_subelement_end;
											}
											$tags_entry['tags'][] = $tag_entry;
											$offset = $sub_subelement_end;
											break;
										default:
											$this->warnings[] = 'Unhandled tags element['.__LINE__.'] ('.$subelement_id.'::'.$subelement_idname.' ['.$subelement_length.' bytes]) at '.$subelement_offset;
											break;
									}
									$offset = $subelement_end;
								}
								$ThisFileInfo['matroska']['tags'] = $tags_entry;
								break;


							case EBML_ID_ATTACHMENTS:
								while ($offset < $element_end) {
									$this->EnsureBufferHasEnoughData($fd, $EBMLdata, $offset, $EBMLdata_offset);
									$subelement_offset = $offset;
									$subelement_id     = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
									$subelement_idname = $this->EBMLidName($subelement_id);
									$subelement_length = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
									$subelement_end    = $offset + $subelement_length;
									switch ($subelement_id) {
										case EBML_ID_ATTACHEDFILE:
											$attachedfile_entry = array();
											while ($offset < $subelement_end) {
												$this->EnsureBufferHasEnoughData($fd, $EBMLdata, $offset, $EBMLdata_offset);
												$sub_subelement_offset = $offset;
												$sub_subelement_id     = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
												$sub_subelement_idname = $this->EBMLidName($sub_subelement_id);
												$sub_subelement_length = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
												$sub_subelement_end    = $offset + $sub_subelement_length;
												switch ($sub_subelement_id) {
													case EBML_ID_FILEDESCRIPTION:
													case EBML_ID_FILENAME:
													case EBML_ID_FILEMIMETYPE:
														$attachedfile_entry[$sub_subelement_idname] =                           substr($EBMLdata, $offset - $EBMLdata_offset, $sub_subelement_length);
														break;

													case EBML_ID_FILEDATA:
														$attachedfile_entry['data_offset'] = $offset;
														$attachedfile_entry['data_length'] = $sub_subelement_length;
														if ($sub_subelement_length < 1024) {
															$attachedfile_entry[$sub_subelement_idname] =                           substr($EBMLdata, $offset - $EBMLdata_offset, $sub_subelement_length);
														}
														break;

													case EBML_ID_FILEUID:
														$attachedfile_entry[$sub_subelement_idname] = getid3_lib::BigEndian2Int(substr($EBMLdata, $offset - $EBMLdata_offset, $sub_subelement_length));
														break;

													default:
														$this->warnings[] = 'Unhandled attachment.attachedfile element['.__LINE__.'] ('.$sub_subelement_id.'::'.$sub_subelement_idname.') at '.$sub_subelement_offset;
														break;
												}
												$offset = $sub_subelement_end;
											}
											$ThisFileInfo['matroska']['attachments'][] = $attachedfile_entry;
											$offset = $sub_subelement_end;
											break;
										default:
											$this->warnings[] = 'Unhandled tags element['.__LINE__.'] ('.$subelement_id.'::'.$subelement_idname.' ['.$subelement_length.' bytes]) at '.$subelement_offset;
											break;
									}
									$offset = $subelement_end;
								}
								break;


							case EBML_ID_CHAPTERS: // not important to us, contains mostly actual audio/video data, ignore
								while ($offset < $element_end) {
									$this->EnsureBufferHasEnoughData($fd, $EBMLdata, $offset, $EBMLdata_offset);
									$subelement_offset = $offset;
									$subelement_id     = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
									$subelement_idname = $this->EBMLidName($subelement_id);
									$subelement_length = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
									$subelement_end    = $offset + $subelement_length;
									switch ($subelement_id) {
										case EBML_ID_EDITIONENTRY:
											$editionentry_entry = array();
											while ($offset < $subelement_end) {
												$this->EnsureBufferHasEnoughData($fd, $EBMLdata, $offset, $EBMLdata_offset);
												$sub_subelement_offset = $offset;
												$sub_subelement_id     = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
												$sub_subelement_idname = $this->EBMLidName($sub_subelement_id);
												$sub_subelement_length = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
												$sub_subelement_end    = $offset + $sub_subelement_length;
												switch ($sub_subelement_id) {
													case EBML_ID_EDITIONUID:
														$editionentry_entry[$sub_subelement_idname] =        getid3_lib::BigEndian2Int(substr($EBMLdata, $offset - $EBMLdata_offset, $sub_subelement_length));
														break;
													case EBML_ID_EDITIONFLAGHIDDEN:
													case EBML_ID_EDITIONFLAGDEFAULT:
													case EBML_ID_EDITIONFLAGORDERED:
														$editionentry_entry[$sub_subelement_idname] = (bool) getid3_lib::BigEndian2Int(substr($EBMLdata, $offset - $EBMLdata_offset, $sub_subelement_length));
														break;
													case EBML_ID_CHAPTERATOM:
														$chapteratom_entry = array();
														while ($offset < $sub_subelement_end) {
															$this->EnsureBufferHasEnoughData($fd, $EBMLdata, $offset, $EBMLdata_offset);
															$sub_sub_subelement_offset = $offset;
															$sub_sub_subelement_id     = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
															$sub_sub_subelement_idname = $this->EBMLidName($sub_sub_subelement_id);
															$sub_sub_subelement_length = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
															$sub_sub_subelement_end    = $offset + $sub_sub_subelement_length;
															switch ($sub_sub_subelement_id) {
																case EBML_ID_CHAPTERSEGMENTUID:
																case EBML_ID_CHAPTERSEGMENTEDITIONUID:
																	$chapteratom_entry[$sub_sub_subelement_idname] =                                  substr($EBMLdata, $offset - $EBMLdata_offset, $sub_sub_subelement_length);
																	break;
																case EBML_ID_CHAPTERFLAGENABLED:
																case EBML_ID_CHAPTERFLAGHIDDEN:
																	$chapteratom_entry[$sub_sub_subelement_idname] = (bool) getid3_lib::BigEndian2Int(substr($EBMLdata, $offset - $EBMLdata_offset, $sub_sub_subelement_length));
																	break;
																case EBML_ID_CHAPTERUID:
																case EBML_ID_CHAPTERTIMESTART:
																case EBML_ID_CHAPTERTIMEEND:
																	$chapteratom_entry[$sub_sub_subelement_idname] =        getid3_lib::BigEndian2Int(substr($EBMLdata, $offset - $EBMLdata_offset, $sub_sub_subelement_length));
																	break;
																case EBML_ID_CHAPTERTRACK:
																	$chaptertrack_entry = array();
																	while ($offset < $sub_sub_subelement_end) {
																		$this->EnsureBufferHasEnoughData($fd, $EBMLdata, $offset, $EBMLdata_offset);
																		$sub_sub_sub_subelement_offset = $offset;
																		$sub_sub_sub_subelement_id     = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
																		$sub_sub_sub_subelement_idname = $this->EBMLidName($sub_sub_subelement_id);
																		$sub_sub_sub_subelement_length = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
																		$sub_sub_sub_subelement_end    = $offset + $sub_sub_sub_subelement_length;
																		switch ($sub_sub_sub_subelement_id) {
																			case EBML_ID_CHAPTERTRACKNUMBER:
																				$chaptertrack_entry[$sub_sub_sub_subelement_idname] =        getid3_lib::BigEndian2Int(substr($EBMLdata, $offset - $EBMLdata_offset, $sub_sub_sub_subelement_length));
																				break;
																			default:
																				$this->warnings[] = 'Unhandled chapters.editionentry.chapteratom.chaptertrack element['.__LINE__.'] ('.$sub_sub_sub_subelement_id.'::'.$sub_sub_sub_subelement_idname.') at '.$sub_sub_sub_subelement_offset;
																				break;
																		}
																		$offset = $sub_sub_sub_subelement_end;
																	}
																	$chapteratom_entry[$sub_sub_subelement_idname][] = $chaptertrack_entry;
																	break;
																case EBML_ID_CHAPTERDISPLAY:
																	$chapterdisplay_entry = array();
																	while ($offset < $sub_sub_subelement_end) {
																		$this->EnsureBufferHasEnoughData($fd, $EBMLdata, $offset, $EBMLdata_offset);
																		$sub_sub_sub_subelement_offset = $offset;
																		$sub_sub_sub_subelement_id     = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
																		$sub_sub_sub_subelement_idname = $this->EBMLidName($sub_sub_sub_subelement_id);
																		$sub_sub_sub_subelement_length = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
																		$sub_sub_sub_subelement_end    = $offset + $sub_sub_sub_subelement_length;
																		switch ($sub_sub_sub_subelement_id) {
																			case EBML_ID_CHAPSTRING:
																			case EBML_ID_CHAPLANGUAGE:
																			case EBML_ID_CHAPCOUNTRY:
																				$chapterdisplay_entry[$sub_sub_sub_subelement_idname] =                                  substr($EBMLdata, $offset - $EBMLdata_offset, $sub_sub_sub_subelement_length);
																				break;
																			default:
																				$this->warnings[] = 'Unhandled chapters.editionentry.chapteratom.chapterdisplay element['.__LINE__.'] ('.$sub_sub_sub_subelement_id.'::'.$sub_sub_sub_subelement_idname.') at '.$sub_sub_sub_subelement_offset;
																				break;
																		}
																		$offset = $sub_sub_sub_subelement_end;
																	}
																	$chapteratom_entry[$sub_sub_subelement_idname][] = $chapterdisplay_entry;
																	break;
																default:
																	$this->warnings[] = 'Unhandled chapters.editionentry.chapteratom element['.__LINE__.'] ('.$sub_sub_subelement_id.'::'.$sub_sub_subelement_idname.') at '.$sub_sub_subelement_offset;
																	break;
															}
															$offset = $sub_sub_subelement_end;
														}
														$editionentry_entry[$sub_subelement_idname][] = $chapteratom_entry;
														break;
													default:
														$this->warnings[] = 'Unhandled chapters.editionentry element['.__LINE__.'] ('.$sub_subelement_id.'::'.$sub_subelement_idname.') at '.$sub_subelement_offset;
														break;
												}
												$offset = $sub_subelement_end;
											}
											$ThisFileInfo['matroska']['chapters'][] = $editionentry_entry;
											$offset = $sub_subelement_end;
											break;
										default:
											$this->warnings[] = 'Unhandled chapters element['.__LINE__.'] ('.$subelement_id.'::'.$subelement_idname.' ['.$subelement_length.' bytes]) at '.$subelement_offset;
											break;
									}
									$offset = $subelement_end;
								}
								break;


							case EBML_ID_VOID:    // padding, ignore
								$void_entry = array();
								$void_entry['offset'] = $offset;
								$ThisFileInfo['matroska']['void'][] = $void_entry;
								break;

							case EBML_ID_CLUSTER: // not important to us, contains mostly actual audio/video data, ignore
								$cluster_entry = array();
								while ($offset < $element_end) {
									$this->EnsureBufferHasEnoughData($fd, $EBMLdata, $offset, $EBMLdata_offset);
									$subelement_offset = $offset;
//var_dump($offset);
									$subelement_id     = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
//var_dump($subelement_id);
//echo '<br>';
									$subelement_idname = $this->EBMLidName($subelement_id);
									$subelement_length = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
//var_dump($subelement_length);
									$subelement_end    = $offset + $subelement_length;
//exit;
									switch ($subelement_id) {
										case EBML_ID_CLUSTERTIMECODE:
										case EBML_ID_CLUSTERPOSITION:
										case EBML_ID_CLUSTERPREVSIZE:
											$cluster_entry[$subelement_idname] =        getid3_lib::BigEndian2Int(substr($EBMLdata, $offset - $EBMLdata_offset, $subelement_length));
											break;

										case EBML_ID_CLUSTERSILENTTRACKS:
											$cluster_silent_tracks = array();
											while ($offset < $subelement_end) {
												$this->EnsureBufferHasEnoughData($fd, $EBMLdata, $offset, $EBMLdata_offset);
												$sub_subelement_offset = $offset;
												$sub_subelement_id     = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
												$sub_subelement_idname = $this->EBMLidName($sub_subelement_id);
												$sub_subelement_length = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
												$sub_subelement_end    = $offset + $sub_subelement_length;
												switch ($sub_subelement_id) {
													case EBML_ID_CLUSTERSILENTTRACKNUMBER:
														$cluster_silent_tracks[] = getid3_lib::BigEndian2Int(substr($EBMLdata, $offset - $EBMLdata_offset, $sub_subelement_length));
														break;
													default:
														$this->warnings[] = 'Unhandled clusters.silenttracks element['.__LINE__.'] ('.$sub_subelement_id.'::'.$sub_subelement_idname.') at '.$sub_subelement_offset;
														break;
												}
												$offset = $sub_subelement_end;
											}
											$cluster_entry[$subelement_idname][] = $cluster_silent_tracks;
											$offset = $sub_subelement_end;
											break;

										case EBML_ID_CLUSTERBLOCKGROUP:
											$cluster_block_group = array('offset'=>$offset);
											while ($offset < $subelement_end) {
												$this->EnsureBufferHasEnoughData($fd, $EBMLdata, $offset, $EBMLdata_offset);
												$sub_subelement_offset = $offset;
												$sub_subelement_id     = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
												$sub_subelement_idname = $this->EBMLidName($sub_subelement_id);
												$sub_subelement_length = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
												$sub_subelement_end    = $offset + $sub_subelement_length;
												switch ($sub_subelement_id) {
													case EBML_ID_CLUSTERBLOCK:
														$cluster_block_data = array();
														$cluster_block_data['tracknumber'] = $this->readEBMLint($EBMLdata, $offset, $EBMLdata_offset);
														$cluster_block_data['timecode'] = getid3_lib::BigEndian2Int(substr($EBMLdata, $offset, 2));
														$offset += 2;
														// unsure whether this is 1 octect or 2 octets? (http://matroska.org/technical/specs/index.html#block_structure)
														$cluster_block_data['flags_raw'] = getid3_lib::BigEndian2Int(substr($EBMLdata, $offset, 1));
														$offset += 1;
														//$cluster_block_data['flags']['reserved1'] =      (($cluster_block_data['flags_raw'] & 0xF0) >> 4);
														$cluster_block_data['flags']['invisible'] = (bool) (($cluster_block_data['flags_raw'] & 0x08) >> 3);
														$cluster_block_data['flags']['lacing']    =        (($cluster_block_data['flags_raw'] & 0x06) >> 1);
														//$cluster_block_data['flags']['reserved2'] =      (($cluster_block_data['flags_raw'] & 0x01) >> 0);
														$cluster_block_data['flags']['lacing_type'] = $this->MatroskaBlockLacingType($cluster_block_data['flags']['lacing']);
														if ($cluster_block_data['flags']['lacing'] != 0) {
															$cluster_block_data['lace_frames'] = getid3_lib::BigEndian2Int(substr($EBMLdata, $offset, 1)); // Number of frames in the lace-1 (uint8)
															$offset += 1;
															if ($cluster_block_data['flags']['lacing'] != 2) {
																$cluster_block_data['lace_frames'] = getid3_lib::BigEndian2Int(substr($EBMLdata, $offset, 1)); // Lace-coded size of each frame of the lace, except for the last one (multiple uint8). *This is not used with Fixed-size lacing as it is calculated automatically from (total size of lace) / (number of frames in lace).
																$offset += 1;
															}
														}
														if (!isset($ThisFileInfo['matroska']['track_data_offsets'][$cluster_block_data['tracknumber']])) {
															$ThisFileInfo['matroska']['track_data_offsets'][$cluster_block_data['tracknumber']] = $offset;
														}
														$cluster_block_group[$sub_subelement_idname] = $cluster_block_data;
														break;

													case EBML_ID_CLUSTERREFERENCEPRIORITY: // unsigned-int
													case EBML_ID_CLUSTERBLOCKDURATION:     // unsigned-int
														$cluster_block_group[$sub_subelement_idname] = getid3_lib::BigEndian2Int(substr($EBMLdata, $offset - $EBMLdata_offset, $sub_subelement_length));
														break;

													case EBML_ID_CLUSTERREFERENCEBLOCK:    // signed-int
														$cluster_block_group[$sub_subelement_idname] = getid3_lib::BigEndian2Int(substr($EBMLdata, $offset - $EBMLdata_offset, $sub_subelement_length), false, true);
														break;

													default:
														$this->warnings[] = 'Unhandled clusters.blockgroup element['.__LINE__.'] ('.$sub_subelement_id.'::'.$sub_subelement_idname.') at '.$sub_subelement_offset;
														break;
												}
												$offset = $sub_subelement_end;
											}
											$cluster_entry[$subelement_idname][] = $cluster_block_group;
											$offset = $sub_subelement_end;
											break;

										default:
											$this->warnings[] = 'Unhandled cluster element['.__LINE__.'] ('.$subelement_id.'::'.$subelement_idname.' ['.$subelement_length.' bytes]) at '.$subelement_offset;
											break;
									}
									$offset = $subelement_end;
								}
								$ThisFileInfo['matroska']['cluster'][] = $cluster_entry;

								// check to see if all the data we need exists already, if so, break out of the loop
								if (isset($ThisFileInfo['matroska']['info']) && is_array($ThisFileInfo['matroska']['info'])) {
									if (isset($ThisFileInfo['matroska']['tracks']['tracks']) && is_array($ThisFileInfo['matroska']['tracks']['tracks'])) {
										break 2;
									}
								}
								break;

							default:
								if ($element_data['id_name'] == dechex($element_data['id'])) {
									$ThisFileInfo['error'][] = 'Unhandled segment['.__LINE__.'] ('.$element_data['id'].') at '.$element_data_offset;
								} else {
									$this->warnings[] = 'Unhandled segment['.__LINE__.'] ('.$element_data['id'].'::'.$element_data['id_name'].') at '.$element_data['offset'];
								}
								break;
						}
						$offset = $element_end;
					}
					break;


				default:
					$ThisFileInfo['error'][] = 'Unhandled chunk['.__LINE__.'] ('.$top_element_id.') at '.$offset;
					break;
			}
			$offset = $top_element_endoffset;
		}



		if (isset($ThisFileInfo['matroska']['info']) && is_array($ThisFileInfo['matroska']['info'])) {
			foreach ($ThisFileInfo['matroska']['info'] as $key => $infoarray) {
				if (isset($infoarray['Duration'])) {
					// TimecodeScale is how many nanoseconds each Duration unit is
					$ThisFileInfo['playtime_seconds'] = $infoarray['Duration'] * ((isset($infoarray['TimecodeScale']) ? $infoarray['TimecodeScale'] : 1000000) / 1000000000);
					break;
				}
			}
		}
		if (isset($ThisFileInfo['matroska']['tracks']['tracks']) && is_array($ThisFileInfo['matroska']['tracks']['tracks'])) {
			foreach ($ThisFileInfo['matroska']['tracks']['tracks'] as $key => $trackarray) {
				$track_info = array();
				switch (@$trackarray['TrackType']) {
					case 1: // Video
						if (@$trackarray['PixelWidth'])      { $track_info['resolution_x']  =                                    $trackarray['PixelWidth'];          }
						if (@$trackarray['PixelHeight'])     { $track_info['resolution_y']  =                                    $trackarray['PixelHeight'];         }
						if (@$trackarray['DisplayWidth'])    { $track_info['display_x']     =                                    $trackarray['DisplayWidth'];        }
						if (@$trackarray['DisplayHeight'])   { $track_info['display_y']     =                                    $trackarray['DisplayHeight'];       }
						if (@$trackarray['DefaultDuration']) { $track_info['frame_rate']    =                 round(1000000000 / $trackarray['DefaultDuration'], 3); }
						if (@$trackarray['CodecID'])         { $track_info['dataformat']    = $this->MatroskaCodecIDtoCommonName($trackarray['CodecID']);            }
						if (!empty($trackarray['codec_private_parsed']['fourcc'])) {
							$track_info['fourcc'] = $trackarray['codec_private_parsed']['fourcc'];
						}
						$ThisFileInfo['video']['streams'][] = $track_info;
						if (isset($track_info['resolution_x']) && empty($ThisFileInfo['video']['resolution_x'])) {
							foreach ($track_info as $key => $value) {
								$ThisFileInfo['video'][$key] = $value;
							}
						}
						break;
					case 2: // Audio
						if (@$trackarray['CodecID'])           { $track_info['dataformat']      = $this->MatroskaCodecIDtoCommonName($trackarray['CodecID']);          }
						if (@$trackarray['SamplingFrequency']) { $track_info['sample_rate']     =                                    $trackarray['SamplingFrequency']; }
						if (@$trackarray['Channels'])          { $track_info['channels']        =                                    $trackarray['Channels'];          }
						if (@$trackarray['BitDepth'])          { $track_info['bits_per_sample'] =                                    $trackarray['BitDepth'];          }
						switch (@$trackarray[$this->EBMLidName(EBML_ID_CODECID)]) {
							case 'A_PCM/INT/LIT':
							case 'A_PCM/INT/BIG':
								$track_info['bitrate'] = $trackarray['SamplingFrequency'] * $trackarray['Channels'] * $trackarray['BitDepth'];
								break;

							case 'A_AC3':
								if (getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.audio.ac3.php', __FILE__, false)) {
									$ac3_thisfileinfo = array('avdataoffset'=>$ThisFileInfo['matroska']['track_data_offsets'][$trackarray['TrackNumber']]);
									$getid3_ac3 = new getid3_ac3($fd, $ac3_thisfileinfo);
									$ThisFileInfo['matroska']['track_codec_parsed'][$trackarray['TrackNumber']] = $ac3_thisfileinfo;
									if (!empty($ac3_thisfileinfo['error'])) {
										foreach ($ac3_thisfileinfo['error'] as $newerror) {
											$this->warnings[] = 'getid3_ac3() says: ['.$newerror.']';
										}
									}
									if (!empty($ac3_thisfileinfo['warning'])) {
										foreach ($ac3_thisfileinfo['warning'] as $newerror) {
											$this->warnings[] = 'getid3_ac3() says: ['.$newerror.']';
										}
									}
									if (isset($ac3_thisfileinfo['audio']) && is_array($ac3_thisfileinfo['audio'])) {
										foreach ($ac3_thisfileinfo['audio'] as $key => $value) {
											$track_info[$key] = $value;
										}
									}
									unset($ac3_thisfileinfo);
									unset($getid3_ac3);
								} else {
									$this->warnings[] = 'Unable to parse audio data['.__LINE__.'] because cannot include "module.audio.ac3.php"';
								}
								break;

							case 'A_DTS':
								$dts_offset = $ThisFileInfo['matroska']['track_data_offsets'][$trackarray['TrackNumber']];
								// this is a NASTY hack, but sometimes audio data is off by a byte or two and not sure why, email info@getid3.org if you can explain better
								fseek($fd, $dts_offset, SEEK_SET);
								$magic_test = fread($fd, 8);
								for ($i = 0; $i < 4; $i++) {
									// look to see if DTS "magic" is here, if so adjust offset by that many bytes
									if (substr($magic_test, $i, 4) == "\x7F\xFE\x80\x01") {
										$dts_offset += $i;
										break;
									}
								}
								if (getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.audio.dts.php', __FILE__, false)) {
									$dts_thisfileinfo = array('avdataoffset'=>$dts_offset);
									$getid3_dts = new getid3_dts($fd, $dts_thisfileinfo);
									$ThisFileInfo['matroska']['track_codec_parsed'][$trackarray['TrackNumber']] = $dts_thisfileinfo;
									if (!empty($dts_thisfileinfo['error'])) {
										foreach ($dts_thisfileinfo['error'] as $newerror) {
											$this->warnings[] = 'getid3_dts() says: ['.$newerror.']';
										}
									}
									if (!empty($dts_thisfileinfo['warning'])) {
										foreach ($dts_thisfileinfo['warning'] as $newerror) {
											$this->warnings[] = 'getid3_dts() says: ['.$newerror.']';
										}
									}
									if (isset($dts_thisfileinfo['audio']) && is_array($dts_thisfileinfo['audio'])) {
										foreach ($dts_thisfileinfo['audio'] as $key => $value) {
											$track_info[$key] = $value;
										}
									}
									unset($dts_thisfileinfo);
									unset($getid3_dts);
								} else {
									$this->warnings[] = 'Unable to parse audio data['.__LINE__.'] because cannot include "module.audio.dts.php"';
								}
								break;

							//case 'A_AAC':
							//	if (getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.audio.aac.php', __FILE__, false)) {
							//		$aac_thisfileinfo = array('avdataoffset'=>$ThisFileInfo['matroska']['track_data_offsets'][$trackarray['TrackNumber']]);
							//		$getid3_aac = new getid3_aac($fd, $aac_thisfileinfo);
							//		$ThisFileInfo['matroska']['track_codec_parsed'][$trackarray['TrackNumber']] = $aac_thisfileinfo;
							//		if (isset($aac_thisfileinfo['audio']) && is_array($aac_thisfileinfo['audio'])) {
							//			foreach ($aac_thisfileinfo['audio'] as $key => $value) {
							//				$track_info[$key] = $value;
							//			}
							//		}
							//		unset($aac_thisfileinfo);
							//		unset($getid3_aac);
							//	} else {
							//		$this->warnings[] = 'Unable to parse audio data['.__LINE__.'] because cannot include "module.audio.aac.php"';
							//	}
							//	break;

							case 'A_MPEG/L3':
								if (getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.audio.mp3.php', __FILE__, false)) {
									$mp3_thisfileinfo = array(
										'avdataoffset' => $ThisFileInfo['matroska']['track_data_offsets'][$trackarray['TrackNumber']],
										'avdataend'    => $ThisFileInfo['matroska']['track_data_offsets'][$trackarray['TrackNumber']] + 1024,
									);
									$getid3_mp3 = new getid3_mp3($fd, $mp3_thisfileinfo);
									$getid3_mp3->allow_bruteforce = true;
									//getid3_mp3::getOnlyMPEGaudioInfo($fd, $mp3_thisfileinfo, $offset, false);
									$ThisFileInfo['matroska']['track_codec_parsed'][$trackarray['TrackNumber']] = $mp3_thisfileinfo;
									if (!empty($mp3_thisfileinfo['error'])) {
										foreach ($mp3_thisfileinfo['error'] as $newerror) {
											$this->warnings[] = 'getid3_mp3() says: ['.$newerror.']';
										}
									}
									if (!empty($mp3_thisfileinfo['warning'])) {
										foreach ($mp3_thisfileinfo['warning'] as $newerror) {
											$this->warnings[] = 'getid3_mp3() says: ['.$newerror.']';
										}
									}
									if (isset($mp3_thisfileinfo['audio']) && is_array($mp3_thisfileinfo['audio'])) {
										foreach ($mp3_thisfileinfo['audio'] as $key => $value) {
											$track_info[$key] = $value;
										}
									}
									unset($mp3_thisfileinfo);
									unset($getid3_mp3);
								} else {
									$this->warnings[] = 'Unable to parse audio data['.__LINE__.'] because cannot include "module.audio.mp3.php"';
								}
								break;

							case 'A_VORBIS':
								if (isset($trackarray['CodecPrivate'])) {
									// this is a NASTY hack, email info@getid3.org if you have a better idea how to get this info out
									$found_vorbis = false;
									for ($vorbis_offset = 1; $vorbis_offset < 16; $vorbis_offset++) {
										if (substr($trackarray['CodecPrivate'], $vorbis_offset, 6) == 'vorbis') {
											$vorbis_offset--;
											$found_vorbis = true;
											break;
										}
									}
									if ($found_vorbis) {
										if (getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.audio.ogg.php', __FILE__, false)) {
											$vorbis_fileinfo = array();
											$oggpageinfo['page_seqno'] = 0;
											getid3_ogg::ParseVorbisPageHeader($trackarray['CodecPrivate'], $vorbis_offset, $vorbis_fileinfo, $oggpageinfo);
											$ThisFileInfo['matroska']['track_codec_parsed'][$trackarray['TrackNumber']] = $vorbis_fileinfo;
											if (!empty($vorbis_fileinfo['error'])) {
												foreach ($vorbis_fileinfo['error'] as $newerror) {
													$this->warnings[] = 'getid3_ogg() says: ['.$newerror.']';
												}
											}
											if (!empty($vorbis_fileinfo['warning'])) {
												foreach ($vorbis_fileinfo['warning'] as $newerror) {
													$this->warnings[] = 'getid3_ogg() says: ['.$newerror.']';
												}
											}
											if (isset($vorbis_fileinfo['audio']) && is_array($vorbis_fileinfo['audio'])) {
												foreach ($vorbis_fileinfo['audio'] as $key => $value) {
													$track_info[$key] = $value;
												}
											}
											if (@$vorbis_fileinfo['ogg']['bitrate_average']) {
												$track_info['bitrate'] = $vorbis_fileinfo['ogg']['bitrate_average'];
											} elseif (@$vorbis_fileinfo['ogg']['bitrate_nominal']) {
												$track_info['bitrate'] = $vorbis_fileinfo['ogg']['bitrate_nominal'];
											}
											unset($vorbis_fileinfo);
											unset($oggpageinfo);
										} else {
											$this->warnings[] = 'Unable to parse audio data['.__LINE__.'] because cannot include "module.audio.ogg.php"';
										}
									} else {
									}
								} else {
								}
								break;

							default:
								$this->warnings[] = 'Unhandled audio type "'.@$trackarray[$this->EBMLidName(EBML_ID_CODECID)].'"';
								break;
						}


						$ThisFileInfo['audio']['streams'][] = $track_info;
						if (isset($track_info['dataformat']) && empty($ThisFileInfo['audio']['dataformat'])) {
							foreach ($track_info as $key => $value) {
								$ThisFileInfo['audio'][$key] = $value;
							}
						}
						break;
					default:
						// ignore, do nothing
						break;
				}
			}
		}

		if ($this->hide_clusters) {
			// too much data returned that is usually not useful
			if (isset($ThisFileInfo['matroska']['segments']) && is_array($ThisFileInfo['matroska']['segments'])) {
				foreach ($ThisFileInfo['matroska']['segments'] as $key => $segmentsarray) {
					if ($segmentsarray['id'] == EBML_ID_CLUSTER) {
						unset($ThisFileInfo['matroska']['segments'][$key]);
					}
				}
			}
			if (isset($ThisFileInfo['matroska']['seek']) && is_array($ThisFileInfo['matroska']['seek'])) {
				foreach ($ThisFileInfo['matroska']['seek'] as $key => $seekarray) {
					if ($seekarray['target_id'] == EBML_ID_CLUSTER) {
						unset($ThisFileInfo['matroska']['seek'][$key]);
					}
				}
			}
			//unset($ThisFileInfo['matroska']['cluster']);
			//unset($ThisFileInfo['matroska']['track_data_offsets']);
		}

		if (!empty($ThisFileInfo['video']['streams'])) {
			$ThisFileInfo['mime_type'] = 'video/x-matroska';
		} elseif (!empty($ThisFileInfo['video']['streams'])) {
			$ThisFileInfo['mime_type'] = 'audio/x-matroska';
		} elseif (isset($ThisFileInfo['mime_type'])) {
			unset($ThisFileInfo['mime_type']);
		}

		foreach ($this->warnings as $key => $value) {
			$ThisFileInfo['warning'][] = $value;
		}

		return true;
	}


///////////////////////////////////////


	function EnsureBufferHasEnoughData(&$fd, &$EBMLdata, &$offset, &$EBMLdata_offset) {
		$min_data = 1024;
		if ($offset > 2147450880) { // 2^31 - 2^15 (2G-32k)
			$offset = pow(2,63);
			return false;
		} elseif (($offset - $EBMLdata_offset) >= (strlen($EBMLdata) - $min_data)) {
			fseek($fd, $offset, SEEK_SET);
			$EBMLdata_offset = ftell($fd);
			$EBMLdata = fread($fd, $this->read_buffer_size);
		}
		return true;
	}

	function readEBMLint(&$string, &$offset, $dataoffset=0) {
		$actual_offset = $offset - $dataoffset;
		if ($offset > 2147450880) { // 2^31 - 2^15 (2G-32k)
			$this->warnings[] = 'aborting readEBMLint() because $offset larger than 2GB';
			return false;
		} elseif ($actual_offset >= strlen($string)) {
			$this->warnings[] = '$actual_offset > $string in readEBMLint($string['.strlen($string).'], '.$offset.', '.$dataoffset.')';
			return false;
		} elseif ($actual_offset < 0) {
			$this->warnings[] = '$actual_offset < 0 in readEBMLint($string['.strlen($string).'], '.$offset.', '.$dataoffset.')';
			return false;
		}
		$first_byte_int = ord($string{$actual_offset});
		if (0x80 & $first_byte_int) {
			$length = 1;
		} elseif (0x40 & $first_byte_int) {
			$length = 2;
		} elseif (0x20 & $first_byte_int) {
			$length = 3;
		} elseif (0x10 & $first_byte_int) {
			$length = 4;
		} elseif (0x08 & $first_byte_int) {
			$length = 5;
		} elseif (0x04 & $first_byte_int) {
			$length = 6;
		} elseif (0x02 & $first_byte_int) {
			$length = 7;
		} elseif (0x01 & $first_byte_int) {
			$length = 8;
		} else {
			$offset = pow(2,63); // abort processing, skip to end of file
			$this->warnings[] = 'invalid EBML integer (leading 0x00) at '.$offset;
			return false;
		}
		$int_value = $this->EBML2Int(substr($string, $actual_offset, $length));
		$offset += $length;
		return $int_value;
	}

	function EBML2Int($EBMLstring) {
		// http://matroska.org/specs/

		// Element ID coded with an UTF-8 like system:
		// 1xxx xxxx                                  - Class A IDs (2^7 -2 possible values) (base 0x8X)
		// 01xx xxxx  xxxx xxxx                       - Class B IDs (2^14-2 possible values) (base 0x4X 0xXX)
		// 001x xxxx  xxxx xxxx  xxxx xxxx            - Class C IDs (2^21-2 possible values) (base 0x2X 0xXX 0xXX)
		// 0001 xxxx  xxxx xxxx  xxxx xxxx  xxxx xxxx - Class D IDs (2^28-2 possible values) (base 0x1X 0xXX 0xXX 0xXX)
		// Values with all x at 0 and 1 are reserved (hence the -2).

		// Data size, in octets, is also coded with an UTF-8 like system :
		// 1xxx xxxx                                                                              - value 0 to  2^7-2
		// 01xx xxxx  xxxx xxxx                                                                   - value 0 to 2^14-2
		// 001x xxxx  xxxx xxxx  xxxx xxxx                                                        - value 0 to 2^21-2
		// 0001 xxxx  xxxx xxxx  xxxx xxxx  xxxx xxxx                                             - value 0 to 2^28-2
		// 0000 1xxx  xxxx xxxx  xxxx xxxx  xxxx xxxx  xxxx xxxx                                  - value 0 to 2^35-2
		// 0000 01xx  xxxx xxxx  xxxx xxxx  xxxx xxxx  xxxx xxxx  xxxx xxxx                       - value 0 to 2^42-2
		// 0000 001x  xxxx xxxx  xxxx xxxx  xxxx xxxx  xxxx xxxx  xxxx xxxx  xxxx xxxx            - value 0 to 2^49-2
		// 0000 0001  xxxx xxxx  xxxx xxxx  xxxx xxxx  xxxx xxxx  xxxx xxxx  xxxx xxxx  xxxx xxxx - value 0 to 2^56-2

		$first_byte_int = ord($EBMLstring{0});
		if (0x80 & $first_byte_int) {
			$EBMLstring{0} = chr($first_byte_int & 0x7F);
		} elseif (0x40 & $first_byte_int) {
			$EBMLstring{0} = chr($first_byte_int & 0x3F);
		} elseif (0x20 & $first_byte_int) {
			$EBMLstring{0} = chr($first_byte_int & 0x1F);
		} elseif (0x10 & $first_byte_int) {
			$EBMLstring{0} = chr($first_byte_int & 0x0F);
		} elseif (0x08 & $first_byte_int) {
			$EBMLstring{0} = chr($first_byte_int & 0x07);
		} elseif (0x04 & $first_byte_int) {
			$EBMLstring{0} = chr($first_byte_int & 0x03);
		} elseif (0x02 & $first_byte_int) {
			$EBMLstring{0} = chr($first_byte_int & 0x01);
		} elseif (0x01 & $first_byte_int) {
			$EBMLstring{0} = chr($first_byte_int & 0x00);
		} else {
			return false;
		}
		return getid3_lib::BigEndian2Int($EBMLstring);
	}


	function EBMLdate2unix($EBMLdatestamp) {
		// Date - signed 8 octets integer in nanoseconds with 0 indicating the precise beginning of the millennium (at 2001-01-01T00:00:00,000000000 UTC)
		// 978307200 == mktime(0, 0, 0, 1, 1, 2001) == January 1, 2001 12:00:00am UTC
		return round(($EBMLdatestamp / 1000000000) + 978307200);
	}


	function MatroskaBlockLacingType($lacingtype) {
		// http://matroska.org/technical/specs/index.html#block_structure
		static $MatroskaBlockLacingType = array();
		if (empty($MatroskaBlockLacingType)) {
			$MatroskaBlockLacingType[0x00] = 'no lacing';
			$MatroskaBlockLacingType[0x01] = 'Xiph lacing';
			$MatroskaBlockLacingType[0x02] = 'fixed-size lacing';
			$MatroskaBlockLacingType[0x03] = 'EBML lacing';
		}
		return (isset($MatroskaBlockLacingType[$lacingtype]) ? $MatroskaBlockLacingType[$lacingtype] : $lacingtype);
	}

	function MatroskaCodecIDtoCommonName($codecid) {
		// http://www.matroska.org/technical/specs/codecid/index.html
		static $MatroskaCodecIDlist = array();
		if (empty($MatroskaCodecIDlist)) {
			$MatroskaCodecIDlist['A_AAC']            = 'aac';
			$MatroskaCodecIDlist['A_AAC/MPEG2/LC']   = 'aac';
			$MatroskaCodecIDlist['A_AC3']            = 'ac3';
			$MatroskaCodecIDlist['A_DTS']            = 'dts';
			$MatroskaCodecIDlist['A_FLAC']           = 'flac';
			$MatroskaCodecIDlist['A_MPEG/L1']        = 'mp1';
			$MatroskaCodecIDlist['A_MPEG/L2']        = 'mp2';
			$MatroskaCodecIDlist['A_MPEG/L3']        = 'mp3';
			$MatroskaCodecIDlist['A_PCM/INT/LIT']    = 'pcm';       // PCM Integer Little Endian
			$MatroskaCodecIDlist['A_PCM/INT/BIG']    = 'pcm';       // PCM Integer Big Endian
			$MatroskaCodecIDlist['A_QUICKTIME/QDMC'] = 'quicktime'; // Quicktime: QDesign Music
			$MatroskaCodecIDlist['A_QUICKTIME/QDM2'] = 'quicktime'; // Quicktime: QDesign Music v2
			$MatroskaCodecIDlist['A_VORBIS']         = 'vorbis';
			$MatroskaCodecIDlist['V_MPEG1']          = 'mpeg';
			$MatroskaCodecIDlist['V_THEORA']         = 'theora';
			$MatroskaCodecIDlist['V_REAL/RV40']      = 'real';
			$MatroskaCodecIDlist['V_REAL/RV10']      = 'real';
			$MatroskaCodecIDlist['V_REAL/RV20']      = 'real';
			$MatroskaCodecIDlist['V_REAL/RV30']      = 'real';
			$MatroskaCodecIDlist['V_QUICKTIME']      = 'quicktime'; // Quicktime
			$MatroskaCodecIDlist['V_MPEG4/ISO/AP']   = 'mpeg4';
			$MatroskaCodecIDlist['V_MPEG4/ISO/ASP']  = 'mpeg4';
			$MatroskaCodecIDlist['V_MPEG4/ISO/AVC']  = 'h264';
			$MatroskaCodecIDlist['V_MPEG4/ISO/SP']   = 'mpeg4';
		}
		return (isset($MatroskaCodecIDlist[$codecid]) ? $MatroskaCodecIDlist[$codecid] : $codecid);
	}

	function EBMLidName($value) {
		static $EBMLidList = array();
		if (empty($EBMLidList)) {
			$EBMLidList[EBML_ID_ASPECTRATIOTYPE]            = 'AspectRatioType';
			$EBMLidList[EBML_ID_ATTACHEDFILE]               = 'AttachedFile';
			$EBMLidList[EBML_ID_ATTACHMENTLINK]             = 'AttachmentLink';
			$EBMLidList[EBML_ID_ATTACHMENTS]                = 'Attachments';
			$EBMLidList[EBML_ID_ATTACHMENTUID]              = 'AttachmentUID';
			$EBMLidList[EBML_ID_AUDIO]                      = 'Audio';
			$EBMLidList[EBML_ID_BITDEPTH]                   = 'BitDepth';
			$EBMLidList[EBML_ID_CHANNELPOSITIONS]           = 'ChannelPositions';
			$EBMLidList[EBML_ID_CHANNELS]                   = 'Channels';
			$EBMLidList[EBML_ID_CHAPCOUNTRY]                = 'ChapCountry';
			$EBMLidList[EBML_ID_CHAPLANGUAGE]               = 'ChapLanguage';
			$EBMLidList[EBML_ID_CHAPPROCESS]                = 'ChapProcess';
			$EBMLidList[EBML_ID_CHAPPROCESSCODECID]         = 'ChapProcessCodecID';
			$EBMLidList[EBML_ID_CHAPPROCESSCOMMAND]         = 'ChapProcessCommand';
			$EBMLidList[EBML_ID_CHAPPROCESSDATA]            = 'ChapProcessData';
			$EBMLidList[EBML_ID_CHAPPROCESSPRIVATE]         = 'ChapProcessPrivate';
			$EBMLidList[EBML_ID_CHAPPROCESSTIME]            = 'ChapProcessTime';
			$EBMLidList[EBML_ID_CHAPSTRING]                 = 'ChapString';
			$EBMLidList[EBML_ID_CHAPTERATOM]                = 'ChapterAtom';
			$EBMLidList[EBML_ID_CHAPTERDISPLAY]             = 'ChapterDisplay';
			$EBMLidList[EBML_ID_CHAPTERFLAGENABLED]         = 'ChapterFlagEnabled';
			$EBMLidList[EBML_ID_CHAPTERFLAGHIDDEN]          = 'ChapterFlagHidden';
			$EBMLidList[EBML_ID_CHAPTERPHYSICALEQUIV]       = 'ChapterPhysicalEquiv';
			$EBMLidList[EBML_ID_CHAPTERS]                   = 'Chapters';
			$EBMLidList[EBML_ID_CHAPTERSEGMENTEDITIONUID]   = 'ChapterSegmentEditionUID';
			$EBMLidList[EBML_ID_CHAPTERSEGMENTUID]          = 'ChapterSegmentUID';
			$EBMLidList[EBML_ID_CHAPTERTIMEEND]             = 'ChapterTimeEnd';
			$EBMLidList[EBML_ID_CHAPTERTIMESTART]           = 'ChapterTimeStart';
			$EBMLidList[EBML_ID_CHAPTERTRACK]               = 'ChapterTrack';
			$EBMLidList[EBML_ID_CHAPTERTRACKNUMBER]         = 'ChapterTrackNumber';
			$EBMLidList[EBML_ID_CHAPTERTRANSLATE]           = 'ChapterTranslate';
			$EBMLidList[EBML_ID_CHAPTERTRANSLATECODEC]      = 'ChapterTranslateCodec';
			$EBMLidList[EBML_ID_CHAPTERTRANSLATEEDITIONUID] = 'ChapterTranslateEditionUID';
			$EBMLidList[EBML_ID_CHAPTERTRANSLATEID]         = 'ChapterTranslateID';
			$EBMLidList[EBML_ID_CHAPTERUID]                 = 'ChapterUID';
			$EBMLidList[EBML_ID_CLUSTER]                    = 'Cluster';
			$EBMLidList[EBML_ID_CLUSTERBLOCK]               = 'ClusterBlock';
			$EBMLidList[EBML_ID_CLUSTERBLOCKADDID]          = 'ClusterBlockAddID';
			$EBMLidList[EBML_ID_CLUSTERBLOCKADDITIONAL]     = 'ClusterBlockAdditional';
			$EBMLidList[EBML_ID_CLUSTERBLOCKADDITIONID]     = 'ClusterBlockAdditionID';
			$EBMLidList[EBML_ID_CLUSTERBLOCKADDITIONS]      = 'ClusterBlockAdditions';
			$EBMLidList[EBML_ID_CLUSTERBLOCKDURATION]       = 'ClusterBlockDuration';
			$EBMLidList[EBML_ID_CLUSTERBLOCKGROUP]          = 'ClusterBlockGroup';
			$EBMLidList[EBML_ID_CLUSTERBLOCKMORE]           = 'ClusterBlockMore';
			$EBMLidList[EBML_ID_CLUSTERBLOCKVIRTUAL]        = 'ClusterBlockVirtual';
			$EBMLidList[EBML_ID_CLUSTERCODECSTATE]          = 'ClusterCodecState';
			$EBMLidList[EBML_ID_CLUSTERDELAY]               = 'ClusterDelay';
			$EBMLidList[EBML_ID_CLUSTERDURATION]            = 'ClusterDuration';
			$EBMLidList[EBML_ID_CLUSTERENCRYPTEDBLOCK]      = 'ClusterEncryptedBlock';
			$EBMLidList[EBML_ID_CLUSTERFRAMENUMBER]         = 'ClusterFrameNumber';
			$EBMLidList[EBML_ID_CLUSTERLACENUMBER]          = 'ClusterLaceNumber';
			$EBMLidList[EBML_ID_CLUSTERPOSITION]            = 'ClusterPosition';
			$EBMLidList[EBML_ID_CLUSTERPREVSIZE]            = 'ClusterPrevSize';
			$EBMLidList[EBML_ID_CLUSTERREFERENCEBLOCK]      = 'ClusterReferenceBlock';
			$EBMLidList[EBML_ID_CLUSTERREFERENCEPRIORITY]   = 'ClusterReferencePriority';
			$EBMLidList[EBML_ID_CLUSTERREFERENCEVIRTUAL]    = 'ClusterReferenceVirtual';
			$EBMLidList[EBML_ID_CLUSTERSILENTTRACKNUMBER]   = 'ClusterSilentTrackNumber';
			$EBMLidList[EBML_ID_CLUSTERSILENTTRACKS]        = 'ClusterSilentTracks';
			$EBMLidList[EBML_ID_CLUSTERSIMPLEBLOCK]         = 'ClusterSimpleBlock';
			$EBMLidList[EBML_ID_CLUSTERTIMECODE]            = 'ClusterTimecode';
			$EBMLidList[EBML_ID_CLUSTERTIMESLICE]           = 'ClusterTimeSlice';
			$EBMLidList[EBML_ID_CODECDECODEALL]             = 'CodecDecodeAll';
			$EBMLidList[EBML_ID_CODECDOWNLOADURL]           = 'CodecDownloadURL';
			$EBMLidList[EBML_ID_CODECID]                    = 'CodecID';
			$EBMLidList[EBML_ID_CODECINFOURL]               = 'CodecInfoURL';
			$EBMLidList[EBML_ID_CODECNAME]                  = 'CodecName';
			$EBMLidList[EBML_ID_CODECPRIVATE]               = 'CodecPrivate';
			$EBMLidList[EBML_ID_CODECSETTINGS]              = 'CodecSettings';
			$EBMLidList[EBML_ID_COLOURSPACE]                = 'ColourSpace';
			$EBMLidList[EBML_ID_CONTENTCOMPALGO]            = 'ContentCompAlgo';
			$EBMLidList[EBML_ID_CONTENTCOMPRESSION]         = 'ContentCompression';
			$EBMLidList[EBML_ID_CONTENTCOMPSETTINGS]        = 'ContentCompSettings';
			$EBMLidList[EBML_ID_CONTENTENCALGO]             = 'ContentEncAlgo';
			$EBMLidList[EBML_ID_CONTENTENCKEYID]            = 'ContentEncKeyID';
			$EBMLidList[EBML_ID_CONTENTENCODING]            = 'ContentEncoding';
			$EBMLidList[EBML_ID_CONTENTENCODINGORDER]       = 'ContentEncodingOrder';
			$EBMLidList[EBML_ID_CONTENTENCODINGS]           = 'ContentEncodings';
			$EBMLidList[EBML_ID_CONTENTENCODINGSCOPE]       = 'ContentEncodingScope';
			$EBMLidList[EBML_ID_CONTENTENCODINGTYPE]        = 'ContentEncodingType';
			$EBMLidList[EBML_ID_CONTENTENCRYPTION]          = 'ContentEncryption';
			$EBMLidList[EBML_ID_CONTENTSIGALGO]             = 'ContentSigAlgo';
			$EBMLidList[EBML_ID_CONTENTSIGHASHALGO]         = 'ContentSigHashAlgo';
			$EBMLidList[EBML_ID_CONTENTSIGKEYID]            = 'ContentSigKeyID';
			$EBMLidList[EBML_ID_CONTENTSIGNATURE]           = 'ContentSignature';
			$EBMLidList[EBML_ID_CRC32]                      = 'CRC32';
			$EBMLidList[EBML_ID_CUEBLOCKNUMBER]             = 'CueBlockNumber';
			$EBMLidList[EBML_ID_CUECLUSTERPOSITION]         = 'CueClusterPosition';
			$EBMLidList[EBML_ID_CUECODECSTATE]              = 'CueCodecState';
			$EBMLidList[EBML_ID_CUEPOINT]                   = 'CuePoint';
			$EBMLidList[EBML_ID_CUEREFCLUSTER]              = 'CueRefCluster';
			$EBMLidList[EBML_ID_CUEREFCODECSTATE]           = 'CueRefCodecState';
			$EBMLidList[EBML_ID_CUEREFERENCE]               = 'CueReference';
			$EBMLidList[EBML_ID_CUEREFNUMBER]               = 'CueRefNumber';
			$EBMLidList[EBML_ID_CUEREFTIME]                 = 'CueRefTime';
			$EBMLidList[EBML_ID_CUES]                       = 'Cues';
			$EBMLidList[EBML_ID_CUETIME]                    = 'CueTime';
			$EBMLidList[EBML_ID_CUETRACK]                   = 'CueTrack';
			$EBMLidList[EBML_ID_CUETRACKPOSITIONS]          = 'CueTrackPositions';
			$EBMLidList[EBML_ID_DATEUTC]                    = 'DateUTC';
			$EBMLidList[EBML_ID_DEFAULTDURATION]            = 'DefaultDuration';
			$EBMLidList[EBML_ID_DISPLAYHEIGHT]              = 'DisplayHeight';
			$EBMLidList[EBML_ID_DISPLAYUNIT]                = 'DisplayUnit';
			$EBMLidList[EBML_ID_DISPLAYWIDTH]               = 'DisplayWidth';
			$EBMLidList[EBML_ID_DOCTYPE]                    = 'DocType';
			$EBMLidList[EBML_ID_DOCTYPEREADVERSION]         = 'DocTypeReadVersion';
			$EBMLidList[EBML_ID_DOCTYPEVERSION]             = 'DocTypeVersion';
			$EBMLidList[EBML_ID_DURATION]                   = 'Duration';
			$EBMLidList[EBML_ID_EBMLMAXIDLENGTH]            = 'EBMLMaxIDLength';
			$EBMLidList[EBML_ID_EBMLMAXSIZELENGTH]          = 'EBMLMaxSizeLength';
			$EBMLidList[EBML_ID_EBMLREADVERSION]            = 'EBMLReadVersion';
			$EBMLidList[EBML_ID_EBMLVERSION]                = 'EBMLVersion';
			$EBMLidList[EBML_ID_EDITIONENTRY]               = 'EditionEntry';
			$EBMLidList[EBML_ID_EDITIONFLAGDEFAULT]         = 'EditionFlagDefault';
			$EBMLidList[EBML_ID_EDITIONFLAGHIDDEN]          = 'EditionFlagHidden';
			$EBMLidList[EBML_ID_EDITIONFLAGORDERED]         = 'EditionFlagOrdered';
			$EBMLidList[EBML_ID_EDITIONUID]                 = 'EditionUID';
			$EBMLidList[EBML_ID_FILEDATA]                   = 'FileData';
			$EBMLidList[EBML_ID_FILEDESCRIPTION]            = 'FileDescription';
			$EBMLidList[EBML_ID_FILEMIMETYPE]               = 'FileMimeType';
			$EBMLidList[EBML_ID_FILENAME]                   = 'FileName';
			$EBMLidList[EBML_ID_FILEREFERRAL]               = 'FileReferral';
			$EBMLidList[EBML_ID_FILEUID]                    = 'FileUID';
			$EBMLidList[EBML_ID_FLAGDEFAULT]                = 'FlagDefault';
			$EBMLidList[EBML_ID_FLAGENABLED]                = 'FlagEnabled';
			$EBMLidList[EBML_ID_FLAGFORCED]                 = 'FlagForced';
			$EBMLidList[EBML_ID_FLAGINTERLACED]             = 'FlagInterlaced';
			$EBMLidList[EBML_ID_FLAGLACING]                 = 'FlagLacing';
			$EBMLidList[EBML_ID_GAMMAVALUE]                 = 'GammaValue';
			$EBMLidList[EBML_ID_INFO]                       = 'Info';
			$EBMLidList[EBML_ID_LANGUAGE]                   = 'Language';
			$EBMLidList[EBML_ID_MAXBLOCKADDITIONID]         = 'MaxBlockAdditionID';
			$EBMLidList[EBML_ID_MAXCACHE]                   = 'MaxCache';
			$EBMLidList[EBML_ID_MINCACHE]                   = 'MinCache';
			$EBMLidList[EBML_ID_MUXINGAPP]                  = 'MuxingApp';
			$EBMLidList[EBML_ID_NAME]                       = 'Name';
			$EBMLidList[EBML_ID_NEXTFILENAME]               = 'NextFilename';
			$EBMLidList[EBML_ID_NEXTUID]                    = 'NextUID';
			$EBMLidList[EBML_ID_OUTPUTSAMPLINGFREQUENCY]    = 'OutputSamplingFrequency';
			$EBMLidList[EBML_ID_PIXELCROPBOTTOM]            = 'PixelCropBottom';
			$EBMLidList[EBML_ID_PIXELCROPLEFT]              = 'PixelCropLeft';
			$EBMLidList[EBML_ID_PIXELCROPRIGHT]             = 'PixelCropRight';
			$EBMLidList[EBML_ID_PIXELCROPTOP]               = 'PixelCropTop';
			$EBMLidList[EBML_ID_PIXELHEIGHT]                = 'PixelHeight';
			$EBMLidList[EBML_ID_PIXELWIDTH]                 = 'PixelWidth';
			$EBMLidList[EBML_ID_PREVFILENAME]               = 'PrevFilename';
			$EBMLidList[EBML_ID_PREVUID]                    = 'PrevUID';
			$EBMLidList[EBML_ID_SAMPLINGFREQUENCY]          = 'SamplingFrequency';
			$EBMLidList[EBML_ID_SEEK]                       = 'Seek';
			$EBMLidList[EBML_ID_SEEKHEAD]                   = 'SeekHead';
			$EBMLidList[EBML_ID_SEEKID]                     = 'SeekID';
			$EBMLidList[EBML_ID_SEEKPOSITION]               = 'SeekPosition';
			$EBMLidList[EBML_ID_SEGMENTFAMILY]              = 'SegmentFamily';
			$EBMLidList[EBML_ID_SEGMENTFILENAME]            = 'SegmentFilename';
			$EBMLidList[EBML_ID_SEGMENTUID]                 = 'SegmentUID';
			$EBMLidList[EBML_ID_SIMPLETAG]                  = 'SimpleTag';
			$EBMLidList[EBML_ID_CLUSTERSLICES]              = 'ClusterSlices';
			$EBMLidList[EBML_ID_STEREOMODE]                 = 'StereoMode';
			$EBMLidList[EBML_ID_TAG]                        = 'Tag';
			$EBMLidList[EBML_ID_TAGBINARY]                  = 'TagBinary';
			$EBMLidList[EBML_ID_TAGCHAPTERUID]              = 'TagChapterUID';
			$EBMLidList[EBML_ID_TAGDEFAULT]                 = 'TagDefault';
			$EBMLidList[EBML_ID_TAGEDITIONUID]              = 'TagEditionUID';
			$EBMLidList[EBML_ID_TAGLANGUAGE]                = 'TagLanguage';
			$EBMLidList[EBML_ID_TAGNAME]                    = 'TagName';
			$EBMLidList[EBML_ID_TAGTRACKUID]                = 'TagTrackUID';
			$EBMLidList[EBML_ID_TAGS]                       = 'Tags';
			$EBMLidList[EBML_ID_TAGSTRING]                  = 'TagString';
			$EBMLidList[EBML_ID_TARGETS]                    = 'Targets';
			$EBMLidList[EBML_ID_TARGETTYPE]                 = 'TargetType';
			$EBMLidList[EBML_ID_TARGETTYPEVALUE]            = 'TargetTypeValue';
			$EBMLidList[EBML_ID_TIMECODESCALE]              = 'TimecodeScale';
			$EBMLidList[EBML_ID_TITLE]                      = 'Title';
			$EBMLidList[EBML_ID_TRACKENTRY]                 = 'TrackEntry';
			$EBMLidList[EBML_ID_TRACKNUMBER]                = 'TrackNumber';
			$EBMLidList[EBML_ID_TRACKOFFSET]                = 'TrackOffset';
			$EBMLidList[EBML_ID_TRACKOVERLAY]               = 'TrackOverlay';
			$EBMLidList[EBML_ID_TRACKS]                     = 'Tracks';
			$EBMLidList[EBML_ID_TRACKTIMECODESCALE]         = 'TrackTimecodeScale';
			$EBMLidList[EBML_ID_TRACKTRANSLATE]             = 'TrackTranslate';
			$EBMLidList[EBML_ID_TRACKTRANSLATECODEC]        = 'TrackTranslateCodec';
			$EBMLidList[EBML_ID_TRACKTRANSLATEEDITIONUID]   = 'TrackTranslateEditionUID';
			$EBMLidList[EBML_ID_TRACKTRANSLATETRACKID]      = 'TrackTranslateTrackID';
			$EBMLidList[EBML_ID_TRACKTYPE]                  = 'TrackType';
			$EBMLidList[EBML_ID_TRACKUID]                   = 'TrackUID';
			$EBMLidList[EBML_ID_VIDEO]                      = 'Video';
			$EBMLidList[EBML_ID_VOID]                       = 'Void';
			$EBMLidList[EBML_ID_WRITINGAPP]                 = 'WritingApp';
		}
		return (isset($EBMLidList[$value]) ? $EBMLidList[$value] : dechex($value));
	}

}

?>