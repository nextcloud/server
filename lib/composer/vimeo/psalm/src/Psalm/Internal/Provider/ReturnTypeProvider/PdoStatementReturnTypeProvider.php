<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Type;

class PdoStatementReturnTypeProvider implements \Psalm\Plugin\Hook\MethodReturnTypeProviderInterface
{
    public static function getClassLikeNames() : array
    {
        return ['PDOStatement'];
    }

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     */
    public static function getMethodReturnType(
        StatementsSource $source,
        string $fq_classlike_name,
        string $method_name_lowercase,
        array $call_args,
        Context $context,
        CodeLocation $code_location,
        ?array $template_type_parameters = null,
        ?string $called_fq_classlike_name = null,
        ?string $called_method_name_lowercase = null
    ): ?Type\Union {
        if ($method_name_lowercase === 'fetch'
            && \class_exists('PDO')
            && isset($call_args[0])
            && ($first_arg_type = $source->getNodeTypeProvider()->getType($call_args[0]->value))
            && $first_arg_type->isSingleIntLiteral()
        ) {
            $fetch_mode = $first_arg_type->getSingleIntLiteral()->value;

            switch ($fetch_mode) {
                case \PDO::FETCH_ASSOC: // array<string,scalar>|false
                    return new Type\Union([
                        new Type\Atomic\TArray([
                            Type::getString(),
                            Type::getScalar(),
                        ]),
                        new Type\Atomic\TFalse(),
                    ]);

                case \PDO::FETCH_BOTH: // array<array-key,scalar>|false
                    return new Type\Union([
                        new Type\Atomic\TArray([
                            Type::getArrayKey(),
                            Type::getScalar()
                        ]),
                        new Type\Atomic\TFalse(),
                    ]);

                case \PDO::FETCH_BOUND: // bool
                    return Type::getBool();

                case \PDO::FETCH_CLASS: // object|false
                    return new Type\Union([
                        new Type\Atomic\TObject(),
                        new Type\Atomic\TFalse(),
                    ]);

                case \PDO::FETCH_LAZY: // object|false
                    // This actually returns a PDORow object, but that class is
                    // undocumented, and its attributes are all dynamic anyway
                    return new Type\Union([
                        new Type\Atomic\TObject(),
                        new Type\Atomic\TFalse(),
                    ]);

                case \PDO::FETCH_NAMED: // array<string, scalar|list<scalar>>|false
                    return new Type\Union([
                        new Type\Atomic\TArray([
                            Type::getString(),
                            new Type\Union([
                                new Type\Atomic\TScalar(),
                                new Type\Atomic\TList(Type::getScalar())
                            ])
                        ]),
                        new Type\Atomic\TFalse(),
                    ]);

                case \PDO::FETCH_NUM: // list<scalar>|false
                    return new Type\Union([
                        new Type\Atomic\TList(Type::getScalar()),
                        new Type\Atomic\TFalse(),
                    ]);

                case \PDO::FETCH_OBJ: // stdClass|false
                    return new Type\Union([
                        new Type\Atomic\TNamedObject('stdClass'),
                        new Type\Atomic\TFalse(),
                    ]);
            }
        }

        return null;
    }
}
