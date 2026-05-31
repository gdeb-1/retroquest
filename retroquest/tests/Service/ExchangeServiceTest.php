<?php

namespace App\Tests\Service;

use App\Entity\CollectionItem;
use App\Entity\Exchange;
use App\Entity\User;
use App\Enum\ExchangeStatuses;
use App\Service\ExchangeService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ExchangeServiceTest extends TestCase
{
    private ExchangeService $exchangeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exchangeService = new ExchangeService();
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
}
