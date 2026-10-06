<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Event;
use App\Service\JiraApiClient;
use App\Service\WorklogService;
use Exception;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/', name: 'app_dashboard_')]
class DashboardController
{
    public function __construct(
        private readonly JiraApiClient $jiraApiClient,
        private readonly WorklogService $worklogService
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

    #[Route('/report', name: 'report', methods: ['GET'])]
    public function report(): Response
    {
        $this->jiraApiClient->jiraAuthUser();

        $start = new \DateTime('first day of this month 00:00:00');
        $end = new \DateTime('first day of next month 00:00:00');

        $events = $this->worklogService->getEvents($start, $end, []);
        usort($events, fn (Event $a, Event $b) => ($a->getOption('worklog')['started'] ?? '') <=> ($b->getOption('worklog')['started'] ?? ''));

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['Klucz zgłoszenia', 'Projekt', 'Podsumowanie zgłoszenia', 'Data', 'Czas w minutach', 'Komentarz'], ';');

        $totalSeconds = 0;
        foreach ($events as $event) {
            $worklog = $event->getOption('worklog');
            $issue = $event->getOption('issue');
            $totalSeconds += (int) $worklog['timeSpentSeconds'];

            fputcsv($handle, [
                $issue['key'],
                strstr($issue['key'], '-', true),
                $issue['fields']['summary'],
                (new \DateTime($worklog['started']))->format('Y-m-d'),
                (int) round($worklog['timeSpentSeconds'] / 60),
                $this->extractCommentText($worklog['comment'] ?? null),
            ], ';');
        }

        $totalMinutes = (int) round($totalSeconds / 60);
        fputcsv($handle, [
            'PODSUMOWANIE',
            '',
            'Suma czasu pracy:',
            '',
            $totalMinutes,
            sprintf('%d godz. %d min.', intdiv($totalMinutes, 60), $totalMinutes % 60),
        ], ';');

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return new Response("\xEF\xBB\xBF" . $csv, Response::HTTP_OK, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => sprintf('attachment; filename="raport_%s.csv"', $start->format('Y-m')),
        ]);
    }

    private function extractCommentText(mixed $comment): string
    {
        if (is_string($comment)) {
            return $comment;
        }
        if (!is_array($comment)) {
            return '';
        }

        $texts = [];
        $walker = function (array $node) use (&$walker, &$texts): void {
            if (isset($node['text']) && is_string($node['text'])) {
                $texts[] = $node['text'];
            }
            foreach ($node['content'] ?? [] as $child) {
                if (is_array($child)) {
                    $walker($child);
                }
            }
        };
        $walker($comment);

        return implode(' ', $texts);
    }
}
