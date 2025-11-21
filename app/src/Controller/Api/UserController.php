<?php

namespace App\Controller\Api;

use App\Entity\Employee;
use App\Repository\EmployeeRepository;
use App\Security\InMemoryUser;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/user/info')]
class UserController extends AbstractController
{
    #[Route('', name: 'api_employees_create', methods: ['GET'])]
    #[OA\Get(
        path: '/api/user/info',
        summary: 'Zwraca info o użytkowiku',
        responses: [
            new OA\Response(
                response: 201,
                description: 'Dane pracownika',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'response',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', example: 'uuid')
                            ]
                        )
                    ]
                )
            ),
        ]
    )]
    public function info(Request $request, EmployeeRepository $employeeRepo, EntityManagerInterface $em): JsonResponse
    {
        /** @var InMemoryUser|null $user */
        $user = $this->getUser();
        $keycloakId = $user->getAttribute('keycloak_id');

        $employee = $employeeRepo->findOneBy(['keycloakId' => \Ramsey\Uuid\Uuid::fromString($keycloakId)]);

        if (!$employee instanceof Employee) {
            $employee = new Employee(
                $user->getAttribute('first_name') ?? 'FirstName',
                $user->getAttribute('last_name') ?? 'LastName'
            );
            $employee->setKeycloakId(\Ramsey\Uuid\Uuid::fromString($keycloakId));
            $em->persist($employee);
            $em->flush();
        }

        return new JsonResponse([
            'response' => [
                'id' => $employee->getId()->toString(),
                'firstName' => $employee->getFirstName(),
                'lastName' => $employee->getLastName(),
            ],
        ], 201);
    }
}
