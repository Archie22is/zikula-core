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

namespace Zikula\Bundle\HookBundle\Category;

use Zikula\Bundle\HookBundle\Hook\DisplayHook;
use Zikula\Bundle\HookBundle\Hook\ProcessHook;
use Zikula\Bundle\HookBundle\Hook\ValidationHook;

class UiHooksCategory implements CategoryInterface
{
    const NAME = 'ui_hooks';

    /**
     * Display hook for view/display templates.
     * Dispatches DisplayHook instances.
     */
    const TYPE_DISPLAY_VIEW = 'display_view';

    /**
     * Display hook for create/edit forms.
     * Dispatches DisplayHook instances.
     */
    const TYPE_FORM_EDIT = 'form_edit';

    /**
     * Display hook for delete dialogues.
     * Dispatches DisplayHook instances.
     */
    const TYPE_FORM_DELETE = 'form_delete';

    /**
     * Used to validate input from a create/edit form.
     * Dispatches ValidationHook instances.
     */
    const TYPE_VALIDATE_EDIT = 'validate_edit';

    /**
     * Used to validate input from a delete form.
     * Dispatches ValidationHook instances.
     */
    const TYPE_VALIDATE_DELETE = 'validate_delete';

    /**
     * Perform the final update actions for a create/edit form.
     * Dispatches ProcessHook instances.
     */
    const TYPE_PROCESS_EDIT = 'process_edit';

    /**
     * Perform the final delete actions for a delete form.
     * Dispatches ProcessHook instances.
     */
    const TYPE_PROCESS_DELETE = 'process_delete';

    public function getName()
    {
        return self::NAME;
    }

    public function getTypes()
    {
        return [
            self::TYPE_DISPLAY_VIEW,
            self::TYPE_FORM_EDIT,
            self::TYPE_VALIDATE_EDIT,
            self::TYPE_PROCESS_EDIT,
            self::TYPE_FORM_DELETE,
            self::TYPE_VALIDATE_DELETE,
            self::TYPE_PROCESS_DELETE,
        ];
    }
}
