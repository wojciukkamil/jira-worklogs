<?php

namespace App\Service;

use App\Entity\Event;
use App\Helper\JiraDateTimeHelper;
use DateTime;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class WorklogService
{

    private $authEmail;

    public function __construct(
        private readonly JiraApiClient $jiraApiClient,
        private readonly ContainerBagInterface $parameters
    ) {
        $this->authEmail = $parameters->get('app.jira_auth_email');
    }

    public function getEvents($start, $end, $filters): array
    {
        $jiraUrl = "search/jql?jql=(worklogAuthor%20in%20(%22{$this->authEmail}%22)%20and%20worklogDate%20%3E=%20%27:dateStart%27%20and%20worklogDate%20%3C%20%27:dateEnd%27)%20&fields=summary,worklog&maxResults=5000";
        $user = $this->jiraApiClient->getSessionUser();
        $result = $this->jiraApiClient
            ->setMethodName($jiraUrl)
            ->setMethodParams([
                ':email' => $user['emailAddress'],
                ':dateStart' => $start->format('Y-m-d'),
                ':dateEnd' => $end->format('Y-m-d'),
            ])
            ->execute();


        $calendarData = [];
        foreach ($result['issues'] as $issue) {
            if ($issue['fields']['worklog']['total'] >= 20) {
                $issueResult = $this->jiraApiClient->setMethodName('issue/:issue/worklog?maxResults=5000')->setMethodParams([':issue' => $issue['id']])->execute();
                $issue['fields']['worklog']['worklogs'] = $issueResult['worklogs'];
            }

            foreach ($issue['fields']['worklog']['worklogs'] as $worklog) {
                // var_dump($worklog);die;
                $startDate = new DateTime($worklog['started']);
                $endDate = (new DateTime($worklog['started']))->modify(sprintf('+%s seconds', $worklog['timeSpentSeconds']));
                // var_dump($endDate);die;
                if ($worklog['author']['accountId'] == $user['accountId']  && $startDate->format('Y-m-d') >= $start->format('Y-m-d') && $endDate->format('Y-m-d') < $end->format('Y-m-d')) {
                    // var_dump($worklog['timeSpentSeconds']);die;
                    // $end = date('Y-m-d H:i:s', strtotime($worklog['started']) + (int) $worklog['timeSpentSeconds'] + 7200);
                    $calendarData[] = [
                        "title" => $issue['key'],
                        "start" => $startDate->format('Y-m-d H:i:s'),
                        "end" => $endDate->format('Y-m-d H:i:s'),
                        'extendedProps' => [
                            'description' => $issue['fields']['summary'],
                            'icon' => $worklog['author']['avatarUrls']['16x16'],
                            'timeInterval' => JiraDateTimeHelper::intervalToString($startDate->diff($endDate)),
                            'worklog' => $worklog,
                            'issue' => $issue,
                        ],
                    ];
                    // var_dump($worklog);
                }
            }
        }
// var_dump($calendarData['extendedProps']['worklog']);
// die;
        return $this->createEvents($calendarData);
    }

    private function createEvents($calendarData)
    {
        $events = [];
        foreach ($calendarData as $row) {
            $events[] = new Event(
                $row['title'],
                new \DateTime($row['start']),
                new \DateTime($row['end']),
                $row['extendedProps'] ?? [],
            );
        }

        return $events;
    }
}
