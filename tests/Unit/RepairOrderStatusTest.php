<?php

namespace App\Tests\Unit;

use App\Entity\RepairOrder;
use App\Entity\RepairOrderStatus;
use PHPUnit\Framework\TestCase;

class RepairOrderStatusTest extends TestCase
{
    public function testUnOrdrAnnuleNePeutPlusChanger(): void
    {
        $order            = new RepairOrder();
        $order->reference = 'OR-TEST';
        $order->status    = RepairOrderStatus::CANCELLED;

        $this->expectException(\DomainException::class);
        $order->transitionTo(RepairOrderStatus::IN_PROGRESS);
    }

    public function testTransitionNormaleAutorisee(): void
    {
        $order            = new RepairOrder();
        $order->reference = 'OR-TEST';

        $order->transitionTo(RepairOrderStatus::IN_PROGRESS);
        $this->assertSame(RepairOrderStatus::IN_PROGRESS, $order->status);
    }
}
