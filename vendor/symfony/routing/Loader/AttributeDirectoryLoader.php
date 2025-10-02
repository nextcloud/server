<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Routing\Loader;

use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Routing\RouteCollection;

/**
 * AttributeDirectoryLoader loads routing information from attributes set
 * on PHP classes and methods.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Alexandre Daubois <alex.daubois@gmail.com>
 */
class AttributeDirectoryLoader extends AttributeFileLoader
{
    /**
     * @throws \InvalidArgumentException When the directory does not exist or its routes cannot be parsed
     */
    public function load(mixed $path, ?string $type = null): ?RouteCollection
    {
        if (!is_dir($dir = $this->locator->locate($path))) {
            return parent::supports($path, $type) ? parent::load($path, $type) : new RouteCollection();
        }

        $collection = new RouteCollection();
        $collection->addResource(new DirectoryResource($dir, '/\.php$/'));
        $files = iterator_to_array(new \RecursiveIteratorIterator(
            new \RecursiveCallbackFilterIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS),
                fn (\SplFileInfo $current) => !str_starts_with($current->getBasename(), '.')
            ),
            \RecursiveIteratorIterator::LEAVES_ONLY
        ));
        usort($files, fn (\SplFileInfo $a, \SplFileInfo $b) => (string) $a > (string) $b ? 1 : -1);

        foreach ($files as $file) {
            if (!$file->isFile() || !str_ends_with($file->getFilename(), '.php')) {
                continue;
            }

            if ($class = $this->findClass($file)) {
                $refl = new \ReflectionClass($class);
                if ($refl->isAbstract()) {
                    continue;
                }

                $collection->addCollection($this->loader->load($class, $type));
            }
        }

        return $collection;
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        if (!\is_string($resource)) {
            return false;
        }

        if (\in_array($type, ['annotation', 'attribute'], true)) {
            if ('annotation' === $type) {
                trigger_deprecation('symfony/routing', '6.4', 'The "annotation" route type is deprecated, use the "attribute" route type instead.');
            }

            return true;
        }

        if ($type) {
            return false;
        }

        try {
            return is_dir($this->locator->locate($resource));
        } catch (\Exception) {
            return false;
        }
    }
}

if (!class_exists(AnnotationDirectoryLoader::class, false)) {
    class_alias(AttributeDirectoryLoader::class, AnnotationDirectoryLoader::class);
}
