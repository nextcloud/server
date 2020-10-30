<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Common\Annotations;

use Doctrine\Common\Annotations\Annotation\Attribute;
use ReflectionClass;
use Doctrine\Common\Annotations\Annotation\Enum;
use Doctrine\Common\Annotations\Annotation\Target;
use Doctrine\Common\Annotations\Annotation\Attributes;

/**
 * A parser for docblock annotations.
 *
 * It is strongly discouraged to change the default annotation parsing process.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author Jonathan Wage <jonwage@gmail.com>
 * @author Roman Borschel <roman@code-factory.org>
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
final class DocParser
{
    /**
     * An array of all valid tokens for a class name.
     *
     * @var array
     */
    private static $classIdentifiers = [
        DocLexer::T_IDENTIFIER,
        DocLexer::T_TRUE,
        DocLexer::T_FALSE,
        DocLexer::T_NULL
    ];

    /**
     * The lexer.
     *
     * @var \Doctrine\Common\Annotations\DocLexer
     */
    private $lexer;

    /**
     * Current target context.
     *
     * @var integer
     */
    private $target;

    /**
     * Doc parser used to collect annotation target.
     *
     * @var \Doctrine\Common\Annotations\DocParser
     */
    private static $metadataParser;

    /**
     * Flag to control if the current annotation is nested or not.
     *
     * @var boolean
     */
    private $isNestedAnnotation = false;

    /**
     * Hashmap containing all use-statements that are to be used when parsing
     * the given doc block.
     *
     * @var array
     */
    private $imports = [];

    /**
     * This hashmap is used internally to cache results of class_exists()
     * look-ups.
     *
     * @var array
     */
    private $classExists = [];

    /**
     * Whether annotations that have not been imported should be ignored.
     *
     * @var boolean
     */
    private $ignoreNotImportedAnnotations = false;

    /**
     * An array of default namespaces if operating in simple mode.
     *
     * @var string[]
     */
    private $namespaces = [];

    /**
     * A list with annotations that are not causing exceptions when not resolved to an annotation class.
     *
     * The names must be the raw names as used in the class, not the fully qualified
     * class names.
     *
     * @var bool[] indexed by annotation name
     */
    private $ignoredAnnotationNames = [];

    /**
     * A list with annotations in namespaced format
     * that are not causing exceptions when not resolved to an annotation class.
     *
     * @var bool[] indexed by namespace name
     */
    private $ignoredAnnotationNamespaces = [];

    /**
     * @var string
     */
    private $context = '';

    /**
     * Hash-map for caching annotation metadata.
     *
     * @var array
     */
    private static $annotationMetadata = [
        'Doctrine\Common\Annotations\Annotation\Target' => [
            'is_annotation'    => true,
            'has_constructor'  => true,
            'properties'       => [],
            'targets_literal'  => 'ANNOTATION_CLASS',
            'targets'          => Target::TARGET_CLASS,
            'default_property' => 'value',
            'attribute_types'  => [
                'value'  => [
                    'required'  => false,
                    'type'      =>'array',
                    'array_type'=>'string',
                    'value'     =>'array<string>'
                ]
             ],
        ],
        'Doctrine\Common\Annotations\Annotation\Attribute' => [
            'is_annotation'    => true,
            'has_constructor'  => false,
            'targets_literal'  => 'ANNOTATION_ANNOTATION',
            'targets'          => Target::TARGET_ANNOTATION,
            'default_property' => 'name',
            'properties'       => [
                'name'      => 'name',
                'type'      => 'type',
                'required'  => 'required'
            ],
            'attribute_types'  => [
                'value'  => [
                    'required'  => true,
                    'type'      =>'string',
                    'value'     =>'string'
                ],
                'type'  => [
                    'required'  =>true,
                    'type'      =>'string',
                    'value'     =>'string'
                ],
                'required'  => [
                    'required'  =>false,
                    'type'      =>'boolean',
                    'value'     =>'boolean'
                ]
             ],
        ],
        'Doctrine\Common\Annotations\Annotation\Attributes' => [
            'is_annotation'    => true,
            'has_constructor'  => false,
            'targets_literal'  => 'ANNOTATION_CLASS',
            'targets'          => Target::TARGET_CLASS,
            'default_property' => 'value',
            'properties'       => [
                'value' => 'value'
            ],
            'attribute_types'  => [
                'value' => [
                    'type'      =>'array',
                    'required'  =>true,
                    'array_type'=>'Doctrine\Common\Annotations\Annotation\Attribute',
                    'value'     =>'array<Doctrine\Common\Annotations\Annotation\Attribute>'
                ]
             ],
        ],
        'Doctrine\Common\Annotations\Annotation\Enum' => [
            'is_annotation'    => true,
            'has_constructor'  => true,
            'targets_literal'  => 'ANNOTATION_PROPERTY',
            'targets'          => Target::TARGET_PROPERTY,
            'default_property' => 'value',
            'properties'       => [
                'value' => 'value'
            ],
            'attribute_types'  => [
                'value' => [
                    'type'      => 'array',
                    'required'  => true,
                ],
                'literal' => [
                    'type'      => 'array',
                    'required'  => false,
                ],
             ],
        ],
    ];

    /**
     * Hash-map for handle types declaration.
     *
     * @var array
     */
    private static $typeMap = [
        'float'     => 'double',
        'bool'      => 'boolean',
        // allow uppercase Boolean in honor of George Boole
        'Boolean'   => 'boolean',
        'int'       => 'integer',
    ];

    /**
     * Constructs a new DocParser.
     */
    public function __construct()
    {
        $this->lexer = new DocLexer;
    }

    /**
     * Sets the annotation names that are ignored during the parsing process.
     *
     * The names are supposed to be the raw names as used in the class, not the
     * fully qualified class names.
     *
     * @param bool[] $names indexed by annotation name
     *
     * @return void
     */
    public function setIgnoredAnnotationNames(array $names)
    {
        $this->ignoredAnnotationNames = $names;
    }

    /**
     * Sets the annotation namespaces that are ignored during the parsing process.
     *
     * @param bool[] $ignoredAnnotationNamespaces indexed by annotation namespace name
     *
     * @return void
     */
    public function setIgnoredAnnotationNamespaces($ignoredAnnotationNamespaces)
    {
        $this->ignoredAnnotationNamespaces = $ignoredAnnotationNamespaces;
    }

    /**
     * Sets ignore on not-imported annotations.
     *
     * @param boolean $bool
     *
     * @return void
     */
    public function setIgnoreNotImportedAnnotations($bool)
    {
        $this->ignoreNotImportedAnnotations = (boolean) $bool;
    }

    /**
     * Sets the default namespaces.
     *
     * @param string $namespace
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    public function addNamespace($namespace)
    {
        if ($this->imports) {
            throw new \RuntimeException('You must either use addNamespace(), or setImports(), but not both.');
        }

        $this->namespaces[] = $namespace;
    }

    /**
     * Sets the imports.
     *
     * @param array $imports
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    public function setImports(array $imports)
    {
        if ($this->namespaces) {
            throw new \RuntimeException('You must either use addNamespace(), or setImports(), but not both.');
        }

        $this->imports = $imports;
    }

    /**
     * Sets current target context as bitmask.
     *
     * @param integer $target
     *
     * @return void
     */
    public function setTarget($target)
    {
        $this->target = $target;
    }

    /**
     * Parses the given docblock string for annotations.
     *
     * @param string $input   The docblock string to parse.
     * @param string $context The parsing context.
     *
     * @return array Array of annotations. If no annotations are found, an empty array is returned.
     */
    public function parse($input, $context = '')
    {
        $pos = $this->findInitialTokenPosition($input);
        if ($pos === null) {
            return [];
        }

        $this->context = $context;

        $this->lexer->setInput(trim(substr($input, $pos), '* /'));
        $this->lexer->moveNext();

        return $this->Annotations();
    }

    /**
     * Finds the first valid annotation
     *
     * @param string $input The docblock string to parse
     *
     * @return int|null
     */
    private function findInitialTokenPosition($input)
    {
        $pos = 0;

        // search for first valid annotation
        while (($pos = strpos($input, '@', $pos)) !== false) {
            $preceding = substr($input, $pos - 1, 1);

            // if the @ is preceded by a space, a tab or * it is valid
            if ($pos === 0 || $preceding === ' ' || $preceding === '*' || $preceding === "\t") {
                return $pos;
            }

            $pos++;
        }

        return null;
    }

    /**
     * Attempts to match the given token with the current lookahead token.
     * If they match, updates the lookahead token; otherwise raises a syntax error.
     *
     * @param integer $token Type of token.
     *
     * @return boolean True if tokens match; false otherwise.
     */
    private function match($token)
    {
        if ( ! $this->lexer->isNextToken($token) ) {
            $this->syntaxError($this->lexer->getLiteral($token));
        }

        return $this->lexer->moveNext();
    }

    /**
     * Attempts to match the current lookahead token with any of the given tokens.
     *
     * If any of them matches, this method updates the lookahead token; otherwise
     * a syntax error is raised.
     *
     * @param array $tokens
     *
     * @return boolean
     */
    private function matchAny(array $tokens)
    {
        if ( ! $this->lexer->isNextTokenAny($tokens)) {
            $this->syntaxError(implode(' or ', array_map([$this->lexer, 'getLiteral'], $tokens)));
        }

        return $this->lexer->moveNext();
    }

    /**
     * Generates a new syntax error.
     *
     * @param string     $expected Expected string.
     * @param array|null $token    Optional token.
     *
     * @return void
     *
     * @throws AnnotationException
     */
    private function syntaxError($expected, $token = null)
    {
        if ($token === null) {
            $token = $this->lexer->lookahead;
        }

        $message  = sprintf('Expected %s, got ', $expected);
        $message .= ($this->lexer->lookahead === null)
            ? 'end of string'
            : sprintf("'%s' at position %s", $token['value'], $token['position']);

        if (strlen($this->context)) {
            $message .= ' in ' . $this->context;
        }

        $message .= '.';

        throw AnnotationException::syntaxError($message);
    }

    /**
     * Attempts to check if a class exists or not. This never goes through the PHP autoloading mechanism
     * but uses the {@link AnnotationRegistry} to load classes.
     *
     * @param string $fqcn
     *
     * @return boolean
     */
    private function classExists($fqcn)
    {
        if (isset($this->classExists[$fqcn])) {
            return $this->classExists[$fqcn];
        }

        // first check if the class already exists, maybe loaded through another AnnotationReader
        if (class_exists($fqcn, false)) {
            return $this->classExists[$fqcn] = true;
        }

        // final check, does this class exist?
        return $this->classExists[$fqcn] = AnnotationRegistry::loadAnnotationClass($fqcn);
    }

    /**
     * Collects parsing metadata for a given annotation class
     *
     * @param string $name The annotation name
     *
     * @return void
     */
    private function collectAnnotationMetadata($name)
    {
        if (self::$metadataParser === null) {
            self::$metadataParser = new self();

            self::$metadataParser->setIgnoreNotImportedAnnotations(true);
            self::$metadataParser->setIgnoredAnnotationNames($this->ignoredAnnotationNames);
            self::$metadataParser->setImports([
                'enum'          => 'Doctrine\Common\Annotations\Annotation\Enum',
                'target'        => 'Doctrine\Common\Annotations\Annotation\Target',
                'attribute'     => 'Doctrine\Common\Annotations\Annotation\Attribute',
                'attributes'    => 'Doctrine\Common\Annotations\Annotation\Attributes'
            ]);

            // Make sure that annotations from metadata are loaded
            class_exists(Enum::class);
            class_exists(Target::class);
            class_exists(Attribute::class);
            class_exists(Attributes::class);
        }

        $class      = new \ReflectionClass($name);
        $docComment = $class->getDocComment();

        // Sets default values for annotation metadata
        $metadata = [
            'default_property' => null,
            'has_constructor'  => (null !== $constructor = $class->getConstructor()) && $constructor->getNumberOfParameters() > 0,
            'properties'       => [],
            'property_types'   => [],
            'attribute_types'  => [],
            'targets_literal'  => null,
            'targets'          => Target::TARGET_ALL,
            'is_annotation'    => false !== strpos($docComment, '@Annotation'),
        ];

        // verify that the class is really meant to be an annotation
        if ($metadata['is_annotation']) {
            self::$metadataParser->setTarget(Target::TARGET_CLASS);

            foreach (self::$metadataParser->parse($docComment, 'class @' . $name) as $annotation) {
                if ($annotation instanceof Target) {
                    $metadata['targets']         = $annotation->targets;
                    $metadata['targets_literal'] = $annotation->literal;

                    continue;
                }

                if ($annotation instanceof Attributes) {
                    foreach ($annotation->value as $attribute) {
                        $this->collectAttributeTypeMetadata($metadata, $attribute);
                    }
                }
            }

            // if not has a constructor will inject values into public properties
            if (false === $metadata['has_constructor']) {
                // collect all public properties
                foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
                    $metadata['properties'][$property->name] = $property->name;

                    if (false === ($propertyComment = $property->getDocComment())) {
                        continue;
                    }

                    $attribute = new Attribute();

                    $attribute->required = (false !== strpos($propertyComment, '@Required'));
                    $attribute->name     = $property->name;
                    $attribute->type     = (false !== strpos($propertyComment, '@var') && preg_match('/@var\s+([^\s]+)/',$propertyComment, $matches))
                        ? $matches[1]
                        : 'mixed';

                    $this->collectAttributeTypeMetadata($metadata, $attribute);

                    // checks if the property has @Enum
                    if (false !== strpos($propertyComment, '@Enum')) {
                        $context = 'property ' . $class->name . "::\$" . $property->name;

                        self::$metadataParser->setTarget(Target::TARGET_PROPERTY);

                        foreach (self::$metadataParser->parse($propertyComment, $context) as $annotation) {
                            if ( ! $annotation instanceof Enum) {
                                continue;
                            }

                            $metadata['enum'][$property->name]['value']   = $annotation->value;
                            $metadata['enum'][$property->name]['literal'] = ( ! empty($annotation->literal))
                                ? $annotation->literal
                                : $annotation->value;
                        }
                    }
                }

                // choose the first property as default property
                $metadata['default_property'] = reset($metadata['properties']);
            }
        }

        self::$annotationMetadata[$name] = $metadata;
    }

    /**
     * Collects parsing metadata for a given attribute.
     *
     * @param array     $metadata
     * @param Attribute $attribute
     *
     * @return void
     */
    private function collectAttributeTypeMetadata(&$metadata, Attribute $attribute)
    {
        // handle internal type declaration
        $type = self::$typeMap[$attribute->type] ?? $attribute->type;

        // handle the case if the property type is mixed
        if ('mixed' === $type) {
            return;
        }

        // Evaluate type
        switch (true) {
            // Checks if the property has array<type>
            case (false !== $pos = strpos($type, '<')):
                $arrayType  = substr($type, $pos + 1, -1);
                $type       = 'array';

                if (isset(self::$typeMap[$arrayType])) {
                    $arrayType = self::$typeMap[$arrayType];
                }

                $metadata['attribute_types'][$attribute->name]['array_type'] = $arrayType;
                break;

            // Checks if the property has type[]
            case (false !== $pos = strrpos($type, '[')):
                $arrayType  = substr($type, 0, $pos);
                $type       = 'array';

                if (isset(self::$typeMap[$arrayType])) {
                    $arrayType = self::$typeMap[$arrayType];
                }

                $metadata['attribute_types'][$attribute->name]['array_type'] = $arrayType;
                break;
        }

        $metadata['attribute_types'][$attribute->name]['type']     = $type;
        $metadata['attribute_types'][$attribute->name]['value']    = $attribute->type;
        $metadata['attribute_types'][$attribute->name]['required'] = $attribute->required;
    }

    /**
     * Annotations ::= Annotation {[ "*" ]* [Annotation]}*
     *
     * @return array
     */
    private function Annotations()
    {
        $annotations = [];

        while (null !== $this->lexer->lookahead) {
            if (DocLexer::T_AT !== $this->lexer->lookahead['type']) {
                $this->lexer->moveNext();
                continue;
            }

            // make sure the @ is preceded by non-catchable pattern
            if (null !== $this->lexer->token && $this->lexer->lookahead['position'] === $this->lexer->token['position'] + strlen($this->lexer->token['value'])) {
                $this->lexer->moveNext();
                continue;
            }

            // make sure the @ is followed by either a namespace separator, or
            // an identifier token
            if ((null === $peek = $this->lexer->glimpse())
                || (DocLexer::T_NAMESPACE_SEPARATOR !== $peek['type'] && !in_array($peek['type'], self::$classIdentifiers, true))
                || $peek['position'] !== $this->lexer->lookahead['position'] + 1) {
                $this->lexer->moveNext();
                continue;
            }

            $this->isNestedAnnotation = false;
            if (false !== $annot = $this->Annotation()) {
                $annotations[] = $annot;
            }
        }

        return $annotations;
    }

    /**
     * Annotation     ::= "@" AnnotationName MethodCall
     * AnnotationName ::= QualifiedName | SimpleName
     * QualifiedName  ::= NameSpacePart "\" {NameSpacePart "\"}* SimpleName
     * NameSpacePart  ::= identifier | null | false | true
     * SimpleName     ::= identifier | null | false | true
     *
     * @return mixed False if it is not a valid annotation.
     *
     * @throws AnnotationException
     */
    private function Annotation()
    {
        $this->match(DocLexer::T_AT);

        // check if we have an annotation
        $name = $this->Identifier();

        if ($this->lexer->isNextToken(DocLexer::T_MINUS)
            && $this->lexer->nextTokenIsAdjacent()
        ) {
            // Annotations with dashes, such as "@foo-" or "@foo-bar", are to be discarded
            return false;
        }

        // only process names which are not fully qualified, yet
        // fully qualified names must start with a \
        $originalName = $name;

        if ('\\' !== $name[0]) {
            $pos = strpos($name, '\\');
            $alias = (false === $pos)? $name : substr($name, 0, $pos);
            $found = false;
            $loweredAlias = strtolower($alias);

            if ($this->namespaces) {
                foreach ($this->namespaces as $namespace) {
                    if ($this->classExists($namespace.'\\'.$name)) {
                        $name = $namespace.'\\'.$name;
                        $found = true;
                        break;
                    }
                }
            } elseif (isset($this->imports[$loweredAlias])) {
                $namespace = ltrim($this->imports[$loweredAlias], '\\');
                $name = (false !== $pos)
                    ? $namespace . substr($name, $pos)
                    : $namespace;
                $found = $this->classExists($name);
            } elseif ( ! isset($this->ignoredAnnotationNames[$name])
                && isset($this->imports['__NAMESPACE__'])
                && $this->classExists($this->imports['__NAMESPACE__'] . '\\' . $name)
            ) {
                $name  = $this->imports['__NAMESPACE__'].'\\'.$name;
                $found = true;
            } elseif (! isset($this->ignoredAnnotationNames[$name]) && $this->classExists($name)) {
                $found = true;
            }

            if ( ! $found) {
                if ($this->isIgnoredAnnotation($name)) {
                    return false;
                }

                throw AnnotationException::semanticalError(sprintf('The annotation "@%s" in %s was never imported. Did you maybe forget to add a "use" statement for this annotation?', $name, $this->context));
            }
        }

        $name = ltrim($name,'\\');

        if ( ! $this->classExists($name)) {
            throw AnnotationException::semanticalError(sprintf('The annotation "@%s" in %s does not exist, or could not be auto-loaded.', $name, $this->context));
        }

        // at this point, $name contains the fully qualified class name of the
        // annotation, and it is also guaranteed that this class exists, and
        // that it is loaded


        // collects the metadata annotation only if there is not yet
        if ( ! isset(self::$annotationMetadata[$name])) {
            $this->collectAnnotationMetadata($name);
        }

        // verify that the class is really meant to be an annotation and not just any ordinary class
        if (self::$annotationMetadata[$name]['is_annotation'] === false) {
            if ($this->isIgnoredAnnotation($originalName) || $this->isIgnoredAnnotation($name)) {
                return false;
            }

            throw AnnotationException::semanticalError(sprintf('The class "%s" is not annotated with @Annotation. Are you sure this class can be used as annotation? If so, then you need to add @Annotation to the _class_ doc comment of "%s". If it is indeed no annotation, then you need to add @IgnoreAnnotation("%s") to the _class_ doc comment of %s.', $name, $name, $originalName, $this->context));
        }

        //if target is nested annotation
        $target = $this->isNestedAnnotation ? Target::TARGET_ANNOTATION : $this->target;

        // Next will be nested
        $this->isNestedAnnotation = true;

        //if annotation does not support current target
        if (0 === (self::$annotationMetadata[$name]['targets'] & $target) && $target) {
            throw AnnotationException::semanticalError(
                sprintf('Annotation @%s is not allowed to be declared on %s. You may only use this annotation on these code elements: %s.',
                     $originalName, $this->context, self::$annotationMetadata[$name]['targets_literal'])
            );
        }

        $values = $this->MethodCall();

        if (isset(self::$annotationMetadata[$name]['enum'])) {
            // checks all declared attributes
            foreach (self::$annotationMetadata[$name]['enum'] as $property => $enum) {
                // checks if the attribute is a valid enumerator
                if (isset($values[$property]) && ! in_array($values[$property], $enum['value'])) {
                    throw AnnotationException::enumeratorError($property, $name, $this->context, $enum['literal'], $values[$property]);
                }
            }
        }

        // checks all declared attributes
        foreach (self::$annotationMetadata[$name]['attribute_types'] as $property => $type) {
            if ($property === self::$annotationMetadata[$name]['default_property']
                && !isset($values[$property]) && isset($values['value'])) {
                $property = 'value';
            }

            // handle a not given attribute or null value
            if (!isset($values[$property])) {
                if ($type['required']) {
                    throw AnnotationException::requiredError($property, $originalName, $this->context, 'a(n) '.$type['value']);
                }

                continue;
            }

            if ($type['type'] === 'array') {
                // handle the case of a single value
                if ( ! is_array($values[$property])) {
                    $values[$property] = [$values[$property]];
                }

                // checks if the attribute has array type declaration, such as "array<string>"
                if (isset($type['array_type'])) {
                    foreach ($values[$property] as $item) {
                        if (gettype($item) !== $type['array_type'] && !$item instanceof $type['array_type']) {
                            throw AnnotationException::attributeTypeError($property, $originalName, $this->context, 'either a(n) '.$type['array_type'].', or an array of '.$type['array_type'].'s', $item);
                        }
                    }
                }
            } elseif (gettype($values[$property]) !== $type['type'] && !$values[$property] instanceof $type['type']) {
                throw AnnotationException::attributeTypeError($property, $originalName, $this->context, 'a(n) '.$type['value'], $values[$property]);
            }
        }

        // check if the annotation expects values via the constructor,
        // or directly injected into public properties
        if (self::$annotationMetadata[$name]['has_constructor'] === true) {
            return new $name($values);
        }

        $instance = new $name();

        foreach ($values as $property => $value) {
            if (!isset(self::$annotationMetadata[$name]['properties'][$property])) {
                if ('value' !== $property) {
                    throw AnnotationException::creationError(sprintf('The annotation @%s declared on %s does not have a property named "%s". Available properties: %s', $originalName, $this->context, $property, implode(', ', self::$annotationMetadata[$name]['properties'])));
                }

                // handle the case if the property has no annotations
                if ( ! $property = self::$annotationMetadata[$name]['default_property']) {
                    throw AnnotationException::creationError(sprintf('The annotation @%s declared on %s does not accept any values, but got %s.', $originalName, $this->context, json_encode($values)));
                }
            }

            $instance->{$property} = $value;
        }

        return $instance;
    }

    /**
     * MethodCall ::= ["(" [Values] ")"]
     *
     * @return array
     */
    private function MethodCall()
    {
        $values = [];

        if ( ! $this->lexer->isNextToken(DocLexer::T_OPEN_PARENTHESIS)) {
            return $values;
        }

        $this->match(DocLexer::T_OPEN_PARENTHESIS);

        if ( ! $this->lexer->isNextToken(DocLexer::T_CLOSE_PARENTHESIS)) {
            $values = $this->Values();
        }

        $this->match(DocLexer::T_CLOSE_PARENTHESIS);

        return $values;
    }

    /**
     * Values ::= Array | Value {"," Value}* [","]
     *
     * @return array
     */
    private function Values()
    {
        $values = [$this->Value()];

        while ($this->lexer->isNextToken(DocLexer::T_COMMA)) {
            $this->match(DocLexer::T_COMMA);

            if ($this->lexer->isNextToken(DocLexer::T_CLOSE_PARENTHESIS)) {
                break;
            }

            $token = $this->lexer->lookahead;
            $value = $this->Value();

            if ( ! is_object($value) && ! is_array($value)) {
                $this->syntaxError('Value', $token);
            }

            $values[] = $value;
        }

        foreach ($values as $k => $value) {
            if (is_object($value) && $value instanceof \stdClass) {
                $values[$value->name] = $value->value;
            } else if ( ! isset($values['value'])){
                $values['value'] = $value;
            } else {
                if ( ! is_array($values['value'])) {
                    $values['value'] = [$values['value']];
                }

                $values['value'][] = $value;
            }

            unset($values[$k]);
        }

        return $values;
    }

    /**
     * Constant ::= integer | string | float | boolean
     *
     * @return mixed
     *
     * @throws AnnotationException
     */
    private function Constant()
    {
        $identifier = $this->Identifier();

        if ( ! defined($identifier) && false !== strpos($identifier, '::') && '\\' !== $identifier[0]) {
            list($className, $const) = explode('::', $identifier);

            $pos = strpos($className, '\\');
            $alias = (false === $pos) ? $className : substr($className, 0, $pos);
            $found = false;
            $loweredAlias = strtolower($alias);

            switch (true) {
                case !empty ($this->namespaces):
                    foreach ($this->namespaces as $ns) {
                        if (class_exists($ns.'\\'.$className) || interface_exists($ns.'\\'.$className)) {
                             $className = $ns.'\\'.$className;
                             $found = true;
                             break;
                        }
                    }
                    break;

                case isset($this->imports[$loweredAlias]):
                    $found     = true;
                    $className = (false !== $pos)
                        ? $this->imports[$loweredAlias] . substr($className, $pos)
                        : $this->imports[$loweredAlias];
                    break;

                default:
                    if(isset($this->imports['__NAMESPACE__'])) {
                        $ns = $this->imports['__NAMESPACE__'];

                        if (class_exists($ns.'\\'.$className) || interface_exists($ns.'\\'.$className)) {
                            $className = $ns.'\\'.$className;
                            $found = true;
                        }
                    }
                    break;
            }

            if ($found) {
                 $identifier = $className . '::' . $const;
            }
        }

        /**
         * Checks if identifier ends with ::class and remove the leading backslash if it exists.
         */
        if ($this->identifierEndsWithClassConstant($identifier) && ! $this->identifierStartsWithBackslash($identifier)) {
            return substr($identifier, 0, $this->getClassConstantPositionInIdentifier($identifier));
        }
        if ($this->identifierEndsWithClassConstant($identifier) && $this->identifierStartsWithBackslash($identifier)) {
            return substr($identifier, 1, $this->getClassConstantPositionInIdentifier($identifier) - 1);
        }

        if (!defined($identifier)) {
            throw AnnotationException::semanticalErrorConstants($identifier, $this->context);
        }

        return constant($identifier);
    }

    private function identifierStartsWithBackslash(string $identifier) : bool
    {
        return '\\' === $identifier[0];
    }

    private function identifierEndsWithClassConstant(string $identifier) : bool
    {
        return $this->getClassConstantPositionInIdentifier($identifier) === strlen($identifier) - strlen('::class');
    }

    /**
     * @return int|false
     */
    private function getClassConstantPositionInIdentifier(string $identifier)
    {
        return stripos($identifier, '::class');
    }

    /**
     * Identifier ::= string
     *
     * @return string
     */
    private function Identifier()
    {
        // check if we have an annotation
        if ( ! $this->lexer->isNextTokenAny(self::$classIdentifiers)) {
            $this->syntaxError('namespace separator or identifier');
        }

        $this->lexer->moveNext();

        $className = $this->lexer->token['value'];

        while (
            null !== $this->lexer->lookahead &&
            $this->lexer->lookahead['position'] === ($this->lexer->token['position'] + strlen($this->lexer->token['value'])) &&
            $this->lexer->isNextToken(DocLexer::T_NAMESPACE_SEPARATOR)
        ) {
            $this->match(DocLexer::T_NAMESPACE_SEPARATOR);
            $this->matchAny(self::$classIdentifiers);

            $className .= '\\' . $this->lexer->token['value'];
        }

        return $className;
    }

    /**
     * Value ::= PlainValue | FieldAssignment
     *
     * @return mixed
     */
    private function Value()
    {
        $peek = $this->lexer->glimpse();

        if (DocLexer::T_EQUALS === $peek['type']) {
            return $this->FieldAssignment();
        }

        return $this->PlainValue();
    }

    /**
     * PlainValue ::= integer | string | float | boolean | Array | Annotation
     *
     * @return mixed
     */
    private function PlainValue()
    {
        if ($this->lexer->isNextToken(DocLexer::T_OPEN_CURLY_BRACES)) {
            return $this->Arrayx();
        }

        if ($this->lexer->isNextToken(DocLexer::T_AT)) {
            return $this->Annotation();
        }

        if ($this->lexer->isNextToken(DocLexer::T_IDENTIFIER)) {
            return $this->Constant();
        }

        switch ($this->lexer->lookahead['type']) {
            case DocLexer::T_STRING:
                $this->match(DocLexer::T_STRING);
                return $this->lexer->token['value'];

            case DocLexer::T_INTEGER:
                $this->match(DocLexer::T_INTEGER);
                return (int)$this->lexer->token['value'];

            case DocLexer::T_FLOAT:
                $this->match(DocLexer::T_FLOAT);
                return (float)$this->lexer->token['value'];

            case DocLexer::T_TRUE:
                $this->match(DocLexer::T_TRUE);
                return true;

            case DocLexer::T_FALSE:
                $this->match(DocLexer::T_FALSE);
                return false;

            case DocLexer::T_NULL:
                $this->match(DocLexer::T_NULL);
                return null;

            default:
                $this->syntaxError('PlainValue');
        }
    }

    /**
     * FieldAssignment ::= FieldName "=" PlainValue
     * FieldName ::= identifier
     *
     * @return \stdClass
     */
    private function FieldAssignment()
    {
        $this->match(DocLexer::T_IDENTIFIER);
        $fieldName = $this->lexer->token['value'];

        $this->match(DocLexer::T_EQUALS);

        $item = new \stdClass();
        $item->name  = $fieldName;
        $item->value = $this->PlainValue();

        return $item;
    }

    /**
     * Array ::= "{" ArrayEntry {"," ArrayEntry}* [","] "}"
     *
     * @return array
     */
    private function Arrayx()
    {
        $array = $values = [];

        $this->match(DocLexer::T_OPEN_CURLY_BRACES);

        // If the array is empty, stop parsing and return.
        if ($this->lexer->isNextToken(DocLexer::T_CLOSE_CURLY_BRACES)) {
            $this->match(DocLexer::T_CLOSE_CURLY_BRACES);

            return $array;
        }

        $values[] = $this->ArrayEntry();

        while ($this->lexer->isNextToken(DocLexer::T_COMMA)) {
            $this->match(DocLexer::T_COMMA);

            // optional trailing comma
            if ($this->lexer->isNextToken(DocLexer::T_CLOSE_CURLY_BRACES)) {
                break;
            }

            $values[] = $this->ArrayEntry();
        }

        $this->match(DocLexer::T_CLOSE_CURLY_BRACES);

        foreach ($values as $value) {
            list ($key, $val) = $value;

            if ($key !== null) {
                $array[$key] = $val;
            } else {
                $array[] = $val;
            }
        }

        return $array;
    }

    /**
     * ArrayEntry ::= Value | KeyValuePair
     * KeyValuePair ::= Key ("=" | ":") PlainValue | Constant
     * Key ::= string | integer | Constant
     *
     * @return array
     */
    private function ArrayEntry()
    {
        $peek = $this->lexer->glimpse();

        if (DocLexer::T_EQUALS === $peek['type']
                || DocLexer::T_COLON === $peek['type']) {

            if ($this->lexer->isNextToken(DocLexer::T_IDENTIFIER)) {
                $key = $this->Constant();
            } else {
                $this->matchAny([DocLexer::T_INTEGER, DocLexer::T_STRING]);
                $key = $this->lexer->token['value'];
            }

            $this->matchAny([DocLexer::T_EQUALS, DocLexer::T_COLON]);

            return [$key, $this->PlainValue()];
        }

        return [null, $this->Value()];
    }

    /**
     * Checks whether the given $name matches any ignored annotation name or namespace
     *
     * @param string $name
     *
     * @return bool
     */
    private function isIgnoredAnnotation($name)
    {
        if ($this->ignoreNotImportedAnnotations || isset($this->ignoredAnnotationNames[$name])) {
            return true;
        }

        foreach (array_keys($this->ignoredAnnotationNamespaces) as $ignoredAnnotationNamespace) {
            $ignoredAnnotationNamespace = rtrim($ignoredAnnotationNamespace, '\\') . '\\';

            if (0 === stripos(rtrim($name, '\\') . '\\', $ignoredAnnotationNamespace)) {
                return true;
            }
        }

        return false;
    }
}
