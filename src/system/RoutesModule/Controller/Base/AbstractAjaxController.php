<?php

declare(strict_types=1);

/**
 * Routes.
 *
 * @copyright Zikula contributors (Zikula)
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @author Zikula contributors <info@ziku.la>.
 * @link https://ziku.la
 * @version Generated by ModuleStudio 1.4.0 (https://modulestudio.de).
 */

namespace Zikula\RoutesModule\Controller\Base;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Zikula\Core\Controller\AbstractController;
use Zikula\RoutesModule\Entity\Factory\EntityFactory;

/**
 * Ajax controller base class.
 */
abstract class AbstractAjaxController extends AbstractController
{
    
    /**
     * Updates the sort positions for a given list of entities.
     *
     * @throws AccessDeniedException Thrown if the user doesn't have required permissions
     */
    public function updateSortPositionsAction(
        Request $request,
        EntityFactory $entityFactory
    ): JsonResponse
     {
        if (!$request->isXmlHttpRequest()) {
            return $this->json($this->__('Only ajax access is allowed!'), Response::HTTP_BAD_REQUEST);
        }
        
        if (!$this->hasPermission('ZikulaRoutesModule::Ajax', '::', ACCESS_EDIT)) {
            throw new AccessDeniedException();
        }
        
        $objectType = $request->request->getAlnum('ot', 'route');
        $itemIds = $request->request->get('identifiers', []);
        $min = $request->request->getInt('min');
        $max = $request->request->getInt('max');
        
        if (!is_array($itemIds) || 2 > count($itemIds) || 1 > $max || $max <= $min) {
            return $this->json($this->__('Error: invalid input.'), JsonResponse::HTTP_BAD_REQUEST);
        }
        
        $repository = $entityFactory->getRepository($objectType);
        $sortableFieldMap = [
            'route' => 'sort'
        ];
        
        $sortFieldSetter = 'set' . ucfirst($sortableFieldMap[$objectType]);
        $sortCounter = $min;
        
        // update sort values
        foreach ($itemIds as $itemId) {
            if (empty($itemId) || !is_numeric($itemId)) {
                continue;
            }
            $entity = $repository->selectById($itemId);
            $entity->$sortFieldSetter($sortCounter);
            $sortCounter++;
        }
        
        // save entities back to database
        $entityFactory->getEntityManager()->flush();
        
        // return response
        return $this->json([
            'message' => $this->__('The setting has been successfully changed.')
        ]);
    }
}
