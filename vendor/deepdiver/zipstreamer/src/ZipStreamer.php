<?php
/**
 * Class to create zip files on the fly and stream directly to the HTTP client as the content is added.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Inspired by
 * CreateZipFile by Rochak Chauhan  www.rochakchauhan.com (http://www.phpclasses.org/browse/package/2322.html)
 * and
 * ZipStream by A. Grandt https://github.com/Grandt/PHPZip (http://www.phpclasses.org/package/6116)
 *
 * Unix-File attributes according to
 * http://unix.stackexchange.com/questions/14705/the-zip-formats-external-file-attribute
 *
 * @author Nicolai Ehemann <en@enlightened.de>
 * @author André Rothe <arothe@zks.uni-leipzig.de>
 * @copyright Copyright (C) 2013-2015 Nicolai Ehemann and contributors
 * @license GNU GPL
 * @version 1.0
 */
namespace ZipStreamer;

class ZipStreamer {
  const VERSION = "1.0";

  const ZIP_LOCAL_FILE_HEADER = 0x04034b50; // local file header signature
  const ZIP_DATA_DESCRIPTOR_HEADER = 0x08074b50; //data descriptor header signature
  const ZIP_CENTRAL_FILE_HEADER = 0x02014b50; // central file header signature
  const ZIP_END_OF_CENTRAL_DIRECTORY = 0x06054b50; // end of central directory record
  const ZIP64_END_OF_CENTRAL_DIRECTORY = 0x06064b50; //zip64 end of central directory record
  const ZIP64_END_OF_CENTRAL_DIR_LOCATOR = 0x07064b50; // zip64 end of central directory locator

  const ATTR_MADE_BY_VERSION = 0x032d; // made by version  (upper byte: UNIX, lower byte v4.5)

  const STREAM_CHUNK_SIZE = 1048560; // 16 * 65535 = almost 1mb chunks, for best deflate performance

  private $extFileAttrFile;
  private $extFileAttrDir;

  /** @var resource $outStream output stream zip file is written to */
  private $outStream;
  /** @var boolean zip64 enabled */
  private $zip64 = True;
  /** @var int compression method */
  private $compress;
  /** @var int compression level */
  private $level;

  /** @var array central directory record */
  private $cdRec = array();
  /** @var int offset of next file to be added */
  private $offset;
  /** @var boolean indicates zip is finalized and sent to client; no further addition possible */
  private $isFinalized = false;
  /** @var bool only used for unit testing */
  public $turnOffOutputBuffering = true;

    /**
   * Constructor. Initializes ZipStreamer object for immediate usage.
   * @param array $options Optional, ZipStreamer and zip file options as key/value pairs.
   *                       Valid options are:
   *                       * outstream: stream the zip file is output to (default: stdout)
   *                       * zip64: enabled/disable zip64 support (default: True)
   *                       * compress: int, compression method (one of COMPR::STORE,
   *                                   COMPR::DEFLATE, default COMPR::STORE)
   *                                   can be overridden for single files
   *                       * level: int, compression level (one of COMPR::NORMAL,
   *                                COMPR::MAXIMUM, COMPR::SUPERFAST, default COMPR::NORMAL)
   */
  function __construct($options = NULL) {
    $defaultOptions = array(
        'outstream' => NULL,
        'zip64' => True,
        'compress' => COMPR::STORE,
        'level' => COMPR::NORMAL,
    );
    if (is_null($options)) {
      $options = array();
    }
    $options = array_merge($defaultOptions, $options);

    if ($options['outstream']) {
      $this->outStream = $options['outstream'];
    } else {
      $this->outStream = fopen('php://output', 'w');
    }
    $this->zip64 = $options['zip64'];
    $this->compress = $options['compress'];
    $this->level = $options['level'];
    $this->validateCompressionOptions($this->compress, $this->level);
    //TODO: is this advisable/necessary?
    if (ini_get('zlib.output_compression')) {
      ini_set('zlib.output_compression', 'Off');
    }
    // initialize default external file attributes
    $this->extFileAttrFile = UNIX::getExtFileAttr(UNIX::S_IFREG |
                                                  UNIX::S_IRUSR | UNIX::S_IWUSR | UNIX::S_IRGRP |
                                                  UNIX::S_IROTH);
    $this->extFileAttrDir = UNIX::getExtFileAttr(UNIX::S_IFDIR |
                                                 UNIX::S_IRWXU | UNIX::S_IRGRP | UNIX::S_IXGRP |
                                                 UNIX::S_IROTH | UNIX::S_IXOTH) |
                            DOS::getExtFileAttr(DOS::DIR);
    $this->offset = Count64::construct(0, !$this->zip64);
  }

