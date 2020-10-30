<?php declare(strict_types=1);

namespace PhpParser\Lexer;

use PhpParser\Error;
use PhpParser\ErrorHandler;
use PhpParser\Lexer;
use PhpParser\Lexer\TokenEmulator\AttributeEmulator;
use PhpParser\Lexer\TokenEmulator\CoaleseEqualTokenEmulator;
use PhpParser\Lexer\TokenEmulator\FlexibleDocStringEmulator;
use PhpParser\Lexer\TokenEmulator\FnTokenEmulator;
use PhpParser\Lexer\TokenEmulator\MatchTokenEmulator;
use PhpParser\Lexer\TokenEmulator\NullsafeTokenEmulator;
use PhpParser\Lexer\TokenEmulator\NumericLiteralSeparatorEmulator;
use PhpParser\Lexer\TokenEmulator\ReverseEmulator;
use PhpParser\Lexer\TokenEmulator\TokenEmulator;
use PhpParser\Parser\Tokens;

class Emulative extends Lexer
{
    const PHP_7_3 = '7.3dev';
    const PHP_7_4 = '7.4dev';
    const PHP_8_0 = '8.0dev';

    /** @var mixed[] Patches used to reverse changes introduced in the code */
    private $patches = [];

    /** @var TokenEmulator[] */
    private $emulators = [];

    /** @var string */
    private $targetPhpVersion;

    /**
     * @param mixed[] $options Lexer options. In addition to the usual options,
     *                         accepts a 'phpVersion' string that specifies the
     *                         version to emulated. Defaults to newest supported.
     */
    public function __construct(array $options = [])
    {
        $this->targetPhpVersion = $options['phpVersion'] ?? Emulative::PHP_8_0;
        unset($options['phpVersion']);

        parent::__construct($options);

        $emulators = [
            new FlexibleDocStringEmulator(),
            new FnTokenEmulator(),
            new MatchTokenEmulator(),
            new CoaleseEqualTokenEmulator(),
            new NumericLiteralSeparatorEmulator(),
            new NullsafeTokenEmulator(),
            new AttributeEmulator(),
        ];

        // Collect emulators that are relevant for the PHP version we're running
        // and the PHP version we're targeting for emulation.
        foreach ($emulators as $emulator) {
            $emulatorPhpVersion = $emulator->getPhpVersion();
            if ($this->isForwardEmulationNeeded($emulatorPhpVersion)) {
                $this->emulators[] = $emulator;
            } else if ($this->isReverseEmulationNeeded($emulatorPhpVersion)) {
                $this->emulators[] = new ReverseEmulator($emulator);
            }
        }
    }

    public function startLexing(string $code, ErrorHandler $errorHandler = null) {
        $emulators = array_filter($this->emulators, function($emulator) use($code) {
            return $emulator->isEmulationNeeded($code);
        });

        if (empty($emulators)) {
            // Nothing to emulate, yay
            parent::startLexing($code, $errorHandler);
            return;
        }

        $this->patches = [];
        foreach ($emulators as $emulator) {
            $code = $emulator->preprocessCode($code, $this->patches);
        }

        $collector = new ErrorHandler\Collecting();
        parent::startLexing($code, $collector);
        $this->sortPatches();
        $this->fixupTokens();

        $errors = $collector->getErrors();
        if (!empty($errors)) {
            $this->fixupErrors($errors);
            foreach ($errors as $error) {
                $errorHandler->handleError($error);
            }
        }

        foreach ($emulators as $emulator) {
            $this->tokens = $emulator->emulate($code, $this->tokens);
        }
    }

    private function isForwardEmulationNeeded(string $emulatorPhpVersion): bool {
        return version_compare(\PHP_VERSION, $emulatorPhpVersion, '<')
            && version_compare($this->targetPhpVersion, $emulatorPhpVersion, '>=');
    }

    private function isReverseEmulationNeeded(string $emulatorPhpVersion): bool {
        return version_compare(\PHP_VERSION, $emulatorPhpVersion, '>=')
            && version_compare($this->targetPhpVersion, $emulatorPhpVersion, '<');
    }

