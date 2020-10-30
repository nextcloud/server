<?php
namespace Psalm\Internal\Codebase;

use function array_merge;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Storage\FunctionStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Storage\PropertyStorage;
use Psalm\Type;
use function strtolower;

/**
 * @internal
 *
 * Handles information gleaned from class and function reflection
 */
class Reflection
{
    /**
     * @var ClassLikeStorageProvider
     */
    private $storage_provider;

    /**
     * @var Codebase
     */
    private $codebase;

    /**
     * @var array<string, FunctionStorage>
     */
    private static $builtin_functions = [];

    public function __construct(ClassLikeStorageProvider $storage_provider, Codebase $codebase)
    {
        $this->storage_provider = $storage_provider;
        $this->codebase = $codebase;
        self::$builtin_functions = [];
    }

    public function registerClass(\ReflectionClass $reflected_class): void
    {
        $class_name = $reflected_class->name;

        if ($class_name === 'LibXMLError') {
            $class_name = 'libXMLError';
        }

        $class_name_lower = strtolower($class_name);

        try {
            $this->storage_provider->get($class_name_lower);

            return;
        } catch (\Exception $e) {
            // this is fine
        }

        $reflected_parent_class = $reflected_class->getParentClass();

        $storage = $this->storage_provider->create($class_name);
        $storage->abstract = $reflected_class->isAbstract();
        $storage->is_interface = $reflected_class->isInterface();

        /** @psalm-suppress PropertyTypeCoercion */
        $storage->potential_declaring_method_ids['__construct'][$class_name_lower . '::__construct'] = true;

        if ($reflected_parent_class) {
            $parent_class_name = $reflected_parent_class->getName();
            $this->registerClass($reflected_parent_class);
            $parent_class_name_lc = strtolower($parent_class_name);

            $parent_storage = $this->storage_provider->get($parent_class_name_lc);

            $this->registerInheritedMethods($class_name_lower, $parent_class_name_lc);
            $this->registerInheritedProperties($class_name_lower, $parent_class_name_lc);

            $storage->class_implements = $parent_storage->class_implements;

            $storage->constants = $parent_storage->constants;

            $storage->parent_classes = array_merge(
                [$parent_class_name_lc => $parent_class_name],
                $parent_storage->parent_classes
            );

            $storage->used_traits = $parent_storage->used_traits;
        }

        $class_properties = $reflected_class->getProperties();

        $public_mapped_properties = PropertyMap::inPropertyMap($class_name)
            ? PropertyMap::getPropertyMap()[strtolower($class_name)]
            : [];

        foreach ($class_properties as $class_property) {
            $property_name = $class_property->getName();
            $storage->properties[$property_name] = new PropertyStorage();

            $storage->properties[$property_name]->type = Type::getMixed();

            if ($class_property->isStatic()) {
                $storage->properties[$property_name]->is_static = true;
            }

            if ($class_property->isPublic()) {
                $storage->properties[$property_name]->visibility = ClassLikeAnalyzer::VISIBILITY_PUBLIC;
            } elseif ($class_property->isProtected()) {
                $storage->properties[$property_name]->visibility = ClassLikeAnalyzer::VISIBILITY_PROTECTED;
            } elseif ($class_property->isPrivate()) {
                $storage->properties[$property_name]->visibility = ClassLikeAnalyzer::VISIBILITY_PRIVATE;
            }

            $property_id = (string)$class_property->class . '::$' . $property_name;

            $storage->declaring_property_ids[$property_name] = (string)$class_property->class;
            $storage->appearing_property_ids[$property_name] = $property_id;

            if (!$class_property->isPrivate()) {
                $storage->inheritable_property_ids[$property_name] = $property_id;
            }
        }

        // have to do this separately as there can be new properties here
        foreach ($public_mapped_properties as $property_name => $type_string) {
            $property_id = $class_name . '::$' . $property_name;

            if (!isset($storage->properties[$property_name])) {
                $storage->properties[$property_name] = new PropertyStorage();
                $storage->properties[$property_name]->visibility = ClassLikeAnalyzer::VISIBILITY_PUBLIC;

                $storage->declaring_property_ids[$property_name] = $class_name;
                $storage->appearing_property_ids[$property_name] = $property_id;
                $storage->inheritable_property_ids[$property_name] = $property_id;
            }

            $type = Type::parseString($type_string);

            if ($property_id === 'DateInterval::$days') {
                $type->ignore_falsable_issues = true;
            }

            $storage->properties[$property_name]->type = $type;
        }

        /** @var array<string, int|string|float|null|array> */
        $class_constants = $reflected_class->getConstants();

        foreach ($class_constants as $name => $value) {
            $storage->constants[$name] = new \Psalm\Storage\ClassConstantStorage(
                ClassLikeAnalyzer::getTypeFromValue($value),
                ClassLikeAnalyzer::VISIBILITY_PUBLIC,
                null
            );
        }

        if ($reflected_class->isInterface()) {
            $this->codebase->classlikes->addFullyQualifiedInterfaceName($class_name);
        } elseif ($reflected_class->isTrait()) {
            $this->codebase->classlikes->addFullyQualifiedTraitName($class_name);
        } else {
            $this->codebase->classlikes->addFullyQualifiedClassName($class_name);
        }

        $reflection_methods = $reflected_class->getMethods(
            (\ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_PROTECTED)
        );

        if ($class_name_lower === 'generator') {
            $storage->template_types = [
                'TKey' => ['Generator' => [Type::getMixed()]],
                'TValue' => ['Generator' => [Type::getMixed()]],
            ];
        }

        $interfaces = $reflected_class->getInterfaces();

        foreach ($interfaces as $interface) {
            $interface_name = $interface->getName();
            $this->registerClass($interface);

            if ($reflected_class->isInterface()) {
                $storage->parent_interfaces[strtolower($interface_name)] = $interface_name;
            } else {
                $storage->class_implements[strtolower($interface_name)] = $interface_name;
            }
        }

        foreach ($reflection_methods as $reflection_method) {
            $method_reflection_class = $reflection_method->getDeclaringClass();

            $this->registerClass($method_reflection_class);

            $this->extractReflectionMethodInfo($reflection_method);

            if ($reflection_method->class !== $class_name
                && ($class_name !== 'SoapFault' || $reflection_method->name !== '__construct')
            ) {
                $reflection_method_name = strtolower($reflection_method->name);
                $reflection_method_class = $reflection_method->class;

                $this->codebase->methods->setDeclaringMethodId(
                    $class_name,
                    $reflection_method_name,
                    $reflection_method_class,
                    $reflection_method_name
                );

                $this->codebase->methods->setAppearingMethodId(
                    $class_name,
                    $reflection_method_name,
                    $reflection_method_class,
                    $reflection_method_name
                );
            }
        }
    }

