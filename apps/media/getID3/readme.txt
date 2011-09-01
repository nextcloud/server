/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
//                                                             //
// changelog.txt - part of getID3()                            //
// See readme.txt for more details                             //
//                                                            ///
/////////////////////////////////////////////////////////////////

        This code is released under the GNU GPL:
          http://www.gnu.org/copyleft/gpl.html

     +---------------------------------------------+
     | If you do use this code somewhere, send me  |
     | an email and tell me how/where you used it. |
     |                                             |
     | If you want to donate, there is a link on   |
     | http://www.getid3.org for PayPal donations. |
     +---------------------------------------------+



Quick Start
===========================================================================

Q: How can I check that getID3() works on my server/files?
A: Unzip getID3() to a directory, then access /demos/demo.browse.php



Sourceforge Notification
===========================================================================

It's highly recommended that you sign up for notification from
Sourceforge for when new versions are released. Please visit:
http://sourceforge.net/project/showfiles.php?group_id=55859
and click the little "monitor package" icon/link.  If you're
previously signed up for the mailing list, be aware that it has
been discontinued, only the automated Sourceforge notification
will be used from now on.



What does getID3() do?
===========================================================================

Reads & parses (to varying degrees):
 ¤ tags:
  * APE (v1 and v2)
  * ID3v1 (& ID3v1.1)
  * ID3v2 (v2.4, v2.3, v2.2)
  * Lyrics3 (v1 & v2)

 ¤ audio-lossy:
  * MP3/MP2/MP1
  * MPC / Musepack
  * Ogg (Vorbis, OggFLAC, Speex)
  * RealAudio
  * Speex
  * VQF

 ¤ audio-lossless:
  * AIFF
  * AU
  * Bonk
  * CD-audio (*.cda)
  * FLAC
  * LA (Lossless Audio)
  * LPAC
  * MIDI
  * Monkey's Audio
  * OptimFROG
  * RKAU
  * VOC
  * WAV (RIFF)
  * WavPack

 ¤ audio-video:
  * ASF: ASF, Windows Media Audio (WMA), Windows Media Video (WMV)
  * AVI (RIFF)
  * Flash
  * MPEG-1 / MPEG-2
  * NSV (Nullsoft Streaming Video)
  * Quicktime
  * RealVideo

 ¤ still image:
  * BMP
  * GIF
  * JPEG
  * PNG

 ¤ data:
  * ISO-9660 CD-ROM image (directory structure)
  * SZIP (limited support)
  * ZIP (directory structure)


Writes:
  * ID3v1 (& ID3v1.1)
  * ID3v2 (v2.3 & v2.4)
  * VorbisComment on OggVorbis
  * VorbisComment on FLAC (not OggFLAC)
  * APE v2
  * Lyrics3 (delete only)



Requirements
===========================================================================

* PHP 4.2.0 (or higher) for getID3() 1.7.8 (and up).
* PHP 5.0.0 (or higher) for getID3() 2.0.0 (and up).
* at least 4MB memory for PHP. 8MB is highly recommended.
  12MB is required with all modules loaded.



Usage
===========================================================================

See /demos/demo.basic.php for a very basic use of getID3() with no
fancy output, just scanning one file.

See structure.txt for the returned data structure.

*>  For an example of a complete directory-browsing,       <*
*>  file-scanning implementation of getID3(), please run   <*
*>  /demos/demo.browse.php                                 <*

See /demos/demo.mysql.php for a sample recursive scanning code that
scans every file in a given directory, and all sub-directories, stores
the results in a database and allows various analysis / maintenance
operations

To analyze remote files over HTTP or FTP you need to copy the file
locally first before running getID3(). Your code would look something
like this:

// Copy remote file locally to scan with getID3()
$remotefilename = 'http://www.example.com/filename.mp3';
if ($fp_remote = fopen($remotefilename, 'rb')) {
    $localtempfilename = tempnam('/tmp', 'getID3');
    if ($fp_local = fopen($localtempfilename, 'wb')) {
        while ($buffer = fread($fp_remote, 8192)) {
            fwrite($fp_local, $buffer);
        }
        fclose($fp_local);

		// Initialize getID3 engine
		$getID3 = new getID3;

		$ThisFileInfo = $getID3->analyze($filename);

        // Delete temporary file
        unlink($localtempfilename);
    }
    fclose($fp_remote);
}


See /demos/demo.write.php for how to write tags.



What does the returned data structure look like?
===========================================================================

See structure.txt

It is recommended that you look at the output of
/demos/demo.browse.php scanning the file(s) you're interested in to
confirm what data is actually returned for any particular filetype in
general, and your files in particular, as the actual data returned
may vary considerably depending on what information is available in
the file itself.



Notes
===========================================================================

getID3() 1.7:
If the format parser encounters a critical problem, it will return
something in $fileinfo['error'], describing the encountered error. If
a less critical error or notice is generated it will appear in
$fileinfo['warning']. Both keys may contain more than one warning or
error. If something is returned in ['error'] then the file was not
correctly parsed and returned data may or may not be correct and/or
complete. If something is returned in ['warning'] (and not ['error'])
then the data that is returned is OK - usually getID3() is reporting
errors in the file that have been worked around due to known bugs in
other programs. Some warnings may indicate that the data that is
returned is OK but that some data could not be extracted due to
errors in the file.

getID3() 2.0:
See above except errors are thrown (so you will only get one error).



Disclaimer
===========================================================================

getID3() has been tested on many systems, on many types of files,
under many operating systems, and is generally believe to be stable
and safe. That being said, there is still the chance there is an
undiscovered and/or unfixed bug that may potentially corrupt your
file, especially within the writing functions. By using getID3() you
agree that it's not my fault if any of your files are corrupted.
In fact, I'm not liable for anything :)



License
===========================================================================

GNU General Public License - see license.txt

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to:
Free Software Foundation, Inc.
59 Temple Place - Suite 330
Boston, MA  02111-1307, USA.

FAQ:
Q: Can I use getID3() in my program? Do I need a commercial license?
A: You're generally free to use getID3 however you see fit. The only
   case in which you would require a commercial license is if you're
   selling your closed-source program that integrates getID3. If you
   sell your program including a copy of getID3, that's fine as long
   as you include a copy of the sourcecode when you sell it.  Or you
   can distribute your code without getID3 and say "download it from
   getid3.sourceforge.net"



Future Plans
===========================================================================

* Writing support for Real
* Better support for MP4 container format
* Support for Matroska (www.matroska.org)
  http://corecodec.com/modules.php?op=modload&name=PNphpBB2&file=viewtopic&t=227
