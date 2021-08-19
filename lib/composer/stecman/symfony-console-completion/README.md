# BASH/ZSH auto-complete for Symfony Console applications

[![Build Status](https://travis-ci.org/stecman/symfony-console-completion.svg?branch=master)](https://travis-ci.org/stecman/symfony-console-completion)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/stecman/symfony-console-completion/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/stecman/symfony-console-completion/?branch=master)

[![Latest Stable Version](https://poser.pugx.org/stecman/symfony-console-completion/v/stable.png)](https://packagist.org/packages/stecman/symfony-console-completion)
[![Total Downloads](https://poser.pugx.org/stecman/symfony-console-completion/downloads.png)](https://packagist.org/packages/stecman/symfony-console-completion)
[![Latest Unstable Version](https://poser.pugx.org/stecman/symfony-console-completion/v/unstable.svg)](https://packagist.org/packages/stecman/symfony-console-completion)
[![License](https://poser.pugx.org/stecman/symfony-console-completion/license.svg)](https://packagist.org/packages/stecman/symfony-console-completion)

This package provides automatic (tab) completion in BASH and ZSH for Symfony Console Component based applications. With zero configuration, this package allows completion of available command names and the options they provide. User code can define custom completion behaviour for argument and option values.

Example of zero-config use with Composer:

![Composer BASH completion](https://i.imgur.com/MoDWkby.gif)

## Zero-config use

If you don't need any custom completion behaviour, you can simply add the completion command to your application:

1. Install `stecman/symfony-console-completion` using [composer](https://getcomposer.org/) by running:
   ```
   $ composer require stecman/symfony-console-completion
   ```

2. For standalone Symfony Console applications, add an instance of `CompletionCommand` to your application's `Application::getDefaultCommands()` method:

   ```php
   protected function getDefaultCommands()
   {
      //...
       $commands[] = new \Stecman\Component\Symfony\Console\BashCompletion\CompletionCommand();
      //...
   }
   ```

   For Symfony Framework applications, register the `CompletionCommand` as a service in `app/config/services.yml`:

   ```yaml
   services:
   #...
       console.completion_command:
         class: Stecman\Component\Symfony\Console\BashCompletion\CompletionCommand
         tags:
             -  { name: console.command }
   #...
   ```

3. Register completion for your application by running one of the following in a terminal, replacing `[program]` with the command you use to run your application (eg. 'composer'):

   ```bash
   # BASH ~4.x, ZSH
   source <([program] _completion --generate-hook)

   # BASH ~3.x, ZSH
   [program] _completion --generate-hook | source /dev/stdin

   # BASH (any version)
   eval $([program] _completion --generate-hook)
   ```

   By default this registers completion for the absolute path to you application, which will work if the program is accessible on your PATH. You can specify a program name to complete for instead using the `--program` option, which is required if you're using an alias to run the program.

4. If you want the completion to apply automatically for all new shell sessions, add the command from step 3 to your shell's profile (eg. `~/.bash_profile` or `~/.zshrc`)

Note: The type of shell (ZSH/BASH) is automatically detected using the `SHELL` environment variable at run time. In some circumstances, you may need to explicitly specify the shell type with the `--shell-type` option.


## How it works

The `--generate-hook` option of `CompletionCommand` generates a small shell script that registers a function with your shell's completion system to act as a bridge between the shell and the completion command in your application. When you request completion for your program (by pressing tab with your program name as the first word on the command line), the bridge function is run; passing the current command line contents and cursor position to `[program] _completion`, and feeding the resulting output back to the shell.


## Defining value completions

By default, no completion results will be returned for option and argument values. There are two ways of defining custom completion values for values: extend `CompletionCommand`, or implement `CompletionAwareInterface`.

### Implementing `CompletionAwareInterface`

`CompletionAwareInterface` allows a command to be responsible for completing its own option and argument values. When completion is run with a command name specified (eg. `myapp mycommand ...`) and the named command implements this interface, the appropriate interface method is called automatically:

```php
class MyCommand extends Command implements CompletionAwareInterface
{
    ...

    public function completeOptionValues($optionName, CompletionContext $context)
    {
        if ($optionName == 'some-option') {
            return ['myvalue', 'other-value', 'word'];
        }
    }

    public function completeArgumentValues($argumentName, CompletionContext $context)
    {
        if ($argumentName == 'package') {
            return $this->getPackageNamesFromDatabase($context->getCurrentWord());
        }
    }
}
```

This method of generating completions doesn't support use of `CompletionInterface` implementations at the moment, which make it easy to share completion behaviour between commands. To use this functionality, you'll need write your value completions by extending `CompletionCommand`.


### Extending `CompletionCommand`

Argument and option value completions can also be defined by extending `CompletionCommand` and overriding the `configureCompletion` method:

```php
class MyCompletionCommand extends CompletionCommand
{
    protected function configureCompletion(CompletionHandler $handler)
    {
        $handler->addHandlers([
            // Instances of Completion go here.
            // See below for examples.
        ]);
    }
}
```

#### The `Completion` class

The following snippets demonstrate how the `Completion` class works with `CompletionHandler`, and some possible configurations. The examples are for an application with the signature:

    `myapp (walk|run) [-w|--weather=""] direction`


##### Command-specific argument completion with an array

```php
$handler->addHandler(
    new Completion(
        'walk',                    // match command name
        'direction',               // match argument/option name
        Completion::TYPE_ARGUMENT, // match definition type (option/argument)
        [                     // array or callback for results
            'north',
            'east',
            'south',
            'west'
        ]
    )
);
```

This will complete the `direction` argument for this:

```bash
$ myapp walk [tab]
```

but not this:

```bash
$ myapp run [tab]
```

##### Non-command-specific (global) argument completion with a function

```php
$handler->addHandler(
    new Completion(
        Completion::ALL_COMMANDS,
        'direction',
        Completion::TYPE_ARGUMENT,
        function() {
            return range(1, 10);
        }
    )
);
```

This will complete the `direction` argument for both commands:

```bash
$ myapp walk [tab]
$ myapp run [tab]
```

##### Option completion

Option handlers work the same way as argument handlers, except you use `Completion::TYPE_OPTION` for the type.

```php
$handler->addHandler(
    new Completion(
        Completion::ALL_COMMANDS,
        'weather',
        Completion::TYPE_OPTION,
        [
            'raining',
            'sunny',
            'everything is on fire!'
        ]
    )
);
```

##### Completing the for both arguments and options

To have a completion run for both options and arguments matching the specified name, you can use the type `Completion::ALL_TYPES`. Combining this with `Completion::ALL_COMMANDS` and consistent option/argument naming throughout your application, it's easy to share completion behaviour between commands, options and arguments:

```php
$handler->addHandler(
    new Completion(
        Completion::ALL_COMMANDS,
        'package',
        Completion::ALL_TYPES,
        function() {
            // ...
        }
    )
);
```

## Example completions

### Completing references from a Git repository

```php
new Completion(
    Completion::ALL_COMMANDS,
    'ref',
    Completion::TYPE_OPTION,
    function () {
        $raw = shell_exec('git show-ref --abbr');
        if (preg_match_all('/refs\/(?:heads|tags)?\/?(.*)/', $raw, $matches)) {
            return $matches[1];
        }
    }
)
```

### Completing filesystem paths

This library provides the completion implementation `ShellPathCompletion` which defers path completion to the shell's built-in path completion behaviour rather than implementing it in PHP, so that users get the path completion behaviour they expect from their shell.

```php
new Completion\ShellPathCompletion(
    Completion::ALL_COMMANDS,
    'path',
    Completion::TYPE_OPTION
)

```

## Behaviour notes

* Option shortcuts are not offered as completion options, however requesting completion (ie. pressing tab) on a valid option shortcut will complete.
* Completion is not implemented for the `--option="value"` style of passing a value to an option, however `--option value` and `--option "value"` work and are functionally identical.
* Value completion is always run for options marked as `InputOption::VALUE_OPTIONAL` since there is currently no way to determine the desired behaviour from the command line contents (ie. skip the optional value or complete for it)
