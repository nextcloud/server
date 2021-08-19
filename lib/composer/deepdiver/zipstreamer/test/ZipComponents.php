<?php

/**
 * Copyright (C) 2014 Nicolai Ehemann <en@enlightened.de>
 *
 * This file is licensed under the GNU GPL version 3 or later.
 * See COPYING for details.
 */
namespace ZipStreamer;

/**
 * @codeCoverageIgnore
 */
class ParseException extends \Exception {
}

function readstr($str, &$pos, $len) {
  $str = substr($str, $pos, $len);
  $pos += $len;
  return $str;
}

function hexIfFFFF($value) {
  return $value == 0xffff ? '0x' . dechex($value) : $value;
}

function hexIfFFFFFFFF($value) {
  return $value == 0xffffffff ? '0x' . dechex($value) : $value;
}

/**
 * @codeCoverageIgnore
 */
abstract class zipRecord {
  protected static $magicBytes = array();
  protected static $unitTest = null;
  protected static $shortName = "<EMPTY>";
  protected static $magicLength = 4;
  public $begin;
  public $end;

  public function getLength() {
    return $this->end - $this->begin + 1;
  }

  public static function setUnitTest($unitTest) {
    self::$unitTest = $unitTest;
  }

  public static function getMagicBytes() {
    if (!array_key_exists(static::$MAGIC, self::$magicBytes)) {
      if (2 == static::$magicLength) {
        self::$magicBytes[static::$MAGIC] = pack16le(static::$MAGIC);
      } else {
        self::$magicBytes[static::$MAGIC] = pack32le(static::$MAGIC);
      }
    }
    return self::$magicBytes[static::$MAGIC];
  }

  protected static function __constructFromString($str, $pos, $size = -1) {
    $eocdrec = new static();
    try {
      $eocdrec->readFromString($str, $pos, $size);
    } catch (Exception $e) {
      $this->fail("error parsing end of central directory record");
    }

    return $eocdrec;
  }

  public static function constructFromString($str, $offset = 0, $size = -1) {
    return static::__constructFromString($str, $offset, $size);
  }

  protected abstract function readFromString($str, $pos, $size = -1);

  public function assertValues($values) {
    if (self::$unitTest) {
      foreach ($values as $key => $value) {
        self::$unitTest->assertEquals($value, $this->{$key}, static::$shortName . " " . $key);
      }
    }
  }
}

/**
 * @codeCoverageIgnore
 */
class EndOfCentralDirectoryRecord extends zipRecord {
  protected static $MAGIC = 0x06054b50; // end of central directory record
  protected static $shortName = "EOCDR";
  public $numberDisk;
  public $numberDiskStartCD;
  public $numberEntriesDisk;
  public $numberEntriesCD;
  public $size;
  public $offsetStart;
  public $lengthComment;
  public $comment;

  public function __toString() {
    return sprintf(
        "Number of this disk:                      %d\n" .
        "Number of disk with start of eocd record: %d\n" .
        "Number of cd record entries on this disk: %d\n" .
        "Total number of cd record entries:        %d\n" .
        "Size of central directory:                %d\n" .
        "Offset of central directory:              %d\n" .
        "Zip file comment length:                  %d\n" .
        "Zip file comment following (if any)\n%s\n",
        $this->numberDisk,
        $this->numberDiskStartCD,
        $this->numberEntriesDisk,
        $this->numberEntriesCD,
        $this->size,
        $this->offsetStart,
        $this->lengthComment,
        $this->comment);
  }

  public static function constructFromString($str, $offset = 0, $size = -1) {
    $eocdrecPos = strrpos($str, static::getMagicBytes());
    if (self::$unitTest) {
      self::$unitTest->assertFalse(False === $eocdrecPos, "end of central directory record missing");
      self::$unitTest->assertGreaterThanOrEqual(22, strlen($str) - $eocdrecPos, "end of central directory record incomplete (smaller than minimum length)");
    }

    return static::__constructFromString($str, $eocdrecPos);
  }

  public function readFromString($str, $pos, $size = -1) {
    $this->begin = $pos;
    $magic = readstr($str, $pos, 4);
    if (self::getMagicBytes() != $magic) {
      throw new ParseException("invalid magic");
    }
    $this->numberDisk = (int) unpack16le(readstr($str, $pos, 2));
    $this->numberDiskStartCD = (int) unpack16le(readstr($str, $pos, 2));
    $this->numberEntriesDisk = (int) unpack16le(readstr($str, $pos, 2));
    $this->numberEntriesCD = (int) unpack16le(readstr($str, $pos, 2));
    $this->size = (int) unpack32le(readstr($str, $pos, 4));
    $this->offsetStart = (int) unpack32le(readstr($str, $pos, 4));
    $this->lengthComment = unpack16le(readstr($str, $pos, 2));
    if (0 < $this->lengthComment) {
      $this->comment = (string) readstr($str, $pos, $this->lengthComment);
    } else {
      $this->comment = '';
    }
    $this->end = $pos - 1;
  }
}

