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
            // Get version, project, customFields from Jira.
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

            // Get active sprint.
            $boardId = getenv('JIRA_DEFAULT_BOARD');
            $activeSprint = $jira->get(
                '/rest/agile/1.0/board/'.$boardId.'/sprint?state=active'
            );
            $activeSprint = count(
                $activeSprint->values
            ) > 0 ? $activeSprint->values[0] : null;

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
                'spentSum' => 0,
                'remainingSum' => 0,
                'originalEstimateSum' => 0,
                'plannedWorkSum' => 0,
            ];
            $sprints = [];
            $spentSum = 0;
            $remainingSum = 0;

            // Extract sprint and epics from agile custom field.
            foreach ($issues as $issue) {
                $issue->sprints = [];

                // Get sprints for issue.
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

                    // Set shortName
                    $sprint['shortName'] = str_replace('Sprint ', '', $sprint['name']);

                    $issue->sprints[] = (object) $sprint;

                    if (!isset($sprints[$sprint['id']])) {
                        $sprints[$sprint['id']] = (object) $sprint;
                    }

                    if ($sprint['state'] == 'ACTIVE' || $sprint['state'] == 'FUTURE') {
                        $issue->assignedToSprint = (object) $sprint;
                    }
                }

                // Get issue epic.
                if (isset($issue->fields->{$customFieldEpicLink->key})) {
                    if (!isset($epics[$issue->fields->{$customFieldEpicLink->key}])) {
                        $epic = $epics[$issue->fields->{$customFieldEpicLink->key}] = $jira->get(
                            'rest/agile/1.0/epic/'.$issue->fields->{$customFieldEpicLink->key}
                        );

                        $epic->spentSum = 0;
                        $epic->remainingSum = 0;
                        $epic->originalEstimateSum = 0;
                        $epic->plannedWorkSum = 0;
                    }
                    $issue->epic = $epics[$issue->fields->{$customFieldEpicLink->key}];
                }
                else {
                    $issue->epic = $epics['NoEpic'];
                }

                // Gather worklogs for sprints/epics.
                if (!isset($issue->epic->worklogs)) {
                    $issue->epic->worklogs = [];
                }
                if (!isset($issue->epic->loggedWork)) {
                    $issue->epic->loggedWork = [];
                }
                foreach ($issue->fields->worklog->worklogs as $worklog) {
                    $this->worklogStarted = strtotime($worklog->started);
                    $sprint = array_filter($sprints, function($k) {
                        return
                            strtotime($k->startDate) <= $this->worklogStarted &&
                            (isset($k->completeDate) ? strtotime($k->completeDate) : strtotime($k->endDate)) > $this->worklogStarted;
                    });

                    if (!empty($sprint)) {
                        $sprint = array_pop($sprint);

                        if (!isset($issue->epic->worklogs[$sprint->id])) {
                            $issue->epic->worklogs[$sprint->id] = [];
                        }
                        $issue->epic->worklogs[$sprint->id][] = $worklog;

                        $issue->epic->loggedWork[$sprint->id] = (isset($issue->epic->loggedWork[$sprint->id]) ? $issue->epic->loggedWork[$sprint->id] : 0) + $worklog->timeSpentSeconds;
                    }
                    else {
                        if (!isset($issue->epic->worklogs['NoSprint'])) {
                            $issue->epic->worklogs['NoSprint'] = [];
                        }
                        $issue->epic->worklogs['NoSprint'][] = $worklog;

                        $issue->epic->loggedWork['NoSprint'] = (isset($issue->epic->loggedWork['NoSprint']) ? $issue->epic->loggedWork['NoSprint'] : 0) + $worklog->timeSpentSeconds;
                    }
                }

                // Accumulate spentSum.
                $spentSum = $spentSum + $issue->fields->timespent;
                $issue->epic->spentSum = $issue->epic->spentSum + $issue->fields->timespent;

                // Accumulate remainingSum.
                if (!$issue->fields->status->name != 'Done' && isset($issue->fields->timetracking->remainingEstimateSeconds)) {
                    $remainingSum = $remainingSum + $issue->fields->timetracking->remainingEstimateSeconds;

                    $issue->epic->remainingSum = $issue->epic->remainingSum + $issue->fields->timetracking->remainingEstimateSeconds;

                    if (!empty($issue->assignedToSprint)) {
                        $sprint = $issue->assignedToSprint;
                        $issue->epic->remainingWork[$sprint->id] = (isset($issue->epic->remainingWork[$sprint->id]) ? $issue->epic->remainingWork[$sprint->id] : 0) + $issue->fields->timetracking->remainingEstimateSeconds;
                        $issue->epic->plannedWorkSum = $issue->epic->plannedWorkSum + $issue->fields->timetracking->remainingEstimateSeconds;
                    }
                }

                // Accumulate originalEstimateSum.
                if (isset($issue->fields->timeoriginalestimate)) {
                    $issue->epic->originalEstimateSum = $issue->epic->originalEstimateSum + $issue->fields->timeoriginalestimate;
                }
            }

            // Sort sprints by key.
            ksort($sprints);

            // Calculate spent, remaining hours.
            $spentHours = $spentSum / 3600;
            $remainingHours = $remainingSum / 3600;

            return $this->render(
                'jira/sprint_report_version.html.twig',
                [
                    'version' => $version,
                    'project' => $project,
                    'issues' => $issues,
                    'activeSprint' => $activeSprint,
                    'sprints' => $sprints,
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
