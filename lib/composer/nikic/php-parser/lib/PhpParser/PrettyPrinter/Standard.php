<?php declare(strict_types=1);

namespace PhpParser\PrettyPrinter;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\AssignOp;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\Cast;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\Node\Scalar\MagicConst;
use PhpParser\Node\Stmt;
use PhpParser\PrettyPrinterAbstract;

class Standard extends PrettyPrinterAbstract
{
    // Special nodes

    protected function pParam(Node\Param $node) {
        return $this->pAttrGroups($node->attrGroups, true)
             . $this->pModifiers($node->flags)
             . ($node->type ? $this->p($node->type) . ' ' : '')
             . ($node->byRef ? '&' : '')
             . ($node->variadic ? '...' : '')
             . $this->p($node->var)
             . ($node->default ? ' = ' . $this->p($node->default) : '');
    }

    protected function pArg(Node\Arg $node) {
        return ($node->name ? $node->name->toString() . ': ' : '')
             . ($node->byRef ? '&' : '') . ($node->unpack ? '...' : '')
             . $this->p($node->value);
    }

    protected function pConst(Node\Const_ $node) {
        return $node->name . ' = ' . $this->p($node->value);
    }

    protected function pNullableType(Node\NullableType $node) {
        return '?' . $this->p($node->type);
    }

    protected function pUnionType(Node\UnionType $node) {
        return $this->pImplode($node->types, '|');
    }

    protected function pIdentifier(Node\Identifier $node) {
        return $node->name;
    }

    protected function pVarLikeIdentifier(Node\VarLikeIdentifier $node) {
        return '$' . $node->name;
    }

    protected function pAttribute(Node\Attribute $node) {
        return $this->p($node->name)
             . ($node->args ? '(' . $this->pCommaSeparated($node->args) . ')' : '');
    }

    protected function pAttributeGroup(Node\AttributeGroup $node) {
        return '#[' . $this->pCommaSeparated($node->attrs) . ']';
    }

    // Names

    protected function pName(Name $node) {
        return implode('\\', $node->parts);
    }

    protected function pName_FullyQualified(Name\FullyQualified $node) {
        return '\\' . implode('\\', $node->parts);
    }

    protected function pName_Relative(Name\Relative $node) {
        return 'namespace\\' . implode('\\', $node->parts);
    }

    // Magic Constants

    protected function pScalar_MagicConst_Class(MagicConst\Class_ $node) {
        return '__CLASS__';
    }

    protected function pScalar_MagicConst_Dir(MagicConst\Dir $node) {
        return '__DIR__';
    }

    protected function pScalar_MagicConst_File(MagicConst\File $node) {
        return '__FILE__';
    }

    protected function pScalar_MagicConst_Function(MagicConst\Function_ $node) {
        return '__FUNCTION__';
    }

    protected function pScalar_MagicConst_Line(MagicConst\Line $node) {
        return '__LINE__';
    }

    protected function pScalar_MagicConst_Method(MagicConst\Method $node) {
        return '__METHOD__';
    }

    protected function pScalar_MagicConst_Namespace(MagicConst\Namespace_ $node) {
        return '__NAMESPACE__';
    }

    protected function pScalar_MagicConst_Trait(MagicConst\Trait_ $node) {
        return '__TRAIT__';
    }

    // Scalars

    protected function pScalar_String(Scalar\String_ $node) {
        $kind = $node->getAttribute('kind', Scalar\String_::KIND_SINGLE_QUOTED);
        switch ($kind) {
            case Scalar\String_::KIND_NOWDOC:
                $label = $node->getAttribute('docLabel');
                if ($label && !$this->containsEndLabel($node->value, $label)) {
                    if ($node->value === '') {
                        return "<<<'$label'\n$label" . $this->docStringEndToken;
                    }

                    return "<<<'$label'\n$node->value\n$label"
                         . $this->docStringEndToken;
                }
                /* break missing intentionally */
            case Scalar\String_::KIND_SINGLE_QUOTED:
                return $this->pSingleQuotedString($node->value);
            case Scalar\String_::KIND_HEREDOC:
                $label = $node->getAttribute('docLabel');
                if ($label && !$this->containsEndLabel($node->value, $label)) {
                    if ($node->value === '') {
                        return "<<<$label\n$label" . $this->docStringEndToken;
                    }

                    $escaped = $this->escapeString($node->value, null);
                    return "<<<$label\n" . $escaped . "\n$label"
                         . $this->docStringEndToken;
                }
            /* break missing intentionally */
            case Scalar\String_::KIND_DOUBLE_QUOTED:
                return '"' . $this->escapeString($node->value, '"') . '"';
        }
        throw new \Exception('Invalid string kind');
    }

    protected function pScalar_Encapsed(Scalar\Encapsed $node) {
        if ($node->getAttribute('kind') === Scalar\String_::KIND_HEREDOC) {
            $label = $node->getAttribute('docLabel');
            if ($label && !$this->encapsedContainsEndLabel($node->parts, $label)) {
                if (count($node->parts) === 1
                    && $node->parts[0] instanceof Scalar\EncapsedStringPart
                    && $node->parts[0]->value === ''
                ) {
                    return "<<<$label\n$label" . $this->docStringEndToken;
                }

                return "<<<$label\n" . $this->pEncapsList($node->parts, null) . "\n$label"
                     . $this->docStringEndToken;
            }
        }
        return '"' . $this->pEncapsList($node->parts, '"') . '"';
    }