/**
 * @codeCoverageIgnore
 */
class Zip64EndOfCentralDirectoryLocator extends zipRecord {
  protected static $MAGIC = 0x07064b50; // zip64 end of central directory locator
  protected static $shortName = "Z64EOCDL";
  public $numberDiskStartZ64EOCDL;
  public $offsetStart;
  public $numberDisks;

  public function __toString() {
    return sprintf(
        "Number of disk with start of zip64 eocd locator: %d\n" .
        "Offset of zip64 eocd record:                     %d\n" .
        "Number of disks:                                 %d\n" .
        $this->numberDiskStartZ64EOCDL,
        $this->offsetStart,
        $this->numberDisks);
  }

  public static function constructFromString($str, $offset = 0, $size = -1) {
    $z64eocdlPos = strrpos($str, static::getMagicBytes(), -$offset);
    if (self::$unitTest) {
      self::$unitTest->assertFalse(False === $z64eocdlPos, "zip64 end of central directory locator missing");
    }

    $z64eocdl = static::__constructFromString($str, $z64eocdlPos);

    if (self::$unitTest) {
      self::$unitTest->assertGreaterThanOrEqual(20, $z64eocdl->getLength(), "zip64 end of central directory locator incomplete (to short)");
      self::$unitTest->assertLessThanOrEqual(20, $z64eocdl->getLength(), "garbage after end of zip64 end of central directory locator");
    }

    return $z64eocdl;
  }

  public function readFromString($str, $pos, $size = -1) {
    $this->begin = $pos;
    $magic = readstr($str, $pos, 4);
    if (static::getMagicBytes() != $magic) {
      throw new ParseException("invalid magic");
    }
    $this->numberDiskStartZ64EOCDL = (int) unpack32le(readstr($str, $pos, 4));
    $this->offsetStart = unpack64le(readstr($str, $pos, 8));
    $this->numberDisks = (int) unpack32le(readstr($str, $pos, 4));
    $this->end = $pos - 1;
  }
}

/**
 * @codeCoverageIgnore
 */
class Zip64EndOfCentralDirectoryRecord extends zipRecord {
  protected static $MAGIC = 0x06064b50; // zip64 end of central directory locator
  protected static $shortName = "Z64EOCDR";
  public $size;
  public $madeByVersion;
  public $versionToExtract;
  public $numberDisk;
  public $numberDiskStartCDR;
  public $numberEntriesDisk;
  public $numberEntriesCD;
  public $sizeCD;
  public $offsetStart;

  public function __toString() {
    return sprintf(
        "Size of Zip64 EOCDR:                      %d\n" .
        "Made by version:                          %s\n" .
        "Version needed to extract:                %s\n" .
        "Number of this disk:                      %d\n" .
        "Number of disk with start of cd:          %d\n" .
        "Number of cd record entries on this disk: %d\n" .
        "Total number of cd record entries:        %d\n" .
        "Size of central directory:                %d\n" .
        "Offset of central directory:              %d\n",
        $this->size,
        $this->madeByVersion,
        $this->versionToExtract,
        $this->numberDisk,
        $this->numberDiskStartCDR,
        $this->numberEntriesDisk,
        $this->numberEntriesCD,
        $this->sizeCD,
        $this->offsetStart);
  }

  public static function constructFromString($str, $offset = 0, $size = -1) {
    $z64eocdlPos = strrpos($str, static::getMagicBytes(), -$offset);
    if (self::$unitTest) {
      self::$unitTest->assertFalse(False === $z64eocdlPos, "zip64 end of central directory record missing");
    }

    $z64eocdl = static::__constructFromString($str, $z64eocdlPos);

    if (self::$unitTest) {
      self::$unitTest->assertGreaterThanOrEqual(56, $z64eocdl->getLength(), "zip64 end of central directory record incomplete (to short)");
      self::$unitTest->assertLessThanOrEqual(56, $z64eocdl->getLength(), "garbage after end of zip64 end of central directory record");
    }

    return $z64eocdl;
  }

