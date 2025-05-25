<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use DateTimeImmutable;
use Exception;
use PDO;

class PdoExpenseRepository implements ExpenseRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {}

    /**
     * @throws Exception
     */
    public function find(int $id): ?Expense
    {
        $query = 'SELECT * FROM expenses WHERE id = :id';
        $statement = $this->pdo->prepare($query);
        $statement->execute(['id' => $id]);
        $data = $statement->fetch();
        if (false === $data) {
            return null;
        }

        return $this->createExpenseFromData($data);
    }

    public function save(Expense $expense): void
    {
        $query = 'INSERT INTO expenses (user_id, date, category, amount_cents, description) 
                  VALUES (:user_id, :date, :category, :amount_cents, :description)';
        $statement = $this->pdo->prepare($query);
        $statement->execute([
            'user_id' => $expense->userId,
            'date' => $expense->date->format('Y-m-d'),
            'category' => $expense->category,
            'amount_cents' => $expense->amountCents,
            'description' => $expense->description,
        ]);
        $expense->id = (int)$this->pdo->lastInsertId();

    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM expenses WHERE id=?');
        $statement->execute([$id]);
    }

    public function findBy(array $criteria, int $from, int $limit): array
    {
        $userId = $criteria['user_id'] ?? null;
        $year = isset($criteria['year']) ? (string)$criteria['year'] : null;
        $month = isset($criteria['month']) ? str_pad((string)$criteria['month'], 2, '0', STR_PAD_LEFT) : null;

        error_log("Finding expenses for user_id: $userId, year: $year, month: $month, from: $from, limit: $limit");

        $query = 'SELECT * FROM expenses WHERE user_id = :user_id';
        $params = ['user_id' => $userId];

        if ($year && $month) {
            $query .= ' AND strftime("%Y", date) = :year AND strftime("%m", date) = :month';
            $params['year'] = $year;
            $params['month'] = $month;
        } elseif ($year) {
            $query .= ' AND strftime("%Y", date) = :year';
            $params['year'] = $year;
        } 

        $query .= ' ORDER BY date DESC LIMIT :limit OFFSET :from';
        
        $params['from'] = $from;
        $params['limit'] = $limit;
        
        $statement = $this->pdo->prepare($query);
        $statement->execute($params);

        $expenses = [];
        while ($data = $statement->fetch()) {
            $expenses[] = $this->createExpenseFromData($data);
        }
        error_log("Found " . count($expenses) . " expenses for user_id: $userId, year: $year, month: $month");
        return $expenses;
    }


    public function countBy(array $criteria): int
    {
        $userId = $criteria['user_id'] ?? null;
        $year = isset($criteria['year']) ? (string)$criteria['year'] : null;
        $month = isset($criteria['month']) ? str_pad((string)$criteria['month'], 2, '0', STR_PAD_LEFT) : null;


        $query = 'SELECT COUNT(id) FROM expenses WHERE user_id = :user_id';
        $params = ['user_id' => $userId];

        if ($year && $month) {
            $query .= ' AND strftime("%Y", date) = :year AND strftime("%m", date) = :month';
            $params['year'] = $year;
            $params['month'] = $month;
        } elseif ($year) {
            $query .= ' AND strftime("%Y", date) = :year';
            $params['year'] = $year;
        }

        $statement = $this->pdo->prepare($query);
        $statement->execute($params);
        $result = $statement->fetch();
        $count = $result ? (int)$result[0] : 0;

        return $count;
    }

    public function listExpenditureYears(User $user): array
    {
        // TODO: Implement listExpenditureYears() method.
        return [];
    }

    public function sumAmountsByCategory(array $criteria): array
    {
        // TODO: Implement sumAmountsByCategory() method.
        return [];
    }

    public function averageAmountsByCategory(array $criteria): array
    {
        // TODO: Implement averageAmountsByCategory() method.
        return [];
    }

    public function sumAmounts(array $criteria): float
    {
        // TODO: Implement sumAmounts() method.
        return 0;
    }

    /**
     * @throws Exception
     */
    private function createExpenseFromData(mixed $data): Expense
    {
        return new Expense(
            $data['id'],
            $data['user_id'],
            new DateTimeImmutable($data['date']),
            $data['category'],
            $data['amount_cents'],
            $data['description'],
        );
    }
}
