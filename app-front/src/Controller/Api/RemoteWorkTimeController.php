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

            $authHeader = $request->headers->get('Authorization');
            $workDay = $data['workDay'] ?? date('Y-m-d');
            $response = $this->httpClient->request(
                'POST',
                rtrim($this->remoteBaseUrl, '/') . '/api/worktimes',
                [
                    'headers' => [
                        'Authorization' => $authHeader,
                        'Accept' => 'application/json',
                    ],
                    'json' => [
                        'employeeId' => $this->fakeEmployeeId,
                        'startAt' => $workDay . 'T' . ($data['startAt'] ?? '08:00:00+00:00'),
                        'endAt' => $workDay . 'T' . ($data['endAt'] ?? '16:00:00+00:00'),
                        'description' => $data['description'] ?? null,
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

            $authHeader = $request->headers->get('Authorization');

            $response = $this->httpClient->request(
                'GET',
                rtrim($this->remoteBaseUrl, '/') . '/api/worktimes',
                [
                    'headers' => [
                        'Authorization' => $authHeader,
                        'Accept' => 'application/json',
                    ],
                    'query' => [
                        'employeeId' => $this->fakeEmployeeId,
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
}
