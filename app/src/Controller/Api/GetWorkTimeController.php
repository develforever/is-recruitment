<?php

namespace App\Controller\Api;

use App\Entity\Employee;
use App\Entity\WorkTime;
use App\Repository\EmployeeRepository;
use App\Repository\WorkTimeRepository;
use App\Security\InMemoryUser;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/worktimes')]
class GetWorkTimeController extends AbstractController
{
    #[Route('', name: 'api_worktimes_list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/worktimes',
        summary: 'Zaraportowane czasy pracy pracownika',
        responses: [
            new OA\Response(response: 200, description: 'Lista czasów pracy'),
        ]
    )]
    public function list(Request $request, EmployeeRepository $employeeRepo, WorkTimeRepository $workRepo): JsonResponse
    {
        /** @var InMemoryUser|null $user */
        $user = $this->getUser();
        $keycloakId = $user->getAttribute('keycloak_id');

        $employee = $employeeRepo->findOneBy(['keycloakId' => \Ramsey\Uuid\Uuid::fromString($keycloakId)]);
        if (!$employee instanceof Employee) {
            return new JsonResponse(['error' => 'Employee not found ' . var_export($employee, true)], 404);
        }

        $workTime = $workRepo->findBy(['employee' => $employee], ['startAt' => 'DESC']);
        $data = array_map(
            function (WorkTime $wt): array {
                return [
                    'id'       => $wt->getId()->toString(),
                    'startAt'  => $wt->getStartAt()->format(\DATE_ATOM),
                    'endAt'    => $wt->getEndAt()->format(\DATE_ATOM),
                    'startDay' => $wt->getStartDay()->format('Y-m-d'),
                    'duration' => $wt->getDurationSeconds(),
                    'description' => $wt->getDescription(),
                ];
            },
            $workTime
        );

        return new JsonResponse(['response' => [
            'data' => $data
        ]], 200);
    }
}