  function __destruct() {
    $this->isFinalized = true;
    $this->cdRec = null;
  }

  private function getVersionToExtract($isDir) {
    if ($this->zip64) {
      $version = 0x2d; // 4.5 - File uses ZIP64 format extensions
    } else if ($isDir) {
      $version = 0x14; // 2.0 - File is a folder (directory)
    } else {
      $version = 0x0a; //   1.0 - Default value
    }
    return $version;
  }

  /**
  * Send appropriate http headers before streaming the zip file and disable output buffering.
  * This method, if used, has to be called before adding anything to the zip file.
  *
  * @param string $archiveName Filename of archive to be created (optional, default 'archive.zip')
  * @param string $contentType Content mime type to be set (optional, default 'application/zip')
  */
  public function sendHeaders($archiveName = 'archive.zip', $contentType = 'application/zip') {
    $headerFile = null;
    $headerLine = null;
    if (!headers_sent($headerFile, $headerLine)
          or die("<p><strong>Error:</strong> Unable to send file " .
                 "$archiveName. HTML Headers have already been sent from " .
                 "<strong>$headerFile</strong> in line <strong>$headerLine" .
                 "</strong></p>")) {
      if ((ob_get_contents() === false || ob_get_contents() == '')
           or die("\n<p><strong>Error:</strong> Unable to send file " .
                  "<strong>$archiveName.epub</strong>. Output buffer " .
                  "already contains text (typically warnings or errors).</p>")) {
        header('Pragma: public');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s T'));
        header('Expires: 0');
        header('Accept-Ranges: bytes');
        header('Connection: Keep-Alive');
        header('Content-Type: ' . $contentType);
        // Use UTF-8 filenames when not using Internet Explorer
        if(isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') > 0) {
          header('Content-Disposition: attachment; filename="' . rawurlencode($archiveName) . '"' );
        }  else  {
          header( 'Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode($archiveName)
              . '; filename="' . rawurlencode($archiveName) . '"' );
        }
        header('Content-Transfer-Encoding: binary');
      }
    }
    $this->flush();
    // turn off output buffering
      if ($this->turnOffOutputBuffering) {
          @ob_end_flush();
      }
  }

  /**
   * Add a file to the archive at the specified location and file name.
   *
   * @param resource $stream    Stream to read data from
   * @param string $filePath    Filepath and name to be used in the archive.
   * @param array $options      Optional, additional options
   *                            Valid options are:
   *                               * int timestamp: timestamp for the file (default: current time)
   *                               * string comment: comment to be added for this file (default: none)
   *                               * int compress: compression method (override global option for this file)
   *                               * int level: compression level (override global option for this file)
   * @return bool $success
   */
  public function addFileFromStream($stream, $filePath, $options = NULL) {
    if ($this->isFinalized) {
      return false;
    }
    $defaultOptions = array(
        'timestamp' => NULL,
        'comment' => NULL,
        'compress' => $this->compress,
        'level' => $this->level,
    );
    if (is_null($options)) {
    	$options = array();
    }
    $options = array_merge($defaultOptions, $options);
    $this->validateCompressionOptions($options['compress'], $options['level']);

    if (!is_resource($stream) || get_resource_type($stream) != 'stream') {
      return false;
    }

    $filePath = self::normalizeFilePath($filePath);

    $gpFlags = GPFLAGS::ADD;

    list($gpFlags, $lfhLength) = $this->beginFile($filePath, False, $options['comment'], $options['timestamp'], $gpFlags, $options['compress']);
    list($dataLength, $gzLength, $dataCRC32) = $this->streamFileData($stream, $options['compress'], $options['level']);

    $ddLength = $this->addDataDescriptor($dataLength, $gzLength, $dataCRC32);

    // build cdRec
    $this->cdRec[] = $this->buildCentralDirectoryHeader($filePath, $options['timestamp'], $gpFlags, $options['compress'],
        $dataLength, $gzLength, $dataCRC32, $this->extFileAttrFile, False);

    // calc offset
    $this->offset->add($ddLength)->add($lfhLength)->add($gzLength);

    return true;
  }

