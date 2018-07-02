<?php

namespace BookmarkManager\ApiBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

class BookType extends AbstractType
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
                'description',
                'text',
                [
                    'required' => !$options['ignoreRequired'],
                    'constraints' => (!$options['ignoreRequired'] ?
                        [
                            new Assert\NotBlank(
                                array('message' => 'name is required')
                            )
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
                'data_class' => 'BookmarkManager\ApiBundle\Entity\Book',
                'ignoreRequired' => false,
            )
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'bookmarkmanager_apibundle_book';
    }
}