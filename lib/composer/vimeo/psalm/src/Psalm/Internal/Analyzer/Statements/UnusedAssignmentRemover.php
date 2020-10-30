<?php
namespace Psalm\Internal\Analyzer\Statements;

use PhpParser;
use Psalm\Internal\PhpVisitor\CheckTrivialExprVisitor;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\FileManipulation;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use function is_string;
use function strlen;
use function substr;
use function array_key_exists;
use function count;
use function token_get_all;
use function array_slice;
use function is_array;
use function trim;

class UnusedAssignmentRemover
{
    /**
     * @var array<string, CodeLocation>
     */
    private $removed_unref_vars = [];

    /**
     * @param array<PhpParser\Node\Stmt>   $stmts
     * @param array<string, CodeLocation> $var_loc_map
     *
     */
    public function findUnusedAssignment(
        Codebase $codebase,
        array $stmts,
        array $var_loc_map,
        string $var_id,
        CodeLocation $original_location
    ): void {
        $search_result = $this->findAssignStmt($stmts, $var_id, $original_location);
        [$assign_stmt, $assign_exp] = $search_result;
        $chain_assignment = false;

        if ($assign_stmt !== null && $assign_exp !== null) {
            // Check if we have to remove assignment statemnt as expression (i.e. just "$var = ")

            // Consider chain of assignments
            $rhs_exp = $assign_exp->expr;
            if ($rhs_exp instanceof PhpParser\Node\Expr\Assign
                || $rhs_exp instanceof PhpParser\Node\Expr\AssignOp
                || $rhs_exp instanceof PhpParser\Node\Expr\AssignRef
            ) {
                $chain_assignment = true;
                $removable_stmt = $this->checkRemovableChainAssignment($assign_exp, $var_loc_map);
            } else {
                $removable_stmt = true;
            }

            if ($removable_stmt) {
                $traverser = new PhpParser\NodeTraverser();
                $visitor = new CheckTrivialExprVisitor();
                $traverser->addVisitor($visitor);
                $traverser->traverse([$rhs_exp]);

                $rhs_exp_trivial = (count($visitor->getNonTrivialExpr()) === 0);

                if ($rhs_exp_trivial) {
                    $treat_as_expr = false;
                } else {
                    $treat_as_expr = true;
                }
            } else {
                $treat_as_expr = true;
            }

            if ($treat_as_expr) {
                $is_assign_ref = $assign_exp instanceof PhpParser\Node\Expr\AssignRef;
                $new_file_manipulation = self::getPartialRemovalBounds(
                    $codebase,
                    $original_location,
                    $assign_stmt->getEndFilePos(),
                    $is_assign_ref
                );
                $this->removed_unref_vars[$var_id] = $original_location;
            } else {
                // Remove whole assignment statement
                $new_file_manipulation = new FileManipulation(
                    $assign_stmt->getStartFilePos(),
                    $assign_stmt->getEndFilePos() + 1,
                    "",
                    false,
                    true
                );

                // If statement we are removing is a chain of assignments, mark other variables as removed
                if ($chain_assignment) {
                    $this->markRemovedChainAssignVar($assign_exp, $var_loc_map);
                } else {
                    $this->removed_unref_vars[$var_id] = $original_location;
                }
            }

            FileManipulationBuffer::add($original_location->file_path, [$new_file_manipulation]);
        } elseif ($assign_exp !== null) {
            $is_assign_ref = $assign_exp instanceof PhpParser\Node\Expr\AssignRef;
            $new_file_manipulation = self::getPartialRemovalBounds(
                $codebase,
                $original_location,
                $assign_exp->getEndFilePos(),
                $is_assign_ref
            );

            FileManipulationBuffer::add($original_location->file_path, [$new_file_manipulation]);
            $this->removed_unref_vars[$var_id] = $original_location;
        }
    }

