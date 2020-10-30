<?php
namespace Psalm\Storage;

use function array_map;
use function implode;

class Assertion
{
    /**
     * @var array<int, array<int, string>> the rule being asserted
     */
    public $rule;

    /**
     * @var int|string the id of the property/variable, or
     *  the parameter offset of the affected arg
     */
    public $var_id;

    /**
     * @param string|int $var_id
     * @param array<int, array<int, string>> $rule
     */
    public function __construct($var_id, array $rule)
    {
        $this->rule = $rule;
        $this->var_id = $var_id;
    }

    /**
     * @param array<string, array<string, array{0:\Psalm\Type\Union}>> $template_type_map
     */
    public function getUntemplatedCopy(array $template_type_map, ?string $this_var_id) : self
    {
        return new Assertion(
            \is_string($this->var_id) && $this_var_id
                ? \str_replace('$this->', $this_var_id . '->', $this->var_id)
                : $this->var_id,
            array_map(
                /**
                 * @param array<int, string> $rules
                 *
                 * @return array{0: string}
                 */
                function (array $rules) use ($template_type_map) : array {
                    $first_rule = $rules[0];

                    if ($template_type_map) {
                        $rule_tokens = \Psalm\Internal\Type\TypeTokenizer::tokenize($first_rule);

                        $substitute = false;

                        foreach ($rule_tokens as &$rule_token) {
                            if (isset($template_type_map[$rule_token[0]])) {
                                foreach ($template_type_map[$rule_token[0]] as [$type]) {
                                    $substitute = true;

                                    $first_type = \array_values($type->getAtomicTypes())[0];

                                    if ($first_type instanceof \Psalm\Type\Atomic\TTemplateParam) {
                                        $rule_token[0] = $first_type->param_name;
                                    } else {
                                        $rule_token[0] = $first_type->getKey();
                                    }
                                }
                            }
                        }

                        if ($substitute) {
                            return [implode(
                                '',
                                array_map(
                                    function ($f) {
                                        return $f[0];
                                    },
                                    $rule_tokens
                                )
                            )];
                        }
                    }

                    return [$first_rule];
                },
                $this->rule
            )
        );
    }
}
