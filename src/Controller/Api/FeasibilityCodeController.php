<?php

namespace App\Controller\Api;

use App\Repository\FeasibilityCodeRepository;
use App\Security\ApiKeyUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_API')]
#[Route('/api/feasibility-codes', name: 'api_feasibility_codes_')]
class FeasibilityCodeController extends AbstractController
{
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, FeasibilityCodeRepository $feasibilityCodes): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $title = trim((string) ($data['title'] ?? ''));
        $requestor = trim((string) ($data['requestor'] ?? ''));

        if (!$title || !$requestor) {
            return $this->json(['error' => 'title and requestor are required.'], 400);
        }

        /** @var ApiKeyUser $apiUser */
        $apiUser = $this->getUser();
        $subsidiary = $apiUser->getSubsidiary();

        $entry = $feasibilityCodes->allocate($subsidiary, $title, $requestor);

        return $this->json([
            'code' => $entry->getCode(),
            'title' => $entry->getTitle(),
            'requestor' => $entry->getRequestor(),
            'subsidiary' => $subsidiary->getCode(),
            'createdAt' => $entry->getCreatedAt()->format(DATE_ATOM),
        ], 201);
    }
}