    public function extractReflectionMethodInfo(\ReflectionMethod $method): void
    {
        $method_name_lc = strtolower($method->getName());

        $fq_class_name = $method->class;

        $fq_class_name_lc = strtolower($fq_class_name);

        $class_storage = $this->storage_provider->get($fq_class_name_lc);

        if (isset($class_storage->methods[$method_name_lc])) {
            return;
        }

        $method_id = $method->class . '::' . $method_name_lc;

        $storage = $class_storage->methods[$method_name_lc] = new MethodStorage();

        $storage->cased_name = $method->name;
        $storage->defining_fqcln = $method->class;

        if ($method_name_lc === $fq_class_name_lc) {
            $this->codebase->methods->setDeclaringMethodId(
                $fq_class_name,
                '__construct',
                $fq_class_name,
                $method_name_lc
            );
            $this->codebase->methods->setAppearingMethodId(
                $fq_class_name,
                '__construct',
                $fq_class_name,
                $method_name_lc
            );
        }

        $declaring_class = $method->getDeclaringClass();

        $storage->is_static = $method->isStatic();
        $storage->abstract = $method->isAbstract();
        $storage->mutation_free = $storage->external_mutation_free
            = $method_name_lc === '__construct' && $fq_class_name_lc === 'datetimezone';

        $class_storage->declaring_method_ids[$method_name_lc] = new \Psalm\Internal\MethodIdentifier(
            $declaring_class->name,
            $method_name_lc
        );

        $class_storage->inheritable_method_ids[$method_name_lc]
            = $class_storage->declaring_method_ids[$method_name_lc];
        $class_storage->appearing_method_ids[$method_name_lc]
            = $class_storage->declaring_method_ids[$method_name_lc];
        $class_storage->overridden_method_ids[$method_name_lc] = [];

        $storage->visibility = $method->isPrivate()
            ? ClassLikeAnalyzer::VISIBILITY_PRIVATE
            : ($method->isProtected() ? ClassLikeAnalyzer::VISIBILITY_PROTECTED : ClassLikeAnalyzer::VISIBILITY_PUBLIC);

        $callables = InternalCallMapHandler::getCallablesFromCallMap($method_id);

        if ($callables && $callables[0]->params !== null && $callables[0]->return_type !== null) {
            $storage->params = [];

            foreach ($callables[0]->params as $param) {
                if ($param->type) {
                    $param->type->queueClassLikesForScanning($this->codebase);
                }
            }

            $storage->params = $callables[0]->params;

            $storage->return_type = $callables[0]->return_type;
            $storage->return_type->queueClassLikesForScanning($this->codebase);
        } else {
            $params = $method->getParameters();

            $storage->params = [];

            foreach ($params as $param) {
                $param_array = $this->getReflectionParamData($param);
                $storage->params[] = $param_array;
                $storage->param_lookup[$param->name] = true;
            }
        }

        $storage->required_param_count = 0;

        foreach ($storage->params as $i => $param) {
            if (!$param->is_optional && !$param->is_variadic) {
                $storage->required_param_count = $i + 1;
            }
        }
    }

    private function getReflectionParamData(\ReflectionParameter $param): FunctionLikeParameter
    {
        $param_type = self::getPsalmTypeFromReflectionType($param->getType());
        $param_name = (string)$param->getName();

        $is_optional = (bool)$param->isOptional();

        $parameter = new FunctionLikeParameter(
            $param_name,
            (bool)$param->isPassedByReference(),
            $param_type,
            null,
            null,
            $is_optional,
            $param_type->isNullable(),
            $param->isVariadic()
        );

        $parameter->signature_type = Type::getMixed();

        return $parameter;
    }

