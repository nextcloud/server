# Locale [![Build Status](https://img.shields.io/travis/giggsey/Locale.svg?style=flat-square)](https://travis-ci.org/giggsey/Locale) [![Coverage Status](https://img.shields.io/coveralls/giggsey/Locale.svg?style=flat-square)](https://coveralls.io/r/giggsey/Locale) [![StyleCI](https://styleci.io/repos/24566760/shield)](https://styleci.io/repos/24566760)

[![Total Downloads](https://poser.pugx.org/giggsey/locale/downloads?format=flat-square)](https://packagist.org/packages/giggsey/locale) [![Latest Stable Version](https://poser.pugx.org/giggsey/locale/v/stable?format=flat-square)](https://packagist.org/packages/giggsey/locale) [![License](https://poser.pugx.org/giggsey/locale/license?format=flat-square)](https://packagist.org/packages/giggsey/locale)

A library providing up to date [CLDR](http://cldr.unicode.org/). Primarily as a requirement of [libphonenumber-for-php](https://github.com/giggsey/libphonenumber-for-php).

## Reasoning

This was created because [libphonenumber-for-php](https://github.com/giggsey/libphonenumber-for-php) required the [intl](http://php.net/intl) extension to use the Geo Coder. The extension is not installed by default, and can be a hurdle for users. It also relies on the [CLDR](http://cldr.unicode.org) data provided by the Operating System, which is quite often out of date.

## Generating data

Data is compiled from the latest [CLDR Data](http://cldr.unicode.org/) as specified in [CLDR-VERSION.txt](CLDR-VERSION.txt).

A [Phing](https://www.phing.info/) task is used to compile the data from [JSON](https://github.com/unicode-cldr/cldr-localenames-full) into native PHP arrays.

It is not normally needed to compile the data, as this repository will always have the up to date CLDR data.
To manually compile the data, ensure you have all the dependencies installed, then run:

```bash
vendor/bin/phing compile
```
