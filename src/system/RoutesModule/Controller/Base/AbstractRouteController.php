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

namespace Zikula\RoutesModule\Controller\Base;

use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Zikula\Bundle\CoreBundle\Controller\AbstractController;
use Zikula\Component\SortableColumns\Column;
use Zikula\Component\SortableColumns\SortableColumns;
use Zikula\UsersModule\Api\ApiInterface\CurrentUserApiInterface;
use Zikula\RoutesModule\Entity\RouteEntity;
use Zikula\RoutesModule\Entity\Factory\EntityFactory;
use Zikula\RoutesModule\Form\Handler\Route\EditHandler;
use Zikula\RoutesModule\Helper\ControllerHelper;
use Zikula\RoutesModule\Helper\PermissionHelper;
use Zikula\RoutesModule\Helper\ViewHelper;
use Zikula\RoutesModule\Helper\WorkflowHelper;

/**
 * Route controller base class.
 */
abstract class AbstractRouteController extends AbstractController
{
    /**
     * This is the default action handling the main area called without defining arguments.
     *
     * @throws AccessDeniedException Thrown if the user doesn't have required permissions
     */
    protected function indexInternal(
        Request $request,
        PermissionHelper $permissionHelper,
        bool $isAdmin = false
    ): Response {
        $objectType = 'route';
        // permission check
        $permLevel = $isAdmin ? ACCESS_ADMIN : ACCESS_OVERVIEW;
        if (!$permissionHelper->hasComponentPermission($objectType, $permLevel)) {
            throw new AccessDeniedException();
        }
        
        $templateParameters = [
            'routeArea' => $isAdmin ? 'admin' : '',
        ];
        
        return $this->redirectToRoute('zikularoutesmodule_route_' . $templateParameters['routeArea'] . 'view');
    }

    /**
     * This action provides an item list overview.
     *
     * @throws AccessDeniedException Thrown if the user doesn't have required permissions
     * @throws Exception
     */
    protected function viewInternal(
        Request $request,
        RouterInterface $router,
        PermissionHelper $permissionHelper,
        ControllerHelper $controllerHelper,
        ViewHelper $viewHelper,
        string $sort,
        string $sortdir,
        int $page,
        int $num,
        bool $isAdmin = false
    ): Response {
        $objectType = 'route';
        // permission check
        $permLevel = $isAdmin ? ACCESS_ADMIN : ACCESS_READ;
        if (!$permissionHelper->hasComponentPermission($objectType, $permLevel)) {
            throw new AccessDeniedException();
        }
        
        $templateParameters = [
            'routeArea' => $isAdmin ? 'admin' : '',
        ];
        
        $request->query->set('sort', $sort);
        $request->query->set('sortdir', $sortdir);
        $request->query->set('page', $page);
        
        $routeName = 'zikularoutesmodule_route_' . ($isAdmin ? 'admin' : '') . 'view';
        $sortableColumns = new SortableColumns($router, $routeName, 'sort', 'sortdir');
        
        $sortableColumns->addColumns([
            new Column('bundle'),
            new Column('controller'),
            new Column('action'),
            new Column('path'),
            new Column('host'),
            new Column('schemes'),
            new Column('methods'),
            new Column('prependBundlePrefix'),
            new Column('translatable'),
            new Column('translationPrefix'),
            new Column('condition'),
            new Column('description'),
            new Column('sort'),
            new Column('createdBy'),
            new Column('createdDate'),
            new Column('updatedBy'),
            new Column('updatedDate'),
        ]);
        
        $templateParameters = $controllerHelper->processViewActionParameters(
            $objectType,
            $sortableColumns,
            $templateParameters
        );
        
        // filter by permissions
        $templateParameters['items'] = $permissionHelper->filterCollection(
            $templateParameters['items'],
            $permLevel
        );
        
        // fetch and return the appropriate template
        return $viewHelper->processTemplate($objectType, 'view', $templateParameters);
    }

    /**
     * This action provides a item detail view.
     *
     * @throws AccessDeniedException Thrown if the user doesn't have required permissions
     * @throws NotFoundHttpException Thrown if route to be displayed isn't found
     */
    protected function displayInternal(
        Request $request,
        PermissionHelper $permissionHelper,
        ControllerHelper $controllerHelper,
        ViewHelper $viewHelper,
        EntityFactory $entityFactory,
        ?RouteEntity $route = null,
        int $id = 0,
        bool $isAdmin = false
    ): Response {
        if (null === $route) {
            $route = $entityFactory->getRepository('route')->selectById($id);
        }
        if (null === $route) {
            throw new NotFoundHttpException(
                'No such route found.'
            );
        }
        
        $objectType = 'route';
        // permission check
        $permLevel = $isAdmin ? ACCESS_ADMIN : ACCESS_READ;
        if (!$permissionHelper->hasEntityPermission($route, $permLevel)) {
            throw new AccessDeniedException();
        }
        
        $templateParameters = [
            'routeArea' => $isAdmin ? 'admin' : '',
            $objectType => $route,
        ];
        
        $templateParameters = $controllerHelper->processDisplayActionParameters(
            $objectType,
            $templateParameters
        );
        
        // fetch and return the appropriate template
        $response = $viewHelper->processTemplate($objectType, 'display', $templateParameters);
        
        return $response;
    }

