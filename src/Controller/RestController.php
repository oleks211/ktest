<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class RestController extends AbstractController
{

    #[Route('/transactions', name: 'create_transaction', methods: ['POST'])]
    public function createTransaction(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/RestController.php',
        ]);
    }

    #[Route('/limits/{userId}', name: 'get_limits', methods: ['GET'])]
    public function getLimits(int $userId): JsonResponse
    {
        return $this->json([
            'message' => "user id: {$userId}",
            'path' => 'src/Controller/RestController.php',
        ]);
    }
}
