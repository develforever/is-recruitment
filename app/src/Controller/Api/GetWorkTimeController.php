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
class GetWorkTimeController extends AbstractController
{
    private WorkTimeRepository $repo;

    public function __construct(WorkTimeRepository $repo)
    {
        $this->repo = $repo;
    }

    #[Route('', name: 'api_worktimes_list', methods: ['GET'])]
    public function list(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
    {
        
        return new JsonResponse(['response' => ['message' => 'Worktime api works']], 201);
    }
}
