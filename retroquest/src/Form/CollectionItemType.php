<?php

namespace App\Form;

use App\Entity\CollectionItem;
use App\Entity\Game;
use App\Enum\CollectionItemStates;
use App\Enum\Currency;
use App\Repository\GameRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class CollectionItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('game', EntityType::class, [
                'class' => Game::class,
                'query_builder' => fn (GameRepository $gameRepository) => $gameRepository->findVisibleGames(),
                'choice_label' => function (Game $game) {
                    return sprintf('%s (%s)', $game->getTitle(), $game->getConsole());
                },
                'label' => 'Jeu',
                'placeholder' => 'Sélectionnez un jeu',
                'invalid_message' => 'Le jeu sélectionné n\'est pas valide.',
                'constraints' => [
                    new NotBlank(message: 'Veuillez sélectionner un jeu.'),
                ],
            ])
            ->add('state', EnumType::class, [
                'class' => CollectionItemStates::class,
                'choice_label' => fn (CollectionItemStates $choice) => $choice->value,
                'label' => 'État',
                'constraints' => [
                    new NotBlank(message: 'Veuillez sélectionner un état.'),
                ],
            ])
            ->add('acquisitionPrice', MoneyType::class, [
                'label' => 'Prix d\'acquisition',
                'divisor' => 100,
                'currency' => false,
                'constraints' => [
                    new NotBlank(message: 'Veuillez entrer un prix d\'acquisition.'),
                    new Positive(message: 'Le prix d\'acquisition doit être strictement supérieur à 0.'),
                ],
            ])
            ->add('currency', EnumType::class, [
                'class' => Currency::class,
                'choice_label' => fn (Currency $choice) => $choice->value,
                'label' => 'Devise',
                'constraints' => [
                    new NotBlank(message: 'Veuillez sélectionner une devise.'),
                ],
            ])
            ->add('acquisitionDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date d\'acquisition',
                'constraints' => [
                    new NotBlank(message: 'Veuillez renseigner la date d\'acquisition.'),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CollectionItem::class,
        ]);
    }
}
