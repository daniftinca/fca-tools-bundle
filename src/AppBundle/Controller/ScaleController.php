<?php

namespace AppBundle\Controller;

use AppBundle\Document\Context;
use AppBundle\Document\DatabaseConnection;
use AppBundle\Document\Scale;
use AppBundle\Document\User;
use AppBundle\Helper\CommonUtils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Security("has_role('ROLE_USER')")
 */
class ScaleController extends BaseController
{
    /**
     * @Route("/create-new-scale", name="create_new_scale")
     *
     * @param Request $request
     * @return Response
     */
    public function createNewScaleAction(Request $request)
    {
        $this->startStatisticsCounter();

        $databaseConnectionService = $this->get("app.database_connection_service");
        $tab = "select-database";
        $errors = array();
        $fillData = array();

        if ($request->isMethod("POST")) {
            $scaleService = $this->get("app.scale_service");
            $postData = $request->request;

            $databaseConnectionId = $postData->get('databaseConnectionId');
            $tableName = $postData->get("tableName");
            $scaleName = $postData->get("scaleName");
            $scaleType = $postData->get("scaleType");

            /** @var DatabaseConnection $databaseConnection */
            $databaseConnection = $this->getRepo("AppBundle:DatabaseConnection")->find($databaseConnectionId);

            $errors = $scaleService->validateDatabaseConnection($errors, $databaseConnection);
            if (empty($errors)) {
                $tab = "describe-scale";
                $fillData['tables'] = $databaseConnectionService->getTables($databaseConnection);

                $errors = $scaleService->validateGenericScale($errors, $tableName, $scaleName, $scaleType, $fillData['tables']);
            }

            if (empty($errors)) {
                $tab = "define-scale";

                $fillData['tableData'] = $databaseConnectionService->getTableData($databaseConnection, $tableName);
                $errors = $scaleService->validateScaleType($errors, $scaleType, $postData, $fillData['tableData']);
            }

            if (empty($errors)) {
                $scale = new Scale();
                $scale->setUser($this->getUser());
                $scale->setDatabaseConnection($databaseConnection);
                $scale->setTable($tableName);
                $scale->setName($scaleName);
                $scale->setType($scaleType);

                $context = new Context();
                $context->setName($scaleName);
                $context->setDimCount(2);
                $context->setIsPublic(false);
                $context->setScale($scale);
                $scale->setContext($context);

                $errors = $scaleService->updateContextByScaleType($context, $scale, $postData, $errors);

                if (empty($errors)) {
                    $fileName = CommonUtils::generateFileName("cxt");
                    $contextService = $this->get("app.context_service");
                    $errors = $contextService->computeConceptsAndConceptLattice($context, $fileName, $errors);

                    if (empty($errors)) {
                        $em = $this->getManager();
                        $em->persist($context);
                        $em->persist($scale);
                        $em->flush();

                        $this->stopCounterAndLogStatistics("create scale", $context);

                        return $this->redirect($this->generateUrl("view_scale", array(
                            "id" => $scale->getId(),
                        )));
                    }
                }
            }
        }

        return $this->render('@App/Scale/createNewScale.html.twig', array(
            'activeMenu' => "my_scales",
            'errors' => $errors,
            'fillData' => $fillData,
            'tab' => $tab,
        ));
    }

    /**
     * @Route("/my-scales", name="list_user_scales")
     *
     * @return Response
     */
    public function listUserScalesAction()
    {
        /** @var User $user */
        $user = $this->getUser();
        $scales = $user->getScales();

        return $this->render('@App/Scale/listUserScales.html.twig', array(
            'activeMenu' => "my_scales",
            'scales' => $scales,
        ));
    }

    /**
     * @Route("/view-scale/{id}", name="view_scale")
     *
     * @param $id
     * @return Response
     */
    public function viewScaleAction($id)
    {
        /** @var Scale $scale */
        $scale = $this->getRepo("AppBundle:Scale")->find($id);

        if (!$this->isValidScale($scale, array("not null", "can view"))) {
            return $this->renderFoundError("my_scales");
        }

        $databaseConnectionService = $this->get("app.database_connection_service");
        $tableData = $databaseConnectionService
            ->getTableData($scale->getDatabaseConnection(), $scale->getTable());

        return $this->render("@App/Scale/scale.html.twig", array(
            'scale' => $scale,
            'tableData' => $tableData,
            'activeMenu' => "my_scales",
        ));
    }

