<?php

namespace App\Tests\Unit;

use App\Entity\Part;
use App\Entity\Quote;
use App\Entity\QuoteLine;
use App\Entity\RepairOrder;
use PHPUnit\Framework\TestCase;

class QuoteLineTest extends TestCase
{
    private function makeQuote(): Quote
    {
        $order            = new RepairOrder();
        $order->reference = 'OR-TEST';
        return new Quote($order);
    }

    private function makePart(float $priceEuros): Part
    {
        $part            = new Part();
        $part->reference = 'REF-001';
        $part->label     = 'Pare-chocs';
        $part->salePrice = $priceEuros;
        return $part;
    }

    public function testMontantHtPiece(): void
    {
        $line = QuoteLine::forPart($this->makeQuote(), $this->makePart(180.0), quantity: 2);

        // 180€ × 2 = 360€ = 36000 centimes
        $this->assertSame(36000, $line->amountHt());
    }

    public function testMontantHtAvecRemise(): void
    {
        $line = QuoteLine::forPart($this->makeQuote(), $this->makePart(180.0), quantity: 2, discountPercent: 10.0);

        // 360€ - 10% = 324€ = 32400 centimes
        $this->assertSame(32400, $line->amountHt());
    }

    public function testTVA(): void
    {
        $line = QuoteLine::forPart($this->makeQuote(), $this->makePart(100.0), quantity: 1);

        // TVA 20% sur 100€ = 20€ = 2000 centimes
        $this->assertSame(2000, $line->vatAmount());
    }

    public function testMontantHtMainOeuvre(): void
    {
        $line = QuoteLine::forLabor($this->makeQuote(), 'TOLERIE', hours: 2.0);

        // 2h × 65€/h = 130€ = 13000 centimes
        $this->assertSame(13000, $line->amountHt());
    }
}
