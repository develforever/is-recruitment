<?php

namespace App\Controller\Api;

use App\Entity\Employee;
use App\Repository\WorkTimeRepository;
use App\Service\TimeCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Security as NelmioSecurity;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/summary')]
class SummaryController extends AbstractController
{
    private WorkTimeRepository $repo;
    private TimeCalculator $calc;
    private EntityManagerInterface $em;

    public function __construct(WorkTimeRepository $repo, TimeCalculator $calc, EntityManagerInterface $em)
    {
        $this->repo = $repo;
        $this->calc = $calc;
        $this->em = $em;
    }

    #[Route('', name: 'api_summary_get', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    #[NelmioSecurity(name: 'Bearer')]
    #[OA\Get(
        path: '/api/summary',
        summary: 'Podsumowanie czasu pracy',
        parameters: [
            new OA\Parameter(name: 'employeeId', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'date', in: 'query', required: true, schema: new OA\Schema(type: 'string', example: '2025-11')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Podsumowanie'),
            new OA\Response(response: 400, description: 'Błędne parametry'),
        ]
    )]
    public function get(Request $request): JsonResponse
    {
        $employeeId = $request->query->get('employeeId');
        $date = $request->query->get('date'); // YYYY-MM or YYYY-MM-DD

        if (!$employeeId || !$date) {
            return new JsonResponse(['error' => 'employeeId and date are required'], 400);
        }

        $employee = $this->em->getRepository(Employee::class)->find($employeeId);
        if (!$employee instanceof Employee) {
            return new JsonResponse(['error' => 'Employee not found'], 404);
        }

        // day summary (YYYY-MM-DD)
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $day = new \DateTimeImmutable($date);
            $workTimes = $this->repo->findByEmployeeAndDay($employee, $day);
            $hours = $this->calc->calculateDayHours($workTimes);

            $baseRate = (float) $this->getParameter('base_rate') ?? 20.0;
            $amount = $baseRate * $hours;

            return new JsonResponse(['response' => [
                'date' => $date,
                'hours' => $hours,
                'base_rate' => $baseRate,
                'amount' => $amount,
            ]]);
        }

        // month summary (YYYY-MM)
        if (preg_match('/^\d{4}-\d{2}$/', $date)) {
            $yearMonth = $date;
            $workTimes = $this->repo->findByEmployeeAndMonth($employee, $yearMonth);

            $monthlyNorm = (float) $this->getParameter('monthly_norm_hours') ?? 40.0;
            $baseRate = (float) $this->getParameter('base_rate') ?? 20.0;
            $overtimeMultiplier = (float) $this->getParameter('overtime_multiplier') ?? 2.0;

            $summary = $this->calc->calculateMonthSummary($workTimes, $monthlyNorm);

            $normalAmount = $summary['normal_hours'] * $baseRate;
            $overtimeAmount = $summary['overtime_hours'] * $baseRate * $overtimeMultiplier;
            $total = $normalAmount + $overtimeAmount;

            return new JsonResponse(['response' => [
                'year_month' => $yearMonth,
                'normal_hours' => $summary['normal_hours'],
                'overtime_hours' => $summary['overtime_hours'],
                'base_rate' => $baseRate,
                'overtime_rate' => $baseRate * $overtimeMultiplier,
                'total' => $total,
            ]]);
        }

        return new JsonResponse(['error' => 'date must be YYYY-MM-DD or YYYY-MM'], 400);
    }
}
