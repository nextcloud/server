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

@trigger_error(sprintf('The "%s" class is deprecated since Symfony 4.3, use "CompiledUrlGeneratorDumper" instead.', PhpGeneratorDumper::class), \E_USER_DEPRECATED);

use Symfony\Component\Routing\Matcher\Dumper\CompiledUrlMatcherDumper;

/**
 * PhpGeneratorDumper creates a PHP class able to generate URLs for a given set of routes.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Tobias Schultze <http://tobion.de>
 *
 * @deprecated since Symfony 4.3, use CompiledUrlGeneratorDumper instead.
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
     */
    public function dump(array $options = [])
    {
        $options = array_merge([
            'class' => 'ProjectUrlGenerator',
            'base_class' => 'Symfony\\Component\\Routing\\Generator\\UrlGenerator',
        ], $options);

        return <<<EOF
<?php

use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Psr\Log\LoggerInterface;

/**
 * This class has been auto-generated
 * by the Symfony Routing Component.
 */
class {$options['class']} extends {$options['base_class']}
{
    private static \$declaredRoutes;
    private \$defaultLocale;

    public function __construct(RequestContext \$context, LoggerInterface \$logger = null, string \$defaultLocale = null)
    {
        \$this->context = \$context;
        \$this->logger = \$logger;
        \$this->defaultLocale = \$defaultLocale;
        if (null === self::\$declaredRoutes) {
            self::\$declaredRoutes = {$this->generateDeclaredRoutes()};
        }
    }

{$this->generateGenerateMethod()}
}

EOF;
    }

    /**
     * Generates PHP code representing an array of defined routes
     * together with the routes properties (e.g. requirements).
     */
    private function generateDeclaredRoutes(): string
    {
        $routes = "[\n";
        foreach ($this->getRoutes()->all() as $name => $route) {
            $compiledRoute = $route->compile();

            $properties = [];
            $properties[] = $compiledRoute->getVariables();
            $properties[] = $route->getDefaults();
            $properties[] = $route->getRequirements();
            $properties[] = $compiledRoute->getTokens();
            $properties[] = $compiledRoute->getHostTokens();
            $properties[] = $route->getSchemes();

            $routes .= sprintf("        '%s' => %s,\n", $name, CompiledUrlMatcherDumper::export($properties));
        }
        $routes .= '    ]';

        return $routes;
    }

    /**
     * Generates PHP code representing the `generate` method that implements the UrlGeneratorInterface.
     */
    private function generateGenerateMethod(): string
    {
        return <<<'EOF'
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        $locale = $parameters['_locale']
            ?? $this->context->getParameter('_locale')
            ?: $this->defaultLocale;

        if (null !== $locale && null !== $name) {
            do {
                if ((self::$declaredRoutes[$name.'.'.$locale][1]['_canonical_route'] ?? null) === $name) {
                    $name .= '.'.$locale;
                    break;
                }
            } while (false !== $locale = strstr($locale, '_', true));
        }

        if (!isset(self::$declaredRoutes[$name])) {
            throw new RouteNotFoundException(sprintf('Unable to generate a URL for the named route "%s" as such route does not exist.', $name));
        }

        list($variables, $defaults, $requirements, $tokens, $hostTokens, $requiredSchemes) = self::$declaredRoutes[$name];

        if (isset($defaults['_canonical_route']) && isset($defaults['_locale'])) {
            if (!\in_array('_locale', $variables, true)) {
                unset($parameters['_locale']);
            } elseif (!isset($parameters['_locale'])) {
                $parameters['_locale'] = $defaults['_locale'];
            }
        }

        return $this->doGenerate($variables, $defaults, $requirements, $tokens, $parameters, $name, $referenceType, $hostTokens, $requiredSchemes);
    }
EOF;
    }
}