  /**
   * Add an empty directory entry to the zip archive.
   *
   * @param string $directoryPath  Directory Path and name to be added to the archive.
   * @param array $options      Optional, additional options
   *                            Valid options are:
   *                               * int timestamp: timestamp for the file (default: current time)
   *                               * string comment: comment to be added for this file (default: none)
   * @return bool $success
   */
  public function addEmptyDir($directoryPath, $options = NULL) {
    if ($this->isFinalized) {
      return false;
    }
    $defaultOptions = array(
    		'timestamp' => NULL,
    		'comment' => NULL,
    );
    if (is_null($options)) {
    	$options = array();
    }
    $options = array_merge($defaultOptions, $options);

    $directoryPath = self::normalizeFilePath($directoryPath) . '/';

    if (strlen($directoryPath) > 0) {
      $gpFlags = 0x0000;
      $gzMethod = COMPR::STORE; // Compression type 0 = stored

      list($gpFlags, $lfhLength) = $this->beginFile($directoryPath, True, $options['comment'], $options['timestamp'], $gpFlags, $gzMethod);
      // build cdRec
      $this->cdRec[] = $this->buildCentralDirectoryHeader($directoryPath, $options['timestamp'], $gpFlags, $gzMethod,
          Count64::construct(0, !$this->zip64), Count64::construct(0, !$this->zip64), 0, $this->extFileAttrDir, True);

      // calc offset
      $this->offset->add($lfhLength);

      return true;
    }
    return false;
  }

  /**
   * Close the archive.
   * A closed archive can no longer have new files added to it. After
   * closing, the zip file is completely written to the output stream.
   * @return bool $success
   */
  public function finalize() {
    if (!$this->isFinalized) {

      // print central directory
      $cd = implode('', $this->cdRec);
      $this->write($cd);

      if ($this->zip64) {
        // print the zip64 end of central directory record
        $this->write($this->buildZip64EndOfCentralDirectoryRecord(strlen($cd)));

        // print the zip64 end of central directory locator
        $this->write($this->buildZip64EndOfCentralDirectoryLocator(strlen($cd)));
      }

      // print end of central directory record
      $this->write($this->buildEndOfCentralDirectoryRecord(strlen($cd)));

      $this->flush();

      $this->isFinalized = true;
      $cd = null;
      $this->cdRec = null;

      return true;
    }
    return false;
  }

  private function validateCompressionOptions($compress, $level) {
    if (COMPR::STORE === $compress) {
    } else if (COMPR::DEFLATE === $compress) {
      if (COMPR::NONE !== $level
        && !class_exists(DeflatePeclStream::PECL1_DEFLATE_STREAM_CLASS)
        && !class_exists(DeflatePeclStream::PECL2_DEFLATE_STREAM_CLASS)) {
        throw new \Exception('unable to use compression method DEFLATE with level other than NONE (requires pecl_http >= 0.10)');
      }
    } else {
      throw new \Exception('invalid option ' . $compress . ' (compression method)');
    }

    if (!(COMPR::NONE === $level ||
        COMPR::NORMAL === $level ||
        COMPR::MAXIMUM === $level ||
        COMPR::SUPERFAST === $level)) {
      throw new \Exception('invalid option ' . $level . ' (compression level');
    }
  }

  private function write($data) {
    return fwrite($this->outStream, $data);
  }

  private function flush() {
    return fflush($this->outStream);
  }

  private function beginFile($filePath, $isDir, $fileComment, $timestamp, $gpFlags, $gzMethod,
      $dataLength = 0, $gzLength = 0, $dataCRC32 = 0) {

    $isFileUTF8 = mb_check_encoding($filePath, 'UTF-8') && !mb_check_encoding($filePath, 'ASCII');
    $isCommentUTF8 = !empty($fileComment) && mb_check_encoding($fileComment, 'UTF-8')
                  && !mb_check_encoding($fileComment, 'ASCII');

    if ($isFileUTF8 || $isCommentUTF8) {
      $gpFlags |= GPFLAGS::EFS;
    }

    $localFileHeader = $this->buildLocalFileHeader($filePath, $timestamp, $gpFlags, $gzMethod, $dataLength,
        $gzLength, $isDir, $dataCRC32);

    $this->write($localFileHeader);

    return array($gpFlags, strlen($localFileHeader));
  }

