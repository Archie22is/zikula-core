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

namespace Zikula\RoutesModule\Event\Base;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event base class for extending item actions menu.
 */
class AbstractConfigureItemActionsMenuEvent extends Event
{
    /**
     * @var FactoryInterface.
     */
    protected $factory;

    /**
     * @var ItemInterface
     */
    protected $menu;

    /**
     * @var array
     */
    protected $options;

    public function __construct(
        FactoryInterface $factory,
        ItemInterface $menu,
        array $options = []
    ) {
        $this->factory = $factory;
        $this->menu = $menu;
        $this->options = $options;
    }

    /**
     * @return FactoryInterface
     */
    public function getFactory(): FactoryInterface
    {
        return $this->factory;
    }

    /**
     * @return ItemInterface
     */
    public function getMenu(): ItemInterface
    {
        return $this->menu;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
