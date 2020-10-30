Painfree Handling of File Paths
===============================

Dealing with file paths usually involves some difficulties:
 
* **System Heterogeneity**: File paths look different on different platforms. 
  UNIX file paths start with a slash ("/"), while Windows file paths start with
  a system drive ("C:"). UNIX uses forward slashes, while Windows uses 
  backslashes by default ("\").
  
* **Absolute/Relative Paths**: Web applications frequently need to deal with
  absolute and relative paths. Converting one to the other properly is tricky
  and repetitive.

This package provides few, but robust utility methods to simplify your life
when dealing with file paths.

Canonicalization
----------------

*Canonicalization* is the transformation of a path into a normalized (the
"canonical") format. You can canonicalize a path with `Path::canonicalize()`:

```php
echo Path::canonicalize('/var/www/vhost/webmozart/../config.ini');
// => /var/www/vhost/config.ini
```

The following modifications happen during canonicalization:

* "." segments are removed;
* ".." segments are resolved;
* backslashes ("\") are converted into forward slashes ("/");
* root paths ("/" and "C:/") always terminate with a slash;
* non-root paths never terminate with a slash;
* schemes (such as "phar://") are kept;
* replace "~" with the user's home directory.

You can pass absolute paths and relative paths to `canonicalize()`. When a
relative path is passed, ".." segments at the beginning of the path are kept:

```php
echo Path::canonicalize('../uploads/../config/config.yml');
// => ../config/config.yml
```

Malformed paths are returned unchanged:

```php
echo Path::canonicalize('C:Programs/PHP/php.ini');
// => C:Programs/PHP/php.ini
```

Converting Absolute/Relative Paths
----------------------------------

Absolute/relative paths can be converted with the methods `Path::makeAbsolute()`
and `Path::makeRelative()`.

`makeAbsolute()` expects a relative path and a base path to base that relative
path upon:

```php
echo Path::makeAbsolute('config/config.yml', '/var/www/project');
// => /var/www/project/config/config.yml
```

If an absolute path is passed in the first argument, the absolute path is
returned unchanged:

```php
echo Path::makeAbsolute('/usr/share/lib/config.ini', '/var/www/project');
// => /usr/share/lib/config.ini
```

The method resolves ".." segments, if there are any:

```php
echo Path::makeAbsolute('../config/config.yml', '/var/www/project/uploads');
// => /var/www/project/config/config.yml
```

This method is very useful if you want to be able to accept relative paths (for 
example, relative to the root directory of your project) and absolute paths at
the same time.

`makeRelative()` is the inverse operation to `makeAbsolute()`:

```php
echo Path::makeRelative('/var/www/project/config/config.yml', '/var/www/project');
// => config/config.yml
```

If the path is not within the base path, the method will prepend ".." segments
as necessary:

```php
echo Path::makeRelative('/var/www/project/config/config.yml', '/var/www/project/uploads');
// => ../config/config.yml
```

Use `isAbsolute()` and `isRelative()` to check whether a path is absolute or
relative:

```php
Path::isAbsolute('C:\Programs\PHP\php.ini')
// => true
```

All four methods internally canonicalize the passed path.

Finding Longest Common Base Paths
---------------------------------

When you store absolute file paths on the file system, this leads to a lot of 
duplicated information:

```php
return array(
    '/var/www/vhosts/project/httpdocs/config/config.yml',
    '/var/www/vhosts/project/httpdocs/config/routing.yml',
    '/var/www/vhosts/project/httpdocs/config/services.yml',
    '/var/www/vhosts/project/httpdocs/images/banana.gif',
    '/var/www/vhosts/project/httpdocs/uploads/images/nicer-banana.gif',
);
```

Especially when storing many paths, the amount of duplicated information is
noticeable. You can use `Path::getLongestCommonBasePath()` to check a list of
paths for a common base path:

```php
$paths = array(
    '/var/www/vhosts/project/httpdocs/config/config.yml',
    '/var/www/vhosts/project/httpdocs/config/routing.yml',
    '/var/www/vhosts/project/httpdocs/config/services.yml',
    '/var/www/vhosts/project/httpdocs/images/banana.gif',
    '/var/www/vhosts/project/httpdocs/uploads/images/nicer-banana.gif',
);

Path::getLongestCommonBasePath($paths);
// => /var/www/vhosts/project/httpdocs
```

Use this path together with `Path::makeRelative()` to shorten the stored paths:

```php
$bp = '/var/www/vhosts/project/httpdocs';

return array(
    $bp.'/config/config.yml',
    $bp.'/config/routing.yml',
    $bp.'/config/services.yml',
    $bp.'/images/banana.gif',
    $bp.'/uploads/images/nicer-banana.gif',
);
```

`getLongestCommonBasePath()` always returns canonical paths.

Use `Path::isBasePath()` to test whether a path is a base path of another path:

```php
Path::isBasePath("/var/www", "/var/www/project");
// => true

Path::isBasePath("/var/www", "/var/www/project/..");
// => true

Path::isBasePath("/var/www", "/var/www/project/../..");
// => false
```

Finding Directories/Root Directories
------------------------------------

PHP offers the function `dirname()` to obtain the directory path of a file path.
This method has a few quirks:

* `dirname()` does not accept backslashes on UNIX
* `dirname("C:/Programs")` returns "C:", not "C:/"
* `dirname("C:/")` returns ".", not "C:/"
* `dirname("C:")` returns ".", not "C:/"
* `dirname("Programs")` returns ".", not ""
* `dirname()` does not canonicalize the result

`Path::getDirectory()` fixes these shortcomings:

```php
echo Path::getDirectory("C:\Programs");
// => C:/
```

Additionally, you can use `Path::getRoot()` to obtain the root of a path:

```php
echo Path::getRoot("/etc/apache2/sites-available");
// => /

echo Path::getRoot("C:\Programs\Apache\Config");
// => C:/
```

