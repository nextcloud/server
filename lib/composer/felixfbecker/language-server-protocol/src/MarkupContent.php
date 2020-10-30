<?php

namespace LanguageServerProtocol;

/**
 * A `MarkupContent` literal represents a string value which content is interpreted base on its
 * kind flag. Currently the protocol supports `plaintext` and `markdown` as markup kinds.
 *
 * If the kind is `markdown` then the value can contain fenced code blocks like in GitHub issues.
 * See https://help.github.com/articles/creating-and-highlighting-code-blocks/#syntax-highlighting
 *
 * Here is an example how such a string can be constructed using JavaScript / TypeScript:
 * ```ts
 * let markdown: MarkdownContent = {
 *  kind: MarkupKind.Markdown,
 *  value: [
 *      '# Header',
 *      'Some text',
 *      '```typescript',
 *      'someCode();',
 *      '```'
 *  ].join('\n')
 * };
 * ```
 *
 * *Please Note* that clients might sanitize the return markdown. A client could decide to
 * remove HTML from the markdown to avoid script execution.
 */
class MarkupContent
{
    /**
     * @var string the type of the Markup (from MarkupKind)
     */
    public $kind;

    /**
     * @var string the content itself
     */
    public $value;

    /**
     * @param string $kind the type of the Markup
     * @param string $value the content itself
     */
    public function __construct(string $kind = null, string $value = null)
    {
        $this->kind = $kind;
        $this->value = $value;
    }
}