  public function readFromString($str, $pos, $size = -1) {
    $this->begin = $pos;
    $magic = readstr($str, $pos, 4);
    if (static::getMagicBytes() != $magic) {
      throw new ParseException("invalid magic");
    }
    $this->size = unpack64le(readstr($str, $pos, 8));
    $this->madeByVersion = readstr($str, $pos, 2);
    $this->versionToExtract = readstr($str, $pos, 2);
    $this->numberDisk = (int) unpack32le(readstr($str, $pos, 4));
    $this->numberDiskStartCDR = (int) unpack32le(readstr($str, $pos, 4));
    $this->numberEntriesDisk = unpack64le(readstr($str, $pos, 8));
    $this->numberEntriesCD = unpack64le(readstr($str, $pos, 8));
    $this->sizeCD = unpack64le(readstr($str, $pos, 8));
    $this->offsetStart = unpack64le(readstr($str, $pos, 8));
    $this->end = $pos - 1;
  }
}

/**
 * @codeCoverageIgnore
 */
class CentralDirectoryHeader extends zipRecord {
  protected static $MAGIC = 0x02014b50; // central file header signature
  protected static $shortName = "CDH";
  public $madeByVersion;
  public $versionToExtract;
  public $gpFlags;
  public $gzMethod;
  public $dosTime;
  public $dataCRC32;
  public $sizeCompressed;
  public $size;
  public $lengthFilename;
  public $lengthExtraField;
  public $lengthComment;
  public $diskNumberStart;
  public $fileAttrInternal;
  public $fileAttrExternal;
  public $offsetStart;
  public $filename;
  public $z64Ext;
  public $comment;

  public function __toString() {
    return sprintf(
        "Made by version:                          0x%s\n" .
        "Version needed to extract:                0x%s\n" .
        "General purpose flags:                    0x%s\n" .
        "Compression method:                       0x%s\n" .
        "Dos time:                                 %s\n" .
        "Data CRC32:                               %s\n" .
        "Compressed file size:                     %s\n" .
        "Uncompressed file size:                   %s\n" .
        "Filename length:                          %d\n" .
        "Extra field length:                       %d\n" .
        "Comment length:                           %d\n" .
        "Number of disk with file start:           %s\n" .
        "Internal file attributes.                 %s\n" .
        "External file attributes:                 %s\n" .
        "Offset of start of local file header:     %s\n" .
        "Filename:                                 %s\n" .
        "Comment:                                  %s\n",
        bin2hex($this->madeByVersion),
        bin2hex($this->versionToExtract),
        bin2hex($this->gpFlags),
        bin2hex($this->gzMethod),
        $this->dosTime,
        $this->dataCRC32,
        hexIfFFFFFFFF($this->sizeCompressed),
        hexIfFFFFFFFF($this->size),
        $this->lengthFilename,
        $this->lengthExtraField,
        $this->lengthComment,
        hexIfFFFF($this->diskNumberStart),
        $this->fileAttrInternal,
        $this->fileAttrExternal,
        hexIfFFFFFFFF($this->offsetStart),
        $this->filename,
        $this->comment);
  }

  public static function constructFromString($str, $offset = 0, $size = -1) {
    $cdheadPos = strpos($str, static::getMagicBytes(), $offset);
    if (self::$unitTest) {
      self::$unitTest->assertFalse(False === $cdheadPos, "central directory header missing");
      self::$unitTest->assertEquals($offset, $cdheadPos, "garbage before central directory header");
    }

    return static::__constructFromString($str, $cdheadPos);
  }

  public function readFromString($str, $pos, $size = -1) {
    $this->begin = $pos;
    $magic = readstr($str, $pos, 4);
    if (static::getMagicBytes() != $magic) {
      throw new ParseException("invalid magic");
    }
    $this->madeByVersion = readstr($str, $pos, 2);
    $this->versionToExtract = readstr($str, $pos, 2);
    $this->gpFlags = readstr($str, $pos, 2);
    $this->gzMethod = readstr($str, $pos, 2);
    $this->dosTime = readstr($str, $pos, 4);
    $this->dataCRC32 = (int) unpack32le(readstr($str, $pos, 4));
    $this->sizeCompressed = (int) unpack32le(readstr($str, $pos, 4));
    $this->size = (int) unpack32le(readstr($str, $pos, 4));
    $this->lengthFilename = (int) unpack16le(readstr($str, $pos, 2));
    $this->lengthExtraField = (int) unpack16le(readstr($str, $pos, 2));
    $this->lengthComment = (int) unpack16le(readstr($str, $pos, 2));
    $this->diskNumberStart = (int) unpack16le(readstr($str, $pos, 2));
    $this->fileAttrInternal = readstr($str, $pos, 2);
    $this->fileAttrExternal = readstr($str, $pos, 4);
    $this->offsetStart = (int) unpack32le(readstr($str, $pos, 4));
    if (0 < $this->lengthFilename) {
      $this->filename = (string) readstr($str, $pos, $this->lengthFilename);
    } else {
      $this->filename = '';
    }
    if (0 < $this->lengthExtraField) {
      $this->z64Ext = Zip64ExtendedInformationField::constructFromString($str, $pos);
      if (self::$unitTest) {
        self::$unitTest->assertEquals($this->lengthExtraField, $this->z64Ext->getLength(), "Z64EIF is only field and fits into propagated length");
      }
      $pos = $this->z64Ext->end + 1;
    }
    if (0 < $this->lengthComment) {
      $this->comment = (string) readstr($str, $pos, $this->lengthComment);
    } else {
      $this->comment = '';
    }
    $this->end = $pos - 1;
  }
}

