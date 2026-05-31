<?php

namespace App\Form;

use App\Entity\CollectionItem;
use App\Entity\Exchange;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;

class ExchangeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('offeredItems', EntityType::class, [
                'class' => CollectionItem::class,
                'choices' => $options['available_items'],
                'choice_label' => function (CollectionItem $item) {
                    return sprintf('%s (%s) - %s', $item->getGame()->getTitle(), $item->getGame()->getConsole(), $item->getState()->value);
                },
                'multiple' => true,
                'expanded' => true,
                'label' => 'Vos jeux à proposer en échange',
                'mapped' => false,
                'constraints' => [
                    new Count(min: 1, minMessage: 'Vous devez proposer au moins un jeu de votre collection en échange.'),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Exchange::class,
            'proposer' => null,
            'available_items' => [],
        ]);

        $resolver->setRequired(['proposer', 'available_items']);
        $resolver->setAllowedTypes('proposer', User::class);
        $resolver->setAllowedTypes('available_items', 'array');
    }
}
