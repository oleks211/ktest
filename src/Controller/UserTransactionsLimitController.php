<?php

namespace App\Controller;

use App\Entity\UserTransactionsLimit;
use App\Repository\UserTransactionsLimitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/ut-limits', name: 'limits_')]
class UserTransactionsLimitController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function limits(UserTransactionsLimitRepository $repository, SerializerInterface $serializer): JsonResponse
    {
        $limits = $repository->findAll();

        $json = $serializer->serialize($limits, 'json', [
            AbstractNormalizer::GROUPS => ['limit_read'],
        ]);

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function getLimit(?UserTransactionsLimit $limit, SerializerInterface $serializer): JsonResponse
    {
        if ($limit === null) {
            return new JsonResponse(['error' => 'User transactions limit not found.'], 404);
        }

        $json = $serializer->serialize($limit, 'json', [
            AbstractNormalizer::GROUPS => ['limit_read'],
        ]);

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, SerializerInterface $serializer): JsonResponse
    {
        $data = $request->getContent();

        /** @var UserTransactionsLimit $limit */
        $limit = $serializer->deserialize($data, UserTransactionsLimit::class, 'json');

        $this->entityManager->persist($limit);
        $this->entityManager->flush();

        return $this->json(['ok'], JsonResponse::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, UserTransactionsLimit $limit, SerializerInterface $serializer): JsonResponse
    {
        $data = $request->getContent();

        /** @var UserTransactionsLimit $updatedLimit */
        $updatedLimit = $serializer->deserialize($data, UserTransactionsLimit::class, 'json', ['object_to_populate' => $limit]);

        $this->entityManager->flush();

        return $this->json(['ok'], JsonResponse::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(UserTransactionsLimit $limit): JsonResponse
    {
        $this->entityManager->remove($limit);
        $this->entityManager->flush();

        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}