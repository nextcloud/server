%pure_parser
%expect 2

%tokens

%%

start:
    top_statement_list                                      { $$ = $this->handleNamespaces($1); }
;

top_statement_list_ex:
      top_statement_list_ex top_statement                   { pushNormalizing($1, $2); }
    | /* empty */                                           { init(); }
;

top_statement_list:
      top_statement_list_ex
          { makeZeroLengthNop($nop, $this->lookaheadStartAttributes);
            if ($nop !== null) { $1[] = $nop; } $$ = $1; }
;

reserved_non_modifiers:
      T_INCLUDE | T_INCLUDE_ONCE | T_EVAL | T_REQUIRE | T_REQUIRE_ONCE | T_LOGICAL_OR | T_LOGICAL_XOR | T_LOGICAL_AND
    | T_INSTANCEOF | T_NEW | T_CLONE | T_EXIT | T_IF | T_ELSEIF | T_ELSE | T_ENDIF | T_ECHO | T_DO | T_WHILE
    | T_ENDWHILE | T_FOR | T_ENDFOR | T_FOREACH | T_ENDFOREACH | T_DECLARE | T_ENDDECLARE | T_AS | T_TRY | T_CATCH
    | T_FINALLY | T_THROW | T_USE | T_INSTEADOF | T_GLOBAL | T_VAR | T_UNSET | T_ISSET | T_EMPTY | T_CONTINUE | T_GOTO
    | T_FUNCTION | T_CONST | T_RETURN | T_PRINT | T_YIELD | T_LIST | T_SWITCH | T_ENDSWITCH | T_CASE | T_DEFAULT
    | T_BREAK | T_ARRAY | T_CALLABLE | T_EXTENDS | T_IMPLEMENTS | T_NAMESPACE | T_TRAIT | T_INTERFACE | T_CLASS
    | T_CLASS_C | T_TRAIT_C | T_FUNC_C | T_METHOD_C | T_LINE | T_FILE | T_DIR | T_NS_C | T_HALT_COMPILER | T_FN
    | T_MATCH
;

semi_reserved:
      reserved_non_modifiers
    | T_STATIC | T_ABSTRACT | T_FINAL | T_PRIVATE | T_PROTECTED | T_PUBLIC
;

identifier_ex:
      T_STRING                                              { $$ = Node\Identifier[$1]; }
    | semi_reserved                                         { $$ = Node\Identifier[$1]; }
;

identifier:
      T_STRING                                              { $$ = Node\Identifier[$1]; }
;

reserved_non_modifiers_identifier:
      reserved_non_modifiers                                { $$ = Node\Identifier[$1]; }
;

namespace_declaration_name:
      T_STRING                                              { $$ = Name[$1]; }
    | semi_reserved                                         { $$ = Name[$1]; }
    | T_NAME_QUALIFIED                                      { $$ = Name[$1]; }
;

namespace_name:
      T_STRING                                              { $$ = Name[$1]; }
    | T_NAME_QUALIFIED                                      { $$ = Name[$1]; }
;

legacy_namespace_name:
      namespace_name                                        { $$ = $1; }
    | T_NAME_FULLY_QUALIFIED                                { $$ = Name[substr($1, 1)]; }
;

plain_variable:
      T_VARIABLE                                            { $$ = Expr\Variable[parseVar($1)]; }
;

semi:
      ';'                                                   { /* nothing */ }
    | error                                                 { /* nothing */ }
;

no_comma:
      /* empty */ { /* nothing */ }
    | ',' { $this->emitError(new Error('A trailing comma is not allowed here', attributes())); }
;

optional_comma:
      /* empty */
    | ','
;

attribute_decl:
      class_name                                            { $$ = Node\Attribute[$1, []]; }
    | class_name argument_list                              { $$ = Node\Attribute[$1, $2]; }
;

attribute_group:
      attribute_decl                                        { init($1); }
    | attribute_group ',' attribute_decl                    { push($1, $3); }
;

attribute:
      T_ATTRIBUTE attribute_group optional_comma ']'        { $$ = Node\AttributeGroup[$2]; }
;

attributes:
      attribute                                             { init($1); }
    | attributes attribute                                  { push($1, $2); }
;

optional_attributes:
      /* empty */                                           { $$ = []; }
    | attributes                                            { $$ = $1; }
;

top_statement:
      statement                                             { $$ = $1; }
    | function_declaration_statement                        { $$ = $1; }
    | class_declaration_statement                           { $$ = $1; }
    | T_HALT_COMPILER
          { $$ = Stmt\HaltCompiler[$this->lexer->handleHaltCompiler()]; }
    | T_NAMESPACE namespace_declaration_name semi
          { $$ = Stmt\Namespace_[$2, null];
            $$->setAttribute('kind', Stmt\Namespace_::KIND_SEMICOLON);
            $this->checkNamespace($$); }
    | T_NAMESPACE namespace_declaration_name '{' top_statement_list '}'
          { $$ = Stmt\Namespace_[$2, $4];
            $$->setAttribute('kind', Stmt\Namespace_::KIND_BRACED);
            $this->checkNamespace($$); }
    | T_NAMESPACE '{' top_statement_list '}'
          { $$ = Stmt\Namespace_[null, $3];
            $$->setAttribute('kind', Stmt\Namespace_::KIND_BRACED);
            $this->checkNamespace($$); }
    | T_USE use_declarations semi                           { $$ = Stmt\Use_[$2, Stmt\Use_::TYPE_NORMAL]; }
    | T_USE use_type use_declarations semi                  { $$ = Stmt\Use_[$3, $2]; }
    | group_use_declaration semi                            { $$ = $1; }
    | T_CONST constant_declaration_list semi                { $$ = Stmt\Const_[$2]; }
;

use_type:
      T_FUNCTION                                            { $$ = Stmt\Use_::TYPE_FUNCTION; }
    | T_CONST                                               { $$ = Stmt\Use_::TYPE_CONSTANT; }
;

group_use_declaration:
      T_USE use_type legacy_namespace_name T_NS_SEPARATOR '{' unprefixed_use_declarations '}'
          { $$ = Stmt\GroupUse[$3, $6, $2]; }
    | T_USE legacy_namespace_name T_NS_SEPARATOR '{' inline_use_declarations '}'
          { $$ = Stmt\GroupUse[$2, $5, Stmt\Use_::TYPE_UNKNOWN]; }
;

unprefixed_use_declarations:
      non_empty_unprefixed_use_declarations optional_comma  { $$ = $1; }
;

non_empty_unprefixed_use_declarations:
      non_empty_unprefixed_use_declarations ',' unprefixed_use_declaration
          { push($1, $3); }
    | unprefixed_use_declaration                            { init($1); }
;

use_declarations:
      non_empty_use_declarations no_comma                   { $$ = $1; }
;

non_empty_use_declarations:
      non_empty_use_declarations ',' use_declaration        { push($1, $3); }
    | use_declaration                                       { init($1); }
;

