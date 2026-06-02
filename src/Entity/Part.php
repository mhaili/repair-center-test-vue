<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'part')]
class Part
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    public string $reference;

    #[ORM\Column(length: 255)]
    public string $label;

    #[ORM\Column(type: 'float')]
    public float $salePrice;
}
