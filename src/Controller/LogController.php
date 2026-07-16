<?php

namespace App\Controller;

use App\Repository\AuditLogEntryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPERADMIN')]
class LogController extends AbstractController
{
    private const PAGE_SIZE = 50;

    #[Route('/logs', name: 'logs_index')]
    public function index(Request $request, AuditLogEntryRepository $logs): Response
    {
        $event = $request->query->get('event') ?: null;
        $page = max(1, (int) $request->query->get('page', 1));

        $qb = $logs->createFilteredQueryBuilder($event);
        $total = (int) (clone $qb)->select('COUNT(a.id)')->getQuery()->getSingleScalarResult();

        $entries = $qb
            ->setFirstResult(self::PAGE_SIZE * ($page - 1))
            ->setMaxResults(self::PAGE_SIZE)
            ->getQuery()
            ->getResult();

        return $this->render('logs/index.html.twig', [
            'entries' => $entries,
            'events' => $logs->findDistinctEvents(),
            'selectedEvent' => $event,
            'page' => $page,
            'totalPages' => max(1, (int) ceil($total / self::PAGE_SIZE)),
        ]);
    }
}
