<?php

/*
 * This file is part of the `src-run/src-silver-papillon` project
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace AppBundle\Component\Facebook\Provider;

use AppBundle\Component\Facebook\Authentication\AuthenticationInterface;
use AppBundle\Component\Facebook\Model\AbstractModel;
use AppBundle\Component\Facebook\Model\Feed\Feed;
use Facebook\FacebookResponse;
use Psr\Cache\CacheItemInterface;

/**
 * Class FeedProvider.
 */
class FeedProvider extends AbstractProvider
{
    /**
     * @var string
     */
    const ENDPOINT_ROOT = '/%ID%/feed';

    /**
     * @var string[]
     */
    const ENDPOINT_FIELDS = [
        'id',
    ];

    /**
     * @var string[]
     */
    static $cleanObjectHashes = [];

    /**
     * @var int
     */
    static $cleanMethodDepth = 0;

    /**
     * @param AuthenticationInterface|null $authentication
     * @param int|null                     $id
     */
    public function __construct(AuthenticationInterface $authentication = null, $id = null)
    {
        parent::__construct($authentication, $authentication->getPageId());
    }

    /**
     * @param FacebookResponse $response
     *
     * @return Feed
     */
    protected function hydrate(FacebookResponse $response)
    {
        $model = $this->buildPostModel($response->getDecodedBody());

        return $this->cleanProperties($model);
    }

    /**
     * @param AbstractModel $model
     *
     * @return AbstractModel
     */
    final protected function cleanProperties(AbstractModel $model)
    {
        static::$cleanMethodDepth++;
        static::$cleanObjectHashes[] = spl_object_hash($model);

        if (static::$cleanMethodDepth > 100 || in_array(spl_object_hash($model), static::$cleanObjectHashes)) {
            return $model;
        }

        $rc = new \ReflectionClass($model);

        foreach (['data', 'dataOriginal'] as $p) {
            if (!$rc->hasProperty($p)) {
                continue;
            }

            $rp = $rc->getProperty($p);
            $rp->setAccessible(true);
            $rp->setValue($model, null);
        }

        $isModel = function ($v) {
            return $v instanceof AbstractModel;
        };

        $properties = array_merge(
            array_filter($rc->getProperties(), function (\ReflectionProperty $p) use ($model, $isModel) {
                return $p->setAccessible(true) && $isModel($p->getValue($model));
            }),
            array_filter($rc->getProperties(), function (\ReflectionProperty $p) use ($model, $isModel) {
                return $p->setAccessible(true) && is_array($v = $p->getValue($model)) && count(array_filter($v, $isModel)) === count($v);
            })
        );

        foreach ($properties as $p) {
            $this->cleanPropertiesRecurse($p->getValue($model), $model);
        }

        static::$cleanMethodDepth--;

        return $model;
    }

    /**
     * @param AbstractModel|AbstractModel[] $work
     * @param AbstractModel $parent
     *
     * @return null
     */
    final private function cleanPropertiesRecurse($work, AbstractModel $parent)
    {
        static::$cleanMethodDepth++;

        if (is_array($work)) {
            foreach ($work as $v) {
                $this->cleanPropertiesRecurse($v, $parent);
            }
        } elseif ($work instanceof AbstractModel) {
            $this->cleanProperties($work);
            $rp = (new \ReflectionClass($work))->getProperty('parent');
            $rp->setAccessible(true);
            $rp->setValue($work, $parent);
        }

        static::$cleanMethodDepth--;

        return;
    }

    /**
     * @param mixed[] $data
     *
     * @return Feed
     */
    protected function buildPostModel($data)
    {
        return Feed::create(['posts' => $this->resolvePostsList($data)], $this->getAuthentication()->getPageId());
    }

    /**
     * @param mixed[] $data
     *
     * @return AbstractModel
     */
    protected function resolvePostsList($data)
    {
        if (!$this->isDataList($data)) {
            return null;
        }

        $posts = array_map(function (array $p) {
            try {
                return isset($p['id']) ? $this->resolvePost($p['id']) : null;
            }
            catch (\Exception $e) {
                return null;
            }
        }, $data['data']);

        return array_filter($posts, function ($p) {
            return $p !== null;
        });
    }

    /**
     * @param string $id
     *
     * @return mixed
     */
    protected function resolvePost($id)
    {
        $provider = new FeedPostProvider($this->getAuthentication(), $id);
        $provider->setCache($this->getCache());

        return $provider->get();
    }

    /**
     * @return CacheItemInterface
     */
    protected function getCachedItem()
    {
        static $cacheItem;

        if ($cacheItem === null) {
            $cacheItem = $this->getCache()->getItem(sprintf('facebook.feed.%s', md5($this->getEndpoint())));
        }

        return $cacheItem;
    }
}

/* EOF */