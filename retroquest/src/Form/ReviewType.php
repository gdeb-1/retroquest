<?php

namespace App\Form;

use App\Entity\Review;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('comment', TextareaType::class, [
                'label' => 'Votre avis',
                'constraints' => [
                    new NotBlank(
                        message: 'Votre avis ne peut pas être vide.',
                    ),
                    new Length(
                        min: 5,
                        minMessage: 'Votre avis doit contenir au moins {{ limit }} caractères.',
                        max: 1000,
                        maxMessage: 'Votre avis ne peut pas dépasser {{ limit }} caractères.',
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Review::class,
        ]);
    }
}