    protected function pScalar_LNumber(Scalar\LNumber $node) {
        if ($node->value === -\PHP_INT_MAX-1) {
            // PHP_INT_MIN cannot be represented as a literal,
            // because the sign is not part of the literal
            return '(-' . \PHP_INT_MAX . '-1)';
        }

        $kind = $node->getAttribute('kind', Scalar\LNumber::KIND_DEC);
        if (Scalar\LNumber::KIND_DEC === $kind) {
            return (string) $node->value;
        }

        if ($node->value < 0) {
            $sign = '-';
            $str = (string) -$node->value;
        } else {
            $sign = '';
            $str = (string) $node->value;
        }
        switch ($kind) {
            case Scalar\LNumber::KIND_BIN:
                return $sign . '0b' . base_convert($str, 10, 2);
            case Scalar\LNumber::KIND_OCT:
                return $sign . '0' . base_convert($str, 10, 8);
            case Scalar\LNumber::KIND_HEX:
                return $sign . '0x' . base_convert($str, 10, 16);
        }
        throw new \Exception('Invalid number kind');
    }

    protected function pScalar_DNumber(Scalar\DNumber $node) {
        if (!is_finite($node->value)) {
            if ($node->value === \INF) {
                return '\INF';
            } elseif ($node->value === -\INF) {
                return '-\INF';
            } else {
                return '\NAN';
            }
        }

        // Try to find a short full-precision representation
        $stringValue = sprintf('%.16G', $node->value);
        if ($node->value !== (double) $stringValue) {
            $stringValue = sprintf('%.17G', $node->value);
        }

        // %G is locale dependent and there exists no locale-independent alternative. We don't want
        // mess with switching locales here, so let's assume that a comma is the only non-standard
        // decimal separator we may encounter...
        $stringValue = str_replace(',', '.', $stringValue);

        // ensure that number is really printed as float
        return preg_match('/^-?[0-9]+$/', $stringValue) ? $stringValue . '.0' : $stringValue;
    }

    protected function pScalar_EncapsedStringPart(Scalar\EncapsedStringPart $node) {
        throw new \LogicException('Cannot directly print EncapsedStringPart');
    }

    // Assignments

    protected function pExpr_Assign(Expr\Assign $node) {
        return $this->pInfixOp(Expr\Assign::class, $node->var, ' = ', $node->expr);
    }

    protected function pExpr_AssignRef(Expr\AssignRef $node) {
        return $this->pInfixOp(Expr\AssignRef::class, $node->var, ' =& ', $node->expr);
    }

    protected function pExpr_AssignOp_Plus(AssignOp\Plus $node) {
        return $this->pInfixOp(AssignOp\Plus::class, $node->var, ' += ', $node->expr);
    }

    protected function pExpr_AssignOp_Minus(AssignOp\Minus $node) {
        return $this->pInfixOp(AssignOp\Minus::class, $node->var, ' -= ', $node->expr);
    }

    protected function pExpr_AssignOp_Mul(AssignOp\Mul $node) {
        return $this->pInfixOp(AssignOp\Mul::class, $node->var, ' *= ', $node->expr);
    }

    protected function pExpr_AssignOp_Div(AssignOp\Div $node) {
        return $this->pInfixOp(AssignOp\Div::class, $node->var, ' /= ', $node->expr);
    }

    protected function pExpr_AssignOp_Concat(AssignOp\Concat $node) {
        return $this->pInfixOp(AssignOp\Concat::class, $node->var, ' .= ', $node->expr);
    }

    protected function pExpr_AssignOp_Mod(AssignOp\Mod $node) {
        return $this->pInfixOp(AssignOp\Mod::class, $node->var, ' %= ', $node->expr);
    }

    protected function pExpr_AssignOp_BitwiseAnd(AssignOp\BitwiseAnd $node) {
        return $this->pInfixOp(AssignOp\BitwiseAnd::class, $node->var, ' &= ', $node->expr);
    }

    protected function pExpr_AssignOp_BitwiseOr(AssignOp\BitwiseOr $node) {
        return $this->pInfixOp(AssignOp\BitwiseOr::class, $node->var, ' |= ', $node->expr);
    }

    protected function pExpr_AssignOp_BitwiseXor(AssignOp\BitwiseXor $node) {
        return $this->pInfixOp(AssignOp\BitwiseXor::class, $node->var, ' ^= ', $node->expr);
    }

    protected function pExpr_AssignOp_ShiftLeft(AssignOp\ShiftLeft $node) {
        return $this->pInfixOp(AssignOp\ShiftLeft::class, $node->var, ' <<= ', $node->expr);
    }

    protected function pExpr_AssignOp_ShiftRight(AssignOp\ShiftRight $node) {
        return $this->pInfixOp(AssignOp\ShiftRight::class, $node->var, ' >>= ', $node->expr);
    }

    protected function pExpr_AssignOp_Pow(AssignOp\Pow $node) {
        return $this->pInfixOp(AssignOp\Pow::class, $node->var, ' **= ', $node->expr);
    }

    protected function pExpr_AssignOp_Coalesce(AssignOp\Coalesce $node) {
        return $this->pInfixOp(AssignOp\Coalesce::class, $node->var, ' ??= ', $node->expr);
    }

    // Binary expressions

    protected function pExpr_BinaryOp_Plus(BinaryOp\Plus $node) {
        return $this->pInfixOp(BinaryOp\Plus::class, $node->left, ' + ', $node->right);
    }

    protected function pExpr_BinaryOp_Minus(BinaryOp\Minus $node) {
        return $this->pInfixOp(BinaryOp\Minus::class, $node->left, ' - ', $node->right);
    }

    protected function pExpr_BinaryOp_Mul(BinaryOp\Mul $node) {
        return $this->pInfixOp(BinaryOp\Mul::class, $node->left, ' * ', $node->right);
    }

    protected function pExpr_BinaryOp_Div(BinaryOp\Div $node) {
        return $this->pInfixOp(BinaryOp\Div::class, $node->left, ' / ', $node->right);
    }

    protected function pExpr_BinaryOp_Concat(BinaryOp\Concat $node) {
        return $this->pInfixOp(BinaryOp\Concat::class, $node->left, ' . ', $node->right);
    }

    protected function pExpr_BinaryOp_Mod(BinaryOp\Mod $node) {
        return $this->pInfixOp(BinaryOp\Mod::class, $node->left, ' % ', $node->right);
    }

    protected function pExpr_BinaryOp_BooleanAnd(BinaryOp\BooleanAnd $node) {
        return $this->pInfixOp(BinaryOp\BooleanAnd::class, $node->left, ' && ', $node->right);
    }

    protected function pExpr_BinaryOp_BooleanOr(BinaryOp\BooleanOr $node) {
        return $this->pInfixOp(BinaryOp\BooleanOr::class, $node->left, ' || ', $node->right);
    }

    protected function pExpr_BinaryOp_BitwiseAnd(BinaryOp\BitwiseAnd $node) {
        return $this->pInfixOp(BinaryOp\BitwiseAnd::class, $node->left, ' & ', $node->right);
    }

    protected function pExpr_BinaryOp_BitwiseOr(BinaryOp\BitwiseOr $node) {
        return $this->pInfixOp(BinaryOp\BitwiseOr::class, $node->left, ' | ', $node->right);
    }

    protected function pExpr_BinaryOp_BitwiseXor(BinaryOp\BitwiseXor $node) {
        return $this->pInfixOp(BinaryOp\BitwiseXor::class, $node->left, ' ^ ', $node->right);
    }

    protected function pExpr_BinaryOp_ShiftLeft(BinaryOp\ShiftLeft $node) {
        return $this->pInfixOp(BinaryOp\ShiftLeft::class, $node->left, ' << ', $node->right);
    }

    protected function pExpr_BinaryOp_ShiftRight(BinaryOp\ShiftRight $node) {
        return $this->pInfixOp(BinaryOp\ShiftRight::class, $node->left, ' >> ', $node->right);
    }

    protected function pExpr_BinaryOp_Pow(BinaryOp\Pow $node) {
        return $this->pInfixOp(BinaryOp\Pow::class, $node->left, ' ** ', $node->right);
    }

    protected function pExpr_BinaryOp_LogicalAnd(BinaryOp\LogicalAnd $node) {
        return $this->pInfixOp(BinaryOp\LogicalAnd::class, $node->left, ' and ', $node->right);
    }

    protected function pExpr_BinaryOp_LogicalOr(BinaryOp\LogicalOr $node) {
        return $this->pInfixOp(BinaryOp\LogicalOr::class, $node->left, ' or ', $node->right);
    }

    protected function pExpr_BinaryOp_LogicalXor(BinaryOp\LogicalXor $node) {
        return $this->pInfixOp(BinaryOp\LogicalXor::class, $node->left, ' xor ', $node->right);
    }

    protected function pExpr_BinaryOp_Equal(BinaryOp\Equal $node) {
        return $this->pInfixOp(BinaryOp\Equal::class, $node->left, ' == ', $node->right);
    }

    protected function pExpr_BinaryOp_NotEqual(BinaryOp\NotEqual $node) {
        return $this->pInfixOp(BinaryOp\NotEqual::class, $node->left, ' != ', $node->right);
    }

    protected function pExpr_BinaryOp_Identical(BinaryOp\Identical $node) {
        return $this->pInfixOp(BinaryOp\Identical::class, $node->left, ' === ', $node->right);
    }

    protected function pExpr_BinaryOp_NotIdentical(BinaryOp\NotIdentical $node) {
        return $this->pInfixOp(BinaryOp\NotIdentical::class, $node->left, ' !== ', $node->right);
    }

    protected function pExpr_BinaryOp_Spaceship(BinaryOp\Spaceship $node) {
        return $this->pInfixOp(BinaryOp\Spaceship::class, $node->left, ' <=> ', $node->right);
    }

    protected function pExpr_BinaryOp_Greater(BinaryOp\Greater $node) {
        return $this->pInfixOp(BinaryOp\Greater::class, $node->left, ' > ', $node->right);
    }

    protected function pExpr_BinaryOp_GreaterOrEqual(BinaryOp\GreaterOrEqual $node) {
        return $this->pInfixOp(BinaryOp\GreaterOrEqual::class, $node->left, ' >= ', $node->right);
    }

    protected function pExpr_BinaryOp_Smaller(BinaryOp\Smaller $node) {
        return $this->pInfixOp(BinaryOp\Smaller::class, $node->left, ' < ', $node->right);
    }

    protected function pExpr_BinaryOp_SmallerOrEqual(BinaryOp\SmallerOrEqual $node) {
        return $this->pInfixOp(BinaryOp\SmallerOrEqual::class, $node->left, ' <= ', $node->right);
    }

    protected function pExpr_BinaryOp_Coalesce(BinaryOp\Coalesce $node) {
        return $this->pInfixOp(BinaryOp\Coalesce::class, $node->left, ' ?? ', $node->right);
    }

    protected function pExpr_Instanceof(Expr\Instanceof_ $node) {
        list($precedence, $associativity) = $this->precedenceMap[Expr\Instanceof_::class];
        return $this->pPrec($node->expr, $precedence, $associativity, -1)
             . ' instanceof '
             . $this->pNewVariable($node->class);
    }

    // Unary expressions

    protected function pExpr_BooleanNot(Expr\BooleanNot $node) {
        return $this->pPrefixOp(Expr\BooleanNot::class, '!', $node->expr);
    }

    protected function pExpr_BitwiseNot(Expr\BitwiseNot $node) {
        return $this->pPrefixOp(Expr\BitwiseNot::class, '~', $node->expr);
    }

    protected function pExpr_UnaryMinus(Expr\UnaryMinus $node) {
        if ($node->expr instanceof Expr\UnaryMinus || $node->expr instanceof Expr\PreDec) {
            // Enforce -(-$expr) instead of --$expr
            return '-(' . $this->p($node->expr) . ')';
        }
        return $this->pPrefixOp(Expr\UnaryMinus::class, '-', $node->expr);
    }

    protected function pExpr_UnaryPlus(Expr\UnaryPlus $node) {
        if ($node->expr instanceof Expr\UnaryPlus || $node->expr instanceof Expr\PreInc) {
            // Enforce +(+$expr) instead of ++$expr
            return '+(' . $this->p($node->expr) . ')';
        }
        return $this->pPrefixOp(Expr\UnaryPlus::class, '+', $node->expr);
    }

    protected function pExpr_PreInc(Expr\PreInc $node) {
        return $this->pPrefixOp(Expr\PreInc::class, '++', $node->var);
    }

    protected function pExpr_PreDec(Expr\PreDec $node) {
        return $this->pPrefixOp(Expr\PreDec::class, '--', $node->var);
    }

    protected function pExpr_PostInc(Expr\PostInc $node) {
        return $this->pPostfixOp(Expr\PostInc::class, $node->var, '++');
    }

    protected function pExpr_PostDec(Expr\PostDec $node) {
        return $this->pPostfixOp(Expr\PostDec::class, $node->var, '--');
    }

    protected function pExpr_ErrorSuppress(Expr\ErrorSuppress $node) {
        return $this->pPrefixOp(Expr\ErrorSuppress::class, '@', $node->expr);
    }

    protected function pExpr_YieldFrom(Expr\YieldFrom $node) {
        return $this->pPrefixOp(Expr\YieldFrom::class, 'yield from ', $node->expr);
    }

    protected function pExpr_Print(Expr\Print_ $node) {
        return $this->pPrefixOp(Expr\Print_::class, 'print ', $node->expr);
    }

    // Casts

    protected function pExpr_Cast_Int(Cast\Int_ $node) {
        return $this->pPrefixOp(Cast\Int_::class, '(int) ', $node->expr);
    }

    protected function pExpr_Cast_Double(Cast\Double $node) {
        $kind = $node->getAttribute('kind', Cast\Double::KIND_DOUBLE);
        if ($kind === Cast\Double::KIND_DOUBLE) {
            $cast = '(double)';
        } elseif ($kind === Cast\Double::KIND_FLOAT) {
            $cast = '(float)';
        } elseif ($kind === Cast\Double::KIND_REAL) {
            $cast = '(real)';
        }
        return $this->pPrefixOp(Cast\Double::class, $cast . ' ', $node->expr);
    }

    protected function pExpr_Cast_String(Cast\String_ $node) {
        return $this->pPrefixOp(Cast\String_::class, '(string) ', $node->expr);
    }

    protected function pExpr_Cast_Array(Cast\Array_ $node) {
        return $this->pPrefixOp(Cast\Array_::class, '(array) ', $node->expr);
    }

    protected function pExpr_Cast_Object(Cast\Object_ $node) {
        return $this->pPrefixOp(Cast\Object_::class, '(object) ', $node->expr);
    }

    protected function pExpr_Cast_Bool(Cast\Bool_ $node) {
        return $this->pPrefixOp(Cast\Bool_::class, '(bool) ', $node->expr);
    }

    protected function pExpr_Cast_Unset(Cast\Unset_ $node) {
        return $this->pPrefixOp(Cast\Unset_::class, '(unset) ', $node->expr);
    }

    // Function calls and similar constructs

    protected function pExpr_FuncCall(Expr\FuncCall $node) {
        return $this->pCallLhs($node->name)
             . '(' . $this->pMaybeMultiline($node->args) . ')';
    }

    protected function pExpr_MethodCall(Expr\MethodCall $node) {
        return $this->pDereferenceLhs($node->var) . '->' . $this->pObjectProperty($node->name)
             . '(' . $this->pMaybeMultiline($node->args) . ')';
    }

    protected function pExpr_NullsafeMethodCall(Expr\NullsafeMethodCall $node) {
        return $this->pDereferenceLhs($node->var) . '?->' . $this->pObjectProperty($node->name)
            . '(' . $this->pMaybeMultiline($node->args) . ')';
    }

    protected function pExpr_StaticCall(Expr\StaticCall $node) {
        return $this->pDereferenceLhs($node->class) . '::'
             . ($node->name instanceof Expr
                ? ($node->name instanceof Expr\Variable
                   ? $this->p($node->name)
                   : '{' . $this->p($node->name) . '}')
                : $node->name)
             . '(' . $this->pMaybeMultiline($node->args) . ')';
    }

    protected function pExpr_Empty(Expr\Empty_ $node) {
        return 'empty(' . $this->p($node->expr) . ')';
    }

    protected function pExpr_Isset(Expr\Isset_ $node) {
        return 'isset(' . $this->pCommaSeparated($node->vars) . ')';
    }

    protected function pExpr_Eval(Expr\Eval_ $node) {
        return 'eval(' . $this->p($node->expr) . ')';
    }

    protected function pExpr_Include(Expr\Include_ $node) {
        static $map = [
            Expr\Include_::TYPE_INCLUDE      => 'include',
            Expr\Include_::TYPE_INCLUDE_ONCE => 'include_once',
            Expr\Include_::TYPE_REQUIRE      => 'require',
            Expr\Include_::TYPE_REQUIRE_ONCE => 'require_once',
        ];

        return $map[$node->type] . ' ' . $this->p($node->expr);
    }

    protected function pExpr_List(Expr\List_ $node) {
        return 'list(' . $this->pCommaSeparated($node->items) . ')';
    }

    // Other

    protected function pExpr_Error(Expr\Error $node) {
        throw new \LogicException('Cannot pretty-print AST with Error nodes');
    }

