<?php

declare(strict_types=1);

namespace App\MessageHandler\Command\Exchange;

use App\Message\Command\Exchange\RejectExchangeCommand;
use App\Enum\ExchangeStatuses;
use App\Exception\ExchangeNotFoundException;
use App\Exception\UserNotFoundException;
use App\Repository\ExchangeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsMessageHandler]
class RejectExchangeCommandHandler
{
    public function __construct(
        private ExchangeRepository $exchangeRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        #[Target('exchange_status')]
        private WorkflowInterface $exchangeWorkflow
    ) {}

    public function __invoke(RejectExchangeCommand $command): void
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
            throw new AccessDeniedException("Vous n'êtes pas autorisé à refuser cet échange.");
        }

        if (!$this->exchangeWorkflow->can($exchange, 'reject')) {
            throw new \LogicException("Cet échange ne peut pas être refusé car il n'est plus en attente.");
        }

        $this->exchangeWorkflow->apply($exchange, 'reject');
        $this->entityManager->flush();
    }
}
