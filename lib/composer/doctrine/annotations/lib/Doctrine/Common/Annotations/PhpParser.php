<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Common\Annotations;

use SplFileObject;

/**
 * Parses a file for namespaces/use/class declarations.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Christian Kaps <christian.kaps@mohiva.com>
 */
final class PhpParser
{
    /**
     * Parses a class.
     *
     * @param \ReflectionClass $class A <code>ReflectionClass</code> object.
     *
     * @return array A list with use statements in the form (Alias => FQN).
     */
    public function parseClass(\ReflectionClass $class)
    {
        if (method_exists($class, 'getUseStatements')) {
            return $class->getUseStatements();
        }

        if (false === $filename = $class->getFileName()) {
            return [];
        }

        $content = $this->getFileContent($filename, $class->getStartLine());

        if (null === $content) {
            return [];
        }

        $namespace = preg_quote($class->getNamespaceName());
        $content = preg_replace('/^.*?(\bnamespace\s+' . $namespace . '\s*[;{].*)$/s', '\\1', $content);
        $tokenizer = new TokenParser('<?php ' . $content);

        $statements = $tokenizer->parseUseStatements($class->getNamespaceName());

        return $statements;
    }

    /**
     * Gets the content of the file right up to the given line number.
     *
     * @param string  $filename   The name of the file to load.
     * @param integer $lineNumber The number of lines to read from file.
     *
     * @return string|null The content of the file or null if the file does not exist.
     */
    private function getFileContent($filename, $lineNumber)
    {
        if ( ! is_file($filename)) {
            return null;
        }

        $content = '';
        $lineCnt = 0;
        $file = new SplFileObject($filename);
        while (!$file->eof()) {
            if ($lineCnt++ == $lineNumber) {
                break;
            }

            $content .= $file->fgets();
        }

        return $content;
    }
}
