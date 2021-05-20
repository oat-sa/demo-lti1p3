<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace App\Ags;

use OAT\Library\Lti1p3Ags\Model\Score\ScoreInterface;
use OAT\Library\Lti1p3Ags\Repository\ScoreRepositoryInterface;
use OAT\Library\Lti1p3Core\Util\Generator\IdGeneratorInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ScoreRepository implements ScoreRepositoryInterface
{
    private const CACHE_KEY = 'lti1p3-ags-scores';

    /** @var CacheItemPoolInterface */
    private $cache;

    public function __construct(
        CacheItemPoolInterface $cache,
        RequestStack $requestStack,
        IdGeneratorInterface $generator
    ) {
        $this->cache = $cache;
        $this->requestStack = $requestStack;
        $this->generator = $generator;
    }

    public function save(ScoreInterface $score): ScoreInterface
    {
        $cache = $this->cache->getItem(self::CACHE_KEY);

        $scores = $cache->get();

        $scores[$score->getLineItemIdentifier()][] = $score;

        $cache->set($scores);

        $this->cache->save($cache);

        return $score;
    }

    public function findByLineItemIdentifier(string $lineItemIdentifier): array
    {
        $cache = $this->cache->getItem(self::CACHE_KEY);

        $scores = $cache->get();

        return $scores[$lineItemIdentifier] ?? [];
    }
}
