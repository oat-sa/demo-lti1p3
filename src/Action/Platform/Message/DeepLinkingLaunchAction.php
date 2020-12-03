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

namespace App\Action\Platform\Message;

use App\Form\Platform\Message\DeepLinkingLaunchType;
use OAT\Library\Lti1p3DeepLinking\Message\Launch\Builder\DeepLinkingLaunchRequestBuilder;
use OAT\Library\Lti1p3DeepLinking\Settings\DeepLinkingSettings;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class DeepLinkingLaunchAction
{
    /** @var FlashBagInterface */
    private $flashBag;

    /** @var ParameterBagInterface */
    private $parameterBag;

    /** @var RouterInterface */
    private $router;

    /** @var Environment */
    private $twig;

    /** @var FormFactoryInterface */
    private $factory;

    /** @var DeepLinkingLaunchRequestBuilder */
    private $builder;

    public function __construct(
        FlashBagInterface $flashBag,
        ParameterBagInterface $parameterBag,
        RouterInterface $router,
        Environment $twig,
        FormFactoryInterface $factory,
        DeepLinkingLaunchRequestBuilder $builder
    ) {
        $this->flashBag = $flashBag;
        $this->parameterBag = $parameterBag;
        $this->router = $router;
        $this->twig = $twig;
        $this->factory = $factory;
        $this->builder = $builder;
    }

    public function __invoke(Request $request): Response
    {
        $form = $this->factory->create(DeepLinkingLaunchType::class);

        $form->handleRequest($request);

        $user = null;
        $claims = [];
        $deepLinkingLaunchRequest = null;

        if ($form->isSubmitted() && $form->isValid()) {

            $formData = $form->getData();

            $deepLinkSettings = new DeepLinkingSettings(
                $this->router->generate('platform_message_deep_linking_return', [], RouterInterface::ABSOLUTE_URL),
                $formData['accept_types'],
                $formData['accept_presentation_document_targets'],
                $formData['accept_media_types'],
                $formData['accept_multiple'],
                $formData['auto_create']
            );

            if ($formData['claims']) {
                $claims = json_decode($formData['claims'], true);

                if (JSON_ERROR_NONE !== json_last_error()) {
                    throw new BadRequestHttpException(sprintf('json_decode error: %s', json_last_error_msg()));
                }
            }

            $deepLinkingLaunchRequest = $this->builder->buildDeepLinkingLaunchRequest(
                $deepLinkSettings,
                $formData['registration'],
                $formData['user'] ?? 'anonymous',
                null,
                [],
                $claims
            );

            $this->flashBag->add('success', 'Deep linking generation success');
        }

        return new Response(
            $this->twig->render(
                'platform/message/deepLinkingLaunch.html.twig',
                [
                    'form' => $form->createView(),
                    'deepLinkingLaunchRequest' => $deepLinkingLaunchRequest,
                    'editorClaims' => $this->parameterBag->get('editor_claims')
                ]
            )
        );
    }
}
