<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Routing\Generator\Dumper;

use Symfony\Component\Routing\Route;

/**
 * PhpGeneratorDumper creates a PHP class able to generate URLs for a given set of routes.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @api
 */
class PhpGeneratorDumper extends GeneratorDumper
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
     * @return string A PHP class representing the generator class
     *
     * @api
     */
    public function dump(array $options = array())
    {
        $options = array_merge(array(
            'class'      => 'ProjectUrlGenerator',
            'base_class' => 'Symfony\\Component\\Routing\\Generator\\UrlGenerator',
        ), $options);

        return
            $this->startClass($options['class'], $options['base_class']).
            $this->addConstructor().
            $this->addGenerator().
            $this->endClass()
        ;
    }

    private function addGenerator()
    {
        $methods = array();
        foreach ($this->getRoutes()->all() as $name => $route) {
            $compiledRoute = $route->compile();

            $variables = str_replace("\n", '', var_export($compiledRoute->getVariables(), true));
            $defaults = str_replace("\n", '', var_export($compiledRoute->getDefaults(), true));
            $requirements = str_replace("\n", '', var_export($compiledRoute->getRequirements(), true));
            $tokens = str_replace("\n", '', var_export($compiledRoute->getTokens(), true));

            $escapedName = str_replace('.', '__', $name);

            $methods[] = <<<EOF
    private function get{$escapedName}RouteInfo()
    {
        return array($variables, $defaults, $requirements, $tokens);
    }

EOF
            ;
        }

        $methods = implode("\n", $methods);

        return <<<EOF

    public function generate(\$name, \$parameters = array(), \$absolute = false)
    {
        if (!isset(self::\$declaredRouteNames[\$name])) {
            throw new RouteNotFoundException(sprintf('Route "%s" does not exist.', \$name));
        }

        \$escapedName = str_replace('.', '__', \$name);

        list(\$variables, \$defaults, \$requirements, \$tokens) = \$this->{'get'.\$escapedName.'RouteInfo'}();

        return \$this->doGenerate(\$variables, \$defaults, \$requirements, \$tokens, \$parameters, \$name, \$absolute);
    }

$methods
EOF;
    }

    private function startClass($class, $baseClass)
    {
        $routes = array();
        foreach ($this->getRoutes()->all() as $name => $route) {
            $routes[] = "       '$name' => true,";
        }
        $routes  = implode("\n", $routes);

        return <<<EOF
<?php

use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Exception\RouteNotFoundException;


/**
 * $class
 *
 * This class has been auto-generated
 * by the Symfony Routing Component.
 */
class $class extends $baseClass
{
    static private \$declaredRouteNames = array(
$routes
    );


EOF;
    }

    private function addConstructor()
    {
        return <<<EOF
    /**
     * Constructor.
     */
    public function __construct(RequestContext \$context)
    {
        \$this->context = \$context;
    }

EOF;
    }

    private function endClass()
    {
        return <<<EOF
}

EOF;
    }
}
