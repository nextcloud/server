<?php

declare(strict_types=1);

/**
 * This file is part of phpDocumentor.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link http://phpdoc.org
 */

namespace phpDocumentor\Reflection\DocBlock;

use InvalidArgumentException;
use phpDocumentor\Reflection\DocBlock\Tags\Author;
use phpDocumentor\Reflection\DocBlock\Tags\Covers;
use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;
use phpDocumentor\Reflection\DocBlock\Tags\Generic;
use phpDocumentor\Reflection\DocBlock\Tags\InvalidTag;
use phpDocumentor\Reflection\DocBlock\Tags\Link as LinkTag;
use phpDocumentor\Reflection\DocBlock\Tags\Method;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlock\Tags\Property;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyRead;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyWrite;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use phpDocumentor\Reflection\DocBlock\Tags\See as SeeTag;
use phpDocumentor\Reflection\DocBlock\Tags\Since;
use phpDocumentor\Reflection\DocBlock\Tags\Source;
use phpDocumentor\Reflection\DocBlock\Tags\Throws;
use phpDocumentor\Reflection\DocBlock\Tags\Uses;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\DocBlock\Tags\Version;
use phpDocumentor\Reflection\FqsenResolver;
use phpDocumentor\Reflection\Types\Context as TypeContext;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use Webmozart\Assert\Assert;
use function array_merge;
use function array_slice;
use function call_user_func_array;
use function count;
use function get_class;
use function preg_match;
use function strpos;
use function trim;

/**
 * Creates a Tag object given the contents of a tag.
 *
 * This Factory is capable of determining the appropriate class for a tag and instantiate it using its `create`
 * factory method. The `create` factory method of a Tag can have a variable number of arguments; this way you can
 * pass the dependencies that you need to construct a tag object.
 *
 * > Important: each parameter in addition to the body variable for the `create` method must default to null, otherwise
 * > it violates the constraint with the interface; it is recommended to use the {@see Assert::notNull()} method to
 * > verify that a dependency is actually passed.
 *
 * This Factory also features a Service Locator component that is used to pass the right dependencies to the
 * `create` method of a tag; each dependency should be registered as a service or as a parameter.
 *
 * When you want to use a Tag of your own with custom handling you need to call the `registerTagHandler` method, pass
 * the name of the tag and a Fully Qualified Class Name pointing to a class that implements the Tag interface.
 */
final class StandardTagFactory implements TagFactory
{
    /** PCRE regular expression matching a tag name. */
    public const REGEX_TAGNAME = '[\w\-\_\\\\:]+';

    /**
     * @var array<class-string<Tag>> An array with a tag as a key, and an
     *                               FQCN to a class that handles it as an array value.
     */
    private $tagHandlerMappings = [
        'author' => Author::class,
        'covers' => Covers::class,
        'deprecated' => Deprecated::class,
        // 'example'        => '\phpDocumentor\Reflection\DocBlock\Tags\Example',
        'link' => LinkTag::class,
        'method' => Method::class,
        'param' => Param::class,
        'property-read' => PropertyRead::class,
        'property' => Property::class,
        'property-write' => PropertyWrite::class,
        'return' => Return_::class,
        'see' => SeeTag::class,
        'since' => Since::class,
        'source' => Source::class,
        'throw' => Throws::class,
        'throws' => Throws::class,
        'uses' => Uses::class,
        'var' => Var_::class,
        'version' => Version::class,
    ];

    /**
     * @var array<class-string<Tag>> An array with a anotation s a key, and an
     *      FQCN to a class that handles it as an array value.
     */
    private $annotationMappings = [];

    /**
     * @var ReflectionParameter[][] a lazy-loading cache containing parameters
     *      for each tagHandler that has been used.
     */
    private $tagHandlerParameterCache = [];

    /** @var FqsenResolver */
    private $fqsenResolver;

    /**
     * @var mixed[] an array representing a simple Service Locator where we can store parameters and
     *     services that can be inserted into the Factory Methods of Tag Handlers.
     */
    private $serviceLocator = [];

    /**
     * Initialize this tag factory with the means to resolve an FQSEN and optionally a list of tag handlers.
     *
     * If no tag handlers are provided than the default list in the {@see self::$tagHandlerMappings} property
     * is used.
     *
     * @see self::registerTagHandler() to add a new tag handler to the existing default list.
     *
     * @param array<class-string<Tag>> $tagHandlers
     */
    public function __construct(FqsenResolver $fqsenResolver, ?array $tagHandlers = null)
    {
        $this->fqsenResolver = $fqsenResolver;
        if ($tagHandlers !== null) {
            $this->tagHandlerMappings = $tagHandlers;
        }

        $this->addService($fqsenResolver, FqsenResolver::class);
    }

    public function create(string $tagLine, ?TypeContext $context = null) : Tag
    {
        if (!$context) {
            $context = new TypeContext('');
        }

        [$tagName, $tagBody] = $this->extractTagParts($tagLine);

        return $this->createTag(trim($tagBody), $tagName, $context);
    }

    /**
     * @param mixed $value
     */
    public function addParameter(string $name, $value) : void
    {
        $this->serviceLocator[$name] = $value;
    }

    public function addService(object $service, ?string $alias = null) : void
    {
        $this->serviceLocator[$alias ?: get_class($service)] = $service;
    }

