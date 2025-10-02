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

trigger_deprecation('symfony/routing', '6.4', 'The "%s" class is deprecated, use "%s" instead.', AnnotationFileLoader::class, AttributeFileLoader::class);

class_exists(AttributeFileLoader::class);

if (false) {
    /**
     * @deprecated since Symfony 6.4, to be removed in 7.0, use {@link AttributeFileLoader} instead
     */
    class AnnotationFileLoader extends AttributeFileLoader
    {
    }
}
