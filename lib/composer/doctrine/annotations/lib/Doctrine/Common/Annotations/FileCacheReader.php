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
 * File cache reader for annotations.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 *
 * @deprecated the FileCacheReader is deprecated and will be removed
 *             in version 2.0.0 of doctrine/annotations. Please use the
 *             {@see \Doctrine\Common\Annotations\CachedReader} instead.
 */
class FileCacheReader implements Reader
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var string
     */
    private $dir;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @var array
     */
    private $loadedAnnotations = [];

    /**
     * @var array
     */
    private $classNameHashes = [];

    /**
     * @var int
     */
    private $umask;

    /**
     * Constructor.
     *
     * @param Reader  $reader
     * @param string  $cacheDir
     * @param boolean $debug
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(Reader $reader, $cacheDir, $debug = false, $umask = 0002)
    {
        if ( ! is_int($umask)) {
            throw new \InvalidArgumentException(sprintf(
                'The parameter umask must be an integer, was: %s',
                gettype($umask)
            ));
        }

        $this->reader = $reader;
        $this->umask = $umask;

        if (!is_dir($cacheDir) && !@mkdir($cacheDir, 0777 & (~$this->umask), true)) {
            throw new \InvalidArgumentException(sprintf('The directory "%s" does not exist and could not be created.', $cacheDir));
        }

        $this->dir   = rtrim($cacheDir, '\\/');
        $this->debug = $debug;
    }

    /**
     * {@inheritDoc}
     */
    public function getClassAnnotations(\ReflectionClass $class)
    {
        if ( ! isset($this->classNameHashes[$class->name])) {
            $this->classNameHashes[$class->name] = sha1($class->name);
        }
        $key = $this->classNameHashes[$class->name];

        if (isset($this->loadedAnnotations[$key])) {
            return $this->loadedAnnotations[$key];
        }

        $path = $this->dir.'/'.strtr($key, '\\', '-').'.cache.php';
        if (!is_file($path)) {
            $annot = $this->reader->getClassAnnotations($class);
            $this->saveCacheFile($path, $annot);
            return $this->loadedAnnotations[$key] = $annot;
        }

        if ($this->debug
            && (false !== $filename = $class->getFileName())
            && filemtime($path) < filemtime($filename)) {
            @unlink($path);

            $annot = $this->reader->getClassAnnotations($class);
            $this->saveCacheFile($path, $annot);
            return $this->loadedAnnotations[$key] = $annot;
        }

        return $this->loadedAnnotations[$key] = include $path;
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertyAnnotations(\ReflectionProperty $property)
    {
        $class = $property->getDeclaringClass();
        if ( ! isset($this->classNameHashes[$class->name])) {
            $this->classNameHashes[$class->name] = sha1($class->name);
        }
        $key = $this->classNameHashes[$class->name].'$'.$property->getName();

        if (isset($this->loadedAnnotations[$key])) {
            return $this->loadedAnnotations[$key];
        }

        $path = $this->dir.'/'.strtr($key, '\\', '-').'.cache.php';
        if (!is_file($path)) {
            $annot = $this->reader->getPropertyAnnotations($property);
            $this->saveCacheFile($path, $annot);
            return $this->loadedAnnotations[$key] = $annot;
        }

        if ($this->debug
            && (false !== $filename = $class->getFilename())
            && filemtime($path) < filemtime($filename)) {
            @unlink($path);

            $annot = $this->reader->getPropertyAnnotations($property);
            $this->saveCacheFile($path, $annot);
            return $this->loadedAnnotations[$key] = $annot;
        }

        return $this->loadedAnnotations[$key] = include $path;
    }

    /**
     * {@inheritDoc}
     */
    public function getMethodAnnotations(\ReflectionMethod $method)
    {
        $class = $method->getDeclaringClass();
        if ( ! isset($this->classNameHashes[$class->name])) {
            $this->classNameHashes[$class->name] = sha1($class->name);
        }
        $key = $this->classNameHashes[$class->name].'#'.$method->getName();

        if (isset($this->loadedAnnotations[$key])) {
            return $this->loadedAnnotations[$key];
        }

        $path = $this->dir.'/'.strtr($key, '\\', '-').'.cache.php';
        if (!is_file($path)) {
            $annot = $this->reader->getMethodAnnotations($method);
            $this->saveCacheFile($path, $annot);
            return $this->loadedAnnotations[$key] = $annot;
        }

        if ($this->debug
            && (false !== $filename = $class->getFilename())
            && filemtime($path) < filemtime($filename)) {
            @unlink($path);

            $annot = $this->reader->getMethodAnnotations($method);
            $this->saveCacheFile($path, $annot);
            return $this->loadedAnnotations[$key] = $annot;
        }

        return $this->loadedAnnotations[$key] = include $path;
    }

    /**
     * Saves the cache file.
     *
     * @param string $path
     * @param mixed  $data
     *
     * @return void
     */
    private function saveCacheFile($path, $data)
    {
        if (!is_writable($this->dir)) {
            throw new \InvalidArgumentException(sprintf('The directory "%s" is not writable. Both, the webserver and the console user need access. You can manage access rights for multiple users with "chmod +a". If your system does not support this, check out the acl package.', $this->dir));
        }

        $tempfile = tempnam($this->dir, uniqid('', true));

        if (false === $tempfile) {
            throw new \RuntimeException(sprintf('Unable to create tempfile in directory: %s', $this->dir));
        }

        @chmod($tempfile, 0666 & (~$this->umask));

        $written = file_put_contents($tempfile, '<?php return unserialize('.var_export(serialize($data), true).');');

        if (false === $written) {
            throw new \RuntimeException(sprintf('Unable to write cached file to: %s', $tempfile));
        }

        @chmod($tempfile, 0666 & (~$this->umask));

        if (false === rename($tempfile, $path)) {
            @unlink($tempfile);
            throw new \RuntimeException(sprintf('Unable to rename %s to %s', $tempfile, $path));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getClassAnnotation(\ReflectionClass $class, $annotationName)
    {
        $annotations = $this->getClassAnnotations($class);

        foreach ($annotations as $annotation) {
            if ($annotation instanceof $annotationName) {
                return $annotation;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getMethodAnnotation(\ReflectionMethod $method, $annotationName)
    {
        $annotations = $this->getMethodAnnotations($method);

        foreach ($annotations as $annotation) {
            if ($annotation instanceof $annotationName) {
                return $annotation;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertyAnnotation(\ReflectionProperty $property, $annotationName)
    {
        $annotations = $this->getPropertyAnnotations($property);

        foreach ($annotations as $annotation) {
            if ($annotation instanceof $annotationName) {
                return $annotation;
            }
        }

        return null;
    }

    /**
     * Clears loaded annotations.
     *
     * @return void
     */
    public function clearLoadedAnnotations()
    {
        $this->loadedAnnotations = [];
    }
}
