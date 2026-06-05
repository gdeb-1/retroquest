<?php

namespace App\Tests\Service;

use App\Entity\CollectionItem;
use App\Entity\Exchange;
use App\Entity\User;
use App\Enum\ExchangeStatuses;
use App\Exception\InvalidStateExchangeException;
use App\Exception\NotEligibleExchangeException;
use App\Service\ExchangeService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ExchangeServiceTest extends TestCase
{
    private ExchangeService $exchangeService;

    protected function setUp(): void
    {
        parent::setUp();

        $workflow = $this->createStub(\Symfony\Component\Workflow\WorkflowInterface::class);
        
        $workflow->method('can')->willReturnCallback(function (Exchange $exchange, string $transition) {
            return $exchange->getStatus() === ExchangeStatuses::PENDING;
        });

        $workflow->method('apply')->willReturnCallback(function (Exchange $exchange, string $transition) {
            if ($transition === 'validate') {
                $this->exchangeService->processExchangeTransfer($exchange);
                $exchange->setStatus(ExchangeStatuses::ACCEPTED);
                
                foreach ($exchange->getItems() as $item) {
                    foreach ($item->getExchanges() as $otherExchange) {
                        if ($otherExchange !== $exchange && $otherExchange->getStatus() === ExchangeStatuses::PENDING) {
                            $otherExchange->setStatus(ExchangeStatuses::CANCELLED);
                        }
                    }
                }
            } elseif ($transition === 'cancel') {
                $exchange->setStatus(ExchangeStatuses::CANCELLED);
            }
            return $this->createStub(\Symfony\Component\Workflow\Marking::class);
        });

        $this->exchangeService = new ExchangeService($workflow);
    }

    #[DataProvider('exchangeEligibilityProvider')]
    public function testIsDirectExchangeEligible(callable $setupCallback, bool $expectedResult): void
    {
        $exchange = $setupCallback();
        $this->assertSame($expectedResult, $this->exchangeService->isDirectExchangeEligible($exchange));
    }

    public static function exchangeEligibilityProvider(): array
    {
        $createUser = function (string $email) {
            $user = new User();
            $user->setEmail($email);
            return $user;
        };

        $createItem = function (User $owner) {
            $item = new CollectionItem();
            $owner->addCollectionItem($item);
            return $item;
        };

        $createExchange = function (?User $proposer, ?User $receiver) {
            $exchange = new Exchange();
            if ($proposer) {
                $exchange->setProposer($proposer);
            }
            if ($receiver) {
                $exchange->setReceiver($receiver);
            }
            return $exchange;
        };

        return [
            'success_nominal' => [
                function () use ($createUser, $createItem, $createExchange) {
                    $proposer = $createUser('proposer@example.com');
                    $receiver = $createUser('receiver@example.com');
                    $exchange = $createExchange($proposer, $receiver);
                    $exchange->addItem($createItem($proposer));
                    $exchange->addItem($createItem($receiver));
                    return $exchange;
                },
                true
            ],
            'error_same_proposer_and_receiver' => [
                function () use ($createUser, $createItem, $createExchange) {
                    $user = $createUser('user@example.com');
                    $exchange = $createExchange($user, $user);
                    $exchange->addItem($createItem($user));
                    return $exchange;
                },
                false
            ],
            'error_null_proposer' => [
                function () use ($createUser, $createItem, $createExchange) {
                    $receiver = $createUser('receiver@example.com');
                    $exchange = $createExchange(null, $receiver);
                    $exchange->addItem($createItem($receiver));
                    return $exchange;
                },
                false
            ],
            'error_null_receiver' => [
                function () use ($createUser, $createItem, $createExchange) {
                    $proposer = $createUser('proposer@example.com');
                    $exchange = $createExchange($proposer, null);
                    $exchange->addItem($createItem($proposer));
                    return $exchange;
                },
                false
            ],
            'error_no_items' => [
                function () use ($createUser, $createExchange) {
                    $proposer = $createUser('proposer@example.com');
                    $receiver = $createUser('receiver@example.com');
                    return $createExchange($proposer, $receiver);
                },
                false
            ],
            'error_no_proposer_items' => [
                function () use ($createUser, $createItem, $createExchange) {
                    $proposer = $createUser('proposer@example.com');
                    $receiver = $createUser('receiver@example.com');
                    $exchange = $createExchange($proposer, $receiver);
                    $exchange->addItem($createItem($receiver));
                    return $exchange;
                },
                false
            ],
            'error_no_receiver_items' => [
                function () use ($createUser, $createItem, $createExchange) {
                    $proposer = $createUser('proposer@example.com');
                    $receiver = $createUser('receiver@example.com');
                    $exchange = $createExchange($proposer, $receiver);
                    $exchange->addItem($createItem($proposer));
                    return $exchange;
                },
                false
            ],
            'error_third_party_item' => [
                function () use ($createUser, $createItem, $createExchange) {
                    $proposer = $createUser('proposer@example.com');
                    $receiver = $createUser('receiver@example.com');
                    $thirdParty = $createUser('thirdparty@example.com');
                    
                    $exchange = $createExchange($proposer, $receiver);
                    $exchange->addItem($createItem($proposer));
                    $exchange->addItem($createItem($thirdParty));
                    return $exchange;
                },
                false
            ],
            'success_item_in_another_pending_exchange_but_different_items_count' => [
                function () use ($createUser, $createItem, $createExchange) {
                    $proposer = $createUser('proposer@example.com');
                    $receiver = $createUser('receiver@example.com');
                    
                    $exchange = $createExchange($proposer, $receiver);
                    $itemP = $createItem($proposer);
                    $itemR = $createItem($receiver);
                    $exchange->addItem($itemP);
                    $exchange->addItem($itemR);
                    
                    $otherExchange = new Exchange();
                    $otherExchange->setStatus(ExchangeStatuses::PENDING);
                    $otherExchange->addItem($itemP);
                    $itemP->addExchange($otherExchange);
                    
                    return $exchange;
                },
                true
            ],
            'success_item_in_another_pending_exchange_same_items_count_but_different_items' => [
                function () use ($createUser, $createItem, $createExchange) {
                    $proposer = $createUser('proposer@example.com');
                    $receiver = $createUser('receiver@example.com');
                    
                    $exchange = $createExchange($proposer, $receiver);
                    $itemP = $createItem($proposer);
                    $itemR = $createItem($receiver);
                    $exchange->addItem($itemP);
                    $exchange->addItem($itemR);
                    
                    $anotherItemP = $createItem($proposer);
                    
                    $otherExchange = new Exchange();
                    $otherExchange->setStatus(ExchangeStatuses::PENDING);
                    $otherExchange->addItem($anotherItemP);
                    $otherExchange->addItem($itemR);
                    
                    $itemR->addExchange($otherExchange);
                    $anotherItemP->addExchange($otherExchange);
                    
                    return $exchange;
                },
                true
            ],
            'error_duplicate_pending_exchange' => [
                function () use ($createUser, $createItem, $createExchange) {
                    $proposer = $createUser('proposer@example.com');
                    $receiver = $createUser('receiver@example.com');
                    
                    $exchange = $createExchange($proposer, $receiver);
                    $itemP = $createItem($proposer);
                    $itemR = $createItem($receiver);
                    $exchange->addItem($itemP);
                    $exchange->addItem($itemR);
                    
                    $otherExchange = new Exchange();
                    $otherExchange->setStatus(ExchangeStatuses::PENDING);
                    $otherExchange->addItem($itemP);
                    $otherExchange->addItem($itemR);
                    $itemP->addExchange($otherExchange);
                    $itemR->addExchange($otherExchange);
                    
                    return $exchange;
                },
                false
            ],
            'success_item_in_another_accepted_exchange' => [
                function () use ($createUser, $createItem, $createExchange) {
                    $proposer = $createUser('proposer@example.com');
                    $receiver = $createUser('receiver@example.com');
                    
                    $exchange = $createExchange($proposer, $receiver);
                    $itemP = $createItem($proposer);
                    $itemR = $createItem($receiver);
                    $exchange->addItem($itemP);
                    $exchange->addItem($itemR);
                    
                    $otherExchange = new Exchange();
                    $otherExchange->setStatus(ExchangeStatuses::ACCEPTED);
                    $otherExchange->addItem($itemP);
                    $otherExchange->addItem($itemR);
                    $itemP->addExchange($otherExchange);
                    $itemR->addExchange($otherExchange);
                    
                    return $exchange;
                },
                true
            ],
            'success_item_in_another_rejected_or_cancelled_exchange' => [
                function () use ($createUser, $createItem, $createExchange) {
                    $proposer = $createUser('proposer@example.com');
                    $receiver = $createUser('receiver@example.com');
                    
                    $exchange = $createExchange($proposer, $receiver);
                    $itemP = $createItem($proposer);
                    $itemR = $createItem($receiver);
                    $exchange->addItem($itemP);
                    $exchange->addItem($itemR);
                    
                    $rejectedExchange = new Exchange();
                    $rejectedExchange->setStatus(ExchangeStatuses::REJECTED);
                    $rejectedExchange->addItem($itemP);
                    $itemP->addExchange($rejectedExchange);
                    
                    $cancelledExchange = new Exchange();
                    $cancelledExchange->setStatus(ExchangeStatuses::CANCELLED);
                    $cancelledExchange->addItem($itemR);
                    $itemR->addExchange($cancelledExchange);
                    
                    return $exchange;
                },
                true
            ],
        ];
    }

    public function testValidateExchangeSuccess(): void
    {
        $proposer = new User();
        $proposer->setEmail('proposer@example.com');
        $receiver = new User();
        $receiver->setEmail('receiver@example.com');

        $exchange = new Exchange();
        $exchange->setStatus(ExchangeStatuses::PENDING);
        $exchange->setProposer($proposer);
        $exchange->setReceiver($receiver);

        $itemP = new CollectionItem();
        $proposer->addCollectionItem($itemP);
        $exchange->addItem($itemP);

        $itemR = new CollectionItem();
        $receiver->addCollectionItem($itemR);
        $exchange->addItem($itemR);

        // Verify initial state
        $this->assertSame($proposer, $itemP->getCollector());
        $this->assertSame($receiver, $itemR->getCollector());
        $this->assertTrue($proposer->getCollectionItems()->contains($itemP));
        $this->assertTrue($receiver->getCollectionItems()->contains($itemR));
        $this->assertFalse($proposer->getCollectionItems()->contains($itemR));
        $this->assertFalse($receiver->getCollectionItems()->contains($itemP));

        // Validate the exchange
        $this->exchangeService->validateExchange($exchange);

        // Verify status has changed to ACCEPTED
        $this->assertSame(ExchangeStatuses::ACCEPTED, $exchange->getStatus());

        // Verify ownership has been swapped
        $this->assertSame($receiver, $itemP->getCollector());
        $this->assertSame($proposer, $itemR->getCollector());
        $this->assertTrue($receiver->getCollectionItems()->contains($itemP));
        $this->assertTrue($proposer->getCollectionItems()->contains($itemR));
        $this->assertFalse($proposer->getCollectionItems()->contains($itemP));
        $this->assertFalse($receiver->getCollectionItems()->contains($itemR));
    }

    public function testValidateExchangeCancelsConflictingPendingExchanges(): void
    {
        $proposer = new User();
        $proposer->setEmail('proposer@example.com');
        $receiver = new User();
        $receiver->setEmail('receiver@example.com');
        $thirdParty = new User();
        $thirdParty->setEmail('third@example.com');

        $exchange = new Exchange();
        $exchange->setStatus(ExchangeStatuses::PENDING);
        $exchange->setProposer($proposer);
        $exchange->setReceiver($receiver);

        $itemP = new CollectionItem();
        $proposer->addCollectionItem($itemP);
        $exchange->addItem($itemP);

        $itemR = new CollectionItem();
        $receiver->addCollectionItem($itemR);
        $exchange->addItem($itemR);

        // Create a conflicting pending exchange with the third party that also contains $itemP
        $otherExchange = new Exchange();
        $otherExchange->setStatus(ExchangeStatuses::PENDING);
        $otherExchange->setProposer($proposer);
        $otherExchange->setReceiver($thirdParty);
        $otherExchange->addItem($itemP);
        $itemP->addExchange($otherExchange);

        // Create another exchange that is already ACCEPTED (should NOT be cancelled)
        $acceptedExchange = new Exchange();
        $acceptedExchange->setStatus(ExchangeStatuses::ACCEPTED);
        $acceptedExchange->setProposer($proposer);
        $acceptedExchange->setReceiver($thirdParty);
        $acceptedExchange->addItem($itemP);
        $itemP->addExchange($acceptedExchange);

        // Validate the exchange
        $this->exchangeService->validateExchange($exchange);

        // Verify the conflicting PENDING exchange is now CANCELLED
        $this->assertSame(ExchangeStatuses::CANCELLED, $otherExchange->getStatus());

        // Verify the already ACCEPTED exchange is still ACCEPTED
        $this->assertSame(ExchangeStatuses::ACCEPTED, $acceptedExchange->getStatus());
    }

    public function testValidateExchangeThrowsExceptionIfNotPending(): void
    {
        $proposer = new User();
        $proposer->setEmail('proposer@example.com');
        $receiver = new User();
        $receiver->setEmail('receiver@example.com');

        $exchange = new Exchange();
        $exchange->setStatus(ExchangeStatuses::ACCEPTED); // Not PENDING
        $exchange->setProposer($proposer);
        $exchange->setReceiver($receiver);

        $itemP = new CollectionItem();
        $proposer->addCollectionItem($itemP);
        $exchange->addItem($itemP);

        $itemR = new CollectionItem();
        $receiver->addCollectionItem($itemR);
        $exchange->addItem($itemR);

        $this->expectException(InvalidStateExchangeException::class);
        $this->expectExceptionMessage("Seuls les échanges en attente peuvent être validés.");

        $this->exchangeService->validateExchange($exchange);
    }

    public function testValidateExchangeThrowsExceptionIfNotEligible(): void
    {
        $proposer = new User();
        $proposer->setEmail('proposer@example.com');
        $receiver = new User();
        $receiver->setEmail('receiver@example.com');

        $exchange = new Exchange();
        $exchange->setStatus(ExchangeStatuses::PENDING);
        $exchange->setProposer($proposer);
        $exchange->setReceiver($receiver);

        // Missing items completely, so isDirectExchangeEligible() will return false
        $this->expectException(NotEligibleExchangeException::class);
        $this->expectExceptionMessage("Cet échange n'est pas éligible et ne peut pas être validé.");

        $this->exchangeService->validateExchange($exchange);
    }
}