    protected function pExpr_Variable(Expr\Variable $node) {
        if ($node->name instanceof Expr) {
            return '${' . $this->p($node->name) . '}';
        } else {
            return '$' . $node->name;
        }
    }

    protected function pExpr_Array(Expr\Array_ $node) {
        $syntax = $node->getAttribute('kind',
            $this->options['shortArraySyntax'] ? Expr\Array_::KIND_SHORT : Expr\Array_::KIND_LONG);
        if ($syntax === Expr\Array_::KIND_SHORT) {
            return '[' . $this->pMaybeMultiline($node->items, true) . ']';
        } else {
            return 'array(' . $this->pMaybeMultiline($node->items, true) . ')';
        }
    }

    protected function pExpr_ArrayItem(Expr\ArrayItem $node) {
        return (null !== $node->key ? $this->p($node->key) . ' => ' : '')
             . ($node->byRef ? '&' : '')
             . ($node->unpack ? '...' : '')
             . $this->p($node->value);
    }

    protected function pExpr_ArrayDimFetch(Expr\ArrayDimFetch $node) {
        return $this->pDereferenceLhs($node->var)
             . '[' . (null !== $node->dim ? $this->p($node->dim) : '') . ']';
    }

    protected function pExpr_ConstFetch(Expr\ConstFetch $node) {
        return $this->p($node->name);
    }

    protected function pExpr_ClassConstFetch(Expr\ClassConstFetch $node) {
        return $this->pDereferenceLhs($node->class) . '::' . $this->p($node->name);
    }

    protected function pExpr_PropertyFetch(Expr\PropertyFetch $node) {
        return $this->pDereferenceLhs($node->var) . '->' . $this->pObjectProperty($node->name);
    }

    protected function pExpr_NullsafePropertyFetch(Expr\NullsafePropertyFetch $node) {
        return $this->pDereferenceLhs($node->var) . '?->' . $this->pObjectProperty($node->name);
    }

    protected function pExpr_StaticPropertyFetch(Expr\StaticPropertyFetch $node) {
        return $this->pDereferenceLhs($node->class) . '::$' . $this->pObjectProperty($node->name);
    }

    protected function pExpr_ShellExec(Expr\ShellExec $node) {
        return '`' . $this->pEncapsList($node->parts, '`') . '`';
    }

    protected function pExpr_Closure(Expr\Closure $node) {
        return $this->pAttrGroups($node->attrGroups, true)
             . ($node->static ? 'static ' : '')
             . 'function ' . ($node->byRef ? '&' : '')
             . '(' . $this->pCommaSeparated($node->params) . ')'
             . (!empty($node->uses) ? ' use(' . $this->pCommaSeparated($node->uses) . ')' : '')
             . (null !== $node->returnType ? ' : ' . $this->p($node->returnType) : '')
             . ' {' . $this->pStmts($node->stmts) . $this->nl . '}';
    }

    protected function pExpr_Match(Expr\Match_ $node) {
        return 'match (' . $this->p($node->cond) . ') {'
            . $this->pCommaSeparatedMultiline($node->arms, true)
            . $this->nl
            . '}';
    }

    protected function pMatchArm(Node\MatchArm $node) {
        return ($node->conds ? $this->pCommaSeparated($node->conds) : 'default')
            . ' => ' . $this->p($node->body);
    }

    protected function pExpr_ArrowFunction(Expr\ArrowFunction $node) {
        return $this->pAttrGroups($node->attrGroups, true)
            . ($node->static ? 'static ' : '')
            . 'fn' . ($node->byRef ? '&' : '')
            . '(' . $this->pCommaSeparated($node->params) . ')'
            . (null !== $node->returnType ? ': ' . $this->p($node->returnType) : '')
            . ' => '
            . $this->p($node->expr);
    }

    protected function pExpr_ClosureUse(Expr\ClosureUse $node) {
        return ($node->byRef ? '&' : '') . $this->p($node->var);
    }

    protected function pExpr_New(Expr\New_ $node) {
        if ($node->class instanceof Stmt\Class_) {
            $args = $node->args ? '(' . $this->pMaybeMultiline($node->args) . ')' : '';
            return 'new ' . $this->pClassCommon($node->class, $args);
        }
        return 'new ' . $this->pNewVariable($node->class)
            . '(' . $this->pMaybeMultiline($node->args) . ')';
    }

    protected function pExpr_Clone(Expr\Clone_ $node) {
        return 'clone ' . $this->p($node->expr);
    }

    protected function pExpr_Ternary(Expr\Ternary $node) {
        // a bit of cheating: we treat the ternary as a binary op where the ?...: part is the operator.
        // this is okay because the part between ? and : never needs parentheses.
        return $this->pInfixOp(Expr\Ternary::class,
            $node->cond, ' ?' . (null !== $node->if ? ' ' . $this->p($node->if) . ' ' : '') . ': ', $node->else
        );
    }

    protected function pExpr_Exit(Expr\Exit_ $node) {
        $kind = $node->getAttribute('kind', Expr\Exit_::KIND_DIE);
        return ($kind === Expr\Exit_::KIND_EXIT ? 'exit' : 'die')
             . (null !== $node->expr ? '(' . $this->p($node->expr) . ')' : '');
    }

    protected function pExpr_Throw(Expr\Throw_ $node) {
        return 'throw ' . $this->p($node->expr);
    }

    protected function pExpr_Yield(Expr\Yield_ $node) {
        if ($node->value === null) {
            return 'yield';
        } else {
            // this is a bit ugly, but currently there is no way to detect whether the parentheses are necessary
            return '(yield '
                 . ($node->key !== null ? $this->p($node->key) . ' => ' : '')
                 . $this->p($node->value)
                 . ')';
        }
    }

