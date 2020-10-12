<?php

/**
 * Routes.
 *
 * @copyright Zikula contributors (Zikula)
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @author Zikula contributors <info@ziku.la>.
 *
 * @see https://ziku.la
 *
 * @version Generated by ModuleStudio 1.5.0 (https://modulestudio.de).
 */

declare(strict_types=1);

namespace Zikula\RoutesModule\Form\Handler\Route\Base;

use Zikula\RoutesModule\Form\Handler\Common\EditHandler;
use Zikula\RoutesModule\Form\Type\RouteType;
use Exception;
use RuntimeException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Zikula\RoutesModule\Entity\RouteEntity;

/**
 * This handler class handles the page events of editing forms.
 * It aims on the route object type.
 */
abstract class AbstractEditHandler extends EditHandler
{
    public function processForm(array $templateParameters = [])
    {
        $this->objectType = 'route';
        $this->objectTypeCapital = 'Route';
        $this->objectTypeLower = 'route';
        
        $this->hasPageLockSupport = true;
    
        $result = parent::processForm($templateParameters);
        if ($result instanceof RedirectResponse) {
            return $result;
        }
    
        if ('create' === $this->templateParameters['mode'] && !$this->modelHelper->canBeCreated($this->objectType)) {
            $request = $this->requestStack->getCurrentRequest();
            if ($request->hasSession() && ($session = $request->getSession())) {
                $session->getFlashBag()->add(
                    'error',
                    'Sorry, but you can not create the route yet as other items are required which must be created before!'
                );
            }
            $logArgs = [
                'app' => 'ZikulaRoutesModule',
                'user' => $this->currentUserApi->get('uname'),
                'entity' => $this->objectType,
            ];
            $this->logger->notice(
                '{app}: User {user} tried to create a new {entity}, but failed'
                    . ' as other items are required which must be created before.',
                $logArgs
            );
    
            return new RedirectResponse($this->getRedirectUrl(['commandName' => '']), 302);
        }
    
        // assign data to template
        $this->templateParameters[$this->objectType] = $this->entityRef;
        $this->templateParameters['supportsHookSubscribers'] = false;
    
        return $result;
    }
    
    protected function createForm(): ?FormInterface
    {
        return $this->formFactory->create(RouteType::class, $this->entityRef, $this->getFormOptions());
    }
    
    protected function getFormOptions(): array
    {
        $options = [
            'mode' => $this->templateParameters['mode'],
            'actions' => $this->templateParameters['actions'],
            'has_moderate_permission' => $this->permissionHelper->hasEntityPermission($this->entityRef, ACCESS_ADMIN),
            'allow_moderation_specific_creator' => (bool) $this->variableApi->get(
                'ZikulaRoutesModule',
                'allowModerationSpecificCreatorFor' . $this->objectTypeCapital,
                false
            ),
            'allow_moderation_specific_creation_date' => (bool) $this->variableApi->get(
                'ZikulaRoutesModule',
                'allowModerationSpecificCreationDateFor' . $this->objectTypeCapital,
                false
            ),
        ];
    
        return $options;
    }

    protected function getRedirectCodes(): array
    {
        $codes = parent::getRedirectCodes();
    
        // user index page of route area
        $codes[] = 'userIndex';
        // admin index page of route area
        $codes[] = 'adminIndex';
    
        // user list of routes
        $codes[] = 'userView';
        // admin list of routes
        $codes[] = 'adminView';
        // user list of own routes
        $codes[] = 'userOwnView';
        // admin list of own routes
        $codes[] = 'adminOwnView';
    
        // user detail page of treated route
        $codes[] = 'userDisplay';
        // admin detail page of treated route
        $codes[] = 'adminDisplay';
    
        return $codes;
    }

    /**
     * Get the default redirect url. Required if no returnTo parameter has been supplied.
     * This method is called in handleCommand so we know which command has been performed.
     */
    protected function getDefaultReturnUrl(array $args = []): string
    {
        $objectIsPersisted = 'delete' !== $args['commandName']
            && !('create' === $this->templateParameters['mode'] && 'cancel' === $args['commandName']
        );
        if (null !== $this->returnTo && $objectIsPersisted) {
            // return to referer
            return $this->returnTo;
        }
    
        $routeArea = array_key_exists('routeArea', $this->templateParameters)
            ? $this->templateParameters['routeArea']
            : ''
        ;
        $routePrefix = 'zikularoutesmodule_' . $this->objectTypeLower . '_' . $routeArea;
    
        // redirect to the list of routes
        $url = $this->router->generate($routePrefix . 'view');
    
        if ($objectIsPersisted) {
            // redirect to the detail page of treated route
            $url = $this->router->generate($routePrefix . 'display', $this->entityRef->createUrlArgs());
        }
    
        return $url;
    }

