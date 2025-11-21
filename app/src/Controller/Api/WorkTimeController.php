<?php

namespace App\Controller\Api;

use App\Entity\Employee;
use App\Entity\WorkTime;
use App\Repository\WorkTimeRepository;
use Doctrine\ORM\EntityManagerInterface;
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
    public function create(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $constraints = new Assert\Collection([
            'employeeId' => [new Assert\NotBlank()],
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

        $employee = $em->getRepository(Employee::class)->find($data['employeeId']);
        if (!$employee instanceof Employee) {
            return new JsonResponse(['error' => 'Employee not found'], 404);
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
