# InvalidClass

Emitted when referencing a class with the wrong casing

```php
<?php

class Foo {}
(new foo());
```

Could also be an issue in the namespace even if the class has the correct casing
```php
<?php

namespace OneTwo {
    class Three {}
}

namespace {
    use Onetwo\Three;
    //     ^ ("t" instead of "T")

    $three = new Three();
}
```
