<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Console\Command;

use PhpCsFixer\Preg;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
final class ReadmeCommand extends Command
{
    protected static $defaultName = 'readme';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Generates the README content, based on the fix command help.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $header = <<<EOF
{$this->header('PHP Coding Standards Fixer', '=')}

The PHP Coding Standards Fixer (PHP CS Fixer) tool fixes your code to follow standards;
whether you want to follow PHP coding standards as defined in the PSR-1, PSR-2, etc.,
or other community driven ones like the Symfony one.
You can **also** define your (team's) style through configuration.

It can modernize your code (like converting the ``pow`` function to the ``**`` operator on PHP 5.6)
and (micro) optimize it.

If you are already using a linter to identify coding standards problems in your
code, you know that fixing them by hand is tedious, especially on large
projects. This tool does not only detect them, but also fixes them for you.

The PHP CS Fixer is maintained on GitHub at https://github.com/FriendsOfPHP/PHP-CS-Fixer
bug reports and ideas about new features are welcome there.

You can talk to us at https://gitter.im/PHP-CS-Fixer/Lobby about the project,
configuration, possible improvements, ideas and questions, please visit us!

{$this->header('Requirements', '-')}

PHP needs to be a minimum version of PHP 5.6.0.

{$this->header('Installation', '-')}

{$this->header('Locally', '~')}

Download the `php-cs-fixer.phar`_ file and store it somewhere on your computer.

{$this->header('Globally (manual)', '~')}

You can run these commands to easily access latest ``php-cs-fixer`` from anywhere on
your system:

.. code-block:: bash

    $ wget %download.url% -O php-cs-fixer

or with specified version:

.. code-block:: bash

    $ wget %download.version_url% -O php-cs-fixer

or with curl:

.. code-block:: bash

    $ curl -L %download.url% -o php-cs-fixer

then:

.. code-block:: bash

    $ sudo chmod a+x php-cs-fixer
    $ sudo mv php-cs-fixer /usr/local/bin/php-cs-fixer

Then, just run ``php-cs-fixer``.

{$this->header('Globally (Composer)', '~')}

To install PHP CS Fixer, `install Composer <https://getcomposer.org/download/>`_ and issue the following command:

.. code-block:: bash

    $ composer global require friendsofphp/php-cs-fixer

Then make sure you have the global Composer binaries directory in your ``PATH``. This directory is platform-dependent, see `Composer documentation <https://getcomposer.org/doc/03-cli.md#composer-home>`_ for details. Example for some Unix systems:

.. code-block:: bash

    $ export PATH="\$PATH:\$HOME/.composer/vendor/bin"

{$this->header('Globally (homebrew)', '~')}

.. code-block:: bash

    $ brew install php-cs-fixer

{$this->header('Locally (PHIVE)', '~')}

Install `PHIVE <https://phar.io>`_ and issue the following command:

.. code-block:: bash

    $ phive install php-cs-fixer # use `--global` for global install

{$this->header('Update', '-')}

{$this->header('Locally', '~')}

The ``self-update`` command tries to update ``php-cs-fixer`` itself:

.. code-block:: bash

    $ php php-cs-fixer.phar self-update

{$this->header('Globally (manual)', '~')}

You can update ``php-cs-fixer`` through this command:

.. code-block:: bash

    $ sudo php-cs-fixer self-update

{$this->header('Globally (Composer)', '~')}

You can update ``php-cs-fixer`` through this command:

.. code-block:: bash

    $ ./composer.phar global update friendsofphp/php-cs-fixer

{$this->header('Globally (homebrew)', '~')}

You can update ``php-cs-fixer`` through this command:

.. code-block:: bash

    $ brew upgrade php-cs-fixer

{$this->header('Locally (PHIVE)', '~')}

.. code-block:: bash

    $ phive update php-cs-fixer

{$this->header('Usage', '-')}

EOF;

        $footer = <<<EOF

{$this->header('Helpers', '-')}

Dedicated plugins exist for:

* `Atom`_
* `NetBeans`_
* `PhpStorm`_
* `Sublime Text`_
* `Vim`_
* `VS Code`_

{$this->header('Contribute', '-')}

The tool comes with quite a few built-in fixers, but everyone is more than
welcome to `contribute`_ more of them.

{$this->header('Fixers', '~')}

A *fixer* is a class that tries to fix one CS issue (a ``Fixer`` class must
implement ``FixerInterface``).

{$this->header('Configs', '~')}

A *config* knows about the CS rules and the files and directories that must be
scanned by the tool when run in the directory of your project. It is useful for
projects that follow a well-known directory structures (like for Symfony
projects for instance).

.. _php-cs-fixer.phar: %download.url%
.. _Atom:              https://github.com/Glavin001/atom-beautify
.. _NetBeans:          http://plugins.netbeans.org/plugin/49042/php-cs-fixer
.. _PhpStorm:          https://medium.com/@valeryan/how-to-configure-phpstorm-to-use-php-cs-fixer-1844991e521f
.. _Sublime Text:      https://github.com/benmatselby/sublime-phpcs
.. _Vim:               https://github.com/stephpy/vim-php-cs-fixer
.. _VS Code:           https://github.com/junstyle/vscode-php-cs-fixer
.. _contribute:        https://github.com/FriendsOfPHP/PHP-CS-Fixer/blob/master/CONTRIBUTING.md

EOF;

        $command = $this->getApplication()->get('fix');
        $help = $command->getHelp();
        $help = str_replace('%command.full_name%', 'php-cs-fixer.phar '.$command->getName(), $help);
        $help = str_replace('%command.name%', $command->getName(), $help);
        $help = Preg::replace('#</?(comment|info)>#', '``', $help);
        $help = Preg::replace('#`(``.+?``)`#', '$1', $help);
        $help = Preg::replace('#^(\s+)``(.+)``$#m', '$1$2', $help);
        $help = Preg::replace('#^ \* ``(.+)``(.*?\n)#m', "* **$1**$2\n", $help);
        $help = Preg::replace('#^   \\| #m', '  ', $help);
        $help = Preg::replace('#^   \\|#m', '', $help);
        $help = Preg::replace('#^(?=  \\*Risky rule: )#m', "\n", $help);
        $help = Preg::replace("#^(  Configuration options:\n)(  - )#m", "$1\n$2", $help);
        $help = Preg::replace("#^\n( +\\$ )#m", "\n.. code-block:: bash\n\n$1", $help);
        $help = Preg::replace("#^\n( +<\\?php)#m", "\n.. code-block:: php\n\n$1", $help);
        $help = Preg::replaceCallback(
            '#^\s*<\?(\w+).*?\?>#ms',
            static function ($matches) {
                $result = Preg::replace("#^\\.\\. code-block:: bash\n\n#m", '', $matches[0]);

                if ('php' !== $matches[1]) {
                    $result = Preg::replace("#<\\?{$matches[1]}\\s*#", '', $result);
                }

                return Preg::replace("#\n\n +\\?>#", '', $result);
            },
            $help
        );

        // Transform links
        // In the console output these have the form
        //      `description` (<url>http://...</url>)
        // Make to RST http://www.sphinx-doc.org/en/stable/rest.html#hyperlinks
        //      `description <http://...>`_

        $help = Preg::replaceCallback(
            '#`(.+)`\s?\(<url>(.+)<\/url>\)#',
            static function (array $matches) {
                return sprintf('`%s <%s>`_', str_replace('\\', '\\\\', $matches[1]), $matches[2]);
            },
            $help
        );

        $help = Preg::replace('#^                        #m', '  ', $help);
        $help = Preg::replace('#\*\* +\[#', '** [', $help);

        $downloadLatestUrl = sprintf('https://github.com/FriendsOfPHP/PHP-CS-Fixer/releases/download/v%s/php-cs-fixer.phar', HelpCommand::getLatestReleaseVersionFromChangeLog());
        $downloadUrl = 'https://cs.symfony.com/download/php-cs-fixer-v2.phar';

        $header = str_replace('%download.version_url%', $downloadLatestUrl, $header);
        $header = str_replace('%download.url%', $downloadUrl, $header);
        $footer = str_replace('%download.version_url%', $downloadLatestUrl, $footer);
        $footer = str_replace('%download.url%', $downloadUrl, $footer);

        $output->write($header."\n".$help."\n".$footer);

        return 0;
    }

    private function header($name, $underline)
    {
        return $name."\n".str_repeat($underline, \strlen($name));
    }
}
