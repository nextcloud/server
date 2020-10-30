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

/**
 * Simple Annotation Reader.
 *
 * This annotation reader is intended to be used in projects where you have
 * full-control over all annotations that are available.
 *
 * @since  2.2
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class SimpleAnnotationReader implements Reader
{
    /**
     * @var DocParser
     */
    private $parser;

    /**
     * Constructor.
     *
     * Initializes a new SimpleAnnotationReader.
     */
    public function __construct()
    {
        $this->parser = new DocParser();
        $this->parser->setIgnoreNotImportedAnnotations(true);
    }

    /**
     * Adds a namespace in which we will look for annotations.
     *
     * @param string $namespace
     *
     * @return void
     */
    public function addNamespace($namespace)
    {
        $this->parser->addNamespace($namespace);
    }

    /**
     * {@inheritDoc}
     */
    public function getClassAnnotations(\ReflectionClass $class)
    {
        return $this->parser->parse($class->getDocComment(), 'class '.$class->getName());
    }

    /**
     * {@inheritDoc}
     */
    public function getMethodAnnotations(\ReflectionMethod $method)
    {
        return $this->parser->parse($method->getDocComment(), 'method '.$method->getDeclaringClass()->name.'::'.$method->getName().'()');
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertyAnnotations(\ReflectionProperty $property)
    {
        return $this->parser->parse($property->getDocComment(), 'property '.$property->getDeclaringClass()->name.'::$'.$property->getName());
    }

    /**
     * {@inheritDoc}
     */
    public function getClassAnnotation(\ReflectionClass $class, $annotationName)
    {
        foreach ($this->getClassAnnotations($class) as $annot) {
            if ($annot instanceof $annotationName) {
                return $annot;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getMethodAnnotation(\ReflectionMethod $method, $annotationName)
    {
        foreach ($this->getMethodAnnotations($method) as $annot) {
            if ($annot instanceof $annotationName) {
                return $annot;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertyAnnotation(\ReflectionProperty $property, $annotationName)
    {
        foreach ($this->getPropertyAnnotations($property) as $annot) {
            if ($annot instanceof $annotationName) {
                return $annot;
            }
        }

        return null;
    }
}
