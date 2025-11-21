<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RemoteUserController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $remoteBaseUrl,
    ) {}

    #[Route('/api/user/info', name: 'api_remote_user_info', methods: ['GET'])]
    public function info(Request $request): JsonResponse
    {

        try {

            $authHeader = $request->headers->get('Authorization');

            $response = $this->httpClient->request(
                'GET',
                rtrim($this->remoteBaseUrl, '/') . '/api/user/info',
                [
                    'headers' => [
                        'Authorization' => $authHeader,
                        'Accept' => 'application/json',
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
