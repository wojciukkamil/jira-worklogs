<?php

declare(strict_types=1);

namespace App\Controller;

use App\Helper\JiraDateTimeHelper;
use App\Service\JiraApiClient;
use DateTime;
use DateTimeZone;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/worklogs', name: 'app_worklogs_')]
class WorklogsController extends AbstractController
{
    private const STATUS_OK = 'OK';
    public function __construct(
        private readonly JiraApiClient $apiClient
    ) {}

    #[Route('/update', name: 'update', methods: ['POST'])]
    public function update(
        Request $request
    ): JsonResponse {
        $requestData = $request->request->all();
        $startDate = (new DateTime($requestData['worklog_started']))->setTimezone(new DateTimeZone('UTC'))->modify('-2 hours');
        $endDate = (new DateTime($requestData['worklog_ended']))->setTimezone(new DateTimeZone('UTC'))->modify('-2 hours');
        $result = $this->apiClient
            ->setRequestMethod('PUT')
            ->setMethodName('issue/:issue/worklog/:worklog?adjustEstimate=AUTO')
            ->setMethodParams([
                ':issue' => $requestData['issue']['key'],
                ':worklog' => $requestData['worklog']['id']
            ])
            ->setPostFields([
                'started' => $startDate->format("Y-m-d\TH:i:s") . '.000' . $startDate->format('O'),
                'timeSpentSeconds' => JiraDateTimeHelper::intervalToSeconds($startDate->diff($endDate)),
                "comment" => [
                    "type" => "doc",
                    "version" => 1,
                    "content" => [[
                        "type" => "paragraph",
                        "content" => [[
                            "type" => "text",
                            "text" => $request->request->all()['worklog']['comment']['content'][0]['content'][0]['text'] ?? ''
                        ]]
                    ]]
                ]
            ])
            ->execute();
        return new JsonResponse($result);
    }

    #[Route('/add', name: 'add', methods: ['POST'])]
    public function add(
        Request $request
    ): Response {
        $requestData = $request->request->all();

        $startDate = (new DateTime($requestData['worklog_started']));
        $endDate = (new DateTime($requestData['worklog_ended']));
        return $this->render('worklogs/add.html.twig', [
            'timeSpent' => JiraDateTimeHelper::intervalToString($startDate->diff($endDate)),
            'start' => $startDate->format('Y-m-d H:i:s'),
            'end' => $endDate->format('Y-m-d H:i:s'),
            'message' => $requestData['worklog']['comment']['content'][0]['content'][0]['text'] ?? '',
        ]);
    }

    #[Route('/edit', name: 'edit', methods: ['POST'])]
    public function edit(
        Request $request
    ): Response {
        $requestData = $request->request->all();
        $startDate = (new DateTime($requestData['worklog']['started']));
        $endDate = (new DateTime($requestData['worklog']['started']))->modify(sprintf('+%s seconds', $requestData['worklog']['timeSpentSeconds']));
        return $this->render('worklogs/edit.html.twig', [
            'issue' => $requestData['issue'],
            'worklog' => $requestData['worklog'],
            'timeSpent' => JiraDateTimeHelper::intervalToString($startDate->diff($endDate)),
            'start' => $startDate->format('Y-m-d\TH:i:s'),
            'end' => $endDate->format('Y-m-d\TH:i:s'),
            'message' => $requestData['worklog']['comment']['content'][0]['content'][0]['text'] ?? '',
        ]);
    }

    #[Route('/show', name: 'show', methods: ['POST'])]
    public function show(
        Request $request
    ): Response {
        $requestData = $request->request->all();
        $startDate = (new DateTime($requestData['worklog']['started']));
        $endDate = (new DateTime($requestData['worklog']['started']))->modify(sprintf('+%s seconds', $requestData['worklog']['timeSpentSeconds']));
        return $this->render('worklogs/show.html.twig', [
            'issue' => $requestData['issue'],
            'worklog' => $requestData['worklog'],
            'timeSpent' => JiraDateTimeHelper::intervalToString($startDate->diff($endDate)),
            'start' => $startDate->format('Y-m-d\TH:i:s'),
            'end' => $endDate->format('Y-m-d\TH:i:s'),
            'message' => $requestData['worklog']['comment']['content'][0]['content'][0]['text'] ?? '',
        ]);
    }