    // Declarations

    protected function pStmt_Namespace(Stmt\Namespace_ $node) {
        if ($this->canUseSemicolonNamespaces) {
            return 'namespace ' . $this->p($node->name) . ';'
                 . $this->nl . $this->pStmts($node->stmts, false);
        } else {
            return 'namespace' . (null !== $node->name ? ' ' . $this->p($node->name) : '')
                 . ' {' . $this->pStmts($node->stmts) . $this->nl . '}';
        }
    }

    protected function pStmt_Use(Stmt\Use_ $node) {
        return 'use ' . $this->pUseType($node->type)
             . $this->pCommaSeparated($node->uses) . ';';
    }

    protected function pStmt_GroupUse(Stmt\GroupUse $node) {
        return 'use ' . $this->pUseType($node->type) . $this->pName($node->prefix)
             . '\{' . $this->pCommaSeparated($node->uses) . '};';
    }

    protected function pStmt_UseUse(Stmt\UseUse $node) {
        return $this->pUseType($node->type) . $this->p($node->name)
             . (null !== $node->alias ? ' as ' . $node->alias : '');
    }

    protected function pUseType($type) {
        return $type === Stmt\Use_::TYPE_FUNCTION ? 'function '
            : ($type === Stmt\Use_::TYPE_CONSTANT ? 'const ' : '');
    }

    protected function pStmt_Interface(Stmt\Interface_ $node) {
        return $this->pAttrGroups($node->attrGroups)
             . 'interface ' . $node->name
             . (!empty($node->extends) ? ' extends ' . $this->pCommaSeparated($node->extends) : '')
             . $this->nl . '{' . $this->pStmts($node->stmts) . $this->nl . '}';
    }

    protected function pStmt_Enum(Stmt\Enum_ $node) {
        return $this->pAttrGroups($node->attrGroups)
             . 'enum ' . $node->name
             . ($node->scalarType ? " : $node->scalarType" : '')
             . (!empty($node->implements) ? ' implements ' . $this->pCommaSeparated($node->implements) : '')
             . $this->nl . '{' . $this->pStmts($node->stmts) . $this->nl . '}';
    }

    protected function pStmt_Class(Stmt\Class_ $node) {
        return $this->pClassCommon($node, ' ' . $node->name);
    }

    protected function pStmt_Trait(Stmt\Trait_ $node) {
        return $this->pAttrGroups($node->attrGroups)
             . 'trait ' . $node->name
             . $this->nl . '{' . $this->pStmts($node->stmts) . $this->nl . '}';
    }

    protected function pStmt_EnumCase(Stmt\EnumCase $node) {
        return $this->pAttrGroups($node->attrGroups)
             . 'case ' . $node->name
             . ($node->expr ? ' = ' . $this->p($node->expr) : '')
             . ';';
    }

    protected function pStmt_TraitUse(Stmt\TraitUse $node) {
        return 'use ' . $this->pCommaSeparated($node->traits)
             . (empty($node->adaptations)
                ? ';'
                : ' {' . $this->pStmts($node->adaptations) . $this->nl . '}');
    }

    protected function pStmt_TraitUseAdaptation_Precedence(Stmt\TraitUseAdaptation\Precedence $node) {
        return $this->p($node->trait) . '::' . $node->method
             . ' insteadof ' . $this->pCommaSeparated($node->insteadof) . ';';
    }

    protected function pStmt_TraitUseAdaptation_Alias(Stmt\TraitUseAdaptation\Alias $node) {
        return (null !== $node->trait ? $this->p($node->trait) . '::' : '')
             . $node->method . ' as'
             . (null !== $node->newModifier ? ' ' . rtrim($this->pModifiers($node->newModifier), ' ') : '')
             . (null !== $node->newName     ? ' ' . $node->newName                        : '')
             . ';';
    }

    protected function pStmt_Property(Stmt\Property $node) {
        return $this->pAttrGroups($node->attrGroups)
            . (0 === $node->flags ? 'var ' : $this->pModifiers($node->flags))
            . ($node->type ? $this->p($node->type) . ' ' : '')
            . $this->pCommaSeparated($node->props) . ';';
    }

    protected function pStmt_PropertyProperty(Stmt\PropertyProperty $node) {
        return '$' . $node->name
             . (null !== $node->default ? ' = ' . $this->p($node->default) : '');
    }

    protected function pStmt_ClassMethod(Stmt\ClassMethod $node) {
        return $this->pAttrGroups($node->attrGroups)
             . $this->pModifiers($node->flags)
             . 'function ' . ($node->byRef ? '&' : '') . $node->name
             . '(' . $this->pMaybeMultiline($node->params) . ')'
             . (null !== $node->returnType ? ' : ' . $this->p($node->returnType) : '')
             . (null !== $node->stmts
                ? $this->nl . '{' . $this->pStmts($node->stmts) . $this->nl . '}'
                : ';');
    }

    protected function pStmt_ClassConst(Stmt\ClassConst $node) {
        return $this->pAttrGroups($node->attrGroups)
             . $this->pModifiers($node->flags)
             . 'const ' . $this->pCommaSeparated($node->consts) . ';';
    }

    protected function pStmt_Function(Stmt\Function_ $node) {
        return $this->pAttrGroups($node->attrGroups)
             . 'function ' . ($node->byRef ? '&' : '') . $node->name
             . '(' . $this->pCommaSeparated($node->params) . ')'
             . (null !== $node->returnType ? ' : ' . $this->p($node->returnType) : '')
             . $this->nl . '{' . $this->pStmts($node->stmts) . $this->nl . '}';
    }

    protected function pStmt_Const(Stmt\Const_ $node) {
        return 'const ' . $this->pCommaSeparated($node->consts) . ';';
    }

