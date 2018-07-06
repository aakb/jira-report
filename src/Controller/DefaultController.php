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
            $sprintReport = $jira->getSprintReport($vid);

            return $this->render(
                'jira/sprint_report_version.html.twig',
                $sprintReport
            );
        } catch (HttpException $e) {
            return new RedirectResponse('/login');
        }
    }

    /**
     * @Route("/planning")
     * @Method("GET")
     */
    public function planningOverviewAction(JiraService $jira) {
        $jiraUrl = getenv('JIRA_URL');

        return $this->render(
            'jira/planning.html.twig',
            [
                'jiraUrl' => $jiraUrl,
            ]
        );
    }

    /**
     * @Route("/future_sprints")
     * @Method("GET")
     */
    public function futureSprints(JiraService $jira) {
        $sprints = $jira->getFutureSprints();

        return new JsonResponse(['sprints' => $sprints]);
    }

    /**
     * @Route("/issues/{sprintId}")
     * @Method("GET")
     */
    public function issuesInSprint(JiraService $jira, $sprintId) {
        $issues = $jira->getIssuesInSprint($sprintId);

        return new JsonResponse(['issues' => $issues]);
    }

    /**
<<<<<<< Updated upstream
=======
     * @Route("/billing")
     * @Method("GET")
     */
    public function getUnbilledIssues(JiraService $jira) {
        return $this->render(
            'jira/billing.html.twig'
        );
    }

    /**
     * @Route("/creator")
     * @Method("GET")
     */
    public function getCreator(JiraService $jira) {
        return $this->render(
            'jira/creator.html.twig'
        );
    }

    /**
     * @Route("/api/issues/{projectId}/{versionId}")
     * @Method("GET")
     */
    public function getIssuesForVersion(JiraService $jira, $projectId, $versionId) {
        return new JsonResponse($jira->getIssuesForVersion($projectId, $versionId));
    }

    /**
     * @Route("/api/project/{projectId}/epic")
     * @Method("GET")
     */
    public function getEpics(JiraService $jira, $projectId) {
        return new JsonResponse(['epics' => $jira->getEpics($projectId)]);
    }

    /**
     * @Route("/api/project/{projectId}/user")
     * @Method("GET")
     */
    public function getUsers(JiraService $jira, $projectId) {
        return new JsonResponse(['users' => $jira->get('/rest/api/2/user/search&projectKey='.$projectId.'&username=%')]);
    }

    /**
     * @Route("/api/project")
     * @Method("GET")
     */
    public function getProjects(JiraService $jira) {
        $projects = $jira->get('/rest/api/2/project');

        $activeProjects = [];

        foreach ($projects as $project) {
            if (!isset($project->projectCategory) || $project->projectCategory->name != 'Lukket') {
                $activeProjects[] = $project;
            }
        }

        return new JsonResponse(['projects' => $activeProjects]);
    }

    /**
     * @Route("/api/project/{projectId}")
     * @Method("GET")
     */
    public function getProject(JiraService $jira, $projectId) {
        $project = $jira->get('/rest/api/2/project/'.$projectId);

        return new JsonResponse(['project' => $project]);
    }

    /**
>>>>>>> Stashed changes
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
