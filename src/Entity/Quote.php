<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'quote')]
class Quote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\OneToOne(targetEntity: RepairOrder::class, inversedBy: 'quote')]
    #[ORM\JoinColumn(nullable: false)]
    public RepairOrder $repairOrder;

    #[ORM\Column(type: 'datetime')]
    public \DateTime $createdAt;

    #[ORM\OneToMany(mappedBy: 'quote', targetEntity: QuoteLine::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    public Collection $lines;

    public function __construct(RepairOrder $repairOrder)
    {
        $this->repairOrder = $repairOrder;
        $this->createdAt   = new \DateTime();
        $this->lines       = new ArrayCollection();
    }

    public function totalHt(): int
    {
        $total = 0;
        foreach ($this->lines as $line) {
            $total += $line->amountHt();
        }
        return $total;
    }

    public function totalDiscount(): int
    {
        $total = 0;
        foreach ($this->lines as $line) {
            $total += $line->discountAmount();
        }
        return $total;
    }

    public function totalVat(): int
    {
        $total = 0;
        foreach ($this->lines as $line) {
            $total += $line->vatAmount();
        }
        return $total;
    }

    public function totalTtc(): int
    {
        return $this->totalHt() + $this->totalVat();
    }
}
