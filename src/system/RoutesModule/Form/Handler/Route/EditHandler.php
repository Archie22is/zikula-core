<?php

declare(strict_types=1);

/**
 * Routes.
 *
 * @copyright Zikula contributors (Zikula)
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @author Zikula contributors <info@ziku.la>.
 * @link https://ziku.la
 * @link https://ziku.la
 * @version Generated by ModuleStudio 1.0.0 (https://modulestudio.de).
 */

namespace Zikula\RoutesModule\Form\Handler\Route;

use Symfony\Component\Routing\RouteCollection;
use Zikula\Bundle\CoreBundle\CacheClearer;
use Zikula\RoutesModule\Form\Handler\Route\Base\AbstractEditHandler;
use Zikula\RoutesModule\Helper\PathBuilderHelper;
use Zikula\RoutesModule\Helper\RouteDumperHelper;
use Zikula\RoutesModule\Helper\SanitizeHelper;

/**
 * This handler class handles the page events of the Form called by the zikulaRoutesModule_route_edit() function.
 * It aims on the route object type.
 */
class EditHandler extends AbstractEditHandler
{
    /**
     * @var PathBuilderHelper
     */
    private $pathBuilderHelper;

    /**
     * @var RouteDumperHelper
     */
    private $routeDumperHelper;

    /**
     * @var SanitizeHelper
     */
    private $sanitizeHelper;

    /**
     * @var CacheClearer
     */
    private $cacheClearer;

    public function applyAction(array $args = []): bool
    {
        $this->sanitizeInput();
        if ($this->hasConflicts()) {
            return false;
        }

        $return = parent::applyAction($args);

        $this->cacheClearer->clear('symfony.routing');

        // reload **all** JS routes
        $this->routeDumperHelper->dumpJsRoutes();

        return $return;
    }

    /**
     * Ensures validity of input data.
     */
    private function sanitizeInput(): void
    {
        $entity = $this->entityRef;

        list($controller,) = $this->sanitizeHelper->sanitizeController((string)$entity['controller']);
        list($action,) = $this->sanitizeHelper->sanitizeAction((string)$entity['action']);

        $entity['controller'] = $controller;
        $entity['action'] = $action;
        $entity['sort'] = 0;

        $this->entityRef = $entity;
    }

    /**
     * Checks for potential conflict.
     */
    private function hasConflicts(): bool
    {
        $newPath = $this->pathBuilderHelper->getPathWithBundlePrefix($this->entityRef);

        /** @var RouteCollection $routeCollection */
        $routeCollection = $this->router->getRouteCollection();

        $errors = [];
        foreach ($routeCollection->all() as $route) {
            $path = $route->getPath();
            if (in_array($path, ['/{url}', '/{path}'])) {
                continue;
            }

            if ($path === $newPath) {
                $errors[] = [
                    'type' => 'SAME',
                    'path' => $path
                ];
                continue;
            }

            $pathRegExp = preg_quote(preg_replace('/{(.+)}/', '____DUMMY____', $path), '/');
            $pathRegExp = '#^' . str_replace('____DUMMY____', '(.+)', $pathRegExp) . '$#';

            $matches = [];
            preg_match($pathRegExp, $newPath, $matches);
            if (count($matches)) {
                $errors[] = [
                    'type' => 'SIMILAR',
                    'path' => $path
                ];
            }
        }

        $hasCriticalErrors = false;

        foreach ($errors as $error) {
            if ('SAME' === $error['type']) {
                $message = $this->__('It looks like you created or updated a route with a path which already exists. This is an error in most cases.');
                $hasCriticalErrors = true;
            } else {
                $message = $this->__f('The path of the route you created or updated looks similar to the following already existing path: %s Are you sure you haven\'t just introduced a conflict?', ['%s' => $error['path']]);
            }
            $request = $this->requestStack->getCurrentRequest();
            if (null !== $request && $request->hasSession() && null !== $request->getSession()) {
                $request->getSession()->getFlashBag()->add('error', $message);
            }
        }

        return $hasCriticalErrors;
    }

    /**
     * @required
     */
    public function setPathBuilderHelper(PathBuilderHelper $pathBuilderHelper): void
    {
        $this->pathBuilderHelper = $pathBuilderHelper;
    }

    /**
     * @required
     */
    public function setRouteDumperHelper(RouteDumperHelper $routeDumperHelper): void
    {
        $this->routeDumperHelper = $routeDumperHelper;
    }

    /**
     * @required
     */
    public function setSanitizeHelper(SanitizeHelper $sanitizeHelper): void
    {
        $this->sanitizeHelper = $sanitizeHelper;
    }

    /**
     * @required
     */
    public function setCacheClearer(CacheClearer $cacheClearer): void
    {
        $this->cacheClearer = $cacheClearer;
    }
}
