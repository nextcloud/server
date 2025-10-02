ZipStreamer Manual
==================

This is a short manual to using ZipStreamer in a php web application.

In short, it works as follows: a ZipStreamer object is initialized. 
Afterwards, (file) streams and directory names/paths can be added to the
ZipStreamer object, which will immediately be streamed to the client (web
browser). After adding all desired files/directories, the ZipStreamer object
is finalized and the zip file is then complete.

Example
-------
```php
require("src/ZipStreamer.php");

# initialize ZipStreamer object (ZipStreamer has it's own namespace)
$zip = new ZipStreamer\ZipStreamer();

# optionally send fitting headers - you can also send your own headers if 
# desired or omit them if you want to stream to other targets but a http
# client
#$zip->sendHeaders();

# get a stream to a file to be added to the zip file
$stream = fopen('inputfile.txt','r');

# add the file to the zip stream (output is sent immediately)
$zip->addFileFromStream($stream, 'test1.txt');

# close the stream if you opened it yourself
fclose($stream);

# add an empty directory to the zip file (also sent immediately)
$zip->addEmptyDir("testdirectory");

# finalize zip file. Nothing can be added any more. 
$zip->finalize();

```

Characteristics
---------------

* **Performance:** ZipStreamer causes no disk i/o (aside from the input
streams, if they are created from disk), has low cpu usage (especially when
not compressing) and a low memory footprint, as the streams are read in small
chunks
* **Compatibility issues:** ZipStreamer produces 'streamed' zip files (part of
the zip standard since 1993). Some (mostly older) zip tools and Mac OS X finder
can not handle that. ZipStreamer by default uses the Zip64 extension. Some
(mostly older) zip tools and Mac OS X can not handle that, therefore it can be
disabled (see below)
* **Large output files:** With the Zip64 extension, ZipStreamer can handle
output zip files larger than 2/4 GB on both 32bit and 64bit machines
* **Large input files:** With the Zip64 extension, ZipStreamer can handle
input streams larger then 2/4 GB on both 32bit and 64bit machines. On 32bit
machines, that usually means that the LFS has to be enabled (but if the stream
source is not the filesystem, that may not even be necessary)
* **Compression:** ZipStreamer will not compress the content by default. That
means that the output zip file will be of the same size (plus a few bytes) as
the input files. However, if the pecl_http extension (>= 0.10) is available,
deflate (the zip standard) compression can be enabled and/or disabled globally
and per file. Without pecl_http extension, it is still possible to enable
deflate compression, but with compression level 0, so there is no actual 
compression.

API Documentation
-----------------

This is the documentation of the public API of ZipStreamer.

###Namespace ZipSteamer
####Class Zipstreamer

#####Methods
```
__construct(array $options)
```

Constructor. Initializes ZipStreamer object for immediate usage.

Valid options for ZipStreamer are:

* stream *outstream*: the zip file is output to (default: stdout)
* int *compress*: compression method (one of *COMPR::STORE*,
*COMPR::DEFLATE*, default *COMPR::STORE*) can be overridden for single files
* int *level*: compression level (one of *COMPR::NONE*, *COMPR::NORMAL*, 
*COMPR::MAXIMUM*, *COMPR::SUPERFAST*, default *COMPR::NORMAL*) can be 
overridden for single files
* zip64:     boolean indicating use of Zip64 extension (default: True)


######Parameters
 * array *$options* Optional, ZipStreamer and zip file options as key/value pairs.

```
sendHeaders(string $archiveName, string $contentType)
```

Send appropriate http headers before streaming the zip file and disable output buffering.

This method, if used, has to be called before adding anything to the zip file.

######Parameters
* string *$archiveName* Filename of archive to be created (optional, default 'archive.zip')
* string *$contentType* Content mime type to be set (optional, default 'application/zip')

```
addFileFromStream(string $stream, string $filePath, array $options) : bool
```

Add a file to the archive at the specified location and file name.

######Parameters
* string *$stream* Stream to read data from
* string *$filePath* Filepath and name to be used in the archive.
* array *options* (optional) additional options. Valid options are:
    * int *$timestamp* Timestamp for the file (default: current time)
    * string *$comment* comment to be added for this file (default: none)
    * int *compress*: compression method (override global option for this
    file)
    * int *level*: compression level (override global option for this file)

######Returns
bool Success

```
addEmptyDir(string $directoryPath, array $options) : bool
```

Add an empty directory entry to the zip archive.

######Parameters
* string *$directoryPath* Directory Path and name to be added to the archive.
* array *options* (optional) additional options. Valid options are:
    * int *$timestamp* Timestamp for the dir (default: current time)
    * string *$comment* comment to be added for the dir (default: none)

######Returns
bool Success

```
finalize() : bool
```

Close the archive.

A closed archive can no longer have new files added to it. After closing, the zip file is completely written to the output stream.

######Returns
bool Success

