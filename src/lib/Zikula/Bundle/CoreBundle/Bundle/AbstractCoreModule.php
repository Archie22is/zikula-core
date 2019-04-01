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

namespace Zikula\Bundle\CoreBundle\Bundle;

use Zikula\Core\AbstractModule;

abstract class AbstractCoreModule extends AbstractModule
{
    public function getState(): int
    {
        return self::STATE_ACTIVE;
    }

    public function getTranslationDomain(): string
    {
        return 'zikula';
    }
}
