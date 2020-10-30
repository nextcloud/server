# Security Analysis in Psalm

Psalm can attempt to find connections between user-controlled input (like `$_GET['name']`) and places that we don’t want unescaped user-controlled input to end up (like `echo "<h1>$name</h1>"` by looking at the ways that data flows through your application (via assignments, function/method calls and array/property access).

You can enable this mode with the `--taint-analysis` command line flag. When taint analysis is enabled, no other analysis is performed.

Tainted input is anything that can be controlled, wholly or in part, by a user of your application. In taint analysis, tainted input is called a _taint source_.

Example sources:

 - `$_GET[‘id’]`
 - `$_POST['email']`
 - `$_COOKIE['token']`

 Taint analysis tracks how data flows from taint sources into _taint sinks_. Taint sinks are places you really don’t want untrusted data to end up.

Example sinks:

 - `<div id="section_<?= $id ?>">`
 - `$pdo->exec("select * from users where name='" . $name . "'")`

## Taint Types

Psalm recognises a number of taint types by default, defined in the [Psalm\Type\TaintKind](https://github.com/vimeo/psalm/blob/master/src/Psalm/Type/TaintKind.php) class:

- `text` - used for strings that could be user-controlled
- `sql` - used for strings that could contain SQL
- `html` - used for strings that could contain angle brackets or unquoted strings
- `shell` - used for strings that could contain shell commands
- `user_secret` - used for strings that could contain user-supplied secrets
- `system_secret` - used for strings that could contain system secrets

You're also free to define your own taint types when defining custom taint sources – they're just strings.

## Taint Sources

Psalm currently defines three default taint sources: the `$_GET`, `$_POST` and `$_COOKIE` server variables.

You can also [define your own taint sources](custom_taint_sources.md).

## Taint Sinks

Psalm currently defines a number of different for builtin functions and methods, including `echo`, `include`, `header`.

You can also [define your own taint sinks](custom_taint_sinks.md).

## Avoiding False-Positives

Nobody likes to wade through a ton of false-positives – [here’s a guide to avoiding them](avoiding_false_positives.md).

## Using Baseline With Taint Analysis

Since taint analysis is performed separately from other static code analysis, it makes sense to use a separate baseline for it.

You can use --use-baseline=PATH option to set a different baseline for taint analysis.
