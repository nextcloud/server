PHPASN1
=======

[![Build Status](https://secure.travis-ci.org/fgrosse/PHPASN1.png?branch=master)](http://travis-ci.org/fgrosse/PHPASN1)
[![PHP 7 ready](http://php7ready.timesplinter.ch/fgrosse/PHPASN1/badge.svg)](https://travis-ci.org/fgrosse/PHPASN1)
[![Coverage Status](https://coveralls.io/repos/fgrosse/PHPASN1/badge.svg?branch=master&service=github)](https://coveralls.io/github/fgrosse/PHPASN1?branch=master)

[![Latest Stable Version](https://poser.pugx.org/fgrosse/phpasn1/v/stable.png)](https://packagist.org/packages/fgrosse/phpasn1)
[![Total Downloads](https://poser.pugx.org/fgrosse/phpasn1/downloads.png)](https://packagist.org/packages/fgrosse/phpasn1)
[![Latest Unstable Version](https://poser.pugx.org/fgrosse/phpasn1/v/unstable.png)](https://packagist.org/packages/fgrosse/phpasn1)
[![License](https://poser.pugx.org/fgrosse/phpasn1/license.png)](https://packagist.org/packages/fgrosse/phpasn1)

A PHP Framework that allows you to encode and decode arbitrary [ASN.1][3] structures
using the [ITU-T X.690 Encoding Rules][4].
This encoding is very frequently used in [X.509 PKI environments][5] or the communication between heterogeneous computer systems.

The API allows you to encode ASN.1 structures to create binary data such as certificate
signing requests (CSR), X.509 certificates or certificate revocation lists (CRL).
PHPASN1 can also read [BER encoded][6] binary data into separate PHP objects that can be manipulated by the user and reencoded afterwards.

The **changelog** can now be found at [CHANGELOG.md](CHANGELOG.md).

## Dependencies

PHPASN1 requires at least `PHP 7.0` and either the `gmp` or `bcmath` extension.
Support for older PHP versions (i.e. PHP 5.6) was dropped starting with `v2.0`.
If you must use an outdated PHP version consider using [PHPASN v1.5][13].

For the loading of object identifier names directly from the web [curl][7] is used.

## Installation

The preferred way to install this library is to rely on [Composer][2]:

```bash
$ composer require fgrosse/phpasn1
```

## Usage

### Encoding ASN.1 Structures

PHPASN1 offers you a class for each of the implemented ASN.1 universal types.
The constructors should be pretty self explanatory so you should have no big trouble getting started.
All data will be encoded using [DER encoding][8]

```php
use FG\ASN1\OID;
use FG\ASN1\Universal\Integer;
use FG\ASN1\Universal\Boolean;
use FG\ASN1\Universal\Enumerated;
use FG\ASN1\Universal\IA5String;
use FG\ASN1\Universal\ObjectIdentifier;
use FG\ASN1\Universal\PrintableString;
use FG\ASN1\Universal\Sequence;
use FG\ASN1\Universal\Set;
use FG\ASN1\Universal\NullObject;

$integer = new Integer(123456);        
$boolean = new Boolean(true);
$enum = new Enumerated(1);
$ia5String = new IA5String('Hello world');

$asnNull = new NullObject();
$objectIdentifier1 = new ObjectIdentifier('1.2.250.1.16.9');
$objectIdentifier2 = new ObjectIdentifier(OID::RSA_ENCRYPTION);
$printableString = new PrintableString('Foo bar');

$sequence = new Sequence($integer, $boolean, $enum, $ia5String);
$set = new Set($sequence, $asnNull, $objectIdentifier1, $objectIdentifier2, $printableString);

$myBinary  = $sequence->getBinary();
$myBinary .= $set->getBinary();

echo base64_encode($myBinary);
```


### Decoding binary data

Decoding BER encoded binary data is just as easy as encoding it:

```php
use FG\ASN1\ASNObject;

$base64String = ...
$binaryData = base64_decode($base64String);        
$asnObject = ASNObject::fromBinary($binaryData);


// do stuff
```

If you already know exactly how your expected data should look like you can use the `FG\ASN1\TemplateParser`:

```php
use FG\ASN1\TemplateParser;

// first define your template
$template = [
    Identifier::SEQUENCE => [
        Identifier::SET => [
            Identifier::OBJECT_IDENTIFIER,
            Identifier::SEQUENCE => [
                Identifier::INTEGER,
                Identifier::BITSTRING,
            ]
        ]
    ]
];

// if your binary data is not matching the template you provided this will throw an `\Exception`:
$parser = new TemplateParser();
$object = $parser->parseBinary($data, $template);

// there is also a convenience function if you parse binary data from base64:
$object = $parser->parseBase64($data, $template);
```

You can use this function to make sure your data has exactly the format you are expecting.

### Navigating decoded data

All constructed classes (i.e. `Sequence` and `Set`) can be navigated by array access or using an iterator.
You can find examples
[here](https://github.com/fgrosse/PHPASN1/blob/f6442cadda9d36f3518c737e32f28300a588b777/tests/ASN1/Universal/SequenceTest.php#L148-148),
[here](https://github.com/fgrosse/PHPASN1/blob/f6442cadda9d36f3518c737e32f28300a588b777/tests/ASN1/Universal/SequenceTest.php#L121) and 
[here](https://github.com/fgrosse/PHPASN1/blob/f6442cadda9d36f3518c737e32f28300a588b777/tests/ASN1/TemplateParserTest.php#L45).


### Give me more examples!

To see some example usage of the API classes or some generated output check out the [examples](https://github.com/fgrosse/PHPASN1/tree/master/examples).


### How do I contribute?

If you found an issue or have a question submit a github issue with detailed information.

In case you already know what caused the issue and feel in the mood to fix it, your code contributions are always welcome. Just fork the repository, implement your changes and make sure that you covered everything with tests.
Afterwards submit a pull request via github and be a little patient :) I usually try to comment and/or merge as soon as possible.

#### Mailing list

New features or questions can be discussed in [this google group/mailing list][12].

### Thanks

To [all contributors][1] so far!

## License

This library is distributed under the [MIT License](LICENSE).

[1]: https://github.com/fgrosse/PHPASN1/graphs/contributors
[2]: https://getcomposer.org/
[3]: http://www.itu.int/ITU-T/asn1/
[4]: http://www.itu.int/ITU-T/recommendations/rec.aspx?rec=x.690
[5]: http://en.wikipedia.org/wiki/X.509
[6]: http://en.wikipedia.org/wiki/X.690#BER_encoding
[7]: http://php.net/manual/en/book.curl.php
[8]: http://en.wikipedia.org/wiki/X.690#DER_encoding
[9]: https://styleci.io
[10]: https://coveralls.io/github/fgrosse/PHPASN1
[11]: https://github.com/fgrosse/PHPASN1/blob/master/tests/ASN1/TemplateParserTest.php#L16
[12]: https://groups.google.com/d/forum/phpasn1
[13]: https://packagist.org/packages/fgrosse/phpasn1#1.5.2
