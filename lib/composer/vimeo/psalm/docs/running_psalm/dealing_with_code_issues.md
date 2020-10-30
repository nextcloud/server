# Dealing with code issues

Psalm has a large number of [code issues](issues.md). Each project can specify its own reporting level for a given issue.

Code issue levels in Psalm fall into three categories:
<dl>
  <dt>error</dt>
  <dd>This will cause Psalm to print a message, and to ultimately terminate with a non-zero exit status</dd>
  <dt>info</dt>
  <dd>This will cause Psalm to print a message</dd>
  <dt>suppress</dt>
  <dd>This will cause Psalm to ignore the code issue entirely</dd>
</dl>

The third category, `suppress`, is the one you will probably be most interested in, especially when introducing Psalm to a large codebase.

## Suppressing issues

There are two ways to suppress an issue – via the Psalm config or via a function docblock.

### Config suppression

You can use the `<issueHandlers>` tag in the config file to influence how issues are treated.

Some issue types allow the use of `referencedMethod`, `referencedClass` or `referencedVariable` to isolate known trouble spots.

```xml
<issueHandlers>
  <MissingPropertyType errorLevel="suppress" />

  <InvalidReturnType>
    <errorLevel type="suppress">
      <directory name="some_bad_directory" /> <!-- all InvalidReturnType issues in this directory are suppressed -->
      <file name="some_bad_file.php" />  <!-- all InvalidReturnType issues in this file are suppressed -->
    </errorLevel>
  </InvalidReturnType>
  <UndefinedMethod>
    <errorLevel type="suppress">
      <referencedMethod name="Bar\Bat::bar" />
      <file name="some_bad_file.php" />
    </errorLevel>
  </UndefinedMethod>
  <UndefinedClass>
    <errorLevel type="suppress">
      <referencedClass name="Bar\Bat\Baz" />
    </errorLevel>
  </UndefinedClass>
  <PropertyNotSetInConstructor>
    <errorLevel type="suppress">
        <referencedProperty name="Symfony\Component\Validator\ConstraintValidator::$context" />
    </errorLevel>
  </PropertyNotSetInConstructor>
  <UndefinedGlobalVariable>
    <errorLevel type="suppress">
      <referencedVariable name="$fooBar" /> <!-- if your variable is "$fooBar" -->
    </errorLevel>
</UndefinedGlobalVariable>
</issueHandlers>
```

### Docblock suppression

You can also use `@psalm-suppress IssueName` on a function's docblock to suppress Psalm issues e.g.

```php
<?php
/**
 * @psalm-suppress InvalidReturnType
 */
function (int $a) : string {
  return $a;
}
```

You can also suppress issues at the line level e.g.

```php
<?php
/**
 * @psalm-suppress InvalidReturnType
 */
function (int $a) : string {
  /**
   * @psalm-suppress InvalidReturnStatement
   */
  return $a;
}
```

If you wish to suppress all issues, you can use `@psalm-suppress all` instead of multiple annotations.

## Using a baseline file

If you have a bunch of errors and you don't want to fix them all at once, Psalm can now grandfather-in errors in existing code, while ensuring that new code doesn't have those same sorts of errors.

```
vendor/bin/psalm --set-baseline=your-baseline.xml
```

will generate a file containing the current errors. You can commit that generated file so that Psalm running in other places (e.g. CI) won't complain about those errors either, and you can update that baseline file (to remove references to things that have been fixed) with

```
vendor/bin/psalm --update-baseline
```

Your mileage may vary, but we've found baseline files to be a great way to gradually improve a codebase.
