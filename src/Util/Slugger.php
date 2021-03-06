<?php

/*
 * This file is part of the `src-run/srw-client-silver-papillon` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace AppBundle\Util;

use SR\Exception\Logic\InvalidArgumentException;
use SR\Reflection\Inspect;
use SR\Utilities\Transform\StringTransform;

class Slugger
{
    /**
     * @param string $string
     *
     * @return string
     */
    public function slugify($string)
    {
        $transformer = new StringTransform($string);

        return $transformer->spacesToDashes()->toAlphanumericAndDashes()->toLower();
    }

    /**
     * @param object   $entity
     * @param string[] $properties
     *
     * @return string
     */
    public function slugifyEntity($entity, array $properties)
    {
        $string = '';

        foreach ($properties as $p) {
            $string .= $this->entityPropertyValue($entity, $p);
        }

        return $this->slugify($string)->__toString();
    }

    /**
     * @param object $entity
     * @param string $property
     *
     * @throws \SR\Exception\ExceptionInterface
     *
     * @return mixed
     */
    private function entityPropertyValue($entity, $property)
    {
        $inspector = Inspect::useInstance($entity);

        if (!$inspector->hasProperty($property)) {
            throw InvalidArgumentException::create('Could not slugify entity property as it does not exist on the passed entity.');
        }

        return $inspector
            ->getProperty($property)
            ->value($entity);
    }
}
