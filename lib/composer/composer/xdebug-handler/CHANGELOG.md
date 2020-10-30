## [Unreleased]

## [1.4.3] - 2020-08-19
  * Fixed: restore SIGINT to default handler in restarted process if no other handler exists.

## [1.4.2] - 2020-06-04
  * Fixed: ignore SIGINTs to let the restarted process handle them.

## [1.4.1] - 2020-03-01
  * Fixed: restart fails if an ini file is empty.

## [1.4.0] - 2019-11-06
  * Added: support for `NO_COLOR` environment variable: https://no-color.org
  * Added: color support for Hyper terminal: https://github.com/zeit/hyper
  * Fixed: correct capitalization of Xdebug (apparently).
  * Fixed: improved handling for uopz extension.

## [1.3.3] - 2019-05-27
  * Fixed: add environment changes to `$_ENV` if it is being used.

## [1.3.2] - 2019-01-28
  * Fixed: exit call being blocked by uopz extension, resulting in application code running twice.

## [1.3.1] - 2018-11-29
  * Fixed: fail restart if `passthru` has been disabled in `disable_functions`.
  * Fixed: fail restart if an ini file cannot be opened, otherwise settings will be missing.

## [1.3.0] - 2018-08-31
  * Added: `setPersistent` method to use environment variables for the restart.
  * Fixed: improved debugging by writing output to stderr.
  * Fixed: no restart when `php_ini_scanned_files` is not functional and is needed.

## [1.2.1] - 2018-08-23
  * Fixed: fatal error with apc, when using `apc.mmap_file_mask`.

## [1.2.0] - 2018-08-16
  * Added: debug information using `XDEBUG_HANDLER_DEBUG`.
  * Added: fluent interface for setters.
  * Added: `PhpConfig` helper class for calling PHP sub-processes.
  * Added: `PHPRC` original value to restart stettings, for use in a restarted process.
  * Changed: internal procedure to disable ini-scanning, using `-n` command-line option.
  * Fixed: replaced `escapeshellarg` usage to avoid locale problems.
  * Fixed: improved color-option handling to respect double-dash delimiter.
  * Fixed: color-option handling regression from main script changes.
  * Fixed: improved handling when checking main script.
  * Fixed: handling for standard input, that never actually did anything.
  * Fixed: fatal error when ctype extension is not available.

## [1.1.0] - 2018-04-11
  * Added: `getRestartSettings` method for calling PHP processes in a restarted process.
  * Added: API definition and @internal class annotations.
  * Added: protected `requiresRestart` method for extending classes.
  * Added: `setMainScript` method for applications that change the working directory.
  * Changed: private `tmpIni` variable to protected for extending classes.
  * Fixed: environment variables not available in $_SERVER when restored in the restart.
  * Fixed: relative path problems caused by Phar::interceptFileFuncs.
  * Fixed: incorrect handling when script file cannot be found.

## [1.0.0] - 2018-03-08
  * Added: PSR3 logging for optional status output.
  * Added: existing ini settings are merged to catch command-line overrides.
  * Added: code, tests and other artefacts to decouple from Composer.
  * Break: the following class was renamed:
    - `Composer\XdebugHandler` -> `Composer\XdebugHandler\XdebugHandler`

[Unreleased]: https://github.com/composer/xdebug-handler/compare/1.4.3...HEAD
[1.4.3]: https://github.com/composer/xdebug-handler/compare/1.4.2...1.4.3
[1.4.2]: https://github.com/composer/xdebug-handler/compare/1.4.1...1.4.2
[1.4.1]: https://github.com/composer/xdebug-handler/compare/1.4.0...1.4.1
[1.4.0]: https://github.com/composer/xdebug-handler/compare/1.3.3...1.4.0
[1.3.3]: https://github.com/composer/xdebug-handler/compare/1.3.2...1.3.3
[1.3.2]: https://github.com/composer/xdebug-handler/compare/1.3.1...1.3.2
[1.3.1]: https://github.com/composer/xdebug-handler/compare/1.3.0...1.3.1
[1.3.0]: https://github.com/composer/xdebug-handler/compare/1.2.1...1.3.0
[1.2.1]: https://github.com/composer/xdebug-handler/compare/1.2.0...1.2.1
[1.2.0]: https://github.com/composer/xdebug-handler/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/composer/xdebug-handler/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/composer/xdebug-handler/compare/d66f0d15cb57...1.0.0
