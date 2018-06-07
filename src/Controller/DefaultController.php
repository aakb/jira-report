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
    public function sprintReportListAction(JiraService $jira) {
        try {
            $projects = $jira->get('/rest/api/2/project');
            return $this->render('jira/sprint_report_list.html.twig', array(
                    'projects' => $projects,
                )
            );
        }
        catch (HttpException $e) {
            return new RedirectResponse('/login');
        }
    }

    /**
     * @Route("/sprint_report/project/{pid}")
     * @Method("GET")
     */
    public function sprintReportAction(JiraService $jira, $pid) {
        try {
            $project = $jira->get('/rest/api/2/project/' . $pid);
            return $this->render('jira/sprint_report.html.twig', array(
                    'project' => $project,
                )
            );
        }
        catch (HttpException $e) {
            return new RedirectResponse('/login');
        }
    }

    /**
     * @Route("/sprint_report/version/{vid}")
     * @Method("GET")
     */
    public function sprintReportVersionAction(JiraService $jira, $vid) {
        try {
            $version = $jira->get('/rest/api/2/version/' . $vid);
            $project = $jira->get('/rest/api/2/project/' . $version->projectId);

            // Get all issues for version.
            $issues = [];
            $startAt = 0;
            while (true) {
                $results = $jira->get(
                    '/rest/api/2/search' .
                    '?jql=fixVersion=' . $vid .
                    '&project=' . $version->projectId .
                    '&maxResults=50' .
                    '&fields=timetracking,worklog,timespent,timeoriginalestimate,summary,assignee,status,resolutionDate,customfield_10006' .
                    '&startAt=' . $startAt);
                $issues = array_merge($issues, $results->issues);

                $startAt = $startAt + 50;

                if ($results->total < $startAt) {
                    break;
                }
            }

            foreach ($issues as $issue) {
                $issue->sprints = [];

                foreach($issue->fields->customfield_10006 as $sprintString) {
                    $replace = preg_replace(['/.*\[/', '/].*/'], '', $sprintString);
                    $fields = explode(',', $replace);

                    $sprint = [];
                    foreach ($fields as $field) {
                        $split = explode('=', $field);

                        $sprint[$split[0]] = $split[1];
                    }

                    $issue->sprints[] = (object)$sprint;
                }
            }

            // Get active sprint.
            $boardId = 65;
            $activeSprint = $jira->get('/rest/agile/1.0/board/' . $boardId . '/sprint?state=active');
            $activeSprint = count($activeSprint->values) > 0 ? $activeSprint->values[0] : null;

            // Get all sprints.
            $allSprints = [];
            $startAt = 0;
            while (true) {
                $results = $jira->get('/rest/agile/1.0/board/' . $boardId . '/sprint?startAt='.$startAt);
                $allSprints = array_merge($allSprints, $results->values);

                $startAt = $startAt + 50;

                if ($results->isLast) {
                    break;
                }
            }

            $spentSum = 0;
            $remainingSum = 0;
            foreach ($issues as $issue) {
                $spentSum = $spentSum + $issue->fields->timespent;

                if (!$issue->fields->status->name != 'Done' && isset($issue->fields->timetracking->remainingEstimateSeconds)) {
                    $remainingSum = $remainingSum + $issue->fields->timetracking->remainingEstimateSeconds;
                }
            }
            $spentHours = $spentSum / 60 / 60;
            $remainingHours = $remainingSum / 60 / 60;

            return $this->render('jira/sprint_report_version.html.twig', array(
                    'version' => $version,
                    'project' => $project,
                    'issues' => $issues,
                    'activeSprint' => $activeSprint,
                    'allSprints' => $allSprints,
                    'spentSum' => $spentSum,
                    'spentHours' => $spentHours,
                    'remainingHours' => $remainingHours,
                )
            );
        }
        catch (HttpException $e) {
            return new RedirectResponse('/login');
        }
    }

  /**
   * @Route("/")
   * @Method("GET")
   */
  public function indexAction(JiraService $jira) {
    $boardId = 65;
    $activeSprint = $jira->get('/rest/agile/1.0/board/' . $boardId . '/sprint?state=active');

    return $this->render('jira/index.html.twig', array(
      'defaultBoard' => array(
        'id' => $boardId,
        'name' => 'Team Board'
      ),
        'activeSprint' => $activeSprint->values['0']->id,
        'activeSprintName' => $activeSprint->values['0']->name,
      )
    );
  }

  /**
   * @Route("/boards", name="boards")
   * @Method("GET")
   */
  public function boardsAction(JiraService $jira) {
    $itemsPerPage = 50;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $startAt = ($page - 1) * $itemsPerPage;
    $body = $jira->get('/rest/agile/1.0/board?startAt=' . $startAt . '&maxResults=' . $itemsPerPage);
    $pager = $this->getPagerData($body, $page, $itemsPerPage);

    return $this->render('jira/boards.html.twig', array('values' => $body->values, 'pager' => $pager));
  }

  /**
   * @Route("/board/{boardId}", name="board")
   * @Method("GET")
   */
  public function boardAction($boardId, JiraService $jira) {
    $itemsPerPage = 50;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $startAt = ($page - 1) * $itemsPerPage;
    $body = $jira->get('/rest/agile/1.0/board/' . $boardId . '/sprint?state=future,active');
    $closed = $jira->get('/rest/agile/1.0/board/' . $boardId . '/sprint?state=closed&startAt=' . $startAt . '&maxResults=' . $itemsPerPage);
    $pager = $this->getPagerData($closed, $page, $itemsPerPage);

    return $this->render('jira/board.html.twig', array('sprints' => $body->values, 'id' => $boardId, 'pager' => $pager, 'closed' => $closed->values));
  }

  /**
   * @Route("/board/{boardId}/sprint/{sprintId}", name="board_sprint")
   * @Method("GET")
   */
  public function sprintAction($boardId, $sprintId, JiraService $jira) {
    $sprint = $jira->get('/rest/agile/1.0/sprint/' . $sprintId);

    $board = $jira->get('/rest/agile/1.0/board/' . $boardId . '/sprint/' . $sprintId . '/issue?maxResults=1000');

    $nextBoard = $jira->get('/rest/agile/1.0/board/' . $boardId . '/sprint/' . (1 + $sprintId) . '/issue?maxResults=1000');

    $projects = array();

    foreach ($board->issues as $issue) {
      $key = $issue->fields->project->key;
      if (!isset($projects[$key])) {
        $projects[$key] = array(
          'issues' => array(),
          'next_issues' => array(),
          'name' => $issue->fields->project->name,
          'id' => $key,
        );
      }

      $projects[$key]['issues'][] = $issue;
    }

    foreach ($nextBoard->issues as $issue) {
      $key = $issue->fields->project->key;
      if (isset($projects[$key])) {
        $projects[$key]['next_issues'][] = $issue;
      }
    }

    return $this->render('jira/sprint.html.twig', array(
      'sprint' => $sprint,
      'projects' => $projects,
      'name' => 'test',
      'boardId' => $boardId,
      'sprintId' => $sprintId
    ));
  }

  /**
   * @Route("/jira/{token}", name="jira", requirements={"token"=".+"})
   * @Method("GET")
   */
  public function jiraDisplay(JiraService $jira, Request $request, $token) {
    return new JsonResponse($jira->get('/rest/agile/1.0/' . $token . "?" . $request->getQueryString()));
  }

  /**
   * @Route("/planning", name="planning")
   * @Method("GET")
   */
  public function planningDisplay(JiraService $jira) {
    return $this->render('jira/planning.html.twig');
  }

  /**
   * @Route("/project/{boardId}", name="project")
   * @Method("GET")
   */
  public function projectDisplay(JiraService $jira, $boardId) {
    return $this->render('jira/project.html.twig');
  }

  /**
   *
   */
  public function getPagerData($body, $page, $itemsPerPage) {
    $pager =[];

    $pager['currentPage'] = $page;
    $pager['lastPage'] = property_exists($body, 'total') ? (int)ceil($body->total / $itemsPerPage) : FALSE;
    $pager['itemsPerPage'] = $itemsPerPage;
    $pager['total'] = isset($pager['total']) ? $pager['total'] : FALSE;
    $pager['maxResults'] = $itemsPerPage;
    $pager['isLast'] = $body->isLast;

    return $pager;
  }
}