/**
 * @codeCoverageIgnore
 */
class Zip64ExtendedInformationField extends zipRecord {
  protected static $MAGIC = 0x0001; // central file header signature
  protected static $magicLength = 2;
  protected static $shortName = "Z64EIF";
  public $sizeField;
  public $size;
  public $sizeCompressed;
  public $offsetStart;
  public $diskNumberStart;

  public function __toString() {
    return sprintf(
        "Size of this 'extra' block:               %d\n" .
        "Uncompressed file size:                   %d\n" .
        "Compressed file size:                     %d\n" .
        "Offset of begin of local file header:     %d\n" .
        "Number of disk with file start:           %d\n",
        $this->sizeField,
        $this->size,
        $this->sizeCompressed,
        $this->offsetStart,
        $this->diskNumberStart);
  }

  public static function constructFromString($str, $offsetStart = 0, $size = -1) {
    $pos = strpos($str, static::getMagicBytes(), $offsetStart);
    if (self::$unitTest) {
      self::$unitTest->assertFalse(False === $pos, "extra field magic bytes missing");
      self::$unitTest->assertEquals($offsetStart, $pos, "garbage before extra field");
    }

    return static::__constructFromString($str, $pos);
  }

  public function readFromString($str, $pos, $size = -1) {
    $this->begin = $pos;
    $magic = readstr($str, $pos, 2);
    if (static::getMagicBytes() != $magic) {
      throw new ParseException("invalid magic");
    }
    $this->sizeField = (int) unpack16le(readstr($str, $pos, 2));
    $this->size = unpack64le(readstr($str, $pos, 8));
    $this->sizeCompressed = unpack64le(readstr($str, $pos, 8));
    $this->offsetStart = unpack64le(readstr($str, $pos, 8));
    $this->diskNumberStart = (int) unpack16le(readstr($str, $pos, 4));

    $this->end = $pos - 1;
  }
}

/**
 * @codeCoverageIgnore
 */
class FileEntry extends zipRecord {
  protected static $shortName = "FILE";
  public $lfh;
  public $dataCompressed;
  public $data;
  public $dd;

  public function __toString() {
    return sprintf("File content:\n" . "%s", $this->data);
  }

  public function readFromString($str, $pos, $size = -1) {
    $this->begin = $pos;
    $this->lfh = LocalFileHeader::constructFromString($str, $pos);
    $pos = $this->lfh->end + 1;
    if (self::$unitTest) {
      $this->dataCompressed = readStr($str, $pos, $size);
      if (0 < strlen($this->dataCompressed) && COMPR::DEFLATE & $this->lfh->gzMethod) {
        $this->data = gzinflate($this->dataCompressed);
      } else {
        $this->data = $this->dataCompressed;
      }
    }
    if (GPFLAGS::ADD & $this->lfh->gpFlags) {
      if (is_null($this->lfh->z64Ext)) {
        $ddLength = 16;
      } else {
        $ddLength = 24;
      }
      $this->dd = DataDescriptor::constructFromString($str, $pos, $ddLength);
      $pos = $this->dd->end + 1;
    }

    $this->end = $pos - 1;
  }
}

/**
 * @codeCoverageIgnore
 */
class LocalFileHeader extends zipRecord {
  protected static $MAGIC = 0x04034b50; // central file header signature
  protected static $shortName = "LFH";
  public $versionToExtract;
  public $gpFlags;
  public $gzMethod;
  public $dosTime;
  public $dataCRC32;
  public $sizeCompressed;
  public $size;
  public $lengthFilename;
  public $lengthExtraField;
  public $filename;
  public $z64Ext;

