# Usage

```php
require __DIR__ . '/vendor/autoload.php';
$ini = new bantu\IniGetWrapper\IniGetWrapper;
var_dump(
  $ini->getString('does-not-exist'),
  $ini->getString('default_mimetype'),
  $ini->getBool('display_errors'),
  $ini->getNumeric('precision'),
  $ini->getBytes('memory_limit')
);
```

```
NULL
string(9) "text/html"
bool(false)
int(14)
int(134217728)
```
