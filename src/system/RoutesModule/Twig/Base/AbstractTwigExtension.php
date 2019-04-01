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

namespace Zikula\RoutesModule\Twig\Base;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\Common\Translator\TranslatorTrait;
use Zikula\Core\Doctrine\EntityAccess;
use Zikula\ExtensionsModule\Api\ApiInterface\VariableApiInterface;
use Zikula\RoutesModule\Helper\EntityDisplayHelper;
use Zikula\RoutesModule\Helper\ListEntriesHelper;
use Zikula\RoutesModule\Helper\WorkflowHelper;

/**
 * Twig extension base class.
 */
abstract class AbstractTwigExtension extends AbstractExtension
{
    use TranslatorTrait;
    
    /**
     * @var VariableApiInterface
     */
    protected $variableApi;
    
    /**
     * @var EntityDisplayHelper
     */
    protected $entityDisplayHelper;
    
    /**
     * @var WorkflowHelper
     */
    protected $workflowHelper;
    
    /**
     * @var ListEntriesHelper
     */
    protected $listHelper;
    
    public function __construct(
        TranslatorInterface $translator,
        VariableApiInterface $variableApi,
        EntityDisplayHelper $entityDisplayHelper,
        WorkflowHelper $workflowHelper,
        ListEntriesHelper $listHelper
    ) {
        $this->setTranslator($translator);
        $this->variableApi = $variableApi;
        $this->entityDisplayHelper = $entityDisplayHelper;
        $this->workflowHelper = $workflowHelper;
        $this->listHelper = $listHelper;
    }
    
    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }
    
    public function getFunctions()
    {
        return [
            new TwigFunction('zikularoutesmodule_objectTypeSelector', [$this, 'getObjectTypeSelector']),
            new TwigFunction('zikularoutesmodule_templateSelector', [$this, 'getTemplateSelector'])
        ];
    }
    
    public function getFilters()
    {
        return [
            new TwigFilter('zikularoutesmodule_listEntry', [$this, 'getListEntry']),
            new TwigFilter('zikularoutesmodule_formattedTitle', [$this, 'getFormattedEntityTitle']),
            new TwigFilter('zikularoutesmodule_objectState', [$this, 'getObjectState'], ['is_safe' => ['html']])
        ];
    }
    
    /**
     * The zikularoutesmodule_objectState filter displays the name of a given object's workflow state.
     * Examples:
     *    {{ item.workflowState|zikularoutesmodule_objectState }}        {# with visual feedback #}
     *    {{ item.workflowState|zikularoutesmodule_objectState(false) }} {# no ui feedback #}
     */
    public function getObjectState(string $state = 'initial', bool $uiFeedback = true): string
    {
        $stateInfo = $this->workflowHelper->getStateInfo($state);
    
        $result = $stateInfo['text'];
        if (true === $uiFeedback) {
            $result = '<span class="label label-' . $stateInfo['ui'] . '">' . $result . '</span>';
        }
    
        return $result;
    }
    
    
    /**
     * The zikularoutesmodule_listEntry filter displays the name
     * or names for a given list item.
     * Example:
     *     {{ entity.listField|zikularoutesmodule_listEntry('entityName', 'fieldName') }}
     */
    public function getListEntry(string $value, string $objectType = '', string $fieldName = '', string $delimiter = ', '): string
    {
        if ((empty($value) && '0' !== $value) || empty($objectType) || empty($fieldName)) {
            return $value;
        }
    
        return $this->listHelper->resolve($value, $objectType, $fieldName, $delimiter);
    }
    
    
    
    
    
    /**
     * The zikularoutesmodule_formattedTitle filter outputs a formatted title for a given entity.
     * Example:
     *     {{ myPost|zikularoutesmodule_formattedTitle }}
     */
    public function getFormattedEntityTitle(EntityAccess $entity): string
    {
        return $this->entityDisplayHelper->getFormattedTitle($entity);
    }
}
