<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Event;
use App\Service\JiraApiClient;
use App\Service\WorklogService;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/report', name: 'app_report_')]
class ReportController extends AbstractController
{

    public function __construct(
        private readonly JiraApiClient $jiraApiClient,
        private readonly WorklogService $worklogService,
        private readonly SluggerInterface $slugger
    ) {}

    #[Route('/show', name: 'show', methods: ['POST'])]
    public function show(Request $request): Response
    {
        $requestData = $request->request->all();

        $startDate = (new DateTime($requestData['startDate']))->format('Y-m-d 00:00');
        $endDate = (new DateTime($requestData['endDate']))->format('Y-m-d 00:00');

        return $this->render('report/form.html.twig', [
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }

    #[Route('/generate', name: 'generate', methods: ['POST'])]
    public function generate(Request $request): Response
    {
        $requestData = $request->request->all();
        $filename = "raport_" . date('Y-m-d') . ".csv";
        //         header('Content-Type: text/csv; charset=utf-8');
        // header('Content-Disposition: attachment; filename="' . $filename . '"');
        // var_dump($requestData);die;
        $startDate = (new DateTime($requestData['startDate']));
        $endDate = (new DateTime($requestData['endDate']));

        $events = $this->worklogService->getEvents($startDate, $endDate, []);
        usort($events, fn(Event $a, Event $b) => ($a->getOption('worklog')['started'] ?? '') <=> ($b->getOption('worklog')['started'] ?? ''));

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
            'Content-Disposition' => sprintf('attachment; filename="raport_%s.csv"', $startDate->format('Y-m-d')),
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

    #[Route('/prepare', name: 'prepare', methods: ['POST'])]
    public function prepareCsv(Request $request): JsonResponse
    {
        $requestData = $request->request->all();
        $startDate = (new DateTime($requestData['startDate']));
        $endDate = (new DateTime($requestData['endDate']));

        $events = $this->worklogService->getEvents($startDate, $endDate, []);
        usort($events, fn(Event $a, Event $b) => ($a->getOption('worklog')['started'] ?? '') <=> ($b->getOption('worklog')['started'] ?? ''));

        $data = [['Klucz zgłoszenia', 'Projekt', 'Podsumowanie zgłoszenia', 'Data', 'Czas w minutach', 'Komentarz']];
        $totalSeconds = 0;
        foreach ($events as $event) {
            $worklog = $event->getOption('worklog');
            $issue = $event->getOption('issue');
            $totalSeconds += (int) $worklog['timeSpentSeconds'];
            $data[] = [
                $issue['key'],
                strstr($issue['key'], '-', true),
                $issue['fields']['summary'],
                (new \DateTime($worklog['started']))->format('Y-m-d'),
                (int) round($worklog['timeSpentSeconds'] / 60),
                $this->extractCommentText($worklog['comment'] ?? null),
            ];
        }

        $totalMinutes = (int) round($totalSeconds / 60);
        $data[] = [
            'PODSUMOWANIE',
            '',
            'Suma czasu pracy:',
            '',
            $totalMinutes,
            sprintf('%d godz. %d min.', intdiv($totalMinutes, 60), $totalMinutes % 60),
        ];

        $fileName = 'export_' . uniqid() . '.csv';
        $filePath = sys_get_temp_dir() . '/' . $fileName;

        $fp = fopen($filePath, 'w');

        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));

        foreach ($data as $row) {
            fputcsv($fp, $row, ';');
        }
        fclose($fp);

        $downloadUrl = $this->generateUrl('app_report_download', ['filename' => $fileName]);

        return new JsonResponse(['url' => $downloadUrl]);
    }

    #[Route('/download/{filename}', name: 'download', methods: ['GET', 'POST'])]
    public function downloadCsv(string $filename): BinaryFileResponse
    {
        $filePath = sys_get_temp_dir() . '/' . $filename;

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Plik wygasł lub nie istnieje.');
        }

        $response = new BinaryFileResponse($filePath);
        $user = $this->jiraApiClient->getSessionUser();
        $userDisplayName = $this->slugger->slug($user['displayName'], '_', 'pl');

        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'Raport_czasu_pracy_' . $userDisplayName . '.csv'
        );

        // Opcjonalnie: usuń plik po wysłaniu go do użytkownika
        $response->deleteFileAfterSend(true);

        return $response;
    }
}
