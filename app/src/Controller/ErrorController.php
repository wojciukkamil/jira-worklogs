<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\JiraApiClient;
use Exception;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/', name: 'app_')]
class ErrorController
{
    public function __construct(
        private readonly JiraApiClient $jiraApiClient
    ) {}

    #[Route('/error', name: 'error', methods: ['GET'])]
    #[Template('error/error.html.twig')]
    public function error(Request $request): array
    {

    // var_dump($request->getCode());die;
        // echo 444;die;
        // try {
        //     $this->jiraApiClient->jiraAuthUser();
        // } catch (Exception $e) {
        //     return [
        //         'error' => 'Nie można pobrać danych użytkownika z Jiry. Upewnij się, że jesteś zalogowany do Jiry i spróbuj ponownie.',
        //         'exception' => $e->getMessage()
        //     ];
        // }

        return [];
    }
}