  private function streamFileData($stream, $compress, $level) {
    $dataLength = Count64::construct(0, !$this->zip64);
    $gzLength = Count64::construct(0, !$this->zip64);
    $hashCtx = hash_init('crc32b');
    if (COMPR::DEFLATE === $compress) {
      $compStream = DeflateStream::create($level);
    }

    while (!feof($stream) && ($data = fread($stream, self::STREAM_CHUNK_SIZE)) !== false) {
      $dataLength->add(strlen($data));
      hash_update($hashCtx, $data);
      if (COMPR::DEFLATE === $compress) {
        $data = $compStream->update($data);
      }
      $gzLength->add(strlen($data));
      $this->write($data);

      $this->flush();
    }
    if (COMPR::DEFLATE === $compress) {
      $data = $compStream->finish();
      $gzLength->add(strlen($data));
      $this->write($data);

      $this->flush();
    }
    $crc = unpack('N', hash_final($hashCtx, true));
    return array($dataLength, $gzLength, $crc[1]);
  }

  private function buildZip64ExtendedInformationField($dataLength = 0, $gzLength = 0) {
    return ''
      . pack16le(0x0001)         // tag for this "extra" block type (ZIP64)        2 bytes (0x0001)
      . pack16le(28)             // size of this "extra" block                     2 bytes
      . pack64le($dataLength)    // original uncompressed file size                8 bytes
      . pack64le($gzLength)      // size of compressed data                        8 bytes
      . pack64le($this->offset)  // offset of local header record                  8 bytes
      . pack32le(0);             // number of the disk on which this file starts   4 bytes
  }

  private function buildLocalFileHeader($filePath, $timestamp, $gpFlags,
      $gzMethod, $dataLength, $gzLength, $isDir = False, $dataCRC32 = 0) {
    $versionToExtract = $this->getVersionToExtract($isDir);
    $dosTime = self::getDosTime($timestamp);
    if ($this->zip64) {
      $zip64Ext = $this->buildZip64ExtendedInformationField($dataLength, $gzLength);
      $dataLength = -1;
      $gzLength = -1;
    } else {
      $zip64Ext = '';
    }

    return ''
      . pack32le(self::ZIP_LOCAL_FILE_HEADER)   // local file header signature     4 bytes  (0x04034b50)
      . pack16le($versionToExtract)             // version needed to extract       2 bytes
      . pack16le($gpFlags)                      // general purpose bit flag        2 bytes
      . pack16le($gzMethod)                     // compression method              2 bytes
      . pack32le($dosTime)                      // last mod file time              2 bytes
                                                // last mod file date              2 bytes
      . pack32le($dataCRC32)                    // crc-32                          4 bytes
      . pack32le($gzLength)                     // compressed size                 4 bytes
      . pack32le($dataLength)                   // uncompressed size               4 bytes
      . pack16le(strlen($filePath))             // file name length                2 bytes
      . pack16le(strlen($zip64Ext))             // extra field length              2 bytes
      . $filePath                               // file name                       (variable size)
      . $zip64Ext;                              // extra field                     (variable size)
  }

  private function addDataDescriptor($dataLength, $gzLength, $dataCRC32) {
    if ($this->zip64) {
      $length = 24;
      $packedGzLength = pack64le($gzLength);
      $packedDataLength = pack64le($dataLength);
    } else {
      $length = 16;
      $packedGzLength = pack32le($gzLength->getLoBytes());
      $packedDataLength = pack32le($dataLength->getLoBytes());
     }

    $this->write(''
        . pack32le(self::ZIP_DATA_DESCRIPTOR_HEADER)  // data descriptor header signature    4 bytes (0x08074b50)
        . pack32le($dataCRC32)  // crc-32                          4 bytes
        . $packedGzLength       // compressed size                 4/8 bytes (depending on zip64 enabled)
        . $packedDataLength     // uncompressed size               4/8 bytes (depending on zip64 enabled)
        .'');
    return $length;
  }

