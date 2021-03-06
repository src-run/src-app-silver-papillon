<?php

/*
 * This file is part of the `src-run/srw-client-silver-papillon` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace AppBundle\Component\Location\Model\Field;

interface FieldInterface
{
    /**
     * @return string
     */
    public function __toString(): string;

    /**
     * @return bool
     */
    public function hasValue(): bool;

    /**
     * @return null|mixed
     */
    public function getValue();
}

/* EOF */