    /**
     * @param  callable-string $function_id
     *
     * @return false|null
     */
    public function registerFunction(string $function_id): ?bool
    {
        try {
            $reflection_function = new \ReflectionFunction($function_id);

            $callmap_callable = null;

            if (isset(self::$builtin_functions[$function_id])) {
                return null;
            }

            $storage = self::$builtin_functions[$function_id] = new FunctionStorage();

            if (InternalCallMapHandler::inCallMap($function_id)) {
                $callmap_callable = \Psalm\Internal\Codebase\InternalCallMapHandler::getCallableFromCallMapById(
                    $this->codebase,
                    $function_id,
                    [],
                    null
                );
            }

            if ($callmap_callable !== null
                && $callmap_callable->params !== null
                && $callmap_callable->return_type !== null
            ) {
                $storage->params = $callmap_callable->params;
                $storage->return_type = $callmap_callable->return_type;
            } else {
                $reflection_params = $reflection_function->getParameters();

                foreach ($reflection_params as $param) {
                    $param_obj = $this->getReflectionParamData($param);
                    $storage->params[] = $param_obj;
                }

                if ($reflection_return_type = $reflection_function->getReturnType()) {
                    $storage->return_type = self::getPsalmTypeFromReflectionType($reflection_return_type);
                }
            }

            $storage->pure = true;

            $storage->required_param_count = 0;

            foreach ($storage->params as $i => $param) {
                if (!$param->is_optional && !$param->is_variadic) {
                    $storage->required_param_count = $i + 1;
                }
            }

            $storage->cased_name = $reflection_function->getName();
        } catch (\ReflectionException $e) {
            return false;
        }

        return null;
    }

    public static function getPsalmTypeFromReflectionType(?\ReflectionType $reflection_type = null) : Type\Union
    {
        if (!$reflection_type) {
            return Type::getMixed();
        }

        $suffix = '';

        if ($reflection_type->allowsNull()) {
            $suffix = '|null';
        }

        return Type::parseString($reflection_type->getName() . $suffix);
    }

    private function registerInheritedMethods(
        string $fq_class_name,
        string $parent_class
    ): void {
        $parent_storage = $this->storage_provider->get($parent_class);
        $storage = $this->storage_provider->get($fq_class_name);

        // register where they appear (can never be in a trait)
        foreach ($parent_storage->appearing_method_ids as $method_name => $appearing_method_id) {
            $storage->appearing_method_ids[$method_name] = $appearing_method_id;
        }

        // register where they're declared
        foreach ($parent_storage->inheritable_method_ids as $method_name => $declaring_method_id) {
            $storage->declaring_method_ids[$method_name] = $declaring_method_id;
            $storage->inheritable_method_ids[$method_name] = $declaring_method_id;

            $storage->overridden_method_ids[$method_name][$declaring_method_id->fq_class_name]
                = $declaring_method_id;
        }
    }

    /**
     * @param lowercase-string $fq_class_name
     * @param lowercase-string $parent_class
     *
     */
    private function registerInheritedProperties(
        string $fq_class_name,
        string $parent_class
    ): void {
        $parent_storage = $this->storage_provider->get($parent_class);
        $storage = $this->storage_provider->get($fq_class_name);

        // register where they appear (can never be in a trait)
        foreach ($parent_storage->appearing_property_ids as $property_name => $appearing_property_id) {
            if (!$parent_storage->is_trait
                && isset($parent_storage->properties[$property_name])
                && $parent_storage->properties[$property_name]->visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE
            ) {
                continue;
            }

            $storage->appearing_property_ids[$property_name] = $appearing_property_id;
        }

        // register where they're declared
        foreach ($parent_storage->declaring_property_ids as $property_name => $declaring_property_class) {
            if (!$parent_storage->is_trait
                && isset($parent_storage->properties[$property_name])
                && $parent_storage->properties[$property_name]->visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE
            ) {
                continue;
            }

            $storage->declaring_property_ids[$property_name] = strtolower($declaring_property_class);
        }

        // register where they're declared
        foreach ($parent_storage->inheritable_property_ids as $property_name => $inheritable_property_id) {
            if (!$parent_storage->is_trait
                && isset($parent_storage->properties[$property_name])
                && $parent_storage->properties[$property_name]->visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE
            ) {
                continue;
            }

            $storage->inheritable_property_ids[$property_name] = $inheritable_property_id;
        }
    }

    public function hasFunction(string $function_id): bool
    {
        return isset(self::$builtin_functions[$function_id]);
    }

    public function getFunctionStorage(string $function_id): FunctionStorage
    {
        if (isset(self::$builtin_functions[$function_id])) {
            return self::$builtin_functions[$function_id];
        }

        throw new \UnexpectedValueException('Expecting to have a function for ' . $function_id);
    }

    public static function clearCache() : void
    {
        self::$builtin_functions = [];
    }
}
