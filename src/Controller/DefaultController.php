<?php

namespace App\Controller;

use App\Service\JiraService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class DefaultController extends Controller
{
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
   * @Route("/planning/sprint/{boardId}", name="planning")
   * @Method("GET")
   */
  public function planningDisplay($boardId, JiraService $jira) {
    $sprints = $jira->get('/rest/agile/1.0/board/' . $boardId . '/sprint?state=future,active');
    return $this->render('jira/planning.html.twig', array(
      'data' => json_encode(array(
        'sprints' => $sprints,
      )),
    ));
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
