<?php
namespace Psalm\Type\Atomic;

use function array_map;
use function implode;
use Psalm\Codebase;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Type;
use Psalm\Type\Atomic;

trait HasIntersectionTrait
{
    /**
     * @var array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties>|null
     */
    public $extra_types;

    /**
     * @param  array<string, string> $aliased_classes
     */
    private function getNamespacedIntersectionTypes(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ) : string {
        if (!$this->extra_types) {
            return '';
        }

        return '&' . implode(
            '&',
            array_map(
                /**
                 * @param TNamedObject|TTemplateParam|TIterable|TObjectWithProperties $extra_type
                 *
                 * @return string
                 */
                function (Atomic $extra_type) use (
                    $namespace,
                    $aliased_classes,
                    $this_class,
                    $use_phpdoc_format
                ): string {
                    return $extra_type->toNamespacedString(
                        $namespace,
                        $aliased_classes,
                        $this_class,
                        $use_phpdoc_format
                    );
                },
                $this->extra_types
            )
        );
    }

    /**
     * @param TNamedObject|TTemplateParam|TIterable|TObjectWithProperties $type
     */
    public function addIntersectionType(Type\Atomic $type) : void
    {
        $this->extra_types[$type->getKey()] = $type;
    }

    /**
     * @return array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties>|null
     */
    public function getIntersectionTypes() : ?array
    {
        return $this->extra_types;
    }

    public function replaceIntersectionTemplateTypesWithArgTypes(
        TemplateResult $template_result,
        ?Codebase $codebase
    ) : void {
        if (!$this->extra_types) {
            return;
        }

        $new_types = [];

        foreach ($this->extra_types as $extra_type) {
            if ($extra_type instanceof TTemplateParam
                && isset($template_result->upper_bounds[$extra_type->param_name][$extra_type->defining_class])
            ) {
                $template_type = clone $template_result->upper_bounds
                    [$extra_type->param_name][$extra_type->defining_class][0];

                foreach ($template_type->getAtomicTypes() as $template_type_part) {
                    if ($template_type_part instanceof TNamedObject) {
                        $new_types[$template_type_part->getKey()] = $template_type_part;
                    } elseif ($template_type_part instanceof TTemplateParam) {
                        $new_types[$template_type_part->getKey()] = $template_type_part;
                    }
                }
            } else {
                $extra_type->replaceTemplateTypesWithArgTypes($template_result, $codebase);
                $new_types[$extra_type->getKey()] = $extra_type;
            }
        }

        $this->extra_types = $new_types;
    }
}
