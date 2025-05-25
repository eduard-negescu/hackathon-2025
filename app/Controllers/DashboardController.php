<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use App\Domain\Service\MonthlySummaryService;
use App\Domain\Service\AlertGenerator;

class DashboardController extends BaseController
{
    public function __construct(
        Twig $view,
        private readonly MonthlySummaryService $monthlySummaryService,
        private readonly AlertGenerator $alertGenerator,
    )
    {
        parent::__construct($view);
    }

    public function index(Request $request, Response $response): Response
    {
        // TODO: parse the request parameters
        // TODO: load the currently logged-in user
        // TODO: get the list of available years for the year-month selector
        // TODO: call service to generate the overspending alerts for current month
        // TODO: call service to compute total expenditure per selected year/month
        // TODO: call service to compute category totals per selected year/month
        // TODO: call service to compute category averages per selected year/month

        $userId = $_SESSION['user_id'] ?? null;
        $queryParams = $request->getQueryParams();
        $year = isset($queryParams['year']) ? (int)$queryParams['year'] : (int)date('Y');
        $month = isset($queryParams['month']) ? (int)$queryParams['month'] : (int)date('m');

        $years = range(2015, date('Y'));
        $totalForMonth = $this->monthlySummaryService->computeTotalExpenditure($userId, $year, $month);
        $totalsForCategories = $this->monthlySummaryService->computePerCategoryTotals($userId, $year, $month);
        $averagesForCategories = $this->monthlySummaryService->computePerCategoryAverages($userId, $year, $month);

        $alerts = $this->alertGenerator->generate($userId, $year, $month);

        error_log("Total amount for groceries: " . $totalsForCategories['groceries']);


        return $this->render($response, 'dashboard.twig', [

            'alerts'                => $alerts,
            'totalForMonth'         => $totalForMonth,
            'totalsForCategories'   => $totalsForCategories,
            'averagesForCategories' => $averagesForCategories,
            'years'                 => $years,
            'selectedYear'          => $year,
            'selectedMonth'         => $month,
        ]);
    }
}
