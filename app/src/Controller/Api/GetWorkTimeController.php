<?php

namespace App\Controller\Api;

use App\Entity\Employee;
use App\Entity\WorkTime;
use App\Repository\EmployeeRepository;
use App\Repository\WorkTimeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/worktimes')]
class GetWorkTimeController extends AbstractController
{
    private WorkTimeRepository $repo;

    public function __construct(WorkTimeRepository $repo)
    {
        $this->repo = $repo;
    }

    #[Route('', name: 'api_worktimes_list', methods: ['GET'])]
    public function list(Request $request, EmployeeRepository $employeeRepo, WorkTimeRepository $workRepo): JsonResponse
    {
        $requestedEmployeeId = $request->query->get('employeeId');
        if (!$requestedEmployeeId) {
            return new JsonResponse(['error' => 'employeeId is required'], 400);
        }

        $employee = $employeeRepo->find($requestedEmployeeId);
        if (!$employee instanceof Employee) {
            return new JsonResponse(['error' => 'Employee not found'], 404);
        }

        $workTime = $workRepo->findBy(['employee' => $employee]);
        $data = array_map(
            function (WorkTime $wt): array {
                return [
                    'id'       => $wt->getId()->toString(),
                    'startAt'  => $wt->getStartAt()->format(\DATE_ATOM),
                    'endAt'    => $wt->getEndAt()->format(\DATE_ATOM),
                    'startDay' => $wt->getStartDay()->format('Y-m-d'),
                    'duration' => $wt->getDurationSeconds(), 
                ];
            },
            $workTime
        );

        return new JsonResponse(['response' => [
            'data' => $data
        ]], 200);
    }
}
