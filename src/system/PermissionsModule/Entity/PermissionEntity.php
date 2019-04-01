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

namespace Zikula\PermissionsModule\Entity;

use Doctrine\ORM\Mapping as ORM;
use Zikula\Core\Doctrine\EntityAccess;

/**
 * Permission entity class.
 *
 * @ORM\Entity(repositoryClass="Zikula\PermissionsModule\Entity\Repository\PermissionRepository")
 * @ORM\Table(name="group_perms")
 */
class PermissionEntity extends EntityAccess
{
    /**
     * permission rule id
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $pid;

    /**
     * group id for the rule
     *
     * @ORM\Column(type="integer")
     */
    private $gid;

    /**
     * the place of the rule in the sequence
     *
     * @ORM\Column(type="integer")
     */
    private $sequence;

    /**
     * the realm assoiciated with this rule
     *
     * @ORM\Column(type="integer")
     */
    private $realm;

    /**
     * the component part of the rule
     *
     * @ORM\Column(type="string", length=255)
     */
    private $component;

    /**
     * the instance part of the rule
     *
     * @ORM\Column(type="string", length=255)
     */
    private $instance;

    /**
     * the access level of the rule
     *
     * @ORM\Column(type="integer")
     */
    private $level;

    /**
     * the bond of the rule
     *
     * @ORM\Column(type="integer")
     */
    private $bond;

    public function __construct()
    {
        $this->gid = 0;
        $this->sequence = 0;
        $this->realm = 0;
        $this->component = '';
        $this->instance = '';
        $this->level = 0;
        $this->bond = 0;
    }

    public function getPid(): int
    {
        return $this->pid;
    }

    public function setPid(int $pid): void
    {
        $this->pid = $pid;
    }

    public function getGid(): int
    {
        return $this->gid;
    }

    public function setGid(int $gid): void
    {
        $this->gid = $gid;
    }

    public function getSequence(): int
    {
        return $this->sequence;
    }

    public function setSequence(int $sequence): void
    {
        $this->sequence = $sequence;
    }

    public function getRealm(): int
    {
        return $this->realm;
    }

    public function setRealm(int $realm): void
    {
        $this->realm = $realm;
    }

    public function getComponent(): string
    {
        return $this->component;
    }

    public function setComponent(string $component): void
    {
        $this->component = $component;
    }

    public function getInstance(): string
    {
        return $this->instance;
    }

    public function setInstance(string $instance): void
    {
        $this->instance = $instance;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

    public function getBond(): int
    {
        return $this->bond;
    }

    public function setBond(int $bond): void
    {
        $this->bond = $bond;
    }
}
