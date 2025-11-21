<?php

namespace App\Controller\Api;

use App\Entity\Employee;
use App\Entity\WorkTime;
use App\Repository\WorkTimeRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/worktimes')]
class WorkTimeController extends AbstractController
{
    private WorkTimeRepository $repo;

    public function __construct(WorkTimeRepository $repo)
    {
        $this->repo = $repo;
    }

    #[Route('', name: 'api_worktimes_create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/worktimes',
        summary: 'Dodaje czas pracy pracownika',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['startAt', 'endAt'],
                properties: [
                    new OA\Property(property: 'employeeId', type: 'string', example: 'uuid'),
                    new OA\Property(property: 'startAt', type: 'string', format: 'date-time', example: '2025-11-01T08:00:00+01:00'),
                    new OA\Property(property: 'endAt', type: 'string', format: 'date-time', example: '2025-11-01T16:00:00+01:00'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Praca nad feature X'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Czas pracy zapisany'),
            new OA\Response(response: 400, description: 'Błąd walidacji'),
        ]
    )]
    public function create(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
    {
        /** @var InMemoryUser|null $user */
        $user = $this->getUser();
        $keycloakId = $user->getAttribute('keycloak_id');

        $data = json_decode($request->getContent(), true);

        $constraints = new Assert\Collection([
            'startAt' => [new Assert\NotBlank()],
            'endAt' => [new Assert\NotBlank()],
            'description' => [new Assert\Optional([new Assert\Type('string'), new Assert\Length(['max' => 255])])],
        ]);

        $errors = $validator->validate($data, $constraints);
        if (count($errors) > 0) {
            $msgs = [];
            foreach ($errors as $err) {
                $msgs[] = $err->getMessage();
            }
            return new JsonResponse(['error' => $msgs], 400);
        }

        $employee = $em->getRepository(Employee::class)->findOneBy(['keycloakId' => \Ramsey\Uuid\Uuid::fromString($keycloakId)]);
        if (!$employee instanceof Employee) {
            return new JsonResponse(['error' => 'Employee not found' . var_export($employee, true)], 404);
        }

        try {
            $startAt = new \DateTimeImmutable($data['startAt']);
            $endAt = new \DateTimeImmutable($data['endAt']);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Invalid datetime format'], 400);
        }

        if ($endAt <= $startAt) {
            return new JsonResponse(['error' => 'endAt must be after startAt'], 400);
        }

        $diffSec = $endAt->getTimestamp() - $startAt->getTimestamp();
        if ($diffSec > 43200) { // 12 hours
            return new JsonResponse(['error' => 'Work time cannot exceed 12 hours'], 400);
        }

        $day = new \DateTimeImmutable($startAt->format('Y-m-d'));
        $existing = $this->repo->findByEmployeeAndDay($employee, $day);
        if (count($existing) > 0) {
            return new JsonResponse(['error' => 'Employee already has an entry for this day'], 400);
        }

        $description = $data['description'] ?? null;

        $workTime = new WorkTime($employee, $startAt, $endAt, $description);
        $em->persist($workTime);
        $em->flush();

        return new JsonResponse(['response' => ['message' => 'Worktime has been added']], 201);
    }
}
