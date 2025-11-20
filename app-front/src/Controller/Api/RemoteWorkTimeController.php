<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RemoteWorkTimeController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $remoteBaseUrl,
        private string $fakeEmployeeId,
    ) {}

    #[Route('/api/remote-worktimes/create', name: 'api_remote_worktimes_create', methods: ['POST'])]
    public function report(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid JSON body'], 400);
        }

        try {
            $response = $this->httpClient->request(
                'POST',
                rtrim($this->remoteBaseUrl, '/') . '/api/worktimes',
                [
                    'json' => [
                        'employeeId' => $this->fakeEmployeeId,
                        'startAt' => date('Y-m-dT').$data['startAt'] ?? '2025-11-01 08:00:00+00:00',
                        'endAt' => date('Y-m-dT').$data['endAt'] ?? '2025-11-01 16:00:00+00:00',
                    ],
                    'timeout' => 5.0,
                ]
            );
        } catch (TransportExceptionInterface $e) {
            return new JsonResponse([
                'error' => 'Remote API unreachable',
                'details' => $e->getMessage(),
            ], 502);
        }

        $statusCode = $response->getStatusCode();
        $content = $response->toArray(false); // false => nie rzuca wyjątku przy 4xx/5xx

        return new JsonResponse($content, $statusCode);
    }

    #[Route('/api/remote-worktimes/list', name: 'api_remote_worktimes_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {

        try {
            // Wyślij dalej do zewnętrznego API (np. /api/worktimes)
            $response = $this->httpClient->request(
                'GET',
                rtrim($this->remoteBaseUrl, '/') . '/api/worktimes',
                [
                    'query' => [
                        'employeeId' => $this->fakeEmployeeId,
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
