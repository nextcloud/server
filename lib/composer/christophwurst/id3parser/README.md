# ID3 Parser

This is a pure ID3 parser based upon [getID3](https://github.com/JamesHeinrich/getID3). It supports the following ID3
versions inside MP3 files:

- ID3v1 (v1.0 & v1.1)
- ID3v2 (v2.2, v2.3 & v2.4)

## Usage

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

$analyzer = new \ID3Parser\ID3Parser();
$tags = $analyzer->analyze('/tmp/myfile.mp3'));
```

## Why should I use this package over getID3 directly?

getID3 has evolved to a state where it is having a lot of other features such as parsing a ton of other file formats and
for some of it, it is even invoking external programs on the server. For example it is nowadays even supporting SVG files.

Such a big parsing library can easily be haunted by security related bugs as for example [CVE-2014-2053](https://cve.mitre.org/cgi-bin/cvename.cgi?name=CVE-2014-2053)
and some other vulnerabilities have proven. This library takes the ID3 parsing code from getID3 and strips all other
functions.

In cases where reading the ID3v2 tags is sufficient this library is likely to be a more secure approach, if you need any
of the advanced features of getID3 however you're likely to be unhappy with this library.