    protected function pStmt_Declare(Stmt\Declare_ $node) {
        return 'declare (' . $this->pCommaSeparated($node->declares) . ')'
             . (null !== $node->stmts ? ' {' . $this->pStmts($node->stmts) . $this->nl . '}' : ';');
    }

    protected function pStmt_DeclareDeclare(Stmt\DeclareDeclare $node) {
        return $node->key . '=' . $this->p($node->value);
    }

    // Control flow

    protected function pStmt_If(Stmt\If_ $node) {
        return 'if (' . $this->p($node->cond) . ') {'
             . $this->pStmts($node->stmts) . $this->nl . '}'
             . ($node->elseifs ? ' ' . $this->pImplode($node->elseifs, ' ') : '')
             . (null !== $node->else ? ' ' . $this->p($node->else) : '');
    }

    protected function pStmt_ElseIf(Stmt\ElseIf_ $node) {
        return 'elseif (' . $this->p($node->cond) . ') {'
             . $this->pStmts($node->stmts) . $this->nl . '}';
    }

    protected function pStmt_Else(Stmt\Else_ $node) {
        return 'else {' . $this->pStmts($node->stmts) . $this->nl . '}';
    }

    protected function pStmt_For(Stmt\For_ $node) {
        return 'for ('
             . $this->pCommaSeparated($node->init) . ';' . (!empty($node->cond) ? ' ' : '')
             . $this->pCommaSeparated($node->cond) . ';' . (!empty($node->loop) ? ' ' : '')
             . $this->pCommaSeparated($node->loop)
             . ') {' . $this->pStmts($node->stmts) . $this->nl . '}';
    }

    protected function pStmt_Foreach(Stmt\Foreach_ $node) {
        return 'foreach (' . $this->p($node->expr) . ' as '
             . (null !== $node->keyVar ? $this->p($node->keyVar) . ' => ' : '')
             . ($node->byRef ? '&' : '') . $this->p($node->valueVar) . ') {'
             . $this->pStmts($node->stmts) . $this->nl . '}';
    }

    protected function pStmt_While(Stmt\While_ $node) {
        return 'while (' . $this->p($node->cond) . ') {'
             . $this->pStmts($node->stmts) . $this->nl . '}';
    }

    protected function pStmt_Do(Stmt\Do_ $node) {
        return 'do {' . $this->pStmts($node->stmts) . $this->nl
             . '} while (' . $this->p($node->cond) . ');';
    }

    protected function pStmt_Switch(Stmt\Switch_ $node) {
        return 'switch (' . $this->p($node->cond) . ') {'
             . $this->pStmts($node->cases) . $this->nl . '}';
    }

    protected function pStmt_Case(Stmt\Case_ $node) {
        return (null !== $node->cond ? 'case ' . $this->p($node->cond) : 'default') . ':'
             . $this->pStmts($node->stmts);
    }

    protected function pStmt_TryCatch(Stmt\TryCatch $node) {
        return 'try {' . $this->pStmts($node->stmts) . $this->nl . '}'
             . ($node->catches ? ' ' . $this->pImplode($node->catches, ' ') : '')
             . ($node->finally !== null ? ' ' . $this->p($node->finally) : '');
    }

    protected function pStmt_Catch(Stmt\Catch_ $node) {
        return 'catch (' . $this->pImplode($node->types, '|')
             . ($node->var !== null ? ' ' . $this->p($node->var) : '')
             . ') {' . $this->pStmts($node->stmts) . $this->nl . '}';
    }

    protected function pStmt_Finally(Stmt\Finally_ $node) {
        return 'finally {' . $this->pStmts($node->stmts) . $this->nl . '}';
    }

    protected function pStmt_Break(Stmt\Break_ $node) {
        return 'break' . ($node->num !== null ? ' ' . $this->p($node->num) : '') . ';';
    }

    protected function pStmt_Continue(Stmt\Continue_ $node) {
        return 'continue' . ($node->num !== null ? ' ' . $this->p($node->num) : '') . ';';
    }

    protected function pStmt_Return(Stmt\Return_ $node) {
        return 'return' . (null !== $node->expr ? ' ' . $this->p($node->expr) : '') . ';';
    }

    protected function pStmt_Throw(Stmt\Throw_ $node) {
        return 'throw ' . $this->p($node->expr) . ';';
    }

    protected function pStmt_Label(Stmt\Label $node) {
        return $node->name . ':';
    }

    protected function pStmt_Goto(Stmt\Goto_ $node) {
        return 'goto ' . $node->name . ';';
    }

    // Other

    protected function pStmt_Expression(Stmt\Expression $node) {
        return $this->p($node->expr) . ';';
    }

    protected function pStmt_Echo(Stmt\Echo_ $node) {
        return 'echo ' . $this->pCommaSeparated($node->exprs) . ';';
    }

    protected function pStmt_Static(Stmt\Static_ $node) {
        return 'static ' . $this->pCommaSeparated($node->vars) . ';';
    }

    protected function pStmt_Global(Stmt\Global_ $node) {
        return 'global ' . $this->pCommaSeparated($node->vars) . ';';
    }

    protected function pStmt_StaticVar(Stmt\StaticVar $node) {
        return $this->p($node->var)
             . (null !== $node->default ? ' = ' . $this->p($node->default) : '');
    }

    protected function pStmt_Unset(Stmt\Unset_ $node) {
        return 'unset(' . $this->pCommaSeparated($node->vars) . ');';
    }

    protected function pStmt_InlineHTML(Stmt\InlineHTML $node) {
        $newline = $node->getAttribute('hasLeadingNewline', true) ? "\n" : '';
        return '?>' . $newline . $node->value . '<?php ';
    }

    protected function pStmt_HaltCompiler(Stmt\HaltCompiler $node) {
        return '__halt_compiler();' . $node->remaining;
    }

