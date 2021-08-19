<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Routing\Matcher\Dumper;

@trigger_error(sprintf('The "%s" class is deprecated since Symfony 4.3, use "CompiledUrlMatcherDumper" instead.', PhpMatcherDumper::class), \E_USER_DEPRECATED);

/**
 * PhpMatcherDumper creates a PHP class able to match URLs for a given set of routes.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Tobias Schultze <http://tobion.de>
 * @author Arnaud Le Blanc <arnaud.lb@gmail.com>
 * @author Nicolas Grekas <p@tchwork.com>
 *
 * @deprecated since Symfony 4.3, use CompiledUrlMatcherDumper instead.
 */
class PhpMatcherDumper extends CompiledUrlMatcherDumper
{
    /**
     * Dumps a set of routes to a PHP class.
     *
     * Available options:
     *
     *  * class:      The class name
     *  * base_class: The base class name
     *
     * @param array $options An array of options
     *
     * @return string A PHP class representing the matcher class
     */
    public function dump(array $options = [])
    {
        $options = array_replace([
            'class' => 'ProjectUrlMatcher',
            'base_class' => 'Symfony\\Component\\Routing\\Matcher\\UrlMatcher',
        ], $options);

        $code = parent::dump();
        $code = preg_replace('#\n    ([^ ].*?) // \$(\w++)$#m', "\n    \$this->$2 = $1", $code);
        $code = str_replace(",\n    $", ";\n    $", $code);
        $code = substr($code, strpos($code, '$this') - 4, -5).";\n";
        $code = preg_replace('/^    \$this->\w++ = (?:null|false|\[\n    \]);\n/m', '', $code);
        $code = str_replace("\n    ", "\n        ", "\n".$code);

        return <<<EOF
<?php

use Symfony\Component\Routing\Matcher\Dumper\CompiledUrlMatcherTrait;
use Symfony\Component\Routing\RequestContext;

/**
 * This class has been auto-generated
 * by the Symfony Routing Component.
 */
class {$options['class']} extends {$options['base_class']}
{
    use CompiledUrlMatcherTrait;

    public function __construct(RequestContext \$context)
    {
        \$this->context = \$context;{$code}    }
}

EOF;
    }
}
