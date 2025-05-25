<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Service\ExpenseService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class ExpenseController extends BaseController
{
    private const PAGE_SIZE = 20;

    public function __construct(
        Twig $view,
        private readonly ExpenseService $expenseService,
    ) {
        parent::__construct($view);
    }

    public function index(Request $request, Response $response): Response
    {
        $userId = $_SESSION['user_id'];
        $page = (int)($request->getQueryParams()['page'] ?? 1);
        $pageSize = (int)($request->getQueryParams()['pageSize'] ?? self::PAGE_SIZE);
        $year = (int)($request->getQueryParams()['year'] ?? date('Y'));
        $month = (int)($request->getQueryParams()['month'] ?? date('n'));

        $result = $this->expenseService->list($userId, $year, $month, $page, $pageSize);

        $expenses = $result['expenses'] ?? [];
        $total = $result['total'] ?? 0;

        return $this->render($response, 'expenses/index.twig', [
            'expenses' => $expenses,
            'total'    => $total,
            'page'     => $page,
            'pageSize' => $pageSize,
            'year'     => $year,
            'month'    => $month,
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        $category = $_ENV['CATEGORY'];
        return $this->render($response, 'expenses/create.twig', ['categories' => $category]);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $userId = $_SESSION['user_id'] ?? null; // TODO: obtain logged-in user ID from session
        $amount = (float)($data['amount'] ?? 0);
        $amountCents = (int)round($amount * 100); // convert to cents
        $description = (string)($data['description'] ?? '');
        $date = new \DateTimeImmutable($data['date'] ?? 'now');
        $category = (string)($data['category'] ?? '');

        try {
            $this->expenseService->create($userId, $amountCents, $description, $date, $category);
            return $response->withHeader('Location', '/expenses')->withStatus(302);
        } catch (\InvalidArgumentException $e) {
            // handle validation errors
            return $this->render($response, 'expenses/create.twig', [
                'errors' => ['amount' => $e->getMessage()],
                'amount' => $amountCents,
                'description' => $description,
                'date' => $date->format('m-d-Y'),
                'category' => $category
            ]);
        }
        return $response;
    }

    public function edit(Request $request, Response $response, array $routeParams): Response
    {
        $expenseId = (int)$routeParams['id'];
        $expense = $this->expenseService->find($expenseId);
        $userId = $_SESSION['user_id'] ?? null;
        $categories = $_ENV['CATEGORY'];
        
        if (!$expense) {
            return $response->withStatus(404)->write('Expense not found');
        }
        
        if ($expense->userId !== $userId) {
            return $response->withStatus(403)->write('Forbidden: You do not own this expense');
        }

        return $this->render($response, 'expenses/edit.twig', [
            'expense' => $expense,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to update an existing expense

        // Hints:
        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not
        // - get the new values from the request and prepare for update
        // - update the expense entity with the new values
        // - rerender the "expenses.edit" page with included errors in case of failure
        // - redirect to the "expenses.index" page in case of success
        $expenseId = (int)$routeParams['id'];
        $expense = $this->expenseService->find($expenseId);
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$expense) {
            return $response->withStatus(404)->write('Expense not found');
        }
        
        if ($expense->userId !== $userId) {
            return $response->withStatus(403)->write('Forbidden: You do not own this expense');
        }

        $data = $request->getParsedBody();
        $amount = (float)($data['amount'] ?? 0);
        $description = (string)($data['description'] ?? '');
        $date = new \DateTimeImmutable($data['date'] ?? 'now');
        $category = (string)($data['category'] ?? '');
        
        try {
            $this->expenseService->update($expense, $amount, $description, $date, $category);
            return $response->withHeader('Location', '/expenses')->withStatus(302);
        } catch (\Exception $e) {
            return $this->render($response, 'expenses/edit.twig', [
                'errors' => ['form' => $e->getMessage()],
                'expense' => $expense,
            ]);
        }
    }

    public function destroy(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to delete an existing expense

        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not
        // - call the repository method to delete the expense
        // - redirect to the "expenses.index" page
        $expenseId = (int)$routeParams['id'];
        $expense = $this->expenseService->find($expenseId);
        $userId = $_SESSION['user_id'] ?? null;
        if (!$expense) {
            return $response->withStatus(404)->write('Expense not found');
        }
        if ($expense->userId !== $userId) {
            return $response->withStatus(403)->write('Forbidden: You do not own this expense');
        }
        $this->expenseService->delete($expenseId);
        $response = $response->withHeader('Location', '/expenses')->withStatus(302);

        return $response;
    }
}
