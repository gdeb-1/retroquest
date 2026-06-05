<?php

declare(strict_types=1);

namespace App\MessageHandler\Command\Exchange;

use App\Message\Command\Exchange\ValidateExchangeCommand;
use App\Exception\ExchangeNotFoundException;
use App\Exception\UserNotFoundException;
use App\Repository\ExchangeRepository;
use App\Repository\UserRepository;
use App\Service\ExchangeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[AsMessageHandler]
class ValidateExchangeCommandHandler
{
    public function __construct(
        private ExchangeRepository $exchangeRepository,
        private UserRepository $userRepository,
        private ExchangeService $exchangeService,
        private EntityManagerInterface $entityManager
    ) {}

    public function __invoke(ValidateExchangeCommand $command): void
    {
        $exchange = $this->exchangeRepository->find($command->exchangeId);
        if ($exchange === null) {
            throw new ExchangeNotFoundException('Échange introuvable.');
        }

        $user = $this->userRepository->find($command->userId);
        if ($user === null) {
            throw new UserNotFoundException('Utilisateur introuvable.');
        }

        if ($exchange->getReceiver() !== $user) {
            throw new AccessDeniedException("Vous n'êtes pas autorisé à valider cet échange.");
        }

        $this->exchangeService->validateExchange($exchange);
        $this->entityManager->flush();
    }
}