    public function registerTagHandler(string $tagName, string $handler) : void
    {
        Assert::stringNotEmpty($tagName);
        Assert::classExists($handler);
        Assert::implementsInterface($handler, Tag::class);

        if (strpos($tagName, '\\') && $tagName[0] !== '\\') {
            throw new InvalidArgumentException(
                'A namespaced tag must have a leading backslash as it must be fully qualified'
            );
        }

        $this->tagHandlerMappings[$tagName] = $handler;
    }

    /**
     * Extracts all components for a tag.
     *
     * @return string[]
     */
    private function extractTagParts(string $tagLine) : array
    {
        $matches = [];
        if (!preg_match('/^@(' . self::REGEX_TAGNAME . ')((?:[\s\(\{])\s*([^\s].*)|$)/us', $tagLine, $matches)) {
            throw new InvalidArgumentException(
                'The tag "' . $tagLine . '" does not seem to be wellformed, please check it for errors'
            );
        }

        if (count($matches) < 3) {
            $matches[] = '';
        }

        return array_slice($matches, 1);
    }

    /**
     * Creates a new tag object with the given name and body or returns null if the tag name was recognized but the
     * body was invalid.
     */
    private function createTag(string $body, string $name, TypeContext $context) : Tag
    {
        $handlerClassName = $this->findHandlerClassName($name, $context);
        $arguments        = $this->getArgumentsForParametersFromWiring(
            $this->fetchParametersForHandlerFactoryMethod($handlerClassName),
            $this->getServiceLocatorWithDynamicParameters($context, $name, $body)
        );

        try {
            $callable = [$handlerClassName, 'create'];
            Assert::isCallable($callable);
            /** @phpstan-var callable(string): ?Tag $callable */
            $tag = call_user_func_array($callable, $arguments);

            return $tag ?? InvalidTag::create($body, $name);
        } catch (InvalidArgumentException $e) {
            return InvalidTag::create($body, $name)->withError($e);
        }
    }

    /**
     * Determines the Fully Qualified Class Name of the Factory or Tag (containing a Factory Method `create`).
     *
     * @return class-string<Tag>
     */
    private function findHandlerClassName(string $tagName, TypeContext $context) : string
    {
        $handlerClassName = Generic::class;
        if (isset($this->tagHandlerMappings[$tagName])) {
            $handlerClassName = $this->tagHandlerMappings[$tagName];
        } elseif ($this->isAnnotation($tagName)) {
            // TODO: Annotation support is planned for a later stage and as such is disabled for now
            $tagName = (string) $this->fqsenResolver->resolve($tagName, $context);
            if (isset($this->annotationMappings[$tagName])) {
                $handlerClassName = $this->annotationMappings[$tagName];
            }
        }

        return $handlerClassName;
    }

    /**
     * Retrieves the arguments that need to be passed to the Factory Method with the given Parameters.
     *
     * @param ReflectionParameter[] $parameters
     * @param mixed[]               $locator
     *
     * @return mixed[] A series of values that can be passed to the Factory Method of the tag whose parameters
     *     is provided with this method.
     */
    private function getArgumentsForParametersFromWiring(array $parameters, array $locator) : array
    {
        $arguments = [];
        foreach ($parameters as $parameter) {
            $type     = $parameter->getType();
            $typeHint = null;
            if ($type instanceof ReflectionNamedType) {
                $typeHint = $type->getName();
                if ($typeHint === 'self') {
                    $declaringClass = $parameter->getDeclaringClass();
                    if ($declaringClass !== null) {
                        $typeHint = $declaringClass->getName();
                    }
                }
            }

            if (isset($locator[$typeHint])) {
                $arguments[] = $locator[$typeHint];
                continue;
            }

            $parameterName = $parameter->getName();
            if (isset($locator[$parameterName])) {
                $arguments[] = $locator[$parameterName];
                continue;
            }

            $arguments[] = null;
        }

        return $arguments;
    }

    /**
     * Retrieves a series of ReflectionParameter objects for the static 'create' method of the given
     * tag handler class name.
     *
     * @param class-string $handlerClassName
     *
     * @return ReflectionParameter[]
     */
    private function fetchParametersForHandlerFactoryMethod(string $handlerClassName) : array
    {
        if (!isset($this->tagHandlerParameterCache[$handlerClassName])) {
            $methodReflection                                  = new ReflectionMethod($handlerClassName, 'create');
            $this->tagHandlerParameterCache[$handlerClassName] = $methodReflection->getParameters();
        }

        return $this->tagHandlerParameterCache[$handlerClassName];
    }

    /**
     * Returns a copy of this class' Service Locator with added dynamic parameters,
     * such as the tag's name, body and Context.
     *
     * @param TypeContext $context The Context (namespace and aliasses) that may be
     *  passed and is used to resolve FQSENs.
     * @param string      $tagName The name of the tag that may be
     *  passed onto the factory method of the Tag class.
     * @param string      $tagBody The body of the tag that may be
     *  passed onto the factory method of the Tag class.
     *
     * @return mixed[]
     */
    private function getServiceLocatorWithDynamicParameters(
        TypeContext $context,
        string $tagName,
        string $tagBody
    ) : array {
        return array_merge(
            $this->serviceLocator,
            [
                'name' => $tagName,
                'body' => $tagBody,
                TypeContext::class => $context,
            ]
        );
    }

    /**
     * Returns whether the given tag belongs to an annotation.
     *
     * @todo this method should be populated once we implement Annotation notation support.
     */
    private function isAnnotation(string $tagContent) : bool
    {
        // 1. Contains a namespace separator
        // 2. Contains parenthesis
        // 3. Is present in a list of known annotations (make the algorithm smart by first checking is the last part
        //    of the annotation class name matches the found tag name

        return false;
    }
}
