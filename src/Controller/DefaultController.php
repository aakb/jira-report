<?php

namespace App\Controller;

use App\Service\JiraService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DefaultController extends Controller
{
    /**
     * @Route("/sprint_report")
     * @Method("GET")
     */
    public function sprintReportListAction(JiraService $jira)
    {
        try {
            $projects = $jira->get('/rest/api/2/project');

            /*
            $activeProjects = [];

            foreach ($projects as $project) {
                if (!isset($project->projectCategory) || $project->projectCategory->name != 'Lukket') {
                    $activeProjects[] = $project;
                }
            }
            */

            return $this->render(
                'jira/sprint_report_list.html.twig',
                [
                    'projects' => $projects,
                ]
            );
        } catch (HttpException $e) {
            return new RedirectResponse('/login');
        }
    }

    /**
     * @Route("/sprint_report/project/{pid}")
     * @Method("GET")
     */
    public function sprintReportAction(JiraService $jira, $pid)
    {
        try {
            $project = $jira->get('/rest/api/2/project/'.$pid);

            return $this->render(
                'jira/sprint_report.html.twig',
                [
                    'project' => $project,
                ]
            );
        } catch (HttpException $e) {
            return new RedirectResponse('/login');
        }
    }

    /**
     * @Route("/sprint_report/version/{vid}")
     * @Method("GET")
     */
    public function sprintReportVersionAction(JiraService $jira, $vid)
    {
        try {
            $version = $jira->get('/rest/api/2/version/'.$vid);
            $project = $jira->get('/rest/api/2/project/'.$version->projectId);

            $customFields = $jira->get('/rest/api/2/field');

            // Find customField definitions.
            $customFieldEpicLink = array_search(
                'Epic Link',
                array_column($customFields, 'name')
            );
            if ($customFieldEpicLink == false) {
                throw new HttpException(
                    500,
                    'Epic Link custom field does not exist'
                );
            }
            $customFieldEpicLink = $customFields[$customFieldEpicLink];

            $customFieldSprint = array_search(
                'Sprint',
                array_column($customFields, 'name')
            );
            if ($customFieldSprint == false) {
                throw new HttpException(
                    500,
                    'Sprint custom field does not exist'
                );
            }
            $customFieldSprint = $customFields[$customFieldSprint];

            // Get all issues for version.
            $issues = [];
            $startAt = 0;
            $fields = implode(
                ',',
                [
                    'timetracking',
                    'worklog',
                    'timespent',
                    'timeoriginalestimate',
                    'summary',
                    'assignee',
                    'status',
                    'resolutionDate',
                    $customFieldEpicLink->id,
                    $customFieldSprint->id,
                ]
            );

            while (true) {
                $results = $jira->get(
                    '/rest/api/2/search'.
                    '?jql=fixVersion='.$vid.
                    '&project='.$version->projectId.
                    '&maxResults=50'.
                    '&fields='.$fields.
                    '&startAt='.$startAt
                );
                $issues = array_merge($issues, $results->issues);

                $startAt = $startAt + 50;

                if ($results->total < $startAt) {
                    break;
                }
            }

            $epics = [];
            $epics['NoEpic'] = (object)[
                'id' => null,
                'name' => 'No epic',
            ];

            // Extract sprint and epics from agile custom field.
            foreach ($issues as $issue) {
                $issue->sprints = [];

                foreach ($issue->fields->{$customFieldSprint->key} as $sprintString) {
                    $replace = preg_replace(
                        ['/.*\[/', '/].*/'],
                        '',
                        $sprintString
                    );
                    $fields = explode(',', $replace);

                    $sprint = [];
                    foreach ($fields as $field) {
                        $split = explode('=', $field);

                        $sprint[$split[0]] = $split[1];
                    }

                    $issue->sprints[] = (object)$sprint;
                }

                if (isset($issue->fields->{$customFieldEpicLink->key})) {
                    if (!isset($epics[$issue->fields->{$customFieldEpicLink->key}])) {
                        $epics[$issue->fields->{$customFieldEpicLink->key}] = $jira->get(
                            'rest/agile/1.0/epic/'.$issue->fields->{$customFieldEpicLink->key}
                        );
                    }
                    $issue->epic = $epics[$issue->fields->{$customFieldEpicLink->key}];
                }
            }

            // Get active sprint.
            $boardId = 65;
            $activeSprint = $jira->get(
                '/rest/agile/1.0/board/'.$boardId.'/sprint?state=active'
            );
            $activeSprint = count(
                $activeSprint->values
            ) > 0 ? $activeSprint->values[0] : null;

            // Get all sprints.
            $allSprints = [];
            $startAt = 0;
            while (true) {
                $results = $jira->get(
                    '/rest/agile/1.0/board/'.$boardId.'/sprint?startAt='.$startAt
                );
                $allSprints = array_merge($allSprints, $results->values);

                $startAt = $startAt + 50;

                if ($results->isLast) {
                    break;
                }
            }

            foreach ($epics as $epic) {
                $epic->spentSum = 0;
                $epic->remainingSum = 0;
                $epic->originalEstimateSum = 0;
            }

            // Calculate spent and remaining.
            $spentSum = 0;
            $remainingSum = 0;
            foreach ($issues as $issue) {
                $spentSum = $spentSum + $issue->fields->timespent;
                if (isset($issue->epic)) {
                    $issue->epic->spentSum = $issue->epic->spentSum + $issue->fields->timespent;
                } else {
                    $epics['NoEpic']->spentSum = $epics['NoEpic']->spentSum + $issue->fields->timespent;
                }

                if (!$issue->fields->status->name != 'Done' && isset($issue->fields->timetracking->remainingEstimateSeconds)) {
                    $remainingSum = $remainingSum + $issue->fields->timetracking->remainingEstimateSeconds;

                    if (isset($issue->epic)) {
                        $issue->epic->remainingSum = $issue->epic->remainingSum + $issue->fields->timetracking->remainingEstimateSeconds;
                    } else {
                        $epics['NoEpic']->remainingSum = $epics['NoEpic']->remainingSum + $issue->fields->timetracking->remainingEstimateSeconds;
                    }
                }

                if (isset($issue->fields->timeoriginalestimate)) {
                    if (isset($issue->epic)) {
                        $issue->epic->originalEstimateSum = $issue->epic->originalEstimateSum + $issue->fields->timeoriginalestimate;
                    } else {
                        $epics['NoEpic']->originalEstimateSum = $epics['NoEpic']->originalEstimateSum + $issue->fields->timeoriginalestimate;
                    }
                }
            }
            $spentHours = $spentSum / 3600;
            $remainingHours = $remainingSum / 3600;

            return $this->render(
                'jira/sprint_report_version.html.twig',
                [
                    'version' => $version,
                    'project' => $project,
                    'issues' => $issues,
                    'activeSprint' => $activeSprint,
                    'allSprints' => $allSprints,
                    'spentSum' => $spentSum,
                    'spentHours' => $spentHours,
                    'remainingHours' => $remainingHours,
                    'epics' => $epics,
                ]
            );
        } catch (HttpException $e) {
            return new RedirectResponse('/login');
        }
    }


    /**
     * @Route("/")
     * @Method("GET")
     */
    public function indexAction(JiraService $jira)
    {
        return $this->render(
            'jira/index.html.twig',
            []
        );
    }
}
