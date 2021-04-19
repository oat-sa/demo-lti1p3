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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace App\Action\Platform\Service\Ags\LineItem;

use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\Service\LtiServiceSecurityToken;
use OAT\Library\Lti1p3Ags\Service\LineItem\Server\Handler\ListLineItemServiceServerRequestHandler;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;

class ListLineItemsAction
{
    /** @var ListLineItemServiceServerRequestHandler */
    private $handler;

    /** @var Security */
    private $security;

    /** @var HttpFoundationFactoryInterface */
    private $httpFoundationFactory;

    /** @var HttpMessageFactoryInterface */
    private $psr7Factory;

    public function __construct(
        ListLineItemServiceServerRequestHandler $handler,
        Security $security,
        HttpFoundationFactoryInterface $httpFoundationFactory,
        HttpMessageFactoryInterface $psr7Factory
    ) {
        $this->handler = $handler;
        $this->security = $security;
        $this->httpFoundationFactory = $httpFoundationFactory;
        $this->psr7Factory = $psr7Factory;
    }

    public function __invoke(Request $request): Response
    {
        /** @var LtiServiceSecurityToken $token */
        $token = $this->security->getToken();

        $basicOutcomeResponse = $this->handler->handleServiceRequest(
            $token->getRegistration(),
            $this->psr7Factory->createRequest($request)
        );

        return $this->httpFoundationFactory->createResponse($basicOutcomeResponse);
    }
}