    private static function getPartialRemovalBounds(
        Codebase $codebase,
        CodeLocation $var_loc,
        int $end_bound,
        bool $assign_ref = false
    ): FileManipulation {
        $var_start_loc= $var_loc->raw_file_start;
        $stmt_content = $codebase->file_provider->getContents(
            $var_loc->file_path
        );
        $str_for_token = "<?php\n" . substr($stmt_content, $var_start_loc, $end_bound - $var_start_loc + 1);
        $token_list = array_slice(token_get_all($str_for_token), 1);   //Ignore "<?php"

        $offset_count = strlen($token_list[0][1]);
        $iter = 1;

        // Check if second token is just whitespace
        if (is_array($token_list[$iter]) && strlen(trim($token_list[$iter][1])) === 0) {
            $offset_count += strlen($token_list[1][1]);
            $iter++;
        }

        // Add offset for assignment operator
        if (is_string($token_list[$iter])) {
            $offset_count += 1;
        } else {
            $offset_count += strlen($token_list[$iter][1]);
        }
        $iter++;

        // Remove any whitespace following assignment operator token (e.g "=", "+=")
        if (is_array($token_list[$iter]) && strlen(trim($token_list[$iter][1])) === 0) {
            $offset_count += strlen($token_list[$iter][1]);
            $iter++;
        }

        // If we are dealing with assignment by reference, we need to handle "&" and any whitespace after
        if ($assign_ref) {
            $offset_count += 1;
            $iter++;
            // Handle any whitespace after "&"
            if (is_array($token_list[$iter]) && strlen(trim($token_list[$iter][1])) === 0) {
                $offset_count += strlen($token_list[$iter][1]);
            }
        }

        $file_man_start = $var_start_loc;
        $file_man_end = $var_start_loc + $offset_count;

        return new FileManipulation($file_man_start, $file_man_end, "", false);
    }

    /**
     * @param  PhpParser\Node\Expr\Assign|PhpParser\Node\Expr\AssignOp|PhpParser\Node\Expr\AssignRef $cur_assign
     * @param  array<string, CodeLocation>    $var_loc_map
     */
    private function markRemovedChainAssignVar(PhpParser\Node\Expr $cur_assign, array $var_loc_map): void
    {
        $var = $cur_assign->var;
        if ($var instanceof PhpParser\Node\Expr\Variable && is_string($var->name)) {
            $var_name = "$" . $var->name;
            $var_loc = $var_loc_map[$var_name];
            $this->removed_unref_vars[$var_name] = $var_loc;

            $rhs_exp = $cur_assign->expr;
            if ($rhs_exp instanceof PhpParser\Node\Expr\Assign
                || $rhs_exp instanceof PhpParser\Node\Expr\AssignOp
                || $rhs_exp instanceof PhpParser\Node\Expr\AssignRef
            ) {
                $this->markRemovedChainAssignVar($rhs_exp, $var_loc_map);
            }
        }
    }

    /**
     * @param  PhpParser\Node\Expr\Assign|PhpParser\Node\Expr\AssignOp|PhpParser\Node\Expr\AssignRef $cur_assign
     * @param  array<string, CodeLocation> $var_loc_map
     */
    private function checkRemovableChainAssignment(PhpParser\Node\Expr $cur_assign, array $var_loc_map): bool
    {
        // Check if current assignment expr's variable is removable
        $var = $cur_assign->var;
        if ($var instanceof PhpParser\Node\Expr\Variable && is_string($var->name)) {
            $var_loc = $cur_assign->var->getStartFilePos();
            $var_name = "$" . $var->name;

            if (array_key_exists($var_name, $var_loc_map) &&
                $var_loc_map[$var_name]->raw_file_start === $var_loc) {
                $curr_removable = true;
            } else {
                $curr_removable = false;
            }

            if ($curr_removable) {
                $rhs_exp = $cur_assign->expr;

                if ($rhs_exp instanceof PhpParser\Node\Expr\Assign
                    || $rhs_exp instanceof PhpParser\Node\Expr\AssignOp
                    || $rhs_exp instanceof PhpParser\Node\Expr\AssignRef
                ) {
                    $rhs_removable = $this->checkRemovableChainAssignment($rhs_exp, $var_loc_map);
                    return $rhs_removable;
                }
            }
            return $curr_removable;
        } else {
            return false;
        }
    }

    /**
     * @param  array<PhpParser\Node\Stmt>   $stmts
     * @return array{
     *          0: PhpParser\Node\Stmt|null,
     *          1: PhpParser\Node\Expr\Assign|PhpParser\Node\Expr\AssignOp|PhpParser\Node\Expr\AssignRef|null
     *          }
     */
    private function findAssignStmt(array $stmts, string $var_id, CodeLocation $original_location): array
    {
        $assign_stmt = null;
        $assign_exp = null;
        $assign_exp_found = false;

        $i = 0;

        while ($i < count($stmts) && !$assign_exp_found) {
            $stmt = $stmts[$i];
            if ($stmt instanceof PhpParser\Node\Stmt\Expression) {
                $search_result = $this->findAssignExp($stmt->expr, $var_id, $original_location->raw_file_start);

                [$target_exp, $levels_taken] = $search_result;

                if ($target_exp !== null) {
                    $assign_exp_found = true;
                    $assign_exp = $target_exp;
                    $assign_stmt = $levels_taken === 1 ? $stmt : null;
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\TryCatch) {
                $search_result = $this->findAssignStmt($stmt->stmts, $var_id, $original_location);

                if ($search_result[0] && $search_result[1]) {
                    return $search_result;
                }

                foreach ($stmt->catches as $catch_stmt) {
                    $search_result = $this->findAssignStmt($catch_stmt->stmts, $var_id, $original_location);

                    if ($search_result[0] && $search_result[1]) {
                        return $search_result;
                    }
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Do_
                || $stmt instanceof PhpParser\Node\Stmt\While_
            ) {
                $search_result = $this->findAssignStmt($stmt->stmts, $var_id, $original_location);

                if ($search_result[0] && $search_result[1]) {
                    return $search_result;
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Foreach_) {
                $search_result = $this->findAssignStmt($stmt->stmts, $var_id, $original_location);

                if ($search_result[0] && $search_result[1]) {
                    return $search_result;
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\For_) {
                $search_result = $this->findAssignStmt($stmt->stmts, $var_id, $original_location);

                if ($search_result[0] && $search_result[1]) {
                    return $search_result;
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\If_) {
                $search_result = $this->findAssignStmt($stmt->stmts, $var_id, $original_location);

                if ($search_result[0] && $search_result[1]) {
                    return $search_result;
                }

                foreach ($stmt->elseifs as $elseif_stmt) {
                    $search_result = $this->findAssignStmt($elseif_stmt->stmts, $var_id, $original_location);

                    if ($search_result[0] && $search_result[1]) {
                        return $search_result;
                    }
                }

                if ($stmt->else) {
                    $search_result = $this->findAssignStmt($stmt->else->stmts, $var_id, $original_location);

                    if ($search_result[0] && $search_result[1]) {
                        return $search_result;
                    }
                }
            }

            $i++;
        }

        return [$assign_stmt, $assign_exp];
    }

    /**
     * @return array{
     *          0: PhpParser\Node\Expr\Assign|PhpParser\Node\Expr\AssignOp|PhpParser\Node\Expr\AssignRef|null,
     *          1: int
     *          }
     */
    private function findAssignExp(
        PhpParser\Node\Expr $current_node,
        string $var_id,
        int $var_start_loc,
        int $search_level = 1
    ): array {
        if ($current_node instanceof PhpParser\Node\Expr\Assign
            || $current_node instanceof PhpPArser\Node\Expr\AssignOp
            || $current_node instanceof PhpParser\Node\Expr\AssignRef
        ) {
            $var = $current_node->var;

            if ($var instanceof PhpParser\Node\Expr\Variable
                && $var->name === substr($var_id, 1)
                && $var->getStartFilePos() === $var_start_loc
            ) {
                return [$current_node, $search_level];
            }

            $rhs_exp = $current_node->expr;
            $rhs_search_result = $this->findAssignExp($rhs_exp, $var_id, $var_start_loc, $search_level + 1);
            return [$rhs_search_result[0], $rhs_search_result[1]];
        } else {
            return [null, $search_level];
        }
    }

    public function checkIfVarRemoved(string $var_id, CodeLocation $var_loc): bool
    {
        return array_key_exists($var_id, $this->removed_unref_vars)
                && $this->removed_unref_vars[$var_id] === $var_loc;
    }
}
