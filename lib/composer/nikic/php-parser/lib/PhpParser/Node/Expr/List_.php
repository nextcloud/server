<?php declare(strict_types=1);

namespace PhpParser\Node\Expr;

use PhpParser\Node\Expr;

class List_ extends Expr
{
    /** @var (ArrayItem|null)[] List of items to assign to */
    public $items;

    /**
     * Constructs a list() destructuring node.
     *
     * @param (ArrayItem|null)[] $items      List of items to assign to
     * @param array              $attributes Additional attributes
     */
    public function __construct(array $items, array $attributes = []) {
        $this->attributes = $attributes;
        $this->items = $items;
    }

    public function getSubNodeNames() : array {
        return ['items'];
    }
    
    public function getType() : string {
        return 'Expr_List';
    }
}
