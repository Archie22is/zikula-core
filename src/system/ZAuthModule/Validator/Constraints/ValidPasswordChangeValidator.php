<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ZAuthModule\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\ZAuthModule\Entity\RepositoryInterface\AuthenticationMappingRepositoryInterface;

class ValidPasswordChangeValidator extends ConstraintValidator
{
    /**
     * @var AuthenticationMappingRepositoryInterface
     */
    private $repository;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * ValidPasswordChangeValidator constructor.
     * @param AuthenticationMappingRepositoryInterface $repository
     * @param TranslatorInterface $translator
     */
    public function __construct(AuthenticationMappingRepositoryInterface $repository, TranslatorInterface $translator)
    {
        $this->repository = $repository;
        $this->translator = $translator;
    }

    public function validate($data, Constraint $constraint)
    {
        $userEntity = $this->repository->findOneBy(['uid' => $data['uid']]);
        if ($userEntity) {
            $currentPass = $userEntity->getPass();
            // is oldpass correct?
            if (empty($data['oldpass']) || !\UserUtil::passwordsMatch($data['oldpass'], $currentPass)) {
                $this->context->buildViolation($this->translator->__('Old password is incorrect.'))
                    ->atPath('oldpass')
                    ->addViolation();
            }
            // oldpass == newpass??
            if (isset($data['pass']) && $data['oldpass'] == $data['pass']) {
                $this->context->buildViolation($this->translator->__('Your new password cannot match your current password.'))
                    ->atPath('pass')
                    ->addViolation();
            }
        } else {
            $this->context->buildViolation($this->translator->__('Could not find user to update.'))
                ->atPath('oldpass')
                ->addViolation();
        }
    }
}
