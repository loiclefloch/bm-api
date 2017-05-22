<?php

namespace BookmarkManager\ApiBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

class CircleType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'name',
                'text',
                [
                    'required' => !$options['ignoreRequired'],
                    'constraints' => (!$options['ignoreRequired'] ?
                        [
                            new Assert\NotBlank(
                                array('message' => 'name is required')
                            ),
                        ] : []),
                ]
            )
            ->add(
                'members',
                'collection',
                [
                    'required' => true,
                    'mapped' => false,
                ]
            );
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'BookmarkManager\ApiBundle\Entity\Circle',
                'ignoreRequired' => false,
            )
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'bookmarkmanager_apibundle_team';
    }
}
