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
use phpDocumentor\Reflection\Types\Context as TypeContext;

interface TagFactory
{
    /**
     * Adds a parameter to the service locator that can be injected in a tag's factory method.
     *
     * When calling a tag's "create" method we always check the signature for dependencies to inject. One way is to
     * typehint a parameter in the signature so that we can use that interface or class name to inject a dependency
     * (see {@see addService()} for more information on that).
     *
     * Another way is to check the name of the argument against the names in the Service Locator. With this method
     * you can add a variable that will be inserted when a tag's create method is not typehinted and has a matching
     * name.
     *
     * Be aware that there are two reserved names:
     *
     * - name, representing the name of the tag.
     * - body, representing the complete body of the tag.
     *
     * These parameters are injected at the last moment and will override any existing parameter with those names.
     *
     * @param mixed $value
     */
    public function addParameter(string $name, $value) : void;

    /**
     * Factory method responsible for instantiating the correct sub type.
     *
     * @param string $tagLine The text for this tag, including description.
     *
     * @return Tag A new tag object.
     *
     * @throws InvalidArgumentException If an invalid tag line was presented.
     */
    public function create(string $tagLine, ?TypeContext $context = null) : Tag;

    /**
     * Registers a service with the Service Locator using the FQCN of the class or the alias, if provided.
     *
     * When calling a tag's "create" method we always check the signature for dependencies to inject. If a parameter
     * has a typehint then the ServiceLocator is queried to see if a Service is registered for that typehint.
     *
     * Because interfaces are regularly used as type-hints this method provides an alias parameter; if the FQCN of the
     * interface is passed as alias then every time that interface is requested the provided service will be returned.
     */
    public function addService(object $service) : void;

    /**
     * Registers a handler for tags.
     *
     * If you want to use your own tags then you can use this method to instruct the TagFactory
     * to register the name of a tag with the FQCN of a 'Tag Handler'. The Tag handler should implement
     * the {@see Tag} interface (and thus the create method).
     *
     * @param string                    $tagName Name of tag to register a handler for. When registering a namespaced
     *                                   tag, the full name, along with a prefixing slash MUST be provided.
     * @param class-string<Tag>         $handler FQCN of handler.
     *
     * @throws InvalidArgumentException If the tag name is not a string.
     * @throws InvalidArgumentException If the tag name is namespaced (contains backslashes) but
     *                                   does not start with a backslash.
     * @throws InvalidArgumentException If the handler is not a string.
     * @throws InvalidArgumentException If the handler is not an existing class.
     * @throws InvalidArgumentException If the handler does not implement the {@see Tag} interface.
     */
    public function registerTagHandler(string $tagName, string $handler) : void;
}
