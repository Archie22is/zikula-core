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

namespace Zikula\Bundle\CoreInstallerBundle\Validator\Constraints;

use PDO;
use PDOException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\Common\Translator\TranslatorTrait;

class ValidPdoConnectionValidator extends ConstraintValidator
{
    use TranslatorTrait;

    public function __construct(TranslatorInterface $translator)
    {
        $this->setTranslator($translator);
    }

    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    public function validate($object, Constraint $constraint)
    {
        if ('' === $object['database_host'] || '' === $object['database_name'] || '' === $object['database_user']) {
            $this->context
                ->buildViolation($this->__('Error! Please enter your database credentials.'))
                ->addViolation()
            ;

            return;
        }

        $dbName = $object['database_name'];
        $dsn = $object['database_driver'] . ':host=' . $object['database_host'] . ';dbname=' . $dbName;
        try {
            $dbh = new PDO($dsn, $object['database_user'], $object['database_password']);
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sql = in_array($object['database_driver'], ['mysql', 'mysqli'])
                ? 'SHOW TABLES FROM `' . $dbName . "` LIKE '%'"
                : 'SHOW TABLES FROM ' . $dbName . " LIKE '%'";
            $tables = $dbh->query($sql);
            if (!is_object($tables)) {
                $this->context
                    ->buildViolation($this->__('Error! Determination existing tables failed.') . ' SQL: ' . $sql)
                    ->addViolation()
                ;
            } elseif ($tables->rowCount() > 0) {
                $this->context
                    ->buildViolation($this->__('Error! The database exists and contains tables. Please delete all tables before proceeding or select a new database.'))
                    ->addViolation()
                ;
            }
        } catch (PDOException $exception) {
            $this->context
                ->buildViolation($this->__('Error! Could not connect to the database. Please check that you have entered the correct database information and try again. ') . $exception->getMessage())
                ->addViolation()
            ;
        }
    }
}