  private function buildZip64EndOfCentralDirectoryRecord($cdRecLength) {
    $versionToExtract = $this->getVersionToExtract(False);
    $cdRecCount = count($this->cdRec);

    return ''
        . pack32le(self::ZIP64_END_OF_CENTRAL_DIRECTORY) // zip64 end of central dir signature         4 bytes  (0x06064b50)
        . pack64le(44)                                   // size of zip64 end of central directory
                                                         // record                                     8 bytes
        . pack16le(self::ATTR_MADE_BY_VERSION)           //version made by                             2 bytes
        . pack16le($versionToExtract)                    // version needed to extract                  2 bytes
        . pack32le(0)                                    // number of this disk                        4 bytes
        . pack32le(0)                                    // number of the disk with the start of the
                                                         // central directory                          4 bytes
        . pack64le($cdRecCount)                          // total number of entries in the central
                                                         // directory on this disk                     8 bytes
        . pack64le($cdRecCount)                          // total number of entries in the
                                                         // central directory                          8 bytes
        . pack64le($cdRecLength)                         // size of the central directory              8 bytes
        . pack64le($this->offset)                        // offset of start of central directory
                                                         // with respect to the starting disk number   8 bytes
        . '';                                            // zip64 extensible data sector               (variable size)

  }

  private function buildZip64EndOfCentralDirectoryLocator($cdRecLength) {
    $zip64RecStart = Count64::construct($this->offset, !$this->zip64)->add($cdRecLength);

        return ''
        . pack32le(self::ZIP64_END_OF_CENTRAL_DIR_LOCATOR) // zip64 end of central dir locator signature  4 bytes  (0x07064b50)
        . pack32le(0)                                      // number of the disk with the start of the
                                                           // zip64 end of central directory              4 bytes
        . pack64le($zip64RecStart)                         // relative offset of the zip64 end of
                                                           // central directory record                    8 bytes
        . pack32le(1);                                     // total number of disks                       4 bytes
  }

  private function buildCentralDirectoryHeader($filePath, $timestamp, $gpFlags,
      $gzMethod, $dataLength, $gzLength, $dataCRC32, $extFileAttr, $isDir) {
    $versionToExtract = $this->getVersionToExtract($isDir);
    $dosTime = self::getDosTime($timestamp);
    if ($this->zip64) {
      $zip64Ext = $this->buildZip64ExtendedInformationField($dataLength, $gzLength);
      $dataLength = -1;
      $gzLength = -1;
      $diskNo = -1;
      $offset = -1;
    } else {
      $zip64Ext = '';
      $dataLength = $dataLength->getLoBytes();
      $gzLength = $gzLength->getLoBytes();
      $diskNo = 0;
      $offset = $this->offset->getLoBytes();
    }

    return ''
      . pack32le(self::ZIP_CENTRAL_FILE_HEADER)  //central file header signature   4 bytes  (0x02014b50)
      . pack16le(self::ATTR_MADE_BY_VERSION)     //version made by                 2 bytes
      . pack16le($versionToExtract)              // version needed to extract      2 bytes
      . pack16le($gpFlags)                       //general purpose bit flag        2 bytes
      . pack16le($gzMethod)                      //compression method              2 bytes
      . pack32le($dosTime)                       //last mod file time              2 bytes
                                                 //last mod file date              2 bytes
      . pack32le($dataCRC32)                     //crc-32                          4 bytes
      . pack32le($gzLength)                      //compressed size                 4 bytes
      . pack32le($dataLength)                    //uncompressed size               4 bytes
      . pack16le(strlen($filePath))              //file name length                2 bytes
      . pack16le(strlen($zip64Ext))              //extra field length              2 bytes
      . pack16le(0)                              //file comment length             2 bytes
      . pack16le($diskNo)                        //disk number start               2 bytes
      . pack16le(0)                              //internal file attributes        2 bytes
      . pack32le($extFileAttr)                   //external file attributes        4 bytes
      . pack32le($offset)                        //relative offset of local header 4 bytes
      . $filePath                                //file name                       (variable size)
      . $zip64Ext                                //extra field                     (variable size)
      //TODO: implement?
      . '';                                      //file comment                    (variable size)
  }

