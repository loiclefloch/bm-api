<?php

namespace BookmarkManager\ApiBundle\Form;

use BookmarkManager\ApiBundle\Entity\BookmarkCrawlerStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

class BookmarkFormType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'id',
                'integer',
                [
                    'required' => false,
                ]
            )
            ->add(
                'url',
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
                'name',
                'text',
                [
                    'required' => false,
                    'constraints' => (!$options['ignoreRequired'] ?
                        [
                            new Assert\Length(
                                [
                                    'min' => 0,
                                    'max' => 255,
                                ]
                            ),
                        ] : []),
                ]
            )
            ->add(
                'crawlerStatus',
                'choice',
                [
                    'required' => false,
                    'choices' => [
                        BookmarkCrawlerStatus::NO_RETRIEVE,
                        BookmarkCrawlerStatus::CONTENT_BUG,
                        BookmarkCrawlerStatus::RETRIEVED
                    ]
                ]
            )
            ->add(
                'notes',
                'text',
                [
                    'required' => false,
                ]
            );

        // We handle tags manually.
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'BookmarkManager\ApiBundle\Entity\Bookmark',
                'ignoreRequired' => false,
                'allow_extra_fields' => true,
            )
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'BookmarkManager_apibundle_bookmark';
    }
}