* Scan for appended ID3v2 tag at end of file per ID3v2.4 specs (Section 5.0)
* Support for JPEG-2000 (http://www.morgan-multimedia.com/jpeg2000_overview.htm)
* Support for MOD (mod/stm/s3m/it/xm/mtm/ult/669)
* Support for ACE (thanks Vince)
* Support for Ogg other than Vorbis, Speex and OggFlac (ie. Ogg+Xvid)
* Ability to create Xing/LAME VBR header for VBR MP3s that are missing VBR header
* Ability to "clean" ID3v2 padding (replace invalid padding with valid padding)
* Warn if MP3s change version mid-stream (in full-scan mode)
* check for corrupt/broken mid-file MP3 streams in histogram scan
* Support for lossless-compression formats
  (http://www.firstpr.com.au/audiocomp/lossless/#Links)
  (http://compression.ca/act-sound.html)
  (http://web.inter.nl.net/users/hvdh/lossless/lossless.htm)
* Support for RIFF-INFO chunks
  * http://lotto.st-andrews.ac.uk/~njh/tag_interchange.html
    (thanks Nick Humfrey <njhØsurgeradio*co*uk>)
  * http://abcavi.narod.ru/sof/abcavi/infotags.htm
    (thanks Kibi)
* Better support for Bink video
* http://www.hr/josip/DSP/AudioFile2.html
* http://www.pcisys.net/~melanson/codecs/
* Detect mp3PRO
* Support for PSD
* Support for JPC
* Support for JP2
* Support for JPX
* Support for JB2
* Support for IFF
* Support for ICO
* Support for ANI
* Support for EXE (comments, author, etc) (thanks p*quaedackersØplanet*nl)
* Support for DVD-IFO (region, subtitles, aspect ratio, etc)
  (thanks p*quaedackersØplanet*nl)
* More complete support for SWF - parsing encapsulated MP3 and/or JPEG content
    (thanks n8n8Øyahoo*com)
* Support for a2b
* Optional scan-through-frames for AVI verification
  (thanks rockcohenØmassive-interactive*nl)
* Support for TTF (thanks infoØbutterflyx*com)
* Support for DSS (http://www.getid3.org/phpBB2/viewtopic.php?t=171)
* Support for SMAF (http://smaf-yamaha.com/what/demo.html)
  http://www.getid3.org/phpBB2/viewtopic.php?t=182
* Support for AMR (http://www.getid3.org/phpBB2/viewtopic.php?t=195)
* Support for 3gpp (http://www.getid3.org/phpBB2/viewtopic.php?t=195)
* Support for ID4 (http://www.wackysoft.cjb.net grizlyY2KØhotmail*com)
* Parse XML data returned in Ogg comments
* Parse XML data from Quicktime SMIL metafiles (klausrathØmac*com)
* ID3v2 genre string creator function
* More complete parsing of JPG
* Support for all old-style ASF packets
* ASF/WMA/WMV tag writing
* Parse declared T??? ID3v2 text information frames, where appropriate
    (thanks Christian Fritz for the idea)
* Recognize encoder:
  http://www.guerillasoft.com/EncSpot2/index.html
  http://ff123.net/identify.html
  http://www.hydrogenaudio.org/?act=ST&f=16&t=9414
  http://www.hydrogenaudio.org/?showtopic=11785
* Support for other OS/2 bitmap structures: Bitmap Array('BA'),
  Color Icon('CI'), Color Pointer('CP'), Icon('IC'), Pointer ('PT')
  http://netghost.narod.ru/gff/graphics/summary/os2bmp.htm
* Support for WavPack RAW mode
* ASF/WMA/WMV data packet parsing
* ID3v2FrameFlagsLookupTagAlter()
* ID3v2FrameFlagsLookupFileAlter()
* obey ID3v2 tag alter/preserve/discard rules
* http://www.geocities.com/SiliconValley/Sector/9654/Softdoc/Illyrium/Aolyr.htm
* proper checking for LINK/LNK frame validity in ID3v2 writing
* proper checking for ASPI-TLEN frame validity in ID3v2 writing
* proper checking for COMR frame validity in ID3v2 writing
* http://www.geocities.co.jp/SiliconValley-Oakland/3664/index.html
* decode GEOB ID3v2 structure as encoded by RealJukebox,
  decode NCON ID3v2 structure as encoded by MusicMatch
  (probably won't happen - the formats are proprietary)



Known Bugs/Issues in getID3() that may be fixed eventually
===========================================================================

* Cannot determine bitrate for MPEG video with VBR video data
  (need documentation)
* Interlace/progressive cannot be determined for MPEG video
  (need documentation)
* MIDI playtime is sometimes inaccurate
* AAC-RAW mode files cannot be identified
* WavPack-RAW mode files cannot be identified
* mp4 files report lots of "Unknown QuickTime atom type"
   (need documentation)
* Encrypted ASF/WMA/WMV files warn about "unhandled GUID
  ASF_Content_Encryption_Object"
* Bitrate split between audio and video cannot be calculated for
  NSV, only the total bitrate. (need documentation)
* All Ogg formats (Vorbis, OggFLAC, Speex) are affected by the
  problem of large VorbisComments spanning multiple Ogg pages, but
  but only OggVorbis files can be processed with vorbiscomment.
* The version of "head" supplied with Mac OS 10.2.8 (maybe other
  versions too) does only understands a single option (-n) and
  therefore fails. getID3 ignores this and returns wrong md5_data.



Known Bugs/Issues in getID3() that cannot be fixed
--------------------------------------------------

* Files larger than 2GB cannot always be parsed fully by getID3()
  due to limitations in the PHP filesystem functions.
  NOTE: Since v1.7.8b3 there is partial support for larger-than-
  2GB files, most of which will parse OK, as long as no critical
  data is located beyond the 2GB offset.
  Known will-work:
  * ZIP  (format doesn't support files >2GB)
  * FLAC (current encoders don't support files >2GB)
  Known will-not-work:
  * ID3v1 tags (always located at end-of-file)
  * Lyrics3 tags (always located at end-of-file)
  * APE tags (always located at end-of-file)
  Maybe-will-work:
  * Quicktime (will work if needed metadata is before 2GB offset,
    that is if the file has been hinted/optimized for streaming)
  * RIFF.WAV (should work fine, but gives warnings about not being
    able to parse all chunks)
  * RIFF.AVI (playtime will probably be wrong, is only based on
    "movi" chunk that fits in the first 2GB, should issue error
    to show that playtime is incorrect. Other data should be mostly
    correct, assuming that data is constant throughout the file)



Known Bugs/Issues in other programs
-----------------------------------

* Winamp (up to v2.80 at least) does not support ID3v2.4 tags,
    only ID3v2.3
    see: http://forums.winamp.com/showthread.php?postid=387524
* Some versions of Helium2 (www.helium2.com) do not write
    ID3v2.4-compliant Frame Sizes, even though the tag is marked
    as ID3v2.4)  (detected by getID3())
* MP3ext V3.3.17 places a non-compliant padding string at the end
    of the ID3v2 header. This is supposedly fixed in v3.4b21 but
    only if you manually add a registry key. This fix is not yet
    confirmed.  (detected by getID3())
* CDex v1.40 (fixed by v1.50b7) writes non-compliant Ogg comment
    strings, supposed to be in the format "NAME=value" but actually
    written just "value"  (detected by getID3())
* Oggenc 0.9-rc3 flags the encoded file as ABR whether it's
    actually ABR or VBR.
* iTunes (versions "X v2.0.3", "v3.0.1" are known-guilty, probably
    other versions are too) writes ID3v2.3 comment tags using a
    frame name 'COM ' which is not valid for ID3v2.3+ (it's an
    ID3v2.2-style frame name)  (detected by getID3())
* MP2enc does not encode mono CBR MP2 files properly (half speed
    sound and double playtime)
* MP2enc does not encode mono VBR MP2 files properly (actually
    encoded as stereo)
* tooLAME does not encode mono VBR MP2 files properly (actually
    encoded as stereo)
* AACenc encodes files in VBR mode (actually ABR) even if CBR is
   specified
* AAC/ADIF - bitrate_mode = cbr for vbr files
* LAME 3.90-3.92 prepends one frame of null data (space for the
  LAME/VBR header, but it never gets written) when encoding in CBR
  mode with the DLL
* Ahead Nero encodes TwinVQF with a DSIZ value (which is supposed
  to be the filesize in bytes) of "0" for TwinVQF v1.0 and "1" for
  TwinVQF v2.0  (detected by getID3())
* Ahead Nero encodes TwinVQF files 1 second shorter than they
  should be
* AAC-ADTS files are always actually encoded VBR, even if CBR mode
  is specified (the CBR-mode switches on the encoder enable ABR
  mode, not CBR as such, but it's not possible to tell the
  difference between such ABR files and true VBR)
* STREAMINFO.audio_signature in OggFLAC is always null. "The reason
  it's like that is because there is no seeking support in
  libOggFLAC yet, so it has no way to go back and write the
  computed sum after encoding. Seeking support in Ogg FLAC is the
  #1 item for the next release." - Josh Coalson (FLAC developer)
  NOTE: getID3() will calculate md5_data in a method similar to
  other file formats, but that value cannot be compared to the
  md5_data value from FLAC data in a FLAC file format.
* STREAMINFO.audio_signature is not calculated in FLAC v0.3.0 &
  v0.4.0 - getID3() will calculate md5_data in a method similar to
  other file formats, but that value cannot be compared to the
  md5_data value from FLAC v0.5.0+
* RioPort (various versions including 2.0 and 3.11) tags ID3v2 with
  a WCOM frame that has no data portion
* Earlier versions of Coolplayer adds illegal ID3 tags to Ogg Vorbis
  files, thus making them corrupt.
* Meracl ID3 Tag Writer v1.3.4 (and older) incorrectly truncates the
  last byte of data from an MP3 file when appending a new ID3v1 tag.
  (detected by getID3())
* Lossless-Audio files encoded with and without the -noseek switch
  do actually differ internally and therefore cannot match md5_data
* iTunes has been known to append a new ID3v1 tag on the end of an
  existing ID3v1 tag when ID3v2 tag is also present
  (detected by getID3())




Reference material:
===========================================================================

[www.id3.org material now mirrored at http://id3lib.sourceforge.net/id3/]
* http://www.id3.org/id3v2.4.0-structure.txt
* http://www.id3.org/id3v2.4.0-frames.txt
* http://www.id3.org/id3v2.4.0-changes.txt
* http://www.id3.org/id3v2.3.0.txt
* http://www.id3.org/id3v2-00.txt
* http://www.id3.org/mp3frame.html
* http://minnie.tuhs.org/pipermail/mp3encoder/2001-January/001800.html <mathewhendry@hotmail.com>
* http://www.dv.co.yu/mpgscript/mpeghdr.htm
* http://www.mp3-tech.org/programmer/frame_header.html
* http://users.belgacom.net/gc247244/extra/tag.html
* http://gabriel.mp3-tech.org/mp3infotag.html
* http://www.id3.org/iso4217.html
* http://www.unicode.org/Public/MAPPINGS/ISO8859/8859-1.TXT
* http://www.xiph.org/ogg/vorbis/doc/framing.html
* http://www.xiph.org/ogg/vorbis/doc/v-comment.html
* http://leknor.com/code/php/class.ogg.php.txt
* http://www.id3.org/iso639-2.html
* http://www.id3.org/lyrics3.html
* http://www.id3.org/lyrics3200.html
* http://www.psc.edu/general/software/packages/ieee/ieee.html
* http://www.scri.fsu.edu/~jac/MAD3401/Backgrnd/ieee-expl.html
* http://www.scri.fsu.edu/~jac/MAD3401/Backgrnd/binary.html
* http://www.jmcgowan.com/avi.html
* http://www.wotsit.org/
* http://www.herdsoft.com/ti/davincie/davp3xo2.htm
* http://www.mathdogs.com/vorbis-illuminated/bitstream-appendix.html
* "Standard MIDI File Format" by Dustin Caldwell (from www.wotsit.org)
* http://midistudio.com/Help/GMSpecs_Patches.htm
* http://www.xiph.org/archives/vorbis/200109/0459.html
* http://www.replaygain.org/
* http://www.lossless-audio.com/
* http://download.microsoft.com/download/winmediatech40/Doc/1.0/WIN98MeXP/EN-US/ASF_Specification_v.1.0.exe
* http://mediaxw.sourceforge.net/files/doc/Active%20Streaming%20Format%20(ASF)%201.0%20Specification.pdf
* http://www.uni-jena.de/~pfk/mpp/sv8/
* http://jfaul.de/atl/
* http://www.uni-jena.de/~pfk/mpp/
* http://www.libpng.org/pub/png/spec/png-1.2-pdg.html
* http://www.real.com/devzone/library/creating/rmsdk/doc/rmff.htm
* http://www.fastgraph.com/help/bmp_os2_header_format.html
* http://netghost.narod.ru/gff/graphics/summary/os2bmp.htm
* http://flac.sourceforge.net/format.html
* http://www.research.att.com/projects/mpegaudio/mpeg2.html
* http://www.audiocoding.com/wiki/index.php?page=AAC
* http://libmpeg.org/mpeg4/doc/w2203tfs.pdf
* http://www.geocities.com/xhelmboyx/quicktime/formats/qtm-layout.txt
* http://developer.apple.com/techpubs/quicktime/qtdevdocs/RM/frameset.htm
* http://www.nullsoft.com/nsv/
* http://www.wotsit.org/download.asp?f=iso9660
* http://sandbox.mc.edu/~bennet/cs110/tc/tctod.html
* http://www.cdroller.com/htm/readdata.html
* http://www.speex.org/manual/node10.html
* http://www.harmony-central.com/Computer/Programming/aiff-file-format.doc
* http://www.faqs.org/rfcs/rfc2361.html
* http://ghido.shelter.ro/
* http://www.ebu.ch/tech_t3285.pdf
* http://www.sr.se/utveckling/tu/bwf
* http://ftp.aessc.org/pub/aes46-2002.pdf
* http://cartchunk.org:8080/
* http://www.broadcastpapers.com/radio/cartchunk01.htm
* http://www.hr/josip/DSP/AudioFile2.html
* http://home.attbi.com/~chris.bagwell/AudioFormats-11.html
* http://www.pure-mac.com/extkey.html
* http://cesnet.dl.sourceforge.net/sourceforge/bonkenc/bonk-binary-format-0.9.txt
* http://www.headbands.com/gspot/
* http://www.openswf.org/spec/SWFfileformat.html
* http://j-faul.virtualave.net/
* http://www.btinternet.com/~AnthonyJ/Atari/programming/avr_format.html
* http://cui.unige.ch/OSG/info/AudioFormats/ap11.html
* http://sswf.sourceforge.net/SWFalexref.html
* http://www.geocities.com/xhelmboyx/quicktime/formats/qti-layout.txt
* http://www-lehre.informatik.uni-osnabrueck.de/~fbstark/diplom/docs/swf/Flash_Uncovered.htm
* http://developer.apple.com/quicktime/icefloe/dispatch012.html
* http://www.csdn.net/Dev/Format/graphics/PCD.htm
* http://tta.iszf.irk.ru/
* http://www.atsc.org/standards/a_52a.pdf
* http://www.alanwood.net/unicode/
* http://www.freelists.org/archives/matroska-devel/07-2003/msg00010.html
* http://www.its.msstate.edu/net/real/reports/config/tags.stats
* http://homepages.slingshot.co.nz/~helmboy/quicktime/formats/qtm-layout.txt
* http://brennan.young.net/Comp/LiveStage/things.html
* http://www.multiweb.cz/twoinches/MP3inside.htm
* http://www.geocities.co.jp/SiliconValley-Oakland/3664/alittle.html#GenreExtended
* http://www.mactech.com/articles/mactech/Vol.06/06.01/SANENormalized/
* http://www.unicode.org/unicode/faq/utf_bom.html
* http://tta.corecodec.org/?menu=format
* http://www.scvi.net/nsvformat.htm
* http://pda.etsi.org/pda/queryform.asp
* http://cpansearch.perl.org/src/RGIBSON/Audio-DSS-0.02/lib/Audio/DSS.pm
* http://trac.musepack.net/trac/wiki/SV8Specification