    public function handleCommand(array $args = [])
    {
        $result = parent::handleCommand($args);
        if (false === $result) {
            return $result;
        }
    
        // build $args for BC (e.g. used by redirect handling)
        foreach ($this->templateParameters['actions'] as $action) {
            if ($this->form->get($action['id'])->isClicked()) {
                $args['commandName'] = $action['id'];
            }
        }
        if (
            'create' === $this->templateParameters['mode']
            && $this->form->has('submitrepeat')
            && $this->form->get('submitrepeat')->isClicked()
        ) {
            $args['commandName'] = 'submit';
            $this->repeatCreateAction = true;
        }
    
        return new RedirectResponse($this->getRedirectUrl($args), 302);
    }
    
    protected function getDefaultMessage(array $args = [], bool $success = false): string
    {
        if (false === $success) {
            return parent::getDefaultMessage($args, $success);
        }
    
        switch ($args['commandName']) {
            case 'submit':
                if ('create' === $this->templateParameters['mode']) {
                    $message = $this->trans('Done! Route created.');
                } else {
                    $message = $this->trans('Done! Route updated.');
                }
                break;
            case 'delete':
                $message = $this->trans('Done! Route deleted.');
                break;
            default:
                $message = $this->trans('Done! Route updated.');
                break;
        }
    
        return $message;
    }

    /**
     * @throws RuntimeException Thrown if concurrent editing is recognised or another error occurs
     */
    public function applyAction(array $args = []): bool
    {
        // get treated entity reference from persisted member var
        /** @var RouteEntity $entity */
        $entity = $this->entityRef;
    
        $action = $args['commandName'];
    
        $success = false;
        try {
            // execute the workflow action
            $success = $this->workflowHelper->executeAction($entity, $action);
        } catch (Exception $exception) {
            $request = $this->requestStack->getCurrentRequest();
            if ($request->hasSession() && ($session = $request->getSession())) {
                $session->getFlashBag()->add(
                    'error',
                    $this->trans(
                        'Sorry, but an error occured during the %action% action. Please apply the changes again!',
                        ['%action%' => $action]
                    ) . ' ' . $exception->getMessage()
                );
            }
            $logArgs = [
                'app' => 'ZikulaRoutesModule',
                'user' => $this->currentUserApi->get('uname'),
                'entity' => 'route',
                'id' => $entity->getKey(),
                'errorMessage' => $exception->getMessage(),
            ];
            $this->logger->error(
                '{app}: User {user} tried to edit the {entity} with id {id},'
                    . ' but failed. Error details: {errorMessage}.',
                $logArgs
            );
        }
    
        $this->addDefaultMessage($args, $success);
    
        if ($success && 'create' === $this->templateParameters['mode']) {
            // store new identifier
            $this->idValue = $entity->getKey();
        }
    
        return $success;
    }

    /**
     * Get URL to redirect to.
     */
    protected function getRedirectUrl(array $args = []): string
    {
        if ($this->repeatCreateAction) {
            return $this->repeatReturnUrl;
        }
    
        $request = $this->requestStack->getCurrentRequest();
        if ($request->hasSession() && ($session = $request->getSession())) {
            if ($session->has('zikularoutesmodule' . $this->objectTypeCapital . 'Referer')) {
                $this->returnTo = $session->get('zikularoutesmodule' . $this->objectTypeCapital . 'Referer');
                $session->remove('zikularoutesmodule' . $this->objectTypeCapital . 'Referer');
            }
        }
    
        // normal usage, compute return url from given redirect code
        if (!in_array($this->returnTo, $this->getRedirectCodes(), true)) {
            // invalid return code, so return the default url
            return $this->getDefaultReturnUrl($args);
        }
    
        $routeArea = 0 === mb_strpos($this->returnTo, 'admin') ? 'admin' : '';
        $routePrefix = 'zikularoutesmodule_' . $this->objectTypeLower . '_' . $routeArea;
    
        // parse given redirect code and return corresponding url
        switch ($this->returnTo) {
            case 'userIndex':
            case 'adminIndex':
                return $this->router->generate($routePrefix . 'index');
            case 'userView':
            case 'adminView':
                return $this->router->generate($routePrefix . 'view');
            case 'userOwnView':
            case 'adminOwnView':
                return $this->router->generate($routePrefix . 'view', ['own' => 1]);
            case 'userDisplay':
            case 'adminDisplay':
                if (
                    'delete' !== $args['commandName']
                    && !('create' === $this->templateParameters['mode'] && 'cancel' === $args['commandName'])
                ) {
                    return $this->router->generate($routePrefix . 'display', $this->entityRef->createUrlArgs());
                }
    
                return $this->getDefaultReturnUrl($args);
            default:
                return $this->getDefaultReturnUrl($args);
        }
    }
}
