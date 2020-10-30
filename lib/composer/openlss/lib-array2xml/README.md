lib-array2xml
=============

Array2XML conversion library credit to lalit.org

Usage
----
```php
//create XML
$xml = Array2XML::createXML('root_node_name', $php_array);
echo $xml->saveXML();

//create Array
$array = XML2Array::createArray($xml);
print_r($array);
```

Array2XML
----

@xml example:
```php
// Build the array that should be transformed into a XML object.
$array = [
    'title' => 'A title',
    'body' => [
        '@xml' => '<html><body><p>The content for the news item</p></body></html>',
    ],
];

// Use the Array2XML object to transform it.
$xml = Array2XML::createXML('news', $array);
echo $xml->saveXML();
```
This will result in the following.
```xml
<?xml version="1.0" encoding="UTF-8"?>
<news>
  <title>A title</title>
  <body>
    <html>
      <body>
        <p>The content for the news item</p>
      </body>
    </html>
  </body>
</news>
```

Reference
----
More complete references can be found here
	http://www.lalit.org/lab/convert-xml-to-array-in-php-xml2array/
	http://www.lalit.org/lab/convert-php-array-to-xml-with-attributes/

## Changelog

### 1.0.0
* Add ability for callbacks during processing to check status.

### 0.5.1
* Fix fata error when the array passed is empty fixed by pull request #6

### 0.5.0
* add second parameter to XML2Array::createArray for DOMDocument::load, e.g: LIBXML_NOCDATA
* change method visibility from private to protected for overloading
* Merge pull request #5 to add child xml
* Merge pull request #4 to change method visibility and add second parameter for load.


### 0.1.0
* Initial Release
