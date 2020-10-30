# Checking non-PHP files

Psalm supports the ability to check various PHPish files by extending the `FileChecker` class. For example, if you have a template where the variables are set elsewhere, Psalm can scrape those variables and check the template with those variables pre-populated.

An example TemplateChecker is provided [here](https://github.com/vimeo/psalm/blob/master/examples/TemplateChecker.php).

To ensure your custom `FileChecker` is used, you must update the Psalm `fileExtensions` config in psalm.xml:
```xml
<fileExtensions>
    <extension name=".php" />
    <extension name=".phpt" checker="path/to/TemplateChecker.php" />
</fileExtensions>
```
