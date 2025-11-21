<?php

namespace App\Controller\Api;

use App\Entity\Employee;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/employees')]
class EmployeeController extends AbstractController
{
    #[Route('', name: 'api_employees_create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/employees',
        summary: 'Tworzy nowego pracownika',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['firstName', 'lastName'],
                properties: [
                    new OA\Property(property: 'firstName', type: 'string', example: 'Jan'),
                    new OA\Property(property: 'lastName', type: 'string', example: 'Kowalski'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Pracownik utworzony',
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
            new OA\Response(response: 400, description: 'Błąd walidacji'),
        ]
    )]
    public function create(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $constraints = new Assert\Collection([
            'firstName' => [new Assert\NotBlank(), new Assert\Length(['max' => 100])],
            'lastName' => [new Assert\NotBlank(), new Assert\Length(['max' => 100])],
        ]);

        $errors = $validator->validate($data, $constraints);
        if (count($errors) > 0) {
            $msgs = [];
            foreach ($errors as $err) {
                $msgs[] = $err->getMessage();
            }
            return new JsonResponse(['error' => $msgs], 400);
        }

        $employee = new Employee($data['firstName'], $data['lastName']);
        $em->persist($employee);
        $em->flush();

        return new JsonResponse(['response' => ['id' => $employee->getId()->toString()]], 201);
    }
}