    #[Route('/save', name: 'save', methods: ['POST'])]
    public function save(Request $request): Response
    {
        $requestData = $request->request->all();
        $startDate = (new DateTime($requestData['worklog_form_start']))->setTimezone(new DateTimeZone('UTC'))->modify('-2 hours');

        $result = $this->apiClient
            ->setRequestMethod('PUT')
            ->setMethodName('issue/:issue/worklog/:worklog?adjustEstimate=AUTO')
            ->setMethodParams([
                ':issue' => $requestData['issue_key'],
                ':worklog' => $requestData['worklog_id']
            ])
            ->setPostFields([
                'started' => $startDate->format("Y-m-d\TH:i:s") . '.000' . $startDate->format('O'),
                'timeSpentSeconds' => JiraDateTimeHelper::stringToSeconds($requestData['worklog_form_time_spent']),
                "comment" => [
                    "type" => "doc",
                    "version" => 1,
                    "content" => [[
                        "type" => "paragraph",
                        "content" => [[
                            "type" => "text",
                            "text" => $requestData['worklog_form_message']
                        ]]
                    ]]
                ]
            ])
            ->execute();

        return new JsonResponse($result);
    }

    #[Route('/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request): Response
    {
        $requestData = $request->request->all();

        $result = $this->apiClient
            ->setRequestMethod('DELETE')
            ->setMethodName('issue/:issue/worklog/:worklog')
            ->setMethodParams([
                ':issue' => $requestData['issue_key'],
                ':worklog' => $requestData['worklog_id']
            ])
            ->execute();
        return new JsonResponse($result);
    }

    #[Route('/picker', name: 'picker', methods: ['POST'])]
    public function picker(Request $request): Response
    {
        $requestData = $request->request->all();

        $result = $this->apiClient
            ->setRequestMethod('GET')
            ->setMethodName('issue/picker?query=key=:query')
            ->setMethodParams([
                ':query' => $requestData['search'],
            ])
            ->execute();
        return new JsonResponse($result);
    }

    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request)
    {
        $search = $_GET['search'];
        $issues = $this->apiClient
            ->setRequestMethod('GET')
            ->setMethodName('search/jql?jql=issuekey~":query"%20OR%20summary~":query"&fields=summary&maxResults=20')
            ->setMethodParams([
                ':query' => $search,
            ])
            ->execute();

        $result = [];
        if (isset($issues['issues'])) {
            foreach ($issues['issues'] as $issue) {
                $result[] = [
                    'id' => $issue['id'],
                    'key' => $issue['key'],
                    'name' => '        [' . $issue['key'] . '] - ' . $issue['fields']['summary'],
                ];
            }
        }

        return new JsonResponse($result);
    }

    #[Route('/save-add', name: 'save-add', methods: ['POST'])]
    public function saveAdd(Request $request): Response
    {
        if ($requestData['worklog_add_form_issue_key'] = '') {
            throw new Exception('Nie wybrano zadania');
        }

        $requestData = $request->request->all();
        $startDate = (new DateTime($requestData['worklog_add_form_start']))->setTimezone(new DateTimeZone('UTC'))->modify('-2 hours');
        $result = $this->apiClient
            ->setRequestMethod('POST')
            ->setMethodName('issue/:issue/worklog/')
            ->setMethodParams([
                ':issue' => $requestData['worklog_add_form_issue_key']
            ])
            ->setPostFields([
                'started' => $startDate->format("Y-m-d\TH:i:s") . '.000' . $startDate->format('O'),
                'timeSpentSeconds' => JiraDateTimeHelper::stringToSeconds($requestData['worklog_add_form_time_spent']),
                "comment" => [
                    "type" => "doc",
                    "version" => 1,
                    "content" => [[
                        "type" => "paragraph",
                        "content" => [[
                            "type" => "text",
                            "text" => $requestData['worklog_add_form_message']
                        ]]
                    ]]
                ]
            ])
            ->execute();

        if (array_key_exists('status', $result) && $result['status']) {
            throw new Exception('Nie dodano czasu pracy.');
        }

        return new JsonResponse($result);
    }
}
