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

use App\Form\Platform\Message\LtiResourceLinkLaunchType;
use OAT\Library\Lti1p3Core\Message\Launch\Builder\LtiResourceLinkLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Resource\LtiResourceLink\LtiResourceLink;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class LtiResourceLinkLaunchAction
{
    /** @var RouterInterface */
    private $router;

    /** @var FlashBagInterface */
    private $flashBag;

    /** @var ParameterBagInterface */
    private $parameterBag;

    /** @var Environment */
    private $twig;

    /** @var FormFactoryInterface */
    private $factory;

    /** @var LtiResourceLinkLaunchRequestBuilder */
    private $builder;

    public function __construct(
        RouterInterface $router,
        FlashBagInterface $flashBag,
        ParameterBagInterface $parameterBag,
        Environment $twig,
        FormFactoryInterface $factory,
        LtiResourceLinkLaunchRequestBuilder $builder
    ) {
        $this->router = $router;
        $this->flashBag = $flashBag;
        $this->parameterBag = $parameterBag;
        $this->twig = $twig;
        $this->factory = $factory;
        $this->builder = $builder;
    }

    public function __invoke(Request $request): Response
    {
        $form = $this->factory->create(LtiResourceLinkLaunchType::class);

        $form->handleRequest($request);

        $user = null;
        $claims = [];
        $ltiResourceLinkLaunchRequest = null;

        if ($form->isSubmitted() && $form->isValid()) {

            $formData = $form->getData();

            $resourceLink = new LtiResourceLink(
                Uuid::uuid4()->toString(),
                [
                    'url' => $formData['launch_url'] ?? null
                ]
            );

            if ($formData['claims']) {
                $claims = json_decode($formData['claims'], true);

                if (JSON_ERROR_NONE !== json_last_error()) {
                    throw new BadRequestHttpException(sprintf('json_decode error: %s', json_last_error_msg()));
                }
            }

            $ltiResourceLinkLaunchRequest = $this->builder->buildLtiResourceLinkLaunchRequest(
                $resourceLink,
                $formData['registration'],
                $formData['user'] ?? 'anonymous',
                null,
                [],
                $claims
            );

            $this->flashBag->add('success', 'LTI resource link generation success');
        } else {
            $form->setData($request->query->all());
        }

        return new Response(
            $this->twig->render(
                'platform/message/ltiResourceLinkLaunch.html.twig',
                [
                    'form' => $form->createView(),
                    'formSubmitted' => $form->isSubmitted(),
                    'formShareUrl' => $this->generateFormShareUrl($form),
                    'ltiResourceLinkLaunchRequest' => $ltiResourceLinkLaunchRequest,
                    'editorClaims' => $this->parameterBag->get('editor_claims')
                ]
            )
        );
    }

    private function generateFormShareUrl(FormInterface $form): string
    {
        return $this->router->generate(
            'platform_message_launch_lti_resource_link',
            $form->getNormData(),
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }
}