  public function __toString() {
    return sprintf(
        "Version needed to extract:                %s\n" .
        "General purpose flags:                    %s\n" .
        "Compression method:                       %s\n" .
        "Dos time:                                 %s\n" .
        "Data CRC32:                               %s\n" .
        "Compressed file size:                     %d\n" .
        "Uncompressed file size:                   %d\n" .
        "Filename length:                          %d\n" .
        "Extra field length:                       %d\n" .
        "Filename:                                 %s\n" ,
        bin2hex($this->versionToExtract),
        bin2hex($this->gpFlags),
        bin2hex($this->gzMethod),
        $this->dosTime,
        $this->dataCRC32,
        hexIfFFFFFFFF($this->sizeCompressed),
        hexIfFFFFFFFF($this->size),
        $this->lengthFilename,
        $this->lengthExtraField,
        $this->filename);
  }

  public static function constructFromString($str, $offset = 0, $size = -1) {
    $cdheadPos = strpos($str, static::getMagicBytes(), $offset);
    if (self::$unitTest) {
      self::$unitTest->assertFalse(False === $cdheadPos, "local file header missing");
      self::$unitTest->assertEquals($offset, $cdheadPos, "garbage before local file header");
    }

    return static::__constructFromString($str, $cdheadPos, $size);
  }

  public function readFromString($str, $pos, $size = -1) {
    $this->begin = $pos;
    $magic = readstr($str, $pos, 4);
    if (static::getMagicBytes() != $magic) {
      throw new ParseException("invalid magic");
    }
    $this->versionToExtract = readstr($str, $pos, 2);
    $this->gpFlags = (int) unpack16le(readstr($str, $pos, 2));
    $this->gzMethod = (int) unpack16le(readstr($str, $pos, 2));
    $this->dosTime = readstr($str, $pos, 4);
    $this->dataCRC32 = (int) unpack32le(readstr($str, $pos, 4));
    $this->sizeCompressed = (int) unpack32le(readstr($str, $pos, 4));
    $this->size = (int) unpack32le(readstr($str, $pos, 4));
    $this->lengthFilename = (int) unpack16le(readstr($str, $pos, 2));
    $this->lengthExtraField = (int) unpack16le(readstr($str, $pos, 2));
    if (0 < $this->lengthFilename) {
      $this->filename = (string) readstr($str, $pos, $this->lengthFilename);
    } else {
      $this->filename = '';
    }
    if (0 < $this->lengthExtraField) {
      $this->z64Ext = Zip64ExtendedInformationField::constructFromString($str, $pos);
      if (self::$unitTest) {
        self::$unitTest->assertEquals($this->lengthExtraField, $this->z64Ext->getLength(), "Z64EIF is only field and fits into propagated length");
      }
      $pos = $this->z64Ext->end + 1;
    }
    $this->end = $pos - 1;
  }
}

/**
 * @codeCoverageIgnore
 */
class DataDescriptor extends zipRecord {
  protected static $MAGIC = 0x08074b50; // data descriptor header signature
  protected static $shortName = "DD";
  public $dataCRC32;
  public $sizeCompressed;
  public $size;

  public function __toString() {
    return sprintf(
        "Data CRC32:                               %s\n" .
        "Compressed file size:                     %d\n" .
        "Uncompressed file size:                   %d\n" ,
        $this->dataCRC32,
        hexIfFFFFFFFF($this->sizeCompressed->getLoBytes()),
        hexIfFFFFFFFF($this->size->getLoBytes()));
  }

  public static function constructFromString($str, $offset = 0, $size = -1) {
    $ddheadPos = strpos($str, static::getMagicBytes(), $offset);
    if (self::$unitTest) {
      self::$unitTest->assertFalse(False === $ddheadPos, "data descriptor header missing");
      self::$unitTest->assertEquals($offset, $ddheadPos, "garbage before data descriptor header");
    }

    return static::__constructFromString($str, $offset, $size);
  }

  public function readFromString($str, $pos, $size = -1) {
    $this->begin = $pos;
    $magic = readstr($str, $pos, 4);
    if (static::getMagicBytes() != $magic) {
      throw new ParseException("invalid magic");
    }
    $this->dataCRC32 = (int) unpack32le(readstr($str, $pos, 4));
    if (24 == $size) {
      $this->sizeCompressed = unpack64le(readstr($str, $pos, 8));
      $this->size = unpack64le(readstr($str, $pos, 8));
    } else {
      $this->sizeCompressed = Count64::construct((int) unpack32le(readstr($str, $pos, 4)));
      $this->size = Count64::construct((int) unpack32le(readstr($str, $pos, 4)));
          }
    $this->end = $pos - 1;
  }
}
?>
