<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'quote_line')]
class QuoteLine
{
    public const TYPE_PART  = 'PART';
    public const TYPE_LABOR = 'LABOR';

    public const VAT_RATE = 20;

    public const LABOR_RATES = [
        'TOLERIE'   => 6500,
        'PEINTURE'  => 7000,
        'MECANIQUE' => 6000,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Quote::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false)]
    public Quote $quote;


    #[ORM\Column(length: 10)]
    public string $type;

    // --- Ligne pièce ---
    #[ORM\ManyToOne(targetEntity: Part::class)]
    #[ORM\JoinColumn(nullable: true)]
    public ?Part $part = null;

    /** La quantité lignes pièce)*/
    #[ORM\Column(nullable: true)]
    public ?int $quantity = null;

    /** Prix unitaire HT en centimes */
    #[ORM\Column(nullable: true)]
    public ?int $unitPriceCents = null;

    // --- Ligne main d'œuvre ---
    /** TOLERIE PEINTURE, MECANO */
    #[ORM\Column(length: 20, nullable: true)]
    public ?string $laborType = null;

    /** Durée en centièmes d'heure 2h5 c'est 250) */
    #[ORM\Column(nullable: true)]
    public ?int $durationCentis = null;

    /** Taux horaire en centimes  65€/h c'est 6500 */
    #[ORM\Column(nullable: true)]
    public ?int $hourlyRateCents = null;

    // --- Remise commune ---
    /** Remise en points de base 10% c'est 1000) */
    #[ORM\Column(nullable: true)]
    public ?int $discountBasisPoints = null;

    public function grossAmountHt(): int
    {
        if ($this->type === self::TYPE_PART) {
            return $this->unitPriceCents * $this->quantity;
        }

        // montant MO = nombre d'heures × taux horaire
        return (int) round($this->durationCentis * $this->hourlyRateCents / 100);
    }

    public function discountAmount(): int
    {
        if (!$this->discountBasisPoints) {
            return 0;
        }
        return (int) round($this->grossAmountHt() * $this->discountBasisPoints / 10000);
    }

    public function amountHt(): int
    {
        return $this->grossAmountHt() - $this->discountAmount();
    }

    public function vatAmount(): int
    {
        return (int) round($this->amountHt() * self::VAT_RATE / 100);
    }

    public function amountTtc(): int
    {
        return $this->amountHt() + $this->vatAmount();
    }

    public static function forPart(Quote $quote, Part $part, int $quantity, float $discountPercent = 0): self
    {
        $line                    = new self();
        $line->quote             = $quote;
        $line->type              = self::TYPE_PART;
        $line->part              = $part;
        $line->quantity          = $quantity;
        $line->unitPriceCents    = (int) round($part->salePrice * 100);
        $line->discountBasisPoints = (int) round($discountPercent * 100);
        return $line;
    }

    public static function forLabor(Quote $quote, string $laborType, float $hours, float $discountPercent = 0): self
    {
        if (!array_key_exists($laborType, self::LABOR_RATES)) {
            throw new \InvalidArgumentException('Type de main d\'œuvre inconnu : ' . $laborType);
        }

        $line                    = new self();
        $line->quote             = $quote;
        $line->type              = self::TYPE_LABOR;
        $line->laborType         = $laborType;
        $line->durationCentis    = (int) round($hours * 100);
        $line->hourlyRateCents   = self::LABOR_RATES[$laborType];
        $line->discountBasisPoints = (int) round($discountPercent * 100);
        return $line;
    }
}
