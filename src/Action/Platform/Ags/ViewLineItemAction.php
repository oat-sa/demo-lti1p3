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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace App\Action\Platform\Ags;

use OAT\Library\Lti1p3Ags\Repository\LineItemRepositoryInterface;
use OAT\Library\Lti1p3Ags\Repository\ScoreRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

class ViewLineItemAction
{
    /** @var LineItemRepositoryInterface */
    private $lineItemRepository;

    /** @var ScoreRepositoryInterface */
    private $scoreRepository;

    /** @var Environment */
    private $twig;

    public function __construct(
        LineItemRepositoryInterface $lineItemRepository,
        ScoreRepositoryInterface $scoreRepository,
        Environment $twig
    ) {
        $this->lineItemRepository = $lineItemRepository;
        $this->scoreRepository = $scoreRepository;
        $this->twig = $twig;
    }

    public function __invoke(Request $request, string $lineItemIdentifier): Response
    {
        $lineItem = $this->lineItemRepository->find($lineItemIdentifier);

        if (null === $lineItem) {
            throw new NotFoundHttpException(
                sprintf('Cannot find line item with id %s', $lineItemIdentifier)
            );
        }

        $scores = $this->scoreRepository->findByLineItemIdentifier($lineItemIdentifier);

        return new Response(
            $this->twig->render(
                'platform/ags/viewLineItem.html.twig',
                [
                    'lineItem' => $lineItem,
                    'scores' => $scores
                ]
            )
        );
    }
}
