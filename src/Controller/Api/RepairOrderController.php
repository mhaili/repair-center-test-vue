<?php

namespace App\Controller\Api;

use App\Entity\Customer;
use App\Entity\RepairOrder;
use App\Entity\RepairOrderStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/repair-orders')]
class RepairOrderController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function list(EntityManagerInterface $em): JsonResponse
    {
        $repairOrders = $em->getRepository(RepairOrder::class)->findAll();

        $data = array_map(fn(RepairOrder $ro) => $this->serialize($ro), $repairOrders);

        return new JsonResponse($data);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $em): JsonResponse
    {
        $repairOrder = $em->find(RepairOrder::class, $id);

        if (!$repairOrder) {
            return new JsonResponse(['error' => 'Ordre de réparation introuvable'], 404);
        }

        return new JsonResponse($this->serialize($repairOrder, detailed: true));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['description'])) {
            return new JsonResponse(['error' => 'La description est obligatoire'], 400);
        }

        $status = RepairOrderStatus::tryFrom($data['status'] ?? 'PENDING');
        if ($status === null) {
            return new JsonResponse(['error' => 'Statut invalide'], 400);
        }

        $customer = $this->resolveCustomer($data['customer'] ?? null, $em);
        if ($customer instanceof JsonResponse) {
            return $customer;
        }

        $repairOrder              = new RepairOrder();
        $repairOrder->reference   = 'OR-' . strtoupper(substr(md5(uniqid()), 0, 8));
        $repairOrder->status      = $status;
        $repairOrder->customer    = $customer;
        $repairOrder->description = $data['description'];

        $em->persist($repairOrder);
        $em->flush();

        return new JsonResponse($this->serialize($repairOrder), 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $repairOrder = $em->find(RepairOrder::class, $id);

        if (!$repairOrder) {
            return new JsonResponse(['error' => 'Ordre de réparation introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['description'])) {
            if (empty($data['description'])) {
                return new JsonResponse(['error' => 'La description ne peut pas être vide'], 400);
            }
            $repairOrder->description = $data['description'];
        }

        if (isset($data['status'])) {
            $next = RepairOrderStatus::tryFrom($data['status']);
            if ($next === null) {
                return new JsonResponse(['error' => 'Statut invalide'], 400);
            }
            try {
                $repairOrder->transitionTo($next);
            } catch (\DomainException $e) {
                return new JsonResponse(['error' => $e->getMessage()], 400);
            }
        }

        $em->flush();

        return new JsonResponse($this->serialize($repairOrder));
    }

    #[Route('/{id}/status', methods: ['PATCH'])]
    public function updateStatus(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $repairOrder = $em->find(RepairOrder::class, $id);

        if (!$repairOrder) {
            return new JsonResponse(['error' => 'Ordre de réparation introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $next = RepairOrderStatus::tryFrom($data['status'] ?? '');

        if ($next === null) {
            return new JsonResponse(['error' => 'Statut invalide'], 400);
        }

        try {
            $repairOrder->transitionTo($next);
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }

        $em->flush();

        return new JsonResponse(['status' => $repairOrder->status->value]);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $repairOrder = $em->find(RepairOrder::class, $id);

        if (!$repairOrder) {
            return new JsonResponse(['error' => 'Ordre de réparation introuvable'], 404);
        }

        if ($repairOrder->status === RepairOrderStatus::DELIVERED) {
            return new JsonResponse(['error' => 'Impossible de supprimer un ordre livré'], 400);
        }

        $em->remove($repairOrder);
        $em->flush();

        return new JsonResponse(null, 204);
    }

    private function serialize(RepairOrder $ro, bool $detailed = false): array
    {
        $data = [
            'id'           => $ro->id,
            'reference'    => $ro->reference,
            'status'       => $ro->status->value,
            'createdAt'    => $ro->createdAt->format('Y-m-d H:i:s'),
            'description'  => $ro->description,
            'quoteTotalTtc' => $ro->quote ? round($ro->quote->totalTtc() / 100, 2) : null,
            'customer'     => $ro->customer ? [
                'id'    => $ro->customer->id,
                'name'  => $ro->customer->name,
                'email' => $ro->customer->email,
            ] : null,
        ];

        if ($detailed && $ro->customer) {
            $data['customer']['phone'] = $ro->customer->phone;
        }

        return $data;
    }

    private function resolveCustomer(?array $customerData, EntityManagerInterface $em): Customer|JsonResponse|null
    {
        if ($customerData === null) {
            return null;
        }

        if (isset($customerData['id'])) {
            $customer = $em->find(Customer::class, $customerData['id']);
            if (!$customer) {
                return new JsonResponse(['error' => 'Client introuvable'], 404);
            }
            return $customer;
        }

        if (empty($customerData['name'])) {
            return new JsonResponse(['error' => 'Le nom du client est obligatoire'], 400);
        }

        $customer        = new Customer();
        $customer->name  = $customerData['name'];
        $customer->email = $customerData['email'] ?? null;
        $customer->phone = $customerData['phone'] ?? null;
        $em->persist($customer);

        return $customer;
    }
}
