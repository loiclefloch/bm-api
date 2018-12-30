<?php

namespace BookmarkManager\ApiBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

class TagType extends AbstractType
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
                            new Assert\Length(
                                [
                                    'min' => 0,
                                    'max' => 255
                                ]
                            ),
                            new Assert\NotBlank(),
                            new Assert\NotNull(),
                        ] : []),
                ]
            )
            ->add(
                'color',
                'text',
                [
                    'required' => false,
                    'constraints' => (!$options['ignoreRequired'] ?
                        [
                            new Assert\Length(
                                [
                                    'min' => 0,
                                    'max' => 7
                                ]
                            ),
                        ] : []),
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
                'data_class' => 'BookmarkManager\ApiBundle\Entity\Tag',
                'ignoreRequired' => false,
            )
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'BookmarkManager_apibundle_tag';
    }
}
