<?php
namespace Psalm\Internal\Scope;

use Psalm\Internal\Clause;
use Psalm\Type;

/**
 * @internal
 */
class IfScope
{
    /**
     * @var array<string, Type\Union>|null
     */
    public $new_vars = null;

    /**
     * @var array<string, bool>
     */
    public $new_vars_possibly_in_scope = [];

    /**
     * @var array<string, Type\Union>|null
     */
    public $redefined_vars = null;

    /**
     * @var array<string, bool>|null
     */
    public $assigned_var_ids = null;

    /**
     * @var array<string, bool>
     */
    public $possibly_assigned_var_ids = [];

    /**
     * @var array<string, Type\Union>
     */
    public $possibly_redefined_vars = [];

    /**
     * @var array<string, bool>
     */
    public $updated_vars = [];

    /**
     * @var array<string, array<int, array<int, string>>>
     */
    public $negated_types = [];

    /**
     * @var array<string, bool>
     */
    public $if_cond_changed_var_ids = [];

    /**
     * @var array<string, string>|null
     */
    public $negatable_if_types = null;

    /**
     * @var list<Clause>
     */
    public $negated_clauses = [];

    /**
     * These are the set of clauses that could be applied after the `if`
     * statement, if the `if` statement contains branches with leaving statements,
     * and the else leaves too
     *
     * @var list<Clause>
     */
    public $reasonable_clauses = [];

    /**
     * Variables that were mixed, but are no longer
     *
     * @var array<string, Type\Union>|null
     */
    public $possible_param_types = null;

    /**
     * @var string[]
     */
    public $final_actions = [];

    /**
     * @var ?\Psalm\Context
     */
    public $mic_drop_context;
}
