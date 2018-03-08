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
  public function indexAction() {
    return $this->render('jira/index.html.twig', array('defaultBoard' => array('id' => 65, 'name' => 'Team Board')));
  }

  /**
   * @Route("/boards", name="boards")
   * @Method("GET")
   */
  public function boardsAction(JiraService $jira) {
    $body = $jira->get('/rest/agile/1.0/board');

    return $this->render('jira/boards.html.twig', array('boards' => $body->values));
  }

  /**
   * @Route("/board/{boardId}", name="board")
   * @Method("GET")
   */
  public function boardAction($boardId, JiraService $jira) {
    $body = $jira->get('/rest/agile/1.0/board/' . $boardId . '/sprint');

    return $this->render('jira/board.html.twig', array('sprints' => $body->values, 'id' => $boardId));
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
}