  private function buildEndOfCentralDirectoryRecord($cdRecLength) {
    if ($this->zip64) {
      $diskNumber = -1;
      $cdRecCount = min(count($this->cdRec), 0xffff);
      $cdRecLength = -1;
      $offset = -1;
    } else {
      $diskNumber = 0;
      $cdRecCount = count($this->cdRec);
      $offset = $this->offset->getLoBytes();
    }
    //throw new \Exception(sprintf("zip64 %d diskno %d", $this->zip64, $diskNumber));

    return ''
      . pack32le(self::ZIP_END_OF_CENTRAL_DIRECTORY) // end of central dir signature    4 bytes  (0x06064b50)
      . pack16le($diskNumber)                        // number of this disk             2 bytes
      . pack16le($diskNumber)                        // number of the disk with the
                                                     // start of the central directory  2 bytes
      . pack16le($cdRecCount)                        // total number of entries in the
                                                     // central directory on this disk  2 bytes
      . pack16le($cdRecCount)                        // total number of entries in the
                                                     // central directory               2 bytes
      . pack32le($cdRecLength)                       // size of the central directory   4 bytes
      . pack32le($offset)                            // offset of start of central
                                                     // directory with respect to the
                                                     // starting disk number            4 bytes
      . pack16le(0)                                  // .ZIP file comment length        2 bytes
      //TODO: implement?
      . '';                                          // .ZIP file comment               (variable size)
  }

  // Utility methods ////////////////////////////////////////////////////////

  private static function normalizeFilePath($filePath) {
    return trim(str_replace('\\', '/', $filePath), '/');
  }

  /**
   * Calculate the 2 byte dostime used in the zip entries.
   *
   * @param int $timestamp
   * @return 2-byte encoded DOS Date
   */
  public static function getDosTime($timestamp = 0) {
    $timestamp = (int) $timestamp;
    $oldTZ = @date_default_timezone_get();
    date_default_timezone_set('UTC');
    $date = ($timestamp == 0 ? getdate() : getdate($timestamp));
    date_default_timezone_set($oldTZ);
    if ($date['year'] >= 1980) {
      return (($date['mday'] + ($date['mon'] << 5) + (($date['year'] - 1980) << 9)) << 16)
      | (($date['seconds'] >> 1) + ($date['minutes'] << 5) + ($date['hours'] << 11));
    }
    return 0x0000;
  }
}

abstract class ExtFileAttr {

  /*
    ZIP external file attributes layout
    TTTTsstrwxrwxrwx0000000000ADVSHR
    ^^^^____________________________ UNIX file type
        ^^^_________________________ UNIX setuid, setgid, sticky
           ^^^^^^^^^________________ UNIX permissions
                    ^^^^^^^^________ "lower-middle byte" (TODO: what is this?)
                            ^^^^^^^^ DOS attributes (reserved, reserved, archived, directory, volume, system, hidden, read-only
  */

  public static function getExtFileAttr($attr) {
    return $attr;
  }
}

class UNIX extends ExtFileAttr {

  // Octal
  const S_IFIFO = 0010000; /* named pipe (fifo) */
  const S_IFCHR = 0020000; /* character special */
  const S_IFDIR = 0040000; /* directory */
  const S_IFBLK = 0060000; /* block special */
  const S_IFREG = 0100000; /* regular */
  const S_IFLNK = 0120000; /* symbolic link */
  const S_IFSOCK = 0140000; /* socket */
  const S_ISUID = 0004000; /* set user id on execution */
  const S_ISGID = 0002000; /* set group id on execution */
  const S_ISTXT = 0001000; /* sticky bit */
  const S_IRWXU = 0000700; /* RWX mask for owner */
  const S_IRUSR = 0000400; /* R for owner */
  const S_IWUSR = 0000200; /* W for owner */
  const S_IXUSR = 0000100; /* X for owner */
  const S_IRWXG = 0000070; /* RWX mask for group */
  const S_IRGRP = 0000040; /* R for group */
  const S_IWGRP = 0000020; /* W for group */
  const S_IXGRP = 0000010; /* X for group */
  const S_IRWXO = 0000007; /* RWX mask for other */
  const S_IROTH = 0000004; /* R for other */
  const S_IWOTH = 0000002; /* W for other */
  const S_IXOTH = 0000001; /* X for other */
  const S_ISVTX = 0001000; /* save swapped text even after use */

