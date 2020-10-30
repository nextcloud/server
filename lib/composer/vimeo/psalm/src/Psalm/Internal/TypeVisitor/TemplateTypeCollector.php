<?php
namespace Psalm\Internal\TypeVisitor;

use Psalm\Type\Atomic\TConditional;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\TypeNode;
use Psalm\Type\NodeVisitor;
use Psalm\Type\Union;

class TemplateTypeCollector extends NodeVisitor
{
    /**
     * @var list<TTemplateParam>
     */
    private $template_types = [];

    protected function enterNode(TypeNode $type) : ?int
    {
        if ($type instanceof TTemplateParam) {
            $this->template_types[] = $type;
        } elseif ($type instanceof TTemplateParamClass) {
            $extends = $type->as_type;

            $this->template_types[] = new TTemplateParam(
                $type->param_name,
                $extends ? new Union([$extends]) : \Psalm\Type::getMixed(),
                $type->defining_class
            );
        } elseif ($type instanceof TConditional) {
            $this->template_types[] = new TTemplateParam(
                $type->param_name,
                \Psalm\Type::getMixed(),
                $type->defining_class
            );
        }

        return null;
    }

    /**
     * @return list<TTemplateParam>
     */
    public function getTemplateTypes() : array
    {
        return $this->template_types;
    }
}
