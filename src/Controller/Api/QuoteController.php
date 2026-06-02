<?php

namespace App\Controller\Api;

use App\Entity\Part;
use App\Entity\Quote;
use App\Entity\QuoteLine;
use App\Entity\RepairOrder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/repair-orders/{orderId}/quote')]
class QuoteController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function show(int $orderId, EntityManagerInterface $em): JsonResponse
    {
        $order = $em->find(RepairOrder::class, $orderId);
        if (!$order) {
            return new JsonResponse(['error' => 'Ordre introuvable'], 404);
        }

        if (!$order->quote) {
            return new JsonResponse(null, 204);
        }

        return new JsonResponse($this->serializeQuote($order->quote));
    }

    #[Route('', methods: ['POST'])]
    public function create(int $orderId, EntityManagerInterface $em): JsonResponse
    {
        $order = $em->find(RepairOrder::class, $orderId);
        if (!$order) {
            return new JsonResponse(['error' => 'Ordre introuvable'], 404);
        }

        if ($order->isClosed()) {
            return new JsonResponse(['error' => 'Impossible de créer un devis sur un ordre clôturé'], 400);
        }

        if ($order->quote) {
            return new JsonResponse(['error' => 'Un devis existe déjà pour cet ordre'], 400);
        }

        $quote         = new Quote($order);
        $order->quote  = $quote;
        $em->persist($quote);
        $em->flush();

        return new JsonResponse($this->serializeQuote($quote), 201);
    }

    #[Route('/lines', methods: ['POST'])]
    public function addLine(int $orderId, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $order = $em->find(RepairOrder::class, $orderId);
        if (!$order) {
            return new JsonResponse(['error' => 'Ordre introuvable'], 404);
        }

        if ($order->isClosed()) {
            return new JsonResponse(['error' => 'Impossible de modifier le devis d\'un ordre clôturé'], 400);
        }

        if (!$order->quote) {
            return new JsonResponse(['error' => 'Aucun devis trouvé pour cet ordre'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $type = $data['type'] ?? null;

        try {
            $line = match ($type) {
                QuoteLine::TYPE_PART  => $this->buildPartLine($order->quote, $data, $em),
                QuoteLine::TYPE_LABOR => $this->buildLaborLine($order->quote, $data),
                default               => throw new \InvalidArgumentException('Type de ligne invalide (PART ou LABOR attendu)'),
            };
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }

        $order->quote->lines->add($line);
        $em->persist($line);
        $em->flush();

        return new JsonResponse($this->serializeLine($line), 201);
    }

    #[Route('/lines/{lineId}', methods: ['DELETE'])]
    public function removeLine(int $orderId, int $lineId, EntityManagerInterface $em): JsonResponse
    {
        $order = $em->find(RepairOrder::class, $orderId);
        if (!$order) {
            return new JsonResponse(['error' => 'Ordre introuvable'], 404);
        }

        if ($order->isClosed()) {
            return new JsonResponse(['error' => 'Impossible de modifier le devis d\'un ordre clôturé'], 400);
        }

        $line = $em->find(QuoteLine::class, $lineId);
        if (!$line || $line->quote->id !== $order->quote?->id) {
            return new JsonResponse(['error' => 'Ligne introuvable'], 404);
        }

        $em->remove($line);
        $em->flush();

        return new JsonResponse(null, 204);
    }

    private function buildPartLine(Quote $quote, array $data, EntityManagerInterface $em): QuoteLine
    {
        if (empty($data['partId'])) {
            throw new \InvalidArgumentException('partId est obligatoire pour une ligne pièce');
        }
        if (empty($data['quantity']) || (int) $data['quantity'] <= 0) {
            throw new \InvalidArgumentException('quantity doit être un entier positif');
        }

        $part = $em->find(Part::class, $data['partId']);
        if (!$part) {
            throw new \InvalidArgumentException('Pièce introuvable');
        }

        return QuoteLine::forPart($quote, $part, (int) $data['quantity'], (float) ($data['discount'] ?? 0));
    }

    private function buildLaborLine(Quote $quote, array $data): QuoteLine
    {
        if (empty($data['laborType'])) {
            throw new \InvalidArgumentException('laborType est obligatoire pour une ligne main d\'œuvre');
        }
        if (empty($data['hours']) || (float) $data['hours'] <= 0) {
            throw new \InvalidArgumentException('hours doit être un nombre positif');
        }

        return QuoteLine::forLabor($quote, $data['laborType'], (float) $data['hours'], (float) ($data['discount'] ?? 0));
    }

    private function serializeQuote(Quote $quote): array
    {
        return [
            'id'            => $quote->id,
            'createdAt'     => $quote->createdAt->format('Y-m-d H:i:s'),
            'lines'         => $quote->lines->map(fn(QuoteLine $l) => $this->serializeLine($l))->toArray(),
            'totalHt'       => round($quote->totalHt() / 100, 2),
            'totalDiscount' => round($quote->totalDiscount() / 100, 2),
            'totalVat'      => round($quote->totalVat() / 100, 2),
            'totalTtc'      => round($quote->totalTtc() / 100, 2),
        ];
    }

    private function serializeLine(QuoteLine $line): array
    {
        $base = [
            'id'           => $line->id,
            'type'         => $line->type,
            'discount'     => $line->discountBasisPoints ? round($line->discountBasisPoints / 100, 2) : 0,
            'grossAmountHt'=> round($line->grossAmountHt() / 100, 2),
            'amountHt'     => round($line->amountHt() / 100, 2),
            'vatAmount'    => round($line->vatAmount() / 100, 2),
            'amountTtc'    => round($line->amountTtc() / 100, 2),
        ];

        if ($line->type === QuoteLine::TYPE_PART) {
            $base['part']      = ['id' => $line->part->id, 'reference' => $line->part->reference, 'label' => $line->part->label];
            $base['quantity']  = $line->quantity;
            $base['unitPrice'] = round($line->unitPriceCents / 100, 2);
        } else {
            $base['laborType']   = $line->laborType;
            $base['hours']       = round($line->durationCentis / 100, 2);
            $base['hourlyRate']  = round($line->hourlyRateCents / 100, 2);
        }

        return $base;
    }
}