  public static function getExtFileAttr($attr) {
    return parent::getExtFileAttr($attr) << 16;
  }
}

abstract class DeflateStream {
  static public function create($level) {
    if (COMPR::NONE === $level) {
      return new DeflateStoreStream($level);
    } else {
      return new DeflatePeclStream($level);
    }
  }
  protected function __construct($level) {}

  abstract public function update($data);
  abstract public function finish();
}

class DeflatePeclStream extends DeflateStream {
  private $peclDeflateStream;

  const PECL1_DEFLATE_STREAM_CLASS = '\HttpDeflateStream';
  const PECL2_DEFLATE_STREAM_CLASS = '\http\encoding\Stream\Deflate';

  protected function __construct($level) {
    $class = self::PECL1_DEFLATE_STREAM_CLASS;
    if (!class_exists($class)) {
      $class = self::PECL2_DEFLATE_STREAM_CLASS;
    }
    if (!class_exists($class)) {
      throw new \Exception('unable to instantiate PECL deflate stream (requires pecl_http >= 0.10)');
    }

    $deflateFlags = constant($class . '::TYPE_RAW');
    switch ($level) {
      case COMPR::NORMAL:
        $deflateFlags |= constant($class . '::LEVEL_DEF');
        break;
      case COMPR::MAXIMUM:
        $deflateFlags |= constant($class . '::LEVEL_MAX');
        break;
      case COMPR::SUPERFAST:
        $deflateFlags |= constant($class . '::LEVEL_MIN');
        break;
    }
    $this->peclDeflateStream = new $class($deflateFlags);
  }

  public function update($data) {
    return $this->peclDeflateStream->update($data);
  }

  public function finish() {
    return $this->peclDeflateStream->finish();
  }
}

class DeflateStoreStream extends DeflateStream {
  const BLOCK_HEADER_NORMAL = 0x00;
  const BLOCK_HEADER_FINAL = 0x01;
  const BLOCK_HEADER_ERROR = 0x03;

  const MAX_UNCOMPR_BLOCK_SIZE = 0xffff;

  public function update($data) {
    $result = '';
    for ($pos = 0, $len = strlen($data); $pos < $len; $pos += self::MAX_UNCOMPR_BLOCK_SIZE) {
      $result .= $this->write_block(self::BLOCK_HEADER_NORMAL, substr($data, $pos, self::MAX_UNCOMPR_BLOCK_SIZE));
    }
    return $result;
  }

  public function finish() {
    return $this->write_block(self::BLOCK_HEADER_FINAL, '');
  }

  private function write_block($header, $data) {
    return ''
        . pack8($header)                    // block header                     3 bits, null padding = 1 byte
        . pack16le(strlen($data))           // block data length                2 bytes
        . pack16le(0xffff ^ strlen($data))  // complement of block data size    2 bytes
        . $data                             // data
        . '';
      }
}

class DOS extends ExtFileAttr {

  const READ_ONLY = 0x1;
  const HIDDEN = 0x2;
  const SYSTEM = 0x4;
  const VOLUME = 0x8;
  const DIR = 0x10;
  const ARCHIVE = 0x20;
  const RESERVED1 = 0x40;
  const RESERVED2 = 0x80;
}

class GPFLAGS {
  const NONE = 0x0000; // no flags set
  const COMP1 = 0x0002; // compression flag 1 (compression settings, see APPNOTE for details)
  const COMP2 = 0x0004; // compression flag 2 (compression settings, see APPNOTE for details)
  const ADD = 0x0008; // ADD flag (sizes and crc32 are append in data descriptor)
  const EFS = 0x0800; // EFS flag (UTF-8 encoded filename and/or comment)

  // compression settings for deflate/deflate64
  const DEFL_NORM = 0x0000; // normal compression (COMP1 and COMP2 not set)
  const DEFL_MAX = self::COMP1; // maximum compression
  const DEFL_FAST = self::COMP2; // fast compression
  const DEFL_SFAST = 0x0006; // superfast compression (COMP1 and COMP2 set)
}

