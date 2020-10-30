<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Type\Comparator\CallableTypeComparator;
use Psalm\Internal\Type\TypeCombination;
use Psalm\StatementsSource;
use Psalm\Type;

class ClosureFromCallableReturnTypeProvider implements \Psalm\Plugin\Hook\MethodReturnTypeProviderInterface
{
    public static function getClassLikeNames() : array
    {
        return ['Closure'];
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
        if (!$source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer) {
            return null;
        }

        $type_provider = $source->getNodeTypeProvider();
        $codebase = $source->getCodebase();

        if ($method_name_lowercase === 'fromcallable') {
            $closure_types = [];

            if (isset($call_args[0])
                && ($input_type = $type_provider->getType($call_args[0]->value))
            ) {
                foreach ($input_type->getAtomicTypes() as $atomic_type) {
                    $candidate_callable = CallableTypeComparator::getCallableFromAtomic(
                        $codebase,
                        $atomic_type,
                        null,
                        $source
                    );

                    if ($candidate_callable) {
                        $closure_types[] = new Type\Atomic\TClosure(
                            'Closure',
                            $candidate_callable->params,
                            $candidate_callable->return_type,
                            $candidate_callable->is_pure
                        );
                    } else {
                        return Type::getClosure();
                    }
                }
            }

            if ($closure_types) {
                return TypeCombination::combineTypes($closure_types, $codebase);
            }

            return Type::getClosure();
        }

        return null;
    }
}
