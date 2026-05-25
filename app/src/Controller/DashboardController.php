<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\JiraApiClient;
use Exception;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/', name: 'app_dashboard_')]
class DashboardController
{
    public function __construct(
        private readonly JiraApiClient $jiraApiClient
    ) {}

    #[Route('/', name: 'index', methods: ['GET'])]
    #[Template('dashboard/index.html.twig')]
    public function index(Request $request): array
    {
        // echo 444;die;
        try {
            $this->jiraApiClient->jiraAuthUser();
        } catch (Exception $e) {
            return [
                'error' => 'Nie można pobrać danych użytkownika z Jiry. Upewnij się, że jesteś zalogowany do Jiry i spróbuj ponownie.',
                'exception' => $e->getMessage()
            ];
        }

        return [];
    }
}
