<?php
namespace Kunstmaan\DashboardBundle\Controller;

use Kunstmaan\DashboardBundle\Command\GoogleAnalyticsCommand;
use Kunstmaan\DashboardBundle\Entity\AnalyticsGoal;
use Kunstmaan\DashboardBundle\Entity\AnalyticsSegment;
use Kunstmaan\DashboardBundle\Helper\GoogleClientHelper;
use Kunstmaan\DashboardBundle\Repository\AnalyticsConfigRepository;
use Kunstmaan\DashboardBundle\Repository\AnalyticsOverviewRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GoogleAnalyticsAJAXController extends Controller
{

    /**
     * @Route("/updateData", name="KunstmaanDashboardBundle_analytics_update")
     */
    public function runUpdate(Request $request)
    {
        $configId = $request->query->get('configId');
        $segmentId = $request->query->get('segmentId');

        $command = new GoogleAnalyticsCommand();
        $command->setContainer($this->container);
        $input = new ArrayInput(array('--config' => $configId, '--segment' => $segmentId));
        $output = new NullOutput();
        $command->run($input, $output);

        return new JsonResponse(array(), 200, array('Content-Type' => 'application/json'));
    }


    /**
     * Return an ajax response with all data for an overview
     *
     * @Route("/getOverview/{id}", requirements={"id" = "\d+"}, name="KunstmaanDashboardBundle_analytics_overview_ajax")
     *
     */
    public function getOverviewAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var AnalyticsOverviewRepository $analyticsOverviewRepository */
        $analyticsOverviewRepository = $em->getRepository('KunstmaanDashboardBundle:AnalyticsOverview');
        $overview = $analyticsOverviewRepository->getOverview($id);

        // goals data
        $goals = array();
        foreach ($overview->getActiveGoals() as $key => $goal) {
            /** @var AnalyticsGoal $goal */
            $goals[$key]['name'] = $goal->getName();
            $goals[$key]['visits'] = $goal->getVisits();
            $goals[$key]['id'] = $goal->getId();
            $goals[$key]['chartData'] = json_decode($goal->getChartData());
        }

        // overview data
        $overviewData = array(
            'id' => $overview->getId(),
            'chartData' => json_decode($overview->getChartData(), true),
            'chartDataMaxValue' => $overview->getChartDataMaxValue(),
            'title' => $overview->getTitle(),
            'timespan' => $overview->getTimespan(),
            'startOffset' => $overview->getStartOffset(),
            'sessions' => number_format($overview->getSessions()),
            'users' => number_format($overview->getUsers()),
            'pagesPerSession' => round($overview->getPagesPerSession(), 2),
            'avgSessionDuration' => $overview->getAvgSessionDuration(),
            'returningUsers' => number_format($overview->getReturningUsers()),
            'newUsers' => round($overview->getNewUsers(), 2),
            'pageViews' => number_format($overview->getPageViews()),
            'returningUsersPercentage' => $overview->getReturningUsersPercentage(),
            'newUsersPercentage' => $overview->getNewUsersPercentage(),
        );

        // put all data in the return array
        $return = array(
            'responseCode' => 200,
            'overview' => $overviewData,
            'goals' => $goals,
        );

        // return json response
        return new JsonResponse($return, 200, array('Content-Type' => 'application/json'));
    }

    /* =============================== ACCOUNT =============================== */

        /**
         * @Route("/accounts/", name="DashBoardBundle_AJAX_accounts")
         */
        public function getAccounts(Request $request) {
            $configHelper = $this->container->get('kunstmaan_dashboard.helper.google.analytics.config');
            $configId = $request->query->get('configId');
            if ($configId) $configHelper->init($configId);

            $accounts = $configHelper->getAccounts();
            return new JsonResponse($accounts, 200, array('Content-Type' => 'application/json'));
        }

        /**
         * @Route("/account/save", name="DashBoardBundle_AJAX_account_save")
         */
        public function saveAccount(Request $request) {
            $accountId = $request->query->get('id');
            $configHelper = $this->container->get('kunstmaan_dashboard.helper.google.analytics.config');
            $configHelper->saveAccountId($accountId);
            return new JsonResponse();
        }

    /* =============================== PROPERTY =============================== */

        /**
         * @Route("/properties/", name="DashBoardBundle_AJAX_properties")
         */
        public function getProperties(Request $request) {
            $accountId = $request->query->get('accountId');
            $configHelper = $this->container->get('kunstmaan_dashboard.helper.google.analytics.config');
            $configId = $request->query->get('configId');
            if ($configId) $configHelper->init($configId);

            $properties = $configHelper->getProperties($accountId);
            return new JsonResponse($properties, 200, array('Content-Type' => 'application/json'));
        }

        /**
         * @Route("/property/save", name="DashBoardBundle_AJAX_property_save")
         */
        public function saveProperty(Request $request) {
            $propertyId = $request->query->get('id');
            $configHelper = $this->container->get('kunstmaan_dashboard.helper.google.analytics.config');
            $configHelper->savePropertyId($propertyId);
            return new JsonResponse();
        }

    /* =============================== PROFILE =============================== */

        /**
         * @Route("/profiles/", name="DashBoardBundle_AJAX_profiles")
         */
        public function getProfiles(Request $request) {
            $accountId = $request->query->get('accountId');
            $propertyId = $request->query->get('propertyId');
            $configHelper = $this->container->get('kunstmaan_dashboard.helper.google.analytics.config');
            $configId = $request->query->get('configId');
            if ($configId) $configHelper->init($configId);

            $profiles = $configHelper->getProfiles($accountId, $propertyId);

            return new JsonResponse($profiles, 200, array('Content-Type' => 'application/json'));
        }

        /**
         * @Route("/profile/save", name="DashBoardBundle_AJAX_profile_save")
         */
        public function saveProfile(Request $request) {
            $propertyId = $request->query->get('id');
            $configHelper = $this->container->get('kunstmaan_dashboard.helper.google.analytics.config');
            $configHelper->saveProfileId($propertyId);
            return new JsonResponse();
        }

    /* =============================== CONFIG =============================== */

        /**
         * @Route("/config/save", name="DashBoardBundle_AJAX_config_save")
         */
        public function saveConfig(Request $request) {
            // get params
            $accountId = $request->query->get('accountId');
            $propertyId = $request->query->get('propertyId');
            $profileId = $request->query->get('profileId');

            // edit the config
            $em = $this->getDoctrine()->getManager();
            $config = $em->getRepository('KunstmaanDashboardBundle:AnalyticsConfig')->getConfig();
            if ($accountId && $propertyId && $profileId) {
                $config->setAccountId($accountId);
                $config->setPropertyId($propertyId);
                $config->setProfileId($profileId);
            }

            $em->persist($config);
            $em->flush();
            return new JsonResponse();
        }

        /**
         * @Route("/config/get", name="DashBoardBundle_AJAX_config_get")
         */
        public function getConfig(Request $request) {
            $em = $this->getDoctrine()->getManager();
            $config = $em->getRepository('KunstmaanDashboardBundle:AnalyticsConfig')->getConfig();
            $accountId = $config->getAccountId();

            if (!$accountId) {
                return new JsonResponse();
            }

            $propertyId = $config->getPropertyId();
            $profileId = $config->getPropertyId();

            $configHelper = $this->container->get('kunstmaan_dashboard.helper.google.analytics.config');
            $accounts = $configHelper->getAccounts();
            $properties = $configHelper->getProperties();
            $profiles = $configHelper->getProfiles();

        }

    /* =============================== SEGMENT =============================== */

        /**
         * @Route("/segment/add/", name="DashBoardBundle_AJAX_segement_add")
         */
        public function addSegment(Request $request) {
            $em = $this->getDoctrine()->getManager();

            // create a new segment
            $segment = new AnalyticsSegment();
            $query = $request->query->get('query');
            $name = $request->query->get('name');
            $segment->setQuery($query);
            $segment->setName($name);

            // add the segment to the config
            $analyticsConfig = $em->getRepository('KunstmaanDashboardBundle:AnalyticsConfig');
            $config = $analyticsConfig->getconfig();
            $segment->setConfig($config);
            $segments = $config->getSegments();
            $segments[] = $segment;

            $em->persist($config);
            $em->flush();
            return new JsonResponse();
        }

        /**
         * @Route("/segment/delete", name="DashBoardBundle_AJAX_segement_delete")
         */
        public function deleteSegment(Request $request) {
            $em = $this->getDoctrine()->getManager();

            // remove the segment
            $id = $request->query->get('id');
            $em->getRepository('KunstmaanDashboardBundle:AnalyticsSegment')->deleteSegment($id);
            return new JsonResponse();
        }


}
