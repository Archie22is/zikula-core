<?php
/**
 * Routes.
 *
 * @copyright Zikula contributors (Zikula)
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @author Zikula contributors <support@zikula.org>.
 * @link http://www.zikula.org
 * @link http://zikula.org
 * @version Generated by ModuleStudio 1.1.0 (https://modulestudio.de).
 */

namespace Zikula\RoutesModule\Validator\Constraints\Base;

use Symfony\Component\Validator\Constraint;

/**
 * List entry validation constraint.
 */
abstract class AbstractListEntry extends Constraint
{
    /**
     * Entity name
     * @var string
     */
    public $entityName = '';

    /**
     * Property name
     * @var string
     */
    public $propertyName = '';

    /**
     * Whether multiple list values are allowed or not
     * @var boolean
     */
    public $multiple = false;

    /**
     * Minimum amount of values for multiple lists
     * @var integer
     */
    public $min;

    /**
     * Maximum amount of values for multiple lists
     * @var integer
     */
    public $max;

    /**
     * @inheritDoc
     */
    public function validatedBy()
    {
        return 'zikula_routes_module.validator.list_entry.validator';
    }
}
