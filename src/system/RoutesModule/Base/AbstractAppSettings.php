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

namespace Zikula\RoutesModule\Base;

use Symfony\Component\Validator\Constraints as Assert;
use Zikula\ExtensionsModule\Api\ApiInterface\VariableApiInterface;

/**
 * Application settings class for handling module variables.
 */
abstract class AbstractAppSettings
{
    /**
     * @var VariableApiInterface
     */
    protected $variableApi;
    
    /**
     * The amount of routes shown per page
     *
     * @Assert\Type(type="integer")
     * @Assert\NotBlank()
     * @Assert\NotEqualTo(value=0)
     * @Assert\LessThan(value=100000000000)
     * @var int $routeEntriesPerPage
     */
    protected $routeEntriesPerPage = 10;
    
    /**
     * Whether only own entries should be shown on view pages by default or not
     *
     * @Assert\NotNull()
     * @Assert\Type(type="bool")
     * @var bool $showOnlyOwnEntries
     */
    protected $showOnlyOwnEntries = false;
    
    /**
     * Whether to allow moderators choosing a user which will be set as creator.
     *
     * @Assert\NotNull()
     * @Assert\Type(type="bool")
     * @var bool $allowModerationSpecificCreatorForRoute
     */
    protected $allowModerationSpecificCreatorForRoute = false;
    
    /**
     * Whether to allow moderators choosing a custom creation date.
     *
     * @Assert\NotNull()
     * @Assert\Type(type="bool")
     * @var bool $allowModerationSpecificCreationDateForRoute
     */
    protected $allowModerationSpecificCreationDateForRoute = false;
    
    
    public function __construct(
        VariableApiInterface $variableApi
    ) {
        $this->variableApi = $variableApi;
    
        $this->load();
    }
    
    public function getRouteEntriesPerPage(): int
    {
        return $this->routeEntriesPerPage;
    }
    
    public function setRouteEntriesPerPage(int $routeEntriesPerPage): void
    {
        if ((int)$this->routeEntriesPerPage !== $routeEntriesPerPage) {
            $this->routeEntriesPerPage = $routeEntriesPerPage;
        }
    }
    
    public function getShowOnlyOwnEntries(): bool
    {
        return $this->showOnlyOwnEntries;
    }
    
    public function setShowOnlyOwnEntries(bool $showOnlyOwnEntries): void
    {
        if ((bool)$this->showOnlyOwnEntries !== $showOnlyOwnEntries) {
            $this->showOnlyOwnEntries = $showOnlyOwnEntries;
        }
    }
    
    public function getAllowModerationSpecificCreatorForRoute(): bool
    {
        return $this->allowModerationSpecificCreatorForRoute;
    }
    
    public function setAllowModerationSpecificCreatorForRoute(bool $allowModerationSpecificCreatorForRoute): void
    {
        if ((bool)$this->allowModerationSpecificCreatorForRoute !== $allowModerationSpecificCreatorForRoute) {
            $this->allowModerationSpecificCreatorForRoute = $allowModerationSpecificCreatorForRoute;
        }
    }
    
    public function getAllowModerationSpecificCreationDateForRoute(): bool
    {
        return $this->allowModerationSpecificCreationDateForRoute;
    }
    
    public function setAllowModerationSpecificCreationDateForRoute(bool $allowModerationSpecificCreationDateForRoute): void
    {
        if ((bool)$this->allowModerationSpecificCreationDateForRoute !== $allowModerationSpecificCreationDateForRoute) {
            $this->allowModerationSpecificCreationDateForRoute = $allowModerationSpecificCreationDateForRoute;
        }
    }
    
    
    /**
     * Loads module variables from the database.
     */
    protected function load(): void
    {
        $moduleVars = $this->variableApi->getAll('ZikulaRoutesModule');
    
        if (isset($moduleVars['routeEntriesPerPage'])) {
            $this->setRouteEntriesPerPage($moduleVars['routeEntriesPerPage']);
        }
        if (isset($moduleVars['showOnlyOwnEntries'])) {
            $this->setShowOnlyOwnEntries($moduleVars['showOnlyOwnEntries']);
        }
        if (isset($moduleVars['allowModerationSpecificCreatorForRoute'])) {
            $this->setAllowModerationSpecificCreatorForRoute($moduleVars['allowModerationSpecificCreatorForRoute']);
        }
        if (isset($moduleVars['allowModerationSpecificCreationDateForRoute'])) {
            $this->setAllowModerationSpecificCreationDateForRoute($moduleVars['allowModerationSpecificCreationDateForRoute']);
        }
    }
    
    /**
     * Saves module variables into the database.
     */
    public function save(): void
    {
        $this->variableApi->set('ZikulaRoutesModule', 'routeEntriesPerPage', $this->getRouteEntriesPerPage());
        $this->variableApi->set('ZikulaRoutesModule', 'showOnlyOwnEntries', $this->getShowOnlyOwnEntries());
        $this->variableApi->set('ZikulaRoutesModule', 'allowModerationSpecificCreatorForRoute', $this->getAllowModerationSpecificCreatorForRoute());
        $this->variableApi->set('ZikulaRoutesModule', 'allowModerationSpecificCreationDateForRoute', $this->getAllowModerationSpecificCreationDateForRoute());
    }
}
