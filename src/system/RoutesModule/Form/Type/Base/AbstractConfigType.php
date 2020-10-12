<?php

/**
 * Routes.
 *
 * @copyright Zikula contributors (Zikula)
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @author Zikula contributors <info@ziku.la>.
 *
 * @see https://ziku.la
 *
 * @version Generated by ModuleStudio 1.5.0 (https://modulestudio.de).
 */

declare(strict_types=1);

namespace Zikula\RoutesModule\Form\Type\Base;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Translation\Extractor\Annotation\Ignore;
use Translation\Extractor\Annotation\Translate;
use Zikula\Bundle\FormExtensionBundle\Form\DataTransformer\NullToEmptyTransformer;
use Zikula\RoutesModule\AppSettings;

/**
 * Configuration form type base class.
 */
abstract class AbstractConfigType extends AbstractType
{
    public function __construct(
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->addListViewsFields($builder, $options);
        $this->addModerationFields($builder, $options);

        $this->addSubmitButtons($builder, $options);
    }

    /**
     * Adds fields for list views fields.
     */
    public function addListViewsFields(FormBuilderInterface $builder, array $options = []): void
    {
        $builder->add('routeEntriesPerPage', IntegerType::class, [
            'label' => 'Route entries per page:',
            'label_attr' => [
                'class' => 'tooltips',
                'title' => 'The amount of routes shown per page.',
            ],
            'help' => 'The amount of routes shown per page.',
            'empty_data' => 10,
            'attr' => [
                'maxlength' => 11,
                'class' => '',
                'title' => 'Enter the route entries per page. Only digits are allowed.',
            ],
            'required' => true,
        ]);
        $builder->add($builder->create('showOnlyOwnEntries', CheckboxType::class, [
            'label' => 'Show only own entries:',
            'label_attr' => [
                'class' => 'tooltips switch-custom',
                'title' => 'Whether only own entries should be shown on view pages by default or not.',
            ],
            'help' => 'Whether only own entries should be shown on view pages by default or not.',
            'attr' => [
                'class' => '',
                'title' => 'The show only own entries option',
            ],
            'required' => false,
        ])->addModelTransformer(new NullToEmptyTransformer()));
    }

    /**
     * Adds fields for moderation fields.
     */
    public function addModerationFields(FormBuilderInterface $builder, array $options = []): void
    {
        $builder->add($builder->create('allowModerationSpecificCreatorForRoute', CheckboxType::class, [
            'label' => 'Allow moderation specific creator for route:',
            'label_attr' => [
                'class' => 'tooltips switch-custom',
                'title' => 'Whether to allow moderators choosing a user which will be set as creator.',
            ],
            'help' => 'Whether to allow moderators choosing a user which will be set as creator.',
            'attr' => [
                'class' => '',
                'title' => 'The allow moderation specific creator for route option',
            ],
            'required' => false,
        ])->addModelTransformer(new NullToEmptyTransformer()));
        $builder->add($builder->create('allowModerationSpecificCreationDateForRoute', CheckboxType::class, [
            'label' => 'Allow moderation specific creation date for route:',
            'label_attr' => [
                'class' => 'tooltips switch-custom',
                'title' => 'Whether to allow moderators choosing a custom creation date.',
            ],
            'help' => 'Whether to allow moderators choosing a custom creation date.',
            'attr' => [
                'class' => '',
                'title' => 'The allow moderation specific creation date for route option',
            ],
            'required' => false,
        ])->addModelTransformer(new NullToEmptyTransformer()));
    }

    /**
     * Adds submit buttons.
     */
    public function addSubmitButtons(FormBuilderInterface $builder, array $options = []): void
    {
        $builder->add('save', SubmitType::class, [
            'label' => 'Update configuration',
            'icon' => 'fa-check',
            'attr' => [
                'class' => 'btn-success',
            ],
        ]);
        $builder->add('reset', ResetType::class, [
            'label' => 'Reset',
            'icon' => 'fa-sync',
            'attr' => [
                'formnovalidate' => 'formnovalidate',
            ],
        ]);
        $builder->add('cancel', SubmitType::class, [
            'label' => 'Cancel',
            'validate' => false,
            'icon' => 'fa-times',
            'attr' => [
                'formnovalidate' => 'formnovalidate',
            ],
        ]);
    }

    public function getBlockPrefix()
    {
        return 'zikularoutesmodule_config';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // define class for underlying data
            'data_class' => AppSettings::class,
        ]);
    }
}