    private function sortPatches()
    {
        // Patches may be contributed by different emulators.
        // Make sure they are sorted by increasing patch position.
        usort($this->patches, function($p1, $p2) {
            return $p1[0] <=> $p2[0];
        });
    }

    private function fixupTokens()
    {
        if (\count($this->patches) === 0) {
            return;
        }

        // Load first patch
        $patchIdx = 0;

        list($patchPos, $patchType, $patchText) = $this->patches[$patchIdx];

        // We use a manual loop over the tokens, because we modify the array on the fly
        $pos = 0;
        for ($i = 0, $c = \count($this->tokens); $i < $c; $i++) {
            $token = $this->tokens[$i];
            if (\is_string($token)) {
                if ($patchPos === $pos) {
                    // Only support replacement for string tokens.
                    assert($patchType === 'replace');
                    $this->tokens[$i] = $patchText;

                    // Fetch the next patch
                    $patchIdx++;
                    if ($patchIdx >= \count($this->patches)) {
                        // No more patches, we're done
                        return;
                    }
                    list($patchPos, $patchType, $patchText) = $this->patches[$patchIdx];
                }

                $pos += \strlen($token);
                continue;
            }

            $len = \strlen($token[1]);
            $posDelta = 0;
            while ($patchPos >= $pos && $patchPos < $pos + $len) {
                $patchTextLen = \strlen($patchText);
                if ($patchType === 'remove') {
                    if ($patchPos === $pos && $patchTextLen === $len) {
                        // Remove token entirely
                        array_splice($this->tokens, $i, 1, []);
                        $i--;
                        $c--;
                    } else {
                        // Remove from token string
                        $this->tokens[$i][1] = substr_replace(
                            $token[1], '', $patchPos - $pos + $posDelta, $patchTextLen
                        );
                        $posDelta -= $patchTextLen;
                    }
                } elseif ($patchType === 'add') {
                    // Insert into the token string
                    $this->tokens[$i][1] = substr_replace(
                        $token[1], $patchText, $patchPos - $pos + $posDelta, 0
                    );
                    $posDelta += $patchTextLen;
                } else if ($patchType === 'replace') {
                    // Replace inside the token string
                    $this->tokens[$i][1] = substr_replace(
                        $token[1], $patchText, $patchPos - $pos + $posDelta, $patchTextLen
                    );
                } else {
                    assert(false);
                }

                // Fetch the next patch
                $patchIdx++;
                if ($patchIdx >= \count($this->patches)) {
                    // No more patches, we're done
                    return;
                }

                list($patchPos, $patchType, $patchText) = $this->patches[$patchIdx];

                // Multiple patches may apply to the same token. Reload the current one to check
                // If the new patch applies
                $token = $this->tokens[$i];
            }

            $pos += $len;
        }

        // A patch did not apply
        assert(false);
    }

    /**
     * Fixup line and position information in errors.
     *
     * @param Error[] $errors
     */
    private function fixupErrors(array $errors) {
        foreach ($errors as $error) {
            $attrs = $error->getAttributes();

            $posDelta = 0;
            $lineDelta = 0;
            foreach ($this->patches as $patch) {
                list($patchPos, $patchType, $patchText) = $patch;
                if ($patchPos >= $attrs['startFilePos']) {
                    // No longer relevant
                    break;
                }

                if ($patchType === 'add') {
                    $posDelta += strlen($patchText);
                    $lineDelta += substr_count($patchText, "\n");
                } else if ($patchType === 'remove') {
                    $posDelta -= strlen($patchText);
                    $lineDelta -= substr_count($patchText, "\n");
                }
            }

            $attrs['startFilePos'] += $posDelta;
            $attrs['endFilePos'] += $posDelta;
            $attrs['startLine'] += $lineDelta;
            $attrs['endLine'] += $lineDelta;
            $error->setAttributes($attrs);
        }
    }
}
