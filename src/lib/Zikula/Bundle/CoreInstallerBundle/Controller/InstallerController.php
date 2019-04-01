<?php

declare(strict_types=1);

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\Bundle\CoreInstallerBundle\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Zikula\Component\Wizard\FormHandlerInterface;
use Zikula\Component\Wizard\Wizard;
use Zikula\Component\Wizard\WizardCompleteInterface;

/**
 * Class InstallerController
 */
class InstallerController extends AbstractController
{
    public function installAction(Request $request, string $stage): Response
    {
        // already installed?
        if ('complete' !== $stage && true === $this->container->getParameter('installed')) {
            $stage = 'installed';
        }

        // not installed but requesting installed stage?
        if ('installed' === $stage && false === $this->container->getParameter('installed')) {
            $stage = 'notinstalled';
        }

        // check php
        $ini_warnings = $this->controllerHelper->initPhp();
        if (count($ini_warnings) > 0) {
            $request->getSession()->getFlashBag()->add('warning', implode('<hr>', $ini_warnings));
        }

        $request->setLocale($this->container->getParameter('locale'));
        // begin the wizard
        $wizard = new Wizard($this->container, dirname(__DIR__) . '/Resources/config/install_stages.yml');
        $currentStage = $wizard->getCurrentStage($stage);
        if ($currentStage instanceof WizardCompleteInterface) {
            return $currentStage->getResponse($request);
        }
        $templateParams = $this->controllerHelper->getTemplateGlobals($currentStage);
        $templateParams['headertemplate'] = '@ZikulaCoreInstaller/installheader.html.twig';
        if ($wizard->isHalted()) {
            $request->getSession()->getFlashBag()->add('danger', $wizard->getWarning());

            return $this->renderResponse('ZikulaCoreInstallerBundle::error.html.twig', $templateParams);
        }

        // handle the form
        if ($currentStage instanceof FormHandlerInterface) {
            $form = $this->form->create($currentStage->getFormType(), null, $currentStage->getFormOptions());
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $currentStage->handleFormResult($form);
                $params = ['stage' => $wizard->getNextStage()->getName(), '_locale' => $this->container->getParameter('locale')];
                $url = $this->router->generate('install', $params);

                return new RedirectResponse($url);
            }
            $templateParams['form'] = $form->createView();
        }

        return $this->renderResponse($currentStage->getTemplateName(), $templateParams);
    }
}
