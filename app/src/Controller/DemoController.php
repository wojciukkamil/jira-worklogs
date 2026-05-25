<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\JiraApiClient;
use Exception;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/demo', name: 'app_demo_')]
class DemoController
{
    public function __construct(
        private readonly JiraApiClient $jiraApiClient
    ) {}

    #[Route('/index', name: 'index', methods: ['GET'])]
    #[Template('demo/index.html.twig')]
    public function error(Request $request): array
    {
        return [];
    }

    #[Route('/notifications', name: 'notifications', methods: ['GET'])]
    #[Template('demo/notifications.html.twig')]
    public function notifications(Request $request): array
    {
        return [];
    }
}