    /**
     * This action provides a handling of edit requests.
     *
     * @throws AccessDeniedException Thrown if the user doesn't have required permissions
     * @throws RuntimeException Thrown if another critical error occurs (e.g. workflow actions not available)
     * @throws Exception
     */
    protected function editInternal(
        Request $request,
        PermissionHelper $permissionHelper,
        ControllerHelper $controllerHelper,
        ViewHelper $viewHelper,
        EditHandler $formHandler,
        bool $isAdmin = false
    ): Response {
        $objectType = 'route';
        // permission check
        $permLevel = $isAdmin ? ACCESS_ADMIN : ACCESS_EDIT;
        if (!$permissionHelper->hasComponentPermission($objectType, $permLevel)) {
            throw new AccessDeniedException();
        }
        
        $templateParameters = [
            'routeArea' => $isAdmin ? 'admin' : '',
        ];
        
        // delegate form processing to the form handler
        $result = $formHandler->processForm($templateParameters);
        if ($result instanceof RedirectResponse) {
            return $result;
        }
        
        $templateParameters = $formHandler->getTemplateParameters();
        
        $templateParameters = $controllerHelper->processEditActionParameters(
            $objectType,
            $templateParameters
        );
        
        // fetch and return the appropriate template
        return $viewHelper->processTemplate($objectType, 'edit', $templateParameters);
    }

    
    
    /**
     * Process status changes for multiple items.
     *
     * This function processes the items selected in the admin view page.
     * Multiple items may have their state changed or be deleted.
     *
     * @throws RuntimeException Thrown if executing the workflow action fails
     */
    protected function handleSelectedEntriesInternal(
        Request $request,
        LoggerInterface $logger,
        EntityFactory $entityFactory,
        WorkflowHelper $workflowHelper,
        CurrentUserApiInterface $currentUserApi,
        bool $isAdmin = false
    ): RedirectResponse {
        $objectType = 'route';
        
        // get parameters
        $action = $request->request->get('action');
        $items = $request->request->get('items');
        if (!is_array($items) || !count($items)) {
            return $this->redirectToRoute('zikularoutesmodule_route_' . ($isAdmin ? 'admin' : '') . 'index');
        }
        
        $action = mb_strtolower($action);
        
        $repository = $entityFactory->getRepository($objectType);
        $userName = $currentUserApi->get('uname');
        
        // process each item
        foreach ($items as $itemId) {
            // check if item exists, and get record instance
            $entity = $repository->selectById($itemId, false);
            if (null === $entity) {
                continue;
            }
        
            // check if $action can be applied to this entity (may depend on it's current workflow state)
            $allowedActions = $workflowHelper->getActionsForObject($entity);
            $actionIds = array_keys($allowedActions);
            if (!in_array($action, $actionIds, true)) {
                // action not allowed, skip this object
                continue;
            }
        
            $success = false;
            try {
                // execute the workflow action
                $success = $workflowHelper->executeAction($entity, $action);
            } catch (Exception $exception) {
                $this->addFlash(
                    'error',
                    $this->trans(
                        'Sorry, but an error occured during the %action% action.',
                        ['%action%' => $action]
                    ) . '  ' . $exception->getMessage()
                );
                $logger->error(
                    '{app}: User {user} tried to execute the {action} workflow action for the {entity} with id {id},'
                        . ' but failed. Error details: {errorMessage}.',
                    [
                        'app' => 'ZikulaRoutesModule',
                        'user' => $userName,
                        'action' => $action,
                        'entity' => 'route',
                        'id' => $itemId,
                        'errorMessage' => $exception->getMessage(),
                    ]
                );
            }
        
            if (!$success) {
                continue;
            }
        
            if ('delete' === $action) {
                $this->addFlash(
                    'status',
                    'Done! Route deleted.'
                );
                $logger->notice(
                    '{app}: User {user} deleted the {entity} with id {id}.',
                    [
                        'app' => 'ZikulaRoutesModule',
                        'user' => $userName,
                        'entity' => 'route',
                        'id' => $itemId,
                    ]
                );
            } else {
                $this->addFlash(
                    'status',
                    'Done! Route updated.'
                );
                $logger->notice(
                    '{app}: User {user} executed the {action} workflow action for the {entity} with id {id}.',
                    [
                        'app' => 'ZikulaRoutesModule',
                        'user' => $userName,
                        'action' => $action,
                        'entity' => 'route',
                        'id' => $itemId,
                    ]
                );
            }
        }
        
        return $this->redirectToRoute('zikularoutesmodule_route_' . ($isAdmin ? 'admin' : '') . 'index');
    }
}
