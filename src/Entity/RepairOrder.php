<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'repair_order')]
class RepairOrder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    public string $reference;

    #[ORM\Column(length: 50, enumType: RepairOrderStatus::class)]
    public RepairOrderStatus $status = RepairOrderStatus::PENDING;

    #[ORM\ManyToOne(targetEntity: Customer::class)]
    #[ORM\JoinColumn(nullable: true)]
    public ?Customer $customer = null;

    #[ORM\Column(length: 1000, nullable: true)]
    public ?string $description = null;

    #[ORM\Column(type: 'datetime')]
    public \DateTime $createdAt;

    #[ORM\OneToOne(mappedBy: 'repairOrder', targetEntity: Quote::class, cascade: ['persist', 'remove'])]
    public ?Quote $quote = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function transitionTo(RepairOrderStatus $next): void
    {
        if (!$this->status->canTransitionTo($next)) {
            throw new \DomainException(sprintf(
                'Transition impossible : %s → %s',
                $this->status->value,
                $next->value
            ));
        }

        $this->status = $next;
    }

    public function isClosed(): bool
    {
        return $this->status === RepairOrderStatus::DELIVERED
            || $this->status === RepairOrderStatus::CANCELLED;
    }
}
