<?php
namespace JmesPath;

/**
 * Syntax errors raise this exception that gives context
 */
class SyntaxErrorException extends \InvalidArgumentException
{
    /**
     * @param string $expectedTypesOrMessage Expected array of tokens or message
     * @param array  $token                  Current token
     * @param string $expression             Expression input
     */
    public function __construct(
        $expectedTypesOrMessage,
        array $token,
        $expression
    ) {
        $message = sprintf("Syntax error at character %d\n", max($token['pos'], 0))
            . $expression . "\n" . str_repeat(' ', max($token['pos'], 0)) . "^\n";
        $message .= !is_array($expectedTypesOrMessage)
            ? $expectedTypesOrMessage
            : $this->createTokenMessage($token, $expectedTypesOrMessage);
        parent::__construct($message);
    }

    private function createTokenMessage(array $token, array $valid)
    {
        return sprintf(
            'Expected one of the following: %s; found %s "%s"',
            implode(', ', array_keys($valid)),
            $token['type'],
            $token['value']
        );
    }
}
