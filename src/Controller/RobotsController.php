<?php

/*
 * This file is part of the `src-run/srw-client-silver-papillon` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

class RobotsController
{
    /**
     * @Route("/robots.txt", name="robots_txt")
     *
     * @return Response
     */
    public function indexAction(): Response
    {
        return Response::create("User-Agent: *\nDisallow: /admin\nAllow: /");
    }
}
