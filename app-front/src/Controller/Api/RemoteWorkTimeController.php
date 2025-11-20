<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/remote-worktimes')]
class RemoteWorkTimeController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $remoteBaseUrl, // wstrzyknięte z services.yaml
    ) {}

    #[Route('', name: 'api_remote_worktimes_forward', methods: ['POST'])]
    public function report(Request $request): JsonResponse
    {
        // Pobierz payload z requestu
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid JSON body'], 400);
        }

        try {
            // Wyślij dalej do zewnętrznego API (np. /api/worktimes)
            $response = $this->httpClient->request(
                'POST',
                rtrim($this->remoteBaseUrl, '/') . '/api/worktimes',
                [
                    'json' => [
                        'employeeId' => '6130a7a8-ad6c-485c-8a73-da514445a117',
                        'startAt' => $data['startAt'] ?? '2025-11-01 08:00:00+01:00',
                        'endAt' => $data['endAt'] ?? '2025-11-01 16:00:00+01:00',
                    ],
                    'timeout' => 5.0,
                ]
            );
        } catch (TransportExceptionInterface $e) {
            // Problem z połączeniem / timeout
            return new JsonResponse([
                'error' => 'Remote API unreachable',
                'details' => $e->getMessage(),
            ], 502);
        }

        $statusCode = $response->getStatusCode();
        $content = $response->toArray(false); // false => nie rzuca wyjątku przy 4xx/5xx

        return new JsonResponse($content, $statusCode);
    }
}
