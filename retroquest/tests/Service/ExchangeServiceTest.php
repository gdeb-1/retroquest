<?php

namespace App\Tests\Service;

use App\Entity\CollectionItem;
use App\Entity\Exchange;
use App\Entity\User;
use App\Enum\ExchangeStatuses;
use App\Service\ExchangeService;
use PHPUnit\Framework\TestCase;

class ExchangeServiceTest extends TestCase
{
    private ExchangeService $exchangeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exchangeService = new ExchangeService();
    }

    public function testIsDirectExchangeEligibleSuccess(): void
    {
        $proposer = new User();
        $proposer->setEmail('proposer@example.com');

        $receiver = new User();
        $receiver->setEmail('receiver@example.com');

        $itemProposer = new CollectionItem();
        $proposer->addCollectionItem($itemProposer);

        $itemReceiver = new CollectionItem();
        $receiver->addCollectionItem($itemReceiver);

        $exchange = new Exchange();
        $exchange->setProposer($proposer);
        $exchange->setReceiver($receiver);
        $exchange->addItem($itemProposer);
        $exchange->addItem($itemReceiver);

        $this->assertTrue($this->exchangeService->isDirectExchangeEligible($exchange));
    }

    public function testIsDirectExchangeEligibleSameProposerAndReceiver(): void
    {
        $user = new User();
        $user->setEmail('user@example.com');

        $item1 = new CollectionItem();
        $user->addCollectionItem($item1);

        $exchange = new Exchange();
        $exchange->setProposer($user);
        $exchange->setReceiver($user);
        $exchange->addItem($item1);

        $this->assertFalse($this->exchangeService->isDirectExchangeEligible($exchange));
    }

    public function testIsDirectExchangeEligibleNullProposer(): void
    {
        $receiver = new User();
        $receiver->setEmail('receiver@example.com');

        $item = new CollectionItem();
        $receiver->addCollectionItem($item);

        $exchange = new Exchange();
        $exchange->setReceiver($receiver);
        $exchange->addItem($item);

        $this->assertFalse($this->exchangeService->isDirectExchangeEligible($exchange));
    }

    public function testIsDirectExchangeEligibleNullReceiver(): void
    {
        $proposer = new User();
        $proposer->setEmail('proposer@example.com');

        $item = new CollectionItem();
        $proposer->addCollectionItem($item);

        $exchange = new Exchange();
        $exchange->setProposer($proposer);
        $exchange->addItem($item);

        $this->assertFalse($this->exchangeService->isDirectExchangeEligible($exchange));
    }

    public function testIsDirectExchangeEligibleNoItems(): void
    {
        $proposer = new User();
        $proposer->setEmail('proposer@example.com');

        $receiver = new User();
        $receiver->setEmail('receiver@example.com');

        $exchange = new Exchange();
        $exchange->setProposer($proposer);
        $exchange->setReceiver($receiver);

        $this->assertFalse($this->exchangeService->isDirectExchangeEligible($exchange));
    }

    public function testIsDirectExchangeEligibleNoProposerItems(): void
    {
        $proposer = new User();
        $proposer->setEmail('proposer@example.com');

        $receiver = new User();
        $receiver->setEmail('receiver@example.com');

        $itemReceiver = new CollectionItem();
        $receiver->addCollectionItem($itemReceiver);

        $exchange = new Exchange();
        $exchange->setProposer($proposer);
        $exchange->setReceiver($receiver);
        $exchange->addItem($itemReceiver);

        $this->assertFalse($this->exchangeService->isDirectExchangeEligible($exchange));
    }

    public function testIsDirectExchangeEligibleNoReceiverItems(): void
    {
        $proposer = new User();
        $proposer->setEmail('proposer@example.com');

        $receiver = new User();
        $receiver->setEmail('receiver@example.com');

        $itemProposer = new CollectionItem();
        $proposer->addCollectionItem($itemProposer);

        $exchange = new Exchange();
        $exchange->setProposer($proposer);
        $exchange->setReceiver($receiver);
        $exchange->addItem($itemProposer);

        $this->assertFalse($this->exchangeService->isDirectExchangeEligible($exchange));
    }

    public function testIsDirectExchangeEligibleThirdPartyItem(): void
    {
        $proposer = new User();
        $proposer->setEmail('proposer@example.com');

        $receiver = new User();
        $receiver->setEmail('receiver@example.com');

        $thirdParty = new User();
        $thirdParty->setEmail('thirdparty@example.com');

        $itemProposer = new CollectionItem();
        $proposer->addCollectionItem($itemProposer);

        $itemThirdParty = new CollectionItem();
        $thirdParty->addCollectionItem($itemThirdParty);

        $exchange = new Exchange();
        $exchange->setProposer($proposer);
        $exchange->setReceiver($receiver);
        $exchange->addItem($itemProposer);
        $exchange->addItem($itemThirdParty);

        $this->assertTrue($this->exchangeService->isDirectExchangeEligible($exchange));
    }
    
    public function testIsDirectExchangeEligibleItemInAnotherPendingExchange(): void
    {
        $proposer = new User();
        $proposer->setEmail('proposer@example.com');

        $receiver = new User();
        $receiver->setEmail('receiver@example.com');

        $itemProposer = new CollectionItem();
        $proposer->addCollectionItem($itemProposer);

        $itemReceiver = new CollectionItem();
        $receiver->addCollectionItem($itemReceiver);

        $exchange = new Exchange();
        $exchange->setProposer($proposer);
        $exchange->setReceiver($receiver);
        $exchange->addItem($itemProposer);
        $exchange->addItem($itemReceiver);

        $otherExchange = new Exchange();
        $otherExchange->setStatus(ExchangeStatuses::PENDING);
        $otherExchange->addItem($itemProposer);
        $itemProposer->addExchange($otherExchange);

        $this->assertFalse($this->exchangeService->isDirectExchangeEligible($exchange));
    }

    public function testIsDirectExchangeEligibleItemInAnotherAcceptedExchange(): void
    {
        $proposer = new User();
        $proposer->setEmail('proposer@example.com');

        $receiver = new User();
        $receiver->setEmail('receiver@example.com');

        $itemProposer = new CollectionItem();
        $proposer->addCollectionItem($itemProposer);

        $itemReceiver = new CollectionItem();
        $receiver->addCollectionItem($itemReceiver);

        $exchange = new Exchange();
        $exchange->setProposer($proposer);
        $exchange->setReceiver($receiver);
        $exchange->addItem($itemProposer);
        $exchange->addItem($itemReceiver);

        $otherExchange = new Exchange();
        $otherExchange->setStatus(ExchangeStatuses::ACCEPTED);
        $otherExchange->addItem($itemReceiver);
        $itemReceiver->addExchange($otherExchange);

        $this->assertTrue($this->exchangeService->isDirectExchangeEligible($exchange));
    }

    public function testIsDirectExchangeEligibleItemInAnotherRejectedOrCancelledExchange(): void
    {
        $proposer = new User();
        $proposer->setEmail('proposer@example.com');

        $receiver = new User();
        $receiver->setEmail('receiver@example.com');

        $itemProposer = new CollectionItem();
        $proposer->addCollectionItem($itemProposer);

        $itemReceiver = new CollectionItem();
        $receiver->addCollectionItem($itemReceiver);

        $exchange = new Exchange();
        $exchange->setProposer($proposer);
        $exchange->setReceiver($receiver);
        $exchange->addItem($itemProposer);
        $exchange->addItem($itemReceiver);

        $rejectedExchange = new Exchange();
        $rejectedExchange->setStatus(ExchangeStatuses::REJECTED);
        $rejectedExchange->addItem($itemProposer);
        $itemProposer->addExchange($rejectedExchange);

        $cancelledExchange = new Exchange();
        $cancelledExchange->setStatus(ExchangeStatuses::CANCELLED);
        $cancelledExchange->addItem($itemReceiver);
        $itemReceiver->addExchange($cancelledExchange);

        $this->assertTrue($this->exchangeService->isDirectExchangeEligible($exchange));
    }
}