    /**
     * @Route("/delete-scale/{id}", name="delete_scale")
     *
     * @param $id
     * @return Response
     */
    public function deleteScaleAction($id)
    {
        /** @var Scale $scale */
        $scale = $this->getRepo("AppBundle:Scale")->find($id);

        if (!$this->isValidScale($scale, array("not null", "can view", "is own"))) {
            return $this->renderFoundError("my_scales");
        }

        $em = $this->getManager();
        $em->remove($scale);
        $em->flush();

        return $this->redirect($this->generateUrl("list_user_scales"));
    }

    /**
     * @Route("/apply-scale/{id}", name="apply_scale")
     *
     * @param $id
     * @param Request $request
     * @return Response
     */
    public function applyScaleAction($id, Request $request)
    {
        /** @var Scale $scale */
        $scale = $this->getRepo("AppBundle:Scale")->find($id);

        if (!$this->isValidScale($scale, array("not null", "can view"))) {
            return $this->renderFoundError("my_scales");
        }

        $column = $request->query->get("objectColumn");

        return $this->render("@App/Scale/scaleContextConceptLattice.html.twig", array(
            'activeMenu' => "my_scales",
            'scale' => $scale,
            'column' => $column,
        ));
    }

    /**
     * @Route("/get-scale-concept-lattice-data/{id}/column/{column}", name="get_scale_concept_lattice_data")
     *
     * @param $id
     * @param $column string
     * @return JsonResponse
     */
    public function getScaleConceptLatticeDataAction($id, $column)
    {
        /** @var Scale $scale */
        $scale = $this->getRepo("AppBundle:Scale")->find($id);

        if (!$this->isValidScale($scale, array("not null", "can view"))) {
            return $this->renderFoundErrorAsJson();
        }

        $scaleService = $this->get("app.scale_service");
        $context = $scaleService->generateContextFromScale($scale, $column);
        $fileName = CommonUtils::generateFileName("cxt");
        $errors = array();

        $contextService = $this->container->get("app.context_service");
        $errors = $contextService->computeConceptsAndConceptLattice($context, $fileName, $errors);

        if (!empty($errors)) {
            return $this->renderErrorAsJson($errors[0]);
        }

        $generateLatticeService = $this->container->get("app.generate_lattice_service");
        $parsedConceptLattice = $generateLatticeService->generateParsedConceptLattice($context);
        $wapService = $this->container->get("app.weak_analogical_proportions_service");
        $parsedConceptLattice["analogicalComplexes"] = $wapService->generateWeakAnalogicalProportions($context);

        return new JsonResponse($parsedConceptLattice);
    }

    /**
     * @Route("/get-tables", name="get_tables")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTablesAction(Request $request)
    {
        $databaseConnectionId = $request->query->get("databaseConnectionId");
        /** @var DatabaseConnection $databaseConnection */
        $databaseConnection = $this->getRepo("AppBundle:DatabaseConnection")->find($databaseConnectionId);

        $databaseConnectionService = $this->get("app.database_connection_service");
        $tables = $databaseConnectionService->getTables($databaseConnection);

        return new JsonResponse(array(
            "success" => true,
            "data" => array(
                "tables" => $tables
            )
        ));
    }

    /**
     * @Route("/get-table-data", name="get_table_data")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTableDataAction(Request $request)
    {
        $databaseConnectionId = $request->query->get("databaseConnectionId");
        /** @var DatabaseConnection $databaseConnection */
        $databaseConnection = $this->getRepo("AppBundle:DatabaseConnection")->find($databaseConnectionId);

        $tableName = $request->query->get("table");
        $databaseConnectionService = $this->get("app.database_connection_service");
        $tableData = $databaseConnectionService->getTableData($databaseConnection, $tableName);

        return new JsonResponse(array(
            "success" => true,
            "data" => array(
                "tableData" => $tableData
            )
        ));
    }

}
