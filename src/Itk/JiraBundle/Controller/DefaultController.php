<?php

namespace Itk\JiraBundle\Controller;

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
    return $this->render('ItkJiraBundle:Default:index.html.twig', array('defaultBoard' => array('id' => 6, 'name' => 'Team Board')));
  }

  /**
   * @Route("/boards")
   * @Method("GET")
   */
  public function boardsAction() {
    $jira = $this->get('jira.service');

    $body = $jira->get('/rest/agile/1.0/board');

    return $this->render('ItkJiraBundle:Default:boards.html.twig', array('boards' => $body->values));
  }

  /**
   * @Route("/board/{id}")
   * @Method("GET")
   */
  public function boardAction($id) {
    $jira = $this->get('jira.service');

    $body = $jira->get('/rest/agile/1.0/board/' . $id . '/sprint');

    return $this->render('ItkJiraBundle:Default:board.html.twig', array('sprints' => $body->values, 'id' => $id));
  }

  /**
   * @Route("/board/{boardId}/sprint/{sprintId}")
   * @Method("GET")
   */
  public function sprintAction($boardId, $sprintId) {
    $jira = $this->get('jira.service');

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

    return $this->render('ItkJiraBundle:Default:sprint.html.twig', array(
      'sprint' => $sprint,
      'projects' => $projects,
      'name' => 'test',
      'boardId' => $boardId,
      'sprintId' => $sprintId
    ));
  }
}
