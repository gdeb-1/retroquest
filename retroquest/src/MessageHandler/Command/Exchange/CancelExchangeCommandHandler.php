<?php

declare(strict_types=1);

namespace App\MessageHandler\Command\Exchange;

use App\Message\Command\Exchange\CancelExchangeCommand;
use App\Enum\ExchangeStatuses;
use App\Exception\ExchangeNotFoundException;
use App\Exception\UserNotFoundException;
use App\Repository\ExchangeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[AsMessageHandler]
class CancelExchangeCommandHandler
{
    public function __construct(
        private ExchangeRepository $exchangeRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {}

    public function __invoke(CancelExchangeCommand $command): void
    {
        $exchange = $this->exchangeRepository->find($command->exchangeId);
        if ($exchange === null) {
            throw new ExchangeNotFoundException('Échange introuvable.');
        }

        $user = $this->userRepository->find($command->userId);
        if ($user === null) {
            throw new UserNotFoundException('Utilisateur introuvable.');
        }

        if ($exchange->getProposer() !== $user) {
            throw new AccessDeniedException("Vous n'êtes pas autorisé à annuler cet échange.");
        }

        if ($exchange->getStatus() !== ExchangeStatuses::PENDING) {
            throw new \LogicException("Cet échange ne peut pas être annulé car il n'est plus en attente.");
        }

        $exchange->setStatus(ExchangeStatuses::CANCELLED);
        $this->entityManager->flush();
    }
}
