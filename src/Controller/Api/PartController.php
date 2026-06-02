<?php

namespace App\Controller\Api;

use App\Entity\Part;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/parts')]
class PartController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function list(EntityManagerInterface $em): JsonResponse
    {
        $parts = $em->getRepository(Part::class)->findBy([], ['label' => 'ASC']);

        $data = array_map(fn(Part $p) => [
            'id'        => $p->id,
            'reference' => $p->reference,
            'label'     => $p->label,
            'salePrice' => $p->salePrice,
        ], $parts);

        return new JsonResponse($data);
    }
}