inline_use_declarations:
      non_empty_inline_use_declarations optional_comma      { $$ = $1; }
;

non_empty_inline_use_declarations:
      non_empty_inline_use_declarations ',' inline_use_declaration
          { push($1, $3); }
    | inline_use_declaration                                { init($1); }
;

unprefixed_use_declaration:
      namespace_name
          { $$ = Stmt\UseUse[$1, null, Stmt\Use_::TYPE_UNKNOWN]; $this->checkUseUse($$, #1); }
    | namespace_name T_AS identifier
          { $$ = Stmt\UseUse[$1, $3, Stmt\Use_::TYPE_UNKNOWN]; $this->checkUseUse($$, #3); }
;

use_declaration:
      legacy_namespace_name
          { $$ = Stmt\UseUse[$1, null, Stmt\Use_::TYPE_UNKNOWN]; $this->checkUseUse($$, #1); }
    | legacy_namespace_name T_AS identifier
          { $$ = Stmt\UseUse[$1, $3, Stmt\Use_::TYPE_UNKNOWN]; $this->checkUseUse($$, #3); }
;

inline_use_declaration:
      unprefixed_use_declaration                            { $$ = $1; $$->type = Stmt\Use_::TYPE_NORMAL; }
    | use_type unprefixed_use_declaration                   { $$ = $2; $$->type = $1; }
;

constant_declaration_list:
      non_empty_constant_declaration_list no_comma          { $$ = $1; }
;

non_empty_constant_declaration_list:
      non_empty_constant_declaration_list ',' constant_declaration
          { push($1, $3); }
    | constant_declaration                                  { init($1); }
;

constant_declaration:
    identifier '=' expr                                     { $$ = Node\Const_[$1, $3]; }
;

class_const_list:
      non_empty_class_const_list no_comma                   { $$ = $1; }
;

non_empty_class_const_list:
      non_empty_class_const_list ',' class_const            { push($1, $3); }
    | class_const                                           { init($1); }
;

class_const:
    identifier_ex '=' expr                                  { $$ = Node\Const_[$1, $3]; }
;

inner_statement_list_ex:
      inner_statement_list_ex inner_statement               { pushNormalizing($1, $2); }
    | /* empty */                                           { init(); }
;

inner_statement_list:
      inner_statement_list_ex
          { makeZeroLengthNop($nop, $this->lookaheadStartAttributes);
            if ($nop !== null) { $1[] = $nop; } $$ = $1; }
;

inner_statement:
      statement                                             { $$ = $1; }
    | function_declaration_statement                        { $$ = $1; }
    | class_declaration_statement                           { $$ = $1; }
    | T_HALT_COMPILER
          { throw new Error('__HALT_COMPILER() can only be used from the outermost scope', attributes()); }
;

non_empty_statement:
      '{' inner_statement_list '}'
    {
        if ($2) {
            $$ = $2; prependLeadingComments($$);
        } else {
            makeNop($$, $this->startAttributeStack[#1], $this->endAttributes);
            if (null === $$) { $$ = array(); }
        }
    }
    | T_IF '(' expr ')' statement elseif_list else_single
          { $$ = Stmt\If_[$3, ['stmts' => toArray($5), 'elseifs' => $6, 'else' => $7]]; }
    | T_IF '(' expr ')' ':' inner_statement_list new_elseif_list new_else_single T_ENDIF ';'
          { $$ = Stmt\If_[$3, ['stmts' => $6, 'elseifs' => $7, 'else' => $8]]; }
    | T_WHILE '(' expr ')' while_statement                  { $$ = Stmt\While_[$3, $5]; }
    | T_DO statement T_WHILE '(' expr ')' ';'               { $$ = Stmt\Do_   [$5, toArray($2)]; }
    | T_FOR '(' for_expr ';'  for_expr ';' for_expr ')' for_statement
          { $$ = Stmt\For_[['init' => $3, 'cond' => $5, 'loop' => $7, 'stmts' => $9]]; }
    | T_SWITCH '(' expr ')' switch_case_list                { $$ = Stmt\Switch_[$3, $5]; }
    | T_BREAK optional_expr semi                            { $$ = Stmt\Break_[$2]; }
    | T_CONTINUE optional_expr semi                         { $$ = Stmt\Continue_[$2]; }
    | T_RETURN optional_expr semi                           { $$ = Stmt\Return_[$2]; }
    | T_GLOBAL global_var_list semi                         { $$ = Stmt\Global_[$2]; }
    | T_STATIC static_var_list semi                         { $$ = Stmt\Static_[$2]; }
    | T_ECHO expr_list_forbid_comma semi                    { $$ = Stmt\Echo_[$2]; }
    | T_INLINE_HTML                                         { $$ = Stmt\InlineHTML[$1]; }
    | expr semi {
        $e = $1;
        if ($e instanceof Expr\Throw_) {
            // For backwards-compatibility reasons, convert throw in statement position into
            // Stmt\Throw_ rather than Stmt\Expression(Expr\Throw_).
            $$ = Stmt\Throw_[$e->expr];
        } else {
            $$ = Stmt\Expression[$e];
        }
    }
    | T_UNSET '(' variables_list ')' semi                   { $$ = Stmt\Unset_[$3]; }
    | T_FOREACH '(' expr T_AS foreach_variable ')' foreach_statement
          { $$ = Stmt\Foreach_[$3, $5[0], ['keyVar' => null, 'byRef' => $5[1], 'stmts' => $7]]; }
    | T_FOREACH '(' expr T_AS variable T_DOUBLE_ARROW foreach_variable ')' foreach_statement
          { $$ = Stmt\Foreach_[$3, $7[0], ['keyVar' => $5, 'byRef' => $7[1], 'stmts' => $9]]; }
    | T_FOREACH '(' expr error ')' foreach_statement
          { $$ = Stmt\Foreach_[$3, new Expr\Error(stackAttributes(#4)), ['stmts' => $6]]; }
    | T_DECLARE '(' declare_list ')' declare_statement      { $$ = Stmt\Declare_[$3, $5]; }
    | T_TRY '{' inner_statement_list '}' catches optional_finally
          { $$ = Stmt\TryCatch[$3, $5, $6]; $this->checkTryCatch($$); }
    | T_GOTO identifier semi                                { $$ = Stmt\Goto_[$2]; }
    | identifier ':'                                        { $$ = Stmt\Label[$1]; }
    | error                                                 { $$ = array(); /* means: no statement */ }
;

statement:
      non_empty_statement                                   { $$ = $1; }
    | ';'
          { makeNop($$, $this->startAttributeStack[#1], $this->endAttributes);
            if ($$ === null) $$ = array(); /* means: no statement */ }
;

catches:
      /* empty */                                           { init(); }
    | catches catch                                         { push($1, $2); }
;

name_union:
      name                                                  { init($1); }
    | name_union '|' name                                   { push($1, $3); }
;

catch:
    T_CATCH '(' name_union optional_plain_variable ')' '{' inner_statement_list '}'
        { $$ = Stmt\Catch_[$3, $4, $7]; }
;

optional_finally:
      /* empty */                                           { $$ = null; }
    | T_FINALLY '{' inner_statement_list '}'                { $$ = Stmt\Finally_[$3]; }
;

variables_list:
      non_empty_variables_list optional_comma               { $$ = $1; }
;

non_empty_variables_list:
      variable                                              { init($1); }
    | non_empty_variables_list ',' variable                 { push($1, $3); }
;

optional_ref:
      /* empty */                                           { $$ = false; }
    | '&'                                                   { $$ = true; }
;

optional_ellipsis:
      /* empty */                                           { $$ = false; }
    | T_ELLIPSIS                                            { $$ = true; }
;

block_or_error:
      '{' inner_statement_list '}'                          { $$ = $2; }
    | error                                                 { $$ = []; }
;

function_declaration_statement:
      T_FUNCTION optional_ref identifier '(' parameter_list ')' optional_return_type block_or_error
          { $$ = Stmt\Function_[$3, ['byRef' => $2, 'params' => $5, 'returnType' => $7, 'stmts' => $8, 'attrGroups' => []]]; }
    | attributes T_FUNCTION optional_ref identifier '(' parameter_list ')' optional_return_type block_or_error
          { $$ = Stmt\Function_[$4, ['byRef' => $3, 'params' => $6, 'returnType' => $8, 'stmts' => $9, 'attrGroups' => $1]]; }
;

class_declaration_statement:
      class_entry_type identifier extends_from implements_list '{' class_statement_list '}'
          { $$ = Stmt\Class_[$2, ['type' => $1, 'extends' => $3, 'implements' => $4, 'stmts' => $6, 'attrGroups' => []]];
            $this->checkClass($$, #2); }
    | attributes class_entry_type identifier extends_from implements_list '{' class_statement_list '}'
          { $$ = Stmt\Class_[$3, ['type' => $2, 'extends' => $4, 'implements' => $5, 'stmts' => $7, 'attrGroups' => $1]];
            $this->checkClass($$, #3); }
    | optional_attributes T_INTERFACE identifier interface_extends_list '{' class_statement_list '}'
          { $$ = Stmt\Interface_[$3, ['extends' => $4, 'stmts' => $6, 'attrGroups' => $1]];
            $this->checkInterface($$, #3); }
    | optional_attributes T_TRAIT identifier '{' class_statement_list '}'
          { $$ = Stmt\Trait_[$3, ['stmts' => $5, 'attrGroups' => $1]]; }
;

class_entry_type:
      T_CLASS                                               { $$ = 0; }
    | T_ABSTRACT T_CLASS                                    { $$ = Stmt\Class_::MODIFIER_ABSTRACT; }
    | T_FINAL T_CLASS                                       { $$ = Stmt\Class_::MODIFIER_FINAL; }
;

extends_from:
      /* empty */                                           { $$ = null; }
    | T_EXTENDS class_name                                  { $$ = $2; }
;

interface_extends_list:
      /* empty */                                           { $$ = array(); }
    | T_EXTENDS class_name_list                             { $$ = $2; }
;

implements_list:
      /* empty */                                           { $$ = array(); }
    | T_IMPLEMENTS class_name_list                          { $$ = $2; }
;

class_name_list:
      non_empty_class_name_list no_comma                    { $$ = $1; }
;

non_empty_class_name_list:
      class_name                                            { init($1); }
    | non_empty_class_name_list ',' class_name              { push($1, $3); }
;

for_statement:
      statement                                             { $$ = toArray($1); }
    | ':' inner_statement_list T_ENDFOR ';'                 { $$ = $2; }
;

foreach_statement:
      statement                                             { $$ = toArray($1); }
    | ':' inner_statement_list T_ENDFOREACH ';'             { $$ = $2; }
;

declare_statement:
      non_empty_statement                                   { $$ = toArray($1); }
    | ';'                                                   { $$ = null; }
    | ':' inner_statement_list T_ENDDECLARE ';'             { $$ = $2; }
;

declare_list:
      non_empty_declare_list no_comma                       { $$ = $1; }
;

non_empty_declare_list:
      declare_list_element                                  { init($1); }
    | non_empty_declare_list ',' declare_list_element       { push($1, $3); }
;

declare_list_element:
      identifier '=' expr                                   { $$ = Stmt\DeclareDeclare[$1, $3]; }
;

switch_case_list:
      '{' case_list '}'                                     { $$ = $2; }
    | '{' ';' case_list '}'                                 { $$ = $3; }
    | ':' case_list T_ENDSWITCH ';'                         { $$ = $2; }
    | ':' ';' case_list T_ENDSWITCH ';'                     { $$ = $3; }
;

case_list:
      /* empty */                                           { init(); }
    | case_list case                                        { push($1, $2); }
;

case:
      T_CASE expr case_separator inner_statement_list_ex    { $$ = Stmt\Case_[$2, $4]; }
    | T_DEFAULT case_separator inner_statement_list_ex      { $$ = Stmt\Case_[null, $3]; }
;

case_separator:
      ':'
    | ';'
;

match:
      T_MATCH '(' expr ')' '{' match_arm_list '}'           { $$ = Expr\Match_[$3, $6]; }
;

match_arm_list:
      /* empty */                                           { $$ = []; }
    | non_empty_match_arm_list optional_comma               { $$ = $1; }
;

non_empty_match_arm_list:
      match_arm                                             { init($1); }
    | non_empty_match_arm_list ',' match_arm                { push($1, $3); }
;

match_arm:
      expr_list_allow_comma T_DOUBLE_ARROW expr             { $$ = Node\MatchArm[$1, $3]; }
    | T_DEFAULT optional_comma T_DOUBLE_ARROW expr          { $$ = Node\MatchArm[null, $4]; }
;

while_statement:
      statement                                             { $$ = toArray($1); }
    | ':' inner_statement_list T_ENDWHILE ';'               { $$ = $2; }
;

elseif_list:
      /* empty */                                           { init(); }
    | elseif_list elseif                                    { push($1, $2); }
;

elseif:
      T_ELSEIF '(' expr ')' statement                       { $$ = Stmt\ElseIf_[$3, toArray($5)]; }
;

new_elseif_list:
      /* empty */                                           { init(); }
    | new_elseif_list new_elseif                            { push($1, $2); }
;

new_elseif:
     T_ELSEIF '(' expr ')' ':' inner_statement_list         { $$ = Stmt\ElseIf_[$3, $6]; }
;

else_single:
      /* empty */                                           { $$ = null; }
    | T_ELSE statement                                      { $$ = Stmt\Else_[toArray($2)]; }
;

new_else_single:
      /* empty */                                           { $$ = null; }
    | T_ELSE ':' inner_statement_list                       { $$ = Stmt\Else_[$3]; }
;

foreach_variable:
      variable                                              { $$ = array($1, false); }
    | '&' variable                                          { $$ = array($2, true); }
    | list_expr                                             { $$ = array($1, false); }
    | array_short_syntax                                    { $$ = array($1, false); }
;

parameter_list:
      non_empty_parameter_list optional_comma               { $$ = $1; }
    | /* empty */                                           { $$ = array(); }
;

non_empty_parameter_list:
      parameter                                             { init($1); }
    | non_empty_parameter_list ',' parameter                { push($1, $3); }
;

optional_visibility_modifier:
      /* empty */               { $$ = 0; }
    | T_PUBLIC                  { $$ = Stmt\Class_::MODIFIER_PUBLIC; }
    | T_PROTECTED               { $$ = Stmt\Class_::MODIFIER_PROTECTED; }
    | T_PRIVATE                 { $$ = Stmt\Class_::MODIFIER_PRIVATE; }
;

parameter:
      optional_attributes optional_visibility_modifier optional_type_without_static optional_ref optional_ellipsis plain_variable
          { $$ = new Node\Param($6, null, $3, $4, $5, attributes(), $2, $1);
            $this->checkParam($$); }
    | optional_attributes optional_visibility_modifier optional_type_without_static optional_ref optional_ellipsis plain_variable '=' expr
          { $$ = new Node\Param($6, $8, $3, $4, $5, attributes(), $2, $1);
            $this->checkParam($$); }
    | optional_attributes optional_visibility_modifier optional_type_without_static optional_ref optional_ellipsis error
          { $$ = new Node\Param(Expr\Error[], null, $3, $4, $5, attributes(), $2, $1); }
;

type_expr:
      type                                                  { $$ = $1; }
    | '?' type                                              { $$ = Node\NullableType[$2]; }
    | union_type                                            { $$ = Node\UnionType[$1]; }
;

type:
      type_without_static                                   { $$ = $1; }
    | T_STATIC                                              { $$ = Node\Name['static']; }
;

type_without_static:
      name                                                  { $$ = $this->handleBuiltinTypes($1); }
    | T_ARRAY                                               { $$ = Node\Identifier['array']; }
    | T_CALLABLE                                            { $$ = Node\Identifier['callable']; }
;

union_type:
      type '|' type                                         { init($1, $3); }
    | union_type '|' type                                   { push($1, $3); }
;

union_type_without_static:
      type_without_static '|' type_without_static           { init($1, $3); }
    | union_type_without_static '|' type_without_static     { push($1, $3); }
;

type_expr_without_static:
      type_without_static                                   { $$ = $1; }
    | '?' type_without_static                               { $$ = Node\NullableType[$2]; }
    | union_type_without_static                             { $$ = Node\UnionType[$1]; }
;

optional_type_without_static:
      /* empty */                                           { $$ = null; }
    | type_expr_without_static                              { $$ = $1; }
;

optional_return_type:
      /* empty */                                           { $$ = null; }
    | ':' type_expr                                         { $$ = $2; }
    | ':' error                                             { $$ = null; }
;

argument_list:
      '(' ')'                                               { $$ = array(); }
    | '(' non_empty_argument_list optional_comma ')'        { $$ = $2; }
;

non_empty_argument_list:
      argument                                              { init($1); }
    | non_empty_argument_list ',' argument                  { push($1, $3); }
;

argument:
      expr                                                  { $$ = Node\Arg[$1, false, false]; }
    | '&' variable                                          { $$ = Node\Arg[$2, true, false]; }
    | T_ELLIPSIS expr                                       { $$ = Node\Arg[$2, false, true]; }
    | identifier_ex ':' expr
          { $$ = new Node\Arg($3, false, false, attributes(), $1); }
;

global_var_list:
      non_empty_global_var_list no_comma                    { $$ = $1; }
;

non_empty_global_var_list:
      non_empty_global_var_list ',' global_var              { push($1, $3); }
    | global_var                                            { init($1); }
;

global_var:
      simple_variable                                       { $$ = Expr\Variable[$1]; }
;

static_var_list:
      non_empty_static_var_list no_comma                    { $$ = $1; }
;

non_empty_static_var_list:
      non_empty_static_var_list ',' static_var              { push($1, $3); }
    | static_var                                            { init($1); }
;

static_var:
      plain_variable                                        { $$ = Stmt\StaticVar[$1, null]; }
    | plain_variable '=' expr                               { $$ = Stmt\StaticVar[$1, $3]; }
;

class_statement_list_ex:
      class_statement_list_ex class_statement               { if ($2 !== null) { push($1, $2); } }
    | /* empty */                                           { init(); }
;

class_statement_list:
      class_statement_list_ex
          { makeZeroLengthNop($nop, $this->lookaheadStartAttributes);
            if ($nop !== null) { $1[] = $nop; } $$ = $1; }
;

class_statement:
      optional_attributes variable_modifiers optional_type_without_static property_declaration_list semi
          { $$ = new Stmt\Property($2, $4, attributes(), $3, $1);
            $this->checkProperty($$, #2); }
    | optional_attributes method_modifiers T_CONST class_const_list semi
          { $$ = new Stmt\ClassConst($4, $2, attributes(), $1);
            $this->checkClassConst($$, #2); }
    | optional_attributes method_modifiers T_FUNCTION optional_ref identifier_ex '(' parameter_list ')' optional_return_type method_body
          { $$ = Stmt\ClassMethod[$5, ['type' => $2, 'byRef' => $4, 'params' => $7, 'returnType' => $9, 'stmts' => $10, 'attrGroups' => $1]];
            $this->checkClassMethod($$, #2); }
    | T_USE class_name_list trait_adaptations               { $$ = Stmt\TraitUse[$2, $3]; }
    | error                                                 { $$ = null; /* will be skipped */ }
;

trait_adaptations:
      ';'                                                   { $$ = array(); }
    | '{' trait_adaptation_list '}'                         { $$ = $2; }
;

trait_adaptation_list:
      /* empty */                                           { init(); }
    | trait_adaptation_list trait_adaptation                { push($1, $2); }
;

trait_adaptation:
      trait_method_reference_fully_qualified T_INSTEADOF class_name_list ';'
          { $$ = Stmt\TraitUseAdaptation\Precedence[$1[0], $1[1], $3]; }
    | trait_method_reference T_AS member_modifier identifier_ex ';'
          { $$ = Stmt\TraitUseAdaptation\Alias[$1[0], $1[1], $3, $4]; }
    | trait_method_reference T_AS member_modifier ';'
          { $$ = Stmt\TraitUseAdaptation\Alias[$1[0], $1[1], $3, null]; }
    | trait_method_reference T_AS identifier ';'
          { $$ = Stmt\TraitUseAdaptation\Alias[$1[0], $1[1], null, $3]; }
    | trait_method_reference T_AS reserved_non_modifiers_identifier ';'
          { $$ = Stmt\TraitUseAdaptation\Alias[$1[0], $1[1], null, $3]; }
;

trait_method_reference_fully_qualified:
      name T_PAAMAYIM_NEKUDOTAYIM identifier_ex             { $$ = array($1, $3); }
;
trait_method_reference:
      trait_method_reference_fully_qualified                { $$ = $1; }
    | identifier_ex                                         { $$ = array(null, $1); }
;

method_body:
      ';' /* abstract method */                             { $$ = null; }
    | block_or_error                                        { $$ = $1; }
;

variable_modifiers:
      non_empty_member_modifiers                            { $$ = $1; }
    | T_VAR                                                 { $$ = 0; }
;

method_modifiers:
      /* empty */                                           { $$ = 0; }
    | non_empty_member_modifiers                            { $$ = $1; }
;

non_empty_member_modifiers:
      member_modifier                                       { $$ = $1; }
    | non_empty_member_modifiers member_modifier            { $this->checkModifier($1, $2, #2); $$ = $1 | $2; }
;

member_modifier:
      T_PUBLIC                                              { $$ = Stmt\Class_::MODIFIER_PUBLIC; }
    | T_PROTECTED                                           { $$ = Stmt\Class_::MODIFIER_PROTECTED; }
    | T_PRIVATE                                             { $$ = Stmt\Class_::MODIFIER_PRIVATE; }
    | T_STATIC                                              { $$ = Stmt\Class_::MODIFIER_STATIC; }
    | T_ABSTRACT                                            { $$ = Stmt\Class_::MODIFIER_ABSTRACT; }
    | T_FINAL                                               { $$ = Stmt\Class_::MODIFIER_FINAL; }
;

property_declaration_list:
      non_empty_property_declaration_list no_comma          { $$ = $1; }
;

non_empty_property_declaration_list:
      property_declaration                                  { init($1); }
    | non_empty_property_declaration_list ',' property_declaration
          { push($1, $3); }
;

property_decl_name:
      T_VARIABLE                                            { $$ = Node\VarLikeIdentifier[parseVar($1)]; }
;

property_declaration:
      property_decl_name                                    { $$ = Stmt\PropertyProperty[$1, null]; }
    | property_decl_name '=' expr                           { $$ = Stmt\PropertyProperty[$1, $3]; }
;

expr_list_forbid_comma:
      non_empty_expr_list no_comma                          { $$ = $1; }
;

expr_list_allow_comma:
      non_empty_expr_list optional_comma                    { $$ = $1; }
;

non_empty_expr_list:
      non_empty_expr_list ',' expr                          { push($1, $3); }
    | expr                                                  { init($1); }
;

for_expr:
      /* empty */                                           { $$ = array(); }
    | expr_list_forbid_comma                                { $$ = $1; }
;

expr:
      variable                                              { $$ = $1; }
    | list_expr '=' expr                                    { $$ = Expr\Assign[$1, $3]; }
    | array_short_syntax '=' expr                           { $$ = Expr\Assign[$1, $3]; }
    | variable '=' expr                                     { $$ = Expr\Assign[$1, $3]; }
    | variable '=' '&' variable                             { $$ = Expr\AssignRef[$1, $4]; }
    | new_expr                                              { $$ = $1; }
    | match                                                 { $$ = $1; }
    | T_CLONE expr                                          { $$ = Expr\Clone_[$2]; }
    | variable T_PLUS_EQUAL expr                            { $$ = Expr\AssignOp\Plus      [$1, $3]; }
    | variable T_MINUS_EQUAL expr                           { $$ = Expr\AssignOp\Minus     [$1, $3]; }
    | variable T_MUL_EQUAL expr                             { $$ = Expr\AssignOp\Mul       [$1, $3]; }
    | variable T_DIV_EQUAL expr                             { $$ = Expr\AssignOp\Div       [$1, $3]; }
    | variable T_CONCAT_EQUAL expr                          { $$ = Expr\AssignOp\Concat    [$1, $3]; }
    | variable T_MOD_EQUAL expr                             { $$ = Expr\AssignOp\Mod       [$1, $3]; }
    | variable T_AND_EQUAL expr                             { $$ = Expr\AssignOp\BitwiseAnd[$1, $3]; }
    | variable T_OR_EQUAL expr                              { $$ = Expr\AssignOp\BitwiseOr [$1, $3]; }
    | variable T_XOR_EQUAL expr                             { $$ = Expr\AssignOp\BitwiseXor[$1, $3]; }
    | variable T_SL_EQUAL expr                              { $$ = Expr\AssignOp\ShiftLeft [$1, $3]; }
    | variable T_SR_EQUAL expr                              { $$ = Expr\AssignOp\ShiftRight[$1, $3]; }
    | variable T_POW_EQUAL expr                             { $$ = Expr\AssignOp\Pow       [$1, $3]; }
    | variable T_COALESCE_EQUAL expr                        { $$ = Expr\AssignOp\Coalesce  [$1, $3]; }
    | variable T_INC                                        { $$ = Expr\PostInc[$1]; }
    | T_INC variable                                        { $$ = Expr\PreInc [$2]; }
    | variable T_DEC                                        { $$ = Expr\PostDec[$1]; }
    | T_DEC variable                                        { $$ = Expr\PreDec [$2]; }
    | expr T_BOOLEAN_OR expr                                { $$ = Expr\BinaryOp\BooleanOr [$1, $3]; }
    | expr T_BOOLEAN_AND expr                               { $$ = Expr\BinaryOp\BooleanAnd[$1, $3]; }
    | expr T_LOGICAL_OR expr                                { $$ = Expr\BinaryOp\LogicalOr [$1, $3]; }
    | expr T_LOGICAL_AND expr                               { $$ = Expr\BinaryOp\LogicalAnd[$1, $3]; }
    | expr T_LOGICAL_XOR expr                               { $$ = Expr\BinaryOp\LogicalXor[$1, $3]; }
    | expr '|' expr                                         { $$ = Expr\BinaryOp\BitwiseOr [$1, $3]; }
    | expr '&' expr                                         { $$ = Expr\BinaryOp\BitwiseAnd[$1, $3]; }
    | expr '^' expr                                         { $$ = Expr\BinaryOp\BitwiseXor[$1, $3]; }
    | expr '.' expr                                         { $$ = Expr\BinaryOp\Concat    [$1, $3]; }
    | expr '+' expr                                         { $$ = Expr\BinaryOp\Plus      [$1, $3]; }
    | expr '-' expr                                         { $$ = Expr\BinaryOp\Minus     [$1, $3]; }
    | expr '*' expr                                         { $$ = Expr\BinaryOp\Mul       [$1, $3]; }
    | expr '/' expr                                         { $$ = Expr\BinaryOp\Div       [$1, $3]; }
    | expr '%' expr                                         { $$ = Expr\BinaryOp\Mod       [$1, $3]; }
    | expr T_SL expr                                        { $$ = Expr\BinaryOp\ShiftLeft [$1, $3]; }
    | expr T_SR expr                                        { $$ = Expr\BinaryOp\ShiftRight[$1, $3]; }
    | expr T_POW expr                                       { $$ = Expr\BinaryOp\Pow       [$1, $3]; }
    | '+' expr %prec T_INC                                  { $$ = Expr\UnaryPlus [$2]; }
    | '-' expr %prec T_INC                                  { $$ = Expr\UnaryMinus[$2]; }
    | '!' expr                                              { $$ = Expr\BooleanNot[$2]; }
    | '~' expr                                              { $$ = Expr\BitwiseNot[$2]; }
    | expr T_IS_IDENTICAL expr                              { $$ = Expr\BinaryOp\Identical     [$1, $3]; }
    | expr T_IS_NOT_IDENTICAL expr                          { $$ = Expr\BinaryOp\NotIdentical  [$1, $3]; }
    | expr T_IS_EQUAL expr                                  { $$ = Expr\BinaryOp\Equal         [$1, $3]; }
    | expr T_IS_NOT_EQUAL expr                              { $$ = Expr\BinaryOp\NotEqual      [$1, $3]; }
    | expr T_SPACESHIP expr                                 { $$ = Expr\BinaryOp\Spaceship     [$1, $3]; }
    | expr '<' expr                                         { $$ = Expr\BinaryOp\Smaller       [$1, $3]; }
    | expr T_IS_SMALLER_OR_EQUAL expr                       { $$ = Expr\BinaryOp\SmallerOrEqual[$1, $3]; }
    | expr '>' expr                                         { $$ = Expr\BinaryOp\Greater       [$1, $3]; }
    | expr T_IS_GREATER_OR_EQUAL expr                       { $$ = Expr\BinaryOp\GreaterOrEqual[$1, $3]; }
    | expr T_INSTANCEOF class_name_reference                { $$ = Expr\Instanceof_[$1, $3]; }
    | '(' expr ')'                                          { $$ = $2; }
    | expr '?' expr ':' expr                                { $$ = Expr\Ternary[$1, $3,   $5]; }
    | expr '?' ':' expr                                     { $$ = Expr\Ternary[$1, null, $4]; }
    | expr T_COALESCE expr                                  { $$ = Expr\BinaryOp\Coalesce[$1, $3]; }
    | T_ISSET '(' expr_list_allow_comma ')'                 { $$ = Expr\Isset_[$3]; }
    | T_EMPTY '(' expr ')'                                  { $$ = Expr\Empty_[$3]; }
    | T_INCLUDE expr                                        { $$ = Expr\Include_[$2, Expr\Include_::TYPE_INCLUDE]; }
    | T_INCLUDE_ONCE expr                                   { $$ = Expr\Include_[$2, Expr\Include_::TYPE_INCLUDE_ONCE]; }
    | T_EVAL '(' expr ')'                                   { $$ = Expr\Eval_[$3]; }
    | T_REQUIRE expr                                        { $$ = Expr\Include_[$2, Expr\Include_::TYPE_REQUIRE]; }
    | T_REQUIRE_ONCE expr                                   { $$ = Expr\Include_[$2, Expr\Include_::TYPE_REQUIRE_ONCE]; }
    | T_INT_CAST expr                                       { $$ = Expr\Cast\Int_    [$2]; }
    | T_DOUBLE_CAST expr
          { $attrs = attributes();
            $attrs['kind'] = $this->getFloatCastKind($1);
            $$ = new Expr\Cast\Double($2, $attrs); }
    | T_STRING_CAST expr                                    { $$ = Expr\Cast\String_ [$2]; }
    | T_ARRAY_CAST expr                                     { $$ = Expr\Cast\Array_  [$2]; }
    | T_OBJECT_CAST expr                                    { $$ = Expr\Cast\Object_ [$2]; }
    | T_BOOL_CAST expr                                      { $$ = Expr\Cast\Bool_   [$2]; }
    | T_UNSET_CAST expr                                     { $$ = Expr\Cast\Unset_  [$2]; }
    | T_EXIT exit_expr
          { $attrs = attributes();
            $attrs['kind'] = strtolower($1) === 'exit' ? Expr\Exit_::KIND_EXIT : Expr\Exit_::KIND_DIE;
            $$ = new Expr\Exit_($2, $attrs); }
    | '@' expr                                              { $$ = Expr\ErrorSuppress[$2]; }
    | scalar                                                { $$ = $1; }
    | '`' backticks_expr '`'                                { $$ = Expr\ShellExec[$2]; }
    | T_PRINT expr                                          { $$ = Expr\Print_[$2]; }
    | T_YIELD                                               { $$ = Expr\Yield_[null, null]; }
    | T_YIELD expr                                          { $$ = Expr\Yield_[$2, null]; }
    | T_YIELD expr T_DOUBLE_ARROW expr                      { $$ = Expr\Yield_[$4, $2]; }
    | T_YIELD_FROM expr                                     { $$ = Expr\YieldFrom[$2]; }
    | T_THROW expr                                          { $$ = Expr\Throw_[$2]; }

    | T_FN optional_ref '(' parameter_list ')' optional_return_type T_DOUBLE_ARROW expr
          { $$ = Expr\ArrowFunction[['static' => false, 'byRef' => $2, 'params' => $4, 'returnType' => $6, 'expr' => $8, 'attrGroups' => []]]; }
    | T_STATIC T_FN optional_ref '(' parameter_list ')' optional_return_type T_DOUBLE_ARROW expr
          { $$ = Expr\ArrowFunction[['static' => true, 'byRef' => $3, 'params' => $5, 'returnType' => $7, 'expr' => $9, 'attrGroups' => []]]; }
    | T_FUNCTION optional_ref '(' parameter_list ')' lexical_vars optional_return_type block_or_error
          { $$ = Expr\Closure[['static' => false, 'byRef' => $2, 'params' => $4, 'uses' => $6, 'returnType' => $7, 'stmts' => $8, 'attrGroups' => []]]; }
    | T_STATIC T_FUNCTION optional_ref '(' parameter_list ')' lexical_vars optional_return_type       block_or_error
          { $$ = Expr\Closure[['static' => true, 'byRef' => $3, 'params' => $5, 'uses' => $7, 'returnType' => $8, 'stmts' => $9, 'attrGroups' => []]]; }

    | attributes T_FN optional_ref '(' parameter_list ')' optional_return_type T_DOUBLE_ARROW expr
          { $$ = Expr\ArrowFunction[['static' => false, 'byRef' => $3, 'params' => $5, 'returnType' => $7, 'expr' => $9, 'attrGroups' => $1]]; }
    | attributes T_STATIC T_FN optional_ref '(' parameter_list ')' optional_return_type T_DOUBLE_ARROW expr
          { $$ = Expr\ArrowFunction[['static' => true, 'byRef' => $4, 'params' => $6, 'returnType' => $8, 'expr' => $10, 'attrGroups' => $1]]; }
    | attributes T_FUNCTION optional_ref '(' parameter_list ')' lexical_vars optional_return_type block_or_error
          { $$ = Expr\Closure[['static' => false, 'byRef' => $3, 'params' => $5, 'uses' => $7, 'returnType' => $8, 'stmts' => $9, 'attrGroups' => $1]]; }
    | attributes T_STATIC T_FUNCTION optional_ref '(' parameter_list ')' lexical_vars optional_return_type       block_or_error
          { $$ = Expr\Closure[['static' => true, 'byRef' => $4, 'params' => $6, 'uses' => $8, 'returnType' => $9, 'stmts' => $10, 'attrGroups' => $1]]; }
;

anonymous_class:
      optional_attributes T_CLASS ctor_arguments extends_from implements_list '{' class_statement_list '}'
          { $$ = array(Stmt\Class_[null, ['type' => 0, 'extends' => $4, 'implements' => $5, 'stmts' => $7, 'attrGroups' => $1]], $3);
            $this->checkClass($$[0], -1); }
;

new_expr:
      T_NEW class_name_reference ctor_arguments             { $$ = Expr\New_[$2, $3]; }
    | T_NEW anonymous_class
          { list($class, $ctorArgs) = $2; $$ = Expr\New_[$class, $ctorArgs]; }
;

lexical_vars:
      /* empty */                                           { $$ = array(); }
    | T_USE '(' lexical_var_list ')'                        { $$ = $3; }
;

lexical_var_list:
      non_empty_lexical_var_list optional_comma             { $$ = $1; }
;

non_empty_lexical_var_list:
      lexical_var                                           { init($1); }
    | non_empty_lexical_var_list ',' lexical_var            { push($1, $3); }
;

lexical_var:
      optional_ref plain_variable                           { $$ = Expr\ClosureUse[$2, $1]; }
;

function_call:
      name argument_list                                    { $$ = Expr\FuncCall[$1, $2]; }
    | callable_expr argument_list                           { $$ = Expr\FuncCall[$1, $2]; }
    | class_name_or_var T_PAAMAYIM_NEKUDOTAYIM member_name argument_list
          { $$ = Expr\StaticCall[$1, $3, $4]; }
;

class_name:
      T_STATIC                                              { $$ = Name[$1]; }
    | name                                                  { $$ = $1; }
;

name:
      T_STRING                                              { $$ = Name[$1]; }
    | T_NAME_QUALIFIED                                      { $$ = Name[$1]; }
    | T_NAME_FULLY_QUALIFIED                                { $$ = Name\FullyQualified[substr($1, 1)]; }
    | T_NAME_RELATIVE                                       { $$ = Name\Relative[substr($1, 10)]; }
;

class_name_reference:
      class_name                                            { $$ = $1; }
    | new_variable                                          { $$ = $1; }
    | '(' expr ')'                                          { $$ = $2; }
    | error                                                 { $$ = Expr\Error[]; $this->errorState = 2; }
;

class_name_or_var:
      class_name                                            { $$ = $1; }
    | fully_dereferencable                                  { $$ = $1; }
;

exit_expr:
      /* empty */                                           { $$ = null; }
    | '(' optional_expr ')'                                 { $$ = $2; }
;

backticks_expr:
      /* empty */                                           { $$ = array(); }
    | T_ENCAPSED_AND_WHITESPACE
          { $$ = array(Scalar\EncapsedStringPart[Scalar\String_::parseEscapeSequences($1, '`')]); }
    | encaps_list                                           { parseEncapsed($1, '`', true); $$ = $1; }
;

ctor_arguments:
      /* empty */                                           { $$ = array(); }
    | argument_list                                         { $$ = $1; }
;

constant:
      name                                                  { $$ = Expr\ConstFetch[$1]; }
    | T_LINE                                                { $$ = Scalar\MagicConst\Line[]; }
    | T_FILE                                                { $$ = Scalar\MagicConst\File[]; }
    | T_DIR                                                 { $$ = Scalar\MagicConst\Dir[]; }
    | T_CLASS_C                                             { $$ = Scalar\MagicConst\Class_[]; }
    | T_TRAIT_C                                             { $$ = Scalar\MagicConst\Trait_[]; }
    | T_METHOD_C                                            { $$ = Scalar\MagicConst\Method[]; }
    | T_FUNC_C                                              { $$ = Scalar\MagicConst\Function_[]; }
    | T_NS_C                                                { $$ = Scalar\MagicConst\Namespace_[]; }
;

class_constant:
      class_name_or_var T_PAAMAYIM_NEKUDOTAYIM identifier_ex
          { $$ = Expr\ClassConstFetch[$1, $3]; }
    /* We interpret an isolated FOO:: as an unfinished class constant fetch. It could also be
       an unfinished static property fetch or unfinished scoped call. */
    | class_name_or_var T_PAAMAYIM_NEKUDOTAYIM error
          { $$ = Expr\ClassConstFetch[$1, new Expr\Error(stackAttributes(#3))]; $this->errorState = 2; }
;

array_short_syntax:
      '[' array_pair_list ']'
          { $attrs = attributes(); $attrs['kind'] = Expr\Array_::KIND_SHORT;
            $$ = new Expr\Array_($2, $attrs); }
;

dereferencable_scalar:
      T_ARRAY '(' array_pair_list ')'
          { $attrs = attributes(); $attrs['kind'] = Expr\Array_::KIND_LONG;
            $$ = new Expr\Array_($3, $attrs); }
    | array_short_syntax                                    { $$ = $1; }
    | T_CONSTANT_ENCAPSED_STRING
          { $attrs = attributes(); $attrs['kind'] = strKind($1);
            $$ = new Scalar\String_(Scalar\String_::parse($1), $attrs); }
    | '"' encaps_list '"'
          { $attrs = attributes(); $attrs['kind'] = Scalar\String_::KIND_DOUBLE_QUOTED;
            parseEncapsed($2, '"', true); $$ = new Scalar\Encapsed($2, $attrs); }
;

scalar:
      T_LNUMBER                                             { $$ = $this->parseLNumber($1, attributes()); }
    | T_DNUMBER                                             { $$ = Scalar\DNumber[Scalar\DNumber::parse($1)]; }
    | dereferencable_scalar                                 { $$ = $1; }
    | constant                                              { $$ = $1; }
    | class_constant                                        { $$ = $1; }
    | T_START_HEREDOC T_ENCAPSED_AND_WHITESPACE T_END_HEREDOC
          { $$ = $this->parseDocString($1, $2, $3, attributes(), stackAttributes(#3), true); }
    | T_START_HEREDOC T_END_HEREDOC
          { $$ = $this->parseDocString($1, '', $2, attributes(), stackAttributes(#2), true); }
    | T_START_HEREDOC encaps_list T_END_HEREDOC
          { $$ = $this->parseDocString($1, $2, $3, attributes(), stackAttributes(#3), true); }
;

optional_expr:
      /* empty */                                           { $$ = null; }
    | expr                                                  { $$ = $1; }
;

fully_dereferencable:
      variable                                              { $$ = $1; }
    | '(' expr ')'                                          { $$ = $2; }
    | dereferencable_scalar                                 { $$ = $1; }
    | class_constant                                        { $$ = $1; }
;

array_object_dereferencable:
      fully_dereferencable                                  { $$ = $1; }
    | constant                                              { $$ = $1; }
;

callable_expr:
      callable_variable                                     { $$ = $1; }
    | '(' expr ')'                                          { $$ = $2; }
    | dereferencable_scalar                                 { $$ = $1; }
;

callable_variable:
      simple_variable                                       { $$ = Expr\Variable[$1]; }
    | array_object_dereferencable '[' optional_expr ']'     { $$ = Expr\ArrayDimFetch[$1, $3]; }
    | array_object_dereferencable '{' expr '}'              { $$ = Expr\ArrayDimFetch[$1, $3]; }
    | function_call                                         { $$ = $1; }
    | array_object_dereferencable T_OBJECT_OPERATOR property_name argument_list
          { $$ = Expr\MethodCall[$1, $3, $4]; }
    | array_object_dereferencable T_NULLSAFE_OBJECT_OPERATOR property_name argument_list
          { $$ = Expr\NullsafeMethodCall[$1, $3, $4]; }
;

optional_plain_variable:
      /* empty */                                           { $$ = null; }
    | plain_variable                                        { $$ = $1; }
;

variable:
      callable_variable                                     { $$ = $1; }
    | static_member                                         { $$ = $1; }
    | array_object_dereferencable T_OBJECT_OPERATOR property_name
          { $$ = Expr\PropertyFetch[$1, $3]; }
    | array_object_dereferencable T_NULLSAFE_OBJECT_OPERATOR property_name
          { $$ = Expr\NullsafePropertyFetch[$1, $3]; }
;

simple_variable:
      T_VARIABLE                                            { $$ = parseVar($1); }
    | '$' '{' expr '}'                                      { $$ = $3; }
    | '$' simple_variable                                   { $$ = Expr\Variable[$2]; }
    | '$' error                                             { $$ = Expr\Error[]; $this->errorState = 2; }
;

static_member_prop_name:
      simple_variable
          { $var = $1; $$ = \is_string($var) ? Node\VarLikeIdentifier[$var] : $var; }
;

static_member:
      class_name_or_var T_PAAMAYIM_NEKUDOTAYIM static_member_prop_name
          { $$ = Expr\StaticPropertyFetch[$1, $3]; }
;

new_variable:
      simple_variable                                       { $$ = Expr\Variable[$1]; }
    | new_variable '[' optional_expr ']'                    { $$ = Expr\ArrayDimFetch[$1, $3]; }
    | new_variable '{' expr '}'                             { $$ = Expr\ArrayDimFetch[$1, $3]; }
    | new_variable T_OBJECT_OPERATOR property_name          { $$ = Expr\PropertyFetch[$1, $3]; }
    | new_variable T_NULLSAFE_OBJECT_OPERATOR property_name { $$ = Expr\NullsafePropertyFetch[$1, $3]; }
    | class_name T_PAAMAYIM_NEKUDOTAYIM static_member_prop_name
          { $$ = Expr\StaticPropertyFetch[$1, $3]; }
    | new_variable T_PAAMAYIM_NEKUDOTAYIM static_member_prop_name
          { $$ = Expr\StaticPropertyFetch[$1, $3]; }
;

member_name:
      identifier_ex                                         { $$ = $1; }
    | '{' expr '}'                                          { $$ = $2; }
    | simple_variable                                       { $$ = Expr\Variable[$1]; }
;

property_name:
      identifier                                            { $$ = $1; }
    | '{' expr '}'                                          { $$ = $2; }
    | simple_variable                                       { $$ = Expr\Variable[$1]; }
    | error                                                 { $$ = Expr\Error[]; $this->errorState = 2; }
;

list_expr:
      T_LIST '(' inner_array_pair_list ')'                  { $$ = Expr\List_[$3]; }
;

array_pair_list:
      inner_array_pair_list
          { $$ = $1; $end = count($$)-1; if ($$[$end] === null) array_pop($$); }
;

comma_or_error:
      ','
    | error
          { /* do nothing -- prevent default action of $$=$1. See #551. */ }
;

inner_array_pair_list:
      inner_array_pair_list comma_or_error array_pair       { push($1, $3); }
    | array_pair                                            { init($1); }
;

array_pair:
      expr                                                  { $$ = Expr\ArrayItem[$1, null, false]; }
    | '&' variable                                          { $$ = Expr\ArrayItem[$2, null, true]; }
    | list_expr                                             { $$ = Expr\ArrayItem[$1, null, false]; }
    | expr T_DOUBLE_ARROW expr                              { $$ = Expr\ArrayItem[$3, $1,   false]; }
    | expr T_DOUBLE_ARROW '&' variable                      { $$ = Expr\ArrayItem[$4, $1,   true]; }
    | expr T_DOUBLE_ARROW list_expr                         { $$ = Expr\ArrayItem[$3, $1,   false]; }
    | T_ELLIPSIS expr                                       { $$ = Expr\ArrayItem[$2, null, false, attributes(), true]; }
    | /* empty */                                           { $$ = null; }
;

encaps_list:
      encaps_list encaps_var                                { push($1, $2); }
    | encaps_list encaps_string_part                        { push($1, $2); }
    | encaps_var                                            { init($1); }
    | encaps_string_part encaps_var                         { init($1, $2); }
;

encaps_string_part:
      T_ENCAPSED_AND_WHITESPACE                             { $$ = Scalar\EncapsedStringPart[$1]; }
;

encaps_str_varname:
      T_STRING_VARNAME                                      { $$ = Expr\Variable[$1]; }
;

encaps_var:
      plain_variable                                        { $$ = $1; }
    | plain_variable '[' encaps_var_offset ']'              { $$ = Expr\ArrayDimFetch[$1, $3]; }
    | plain_variable T_OBJECT_OPERATOR identifier           { $$ = Expr\PropertyFetch[$1, $3]; }
    | plain_variable T_NULLSAFE_OBJECT_OPERATOR identifier  { $$ = Expr\NullsafePropertyFetch[$1, $3]; }
    | T_DOLLAR_OPEN_CURLY_BRACES expr '}'                   { $$ = Expr\Variable[$2]; }
    | T_DOLLAR_OPEN_CURLY_BRACES T_STRING_VARNAME '}'       { $$ = Expr\Variable[$2]; }
    | T_DOLLAR_OPEN_CURLY_BRACES encaps_str_varname '[' expr ']' '}'
          { $$ = Expr\ArrayDimFetch[$2, $4]; }
    | T_CURLY_OPEN variable '}'                             { $$ = $2; }
;

encaps_var_offset:
      T_STRING                                              { $$ = Scalar\String_[$1]; }
    | T_NUM_STRING                                          { $$ = $this->parseNumString($1, attributes()); }
    | '-' T_NUM_STRING                                      { $$ = $this->parseNumString('-' . $2, attributes()); }
    | plain_variable                                        { $$ = $1; }
;

%%