    protected function pStmt_Nop(Stmt\Nop $node) {
        return '';
    }

    // Helpers

    protected function pClassCommon(Stmt\Class_ $node, $afterClassToken) {
        return $this->pAttrGroups($node->attrGroups, $node->name === null)
            . $this->pModifiers($node->flags)
            . 'class' . $afterClassToken
            . (null !== $node->extends ? ' extends ' . $this->p($node->extends) : '')
            . (!empty($node->implements) ? ' implements ' . $this->pCommaSeparated($node->implements) : '')
            . $this->nl . '{' . $this->pStmts($node->stmts) . $this->nl . '}';
    }

    protected function pObjectProperty($node) {
        if ($node instanceof Expr) {
            return '{' . $this->p($node) . '}';
        } else {
            return $node;
        }
    }

    protected function pEncapsList(array $encapsList, $quote) {
        $return = '';
        foreach ($encapsList as $element) {
            if ($element instanceof Scalar\EncapsedStringPart) {
                $return .= $this->escapeString($element->value, $quote);
            } else {
                $return .= '{' . $this->p($element) . '}';
            }
        }

        return $return;
    }

    protected function pSingleQuotedString(string $string) {
        return '\'' . addcslashes($string, '\'\\') . '\'';
    }

    protected function escapeString($string, $quote) {
        if (null === $quote) {
            // For doc strings, don't escape newlines
            $escaped = addcslashes($string, "\t\f\v$\\");
        } else {
            $escaped = addcslashes($string, "\n\r\t\f\v$" . $quote . "\\");
        }

        // Escape control characters and non-UTF-8 characters.
        // Regex based on https://stackoverflow.com/a/11709412/385378.
        $regex = '/(
              [\x00-\x08\x0E-\x1F] # Control characters
            | [\xC0-\xC1] # Invalid UTF-8 Bytes
            | [\xF5-\xFF] # Invalid UTF-8 Bytes
            | \xE0(?=[\x80-\x9F]) # Overlong encoding of prior code point
            | \xF0(?=[\x80-\x8F]) # Overlong encoding of prior code point
            | [\xC2-\xDF](?![\x80-\xBF]) # Invalid UTF-8 Sequence Start
            | [\xE0-\xEF](?![\x80-\xBF]{2}) # Invalid UTF-8 Sequence Start
            | [\xF0-\xF4](?![\x80-\xBF]{3}) # Invalid UTF-8 Sequence Start
            | (?<=[\x00-\x7F\xF5-\xFF])[\x80-\xBF] # Invalid UTF-8 Sequence Middle
            | (?<![\xC2-\xDF]|[\xE0-\xEF]|[\xE0-\xEF][\x80-\xBF]|[\xF0-\xF4]|[\xF0-\xF4][\x80-\xBF]|[\xF0-\xF4][\x80-\xBF]{2})[\x80-\xBF] # Overlong Sequence
            | (?<=[\xE0-\xEF])[\x80-\xBF](?![\x80-\xBF]) # Short 3 byte sequence
            | (?<=[\xF0-\xF4])[\x80-\xBF](?![\x80-\xBF]{2}) # Short 4 byte sequence
            | (?<=[\xF0-\xF4][\x80-\xBF])[\x80-\xBF](?![\x80-\xBF]) # Short 4 byte sequence (2)
        )/x';
        return preg_replace_callback($regex, function ($matches) {
            assert(strlen($matches[0]) === 1);
            $hex = dechex(ord($matches[0]));;
            return '\\x' . str_pad($hex, 2, '0', \STR_PAD_LEFT);
        }, $escaped);
    }

    protected function containsEndLabel($string, $label, $atStart = true, $atEnd = true) {
        $start = $atStart ? '(?:^|[\r\n])' : '[\r\n]';
        $end = $atEnd ? '(?:$|[;\r\n])' : '[;\r\n]';
        return false !== strpos($string, $label)
            && preg_match('/' . $start . $label . $end . '/', $string);
    }

    protected function encapsedContainsEndLabel(array $parts, $label) {
        foreach ($parts as $i => $part) {
            $atStart = $i === 0;
            $atEnd = $i === count($parts) - 1;
            if ($part instanceof Scalar\EncapsedStringPart
                && $this->containsEndLabel($part->value, $label, $atStart, $atEnd)
            ) {
                return true;
            }
        }
        return false;
    }

    protected function pDereferenceLhs(Node $node) {
        if (!$this->dereferenceLhsRequiresParens($node)) {
            return $this->p($node);
        } else  {
            return '(' . $this->p($node) . ')';
        }
    }

    protected function pCallLhs(Node $node) {
        if (!$this->callLhsRequiresParens($node)) {
            return $this->p($node);
        } else  {
            return '(' . $this->p($node) . ')';
        }
    }

    protected function pNewVariable(Node $node) {
        // TODO: This is not fully accurate.
        return $this->pDereferenceLhs($node);
    }

    /**
     * @param Node[] $nodes
     * @return bool
     */
    protected function hasNodeWithComments(array $nodes) {
        foreach ($nodes as $node) {
            if ($node && $node->getComments()) {
                return true;
            }
        }
        return false;
    }

    protected function pMaybeMultiline(array $nodes, bool $trailingComma = false) {
        if (!$this->hasNodeWithComments($nodes)) {
            return $this->pCommaSeparated($nodes);
        } else {
            return $this->pCommaSeparatedMultiline($nodes, $trailingComma) . $this->nl;
        }
    }

    protected function pAttrGroups(array $nodes, bool $inline = false): string {
        $result = '';
        $sep = $inline ? ' ' : $this->nl;
        foreach ($nodes as $node) {
            $result .= $this->p($node) . $sep;
        }

        return $result;
    }
}
