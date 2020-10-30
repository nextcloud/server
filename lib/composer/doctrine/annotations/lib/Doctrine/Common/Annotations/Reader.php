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
 * Interface for annotation readers.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface Reader
{
    /**
     * Gets the annotations applied to a class.
     *
     * @param \ReflectionClass $class The ReflectionClass of the class from which
     *                                the class annotations should be read.
     *
     * @return array An array of Annotations.
     */
    function getClassAnnotations(\ReflectionClass $class);

    /**
     * Gets a class annotation.
     *
     * @param \ReflectionClass $class          The ReflectionClass of the class from which
     *                                         the class annotations should be read.
     * @param string           $annotationName The name of the annotation.
     *
     * @return object|null The Annotation or NULL, if the requested annotation does not exist.
     */
    function getClassAnnotation(\ReflectionClass $class, $annotationName);

    /**
     * Gets the annotations applied to a method.
     *
     * @param \ReflectionMethod $method The ReflectionMethod of the method from which
     *                                  the annotations should be read.
     *
     * @return array An array of Annotations.
     */
    function getMethodAnnotations(\ReflectionMethod $method);

    /**
     * Gets a method annotation.
     *
     * @param \ReflectionMethod $method         The ReflectionMethod to read the annotations from.
     * @param string            $annotationName The name of the annotation.
     *
     * @return object|null The Annotation or NULL, if the requested annotation does not exist.
     */
    function getMethodAnnotation(\ReflectionMethod $method, $annotationName);

    /**
     * Gets the annotations applied to a property.
     *
     * @param \ReflectionProperty $property The ReflectionProperty of the property
     *                                      from which the annotations should be read.
     *
     * @return array An array of Annotations.
     */
    function getPropertyAnnotations(\ReflectionProperty $property);

    /**
     * Gets a property annotation.
     *
     * @param \ReflectionProperty $property       The ReflectionProperty to read the annotations from.
     * @param string              $annotationName The name of the annotation.
     *
     * @return object|null The Annotation or NULL, if the requested annotation does not exist.
     */
    function getPropertyAnnotation(\ReflectionProperty $property, $annotationName);
}
