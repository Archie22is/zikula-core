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

namespace Zikula\Bundle\CoreInstallerBundle\Form\Type;

use PDO;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Zikula\Bundle\CoreInstallerBundle\Validator\Constraints\ValidPdoConnection;

class DbCredsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('database_driver', ChoiceType::class, [
                'label' => $this->__('Database type'),
                'label_attr' => [
                    'class' => 'col-sm-3'
                ],
                'choices' => $this->getDbTypes(),
                'data' => 'mysql'
            ])
            ->add('dbtabletype', ChoiceType::class, [
                'label' => $this->__('Storage Engine'),
                'label_attr' => [
                    'class' => 'col-sm-3'
                ],
                'choices' => [
                    'InnoDB' => 'innodb',
                    'MyISAM' => 'myisam'
                ],
                'data' => 'innodb'
            ])
            ->add('database_host', TextType::class, [
                'label' => $this->__('Database Host'),
                'label_attr' => [
                    'class' => 'col-sm-3'
                ],
                'data' => 'localhost',
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('database_user', TextType::class, [
                'label' => $this->__('Database Username'),
                'label_attr' => [
                    'class' => 'col-sm-3'
                ],
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('database_password', PasswordType::class, [
                'label' => $this->__('Database Password'),
                'label_attr' => [
                    'class' => 'col-sm-3'
                ],
                'required' => false
            ])
            ->add('database_name', TextType::class, [
                'label' => $this->__('Database Name'),
                'label_attr' => [
                    'class' => 'col-sm-3'
                ],
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => 64]),
                    new Regex([
                        'pattern' => '/^[\w-]*$/',
                        'message' => $this->__('Error! Invalid database name. Please use only letters, numbers, "-" or "_".')
                    ])
                ]
            ])
        ;
    }

    public function getBlockPrefix()
    {
        return 'dbcreds';
    }

    private function getDbTypes(): array
    {
        $availableDrivers = PDO::getAvailableDrivers();

        $types = [];
        if (in_array('mysql', $availableDrivers, true)) {
            $types['MySQL'] = 'mysql';
        }
        if (in_array('sqlsrv', $availableDrivers, true)) {
            $types['MSSQL (alpha)'] = 'sqlsrv';
        }
        if (in_array('oci8', $availableDrivers, true)) {
            $types['Oracle (alpha) via OCI8 driver'] = 'oci8';
        } elseif (in_array('oci', $availableDrivers, true)) {
            $types['Oracle (alpha) via Oracle driver'] = 'oracle';
        }
        if (in_array('pgsql', $availableDrivers, true)) {
            $types['PostgreSQL'] = 'pgsql';
        }

        return $types;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'constraints' => new ValidPdoConnection()
        ]);
    }
}
