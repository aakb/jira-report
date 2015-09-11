<?php

namespace Itk\JiraBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class DefaultController extends Controller
{
  /**
   * @Route("/main", name="index")
   */
  public function indexAction()
  {
    $jira = $this->get('jira.service');

    $body = $jira->get('/rest/agile/1.0/board');

    return $this->render('ItkJiraBundle:Default:board.html.twig', array('boards' => $body->values));
  }
}
