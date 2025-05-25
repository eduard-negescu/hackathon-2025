<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use DateTimeImmutable;
use Psr\Http\Message\UploadedFileInterface;
use Webmozart\Assert\Assert;

class ExpenseService
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenses,
    ) {}

    public function list(int $userId, int $year, int $month, int $pageNumber, int $pageSize): array
    {
        $offset = ($pageNumber - 1) * $pageSize;

        $criteria = [
            'user_id' => $userId,
            'year' => $year,
            'month' => $month,
        ];

        $expenses = $this->expenses->findBy($criteria, $offset, $pageSize);
        $total = $this->expenses->countBy($criteria);

        return [
            'expenses' => $expenses,
            'total' => $total
        ];
    }

    public function find(int $id): ?Expense
    {
        return $this->expenses->find($id);
    }

    public function create(
        int $userId,
        int $amountCents,
        string $description,
        DateTimeImmutable $date,
        string $category,
    ): void {
        Assert::greaterThan($amountCents, 0, 'Amount must be greater than zero.');
        Assert::notEmpty($description, 'Description cannot be empty.');
        Assert::notEmpty($category, 'Category cannot be empty.');
        Assert::notNull($date, 'Date cannot be null.');
        Assert::lessThanEq($date, new DateTimeImmutable(), 'Date cannot be in the future.');

        $expense = new Expense(null, $userId, $date, $category, $amountCents, $description);
        $this->expenses->save($expense);
    }

    public function update(
        Expense $expense,
        float $amount,
        string $description,
        DateTimeImmutable $date,
        string $category,
    ): void {
        Assert::greaterThan($amountCents, 0, 'Amount must be greater than zero.');
        Assert::notEmpty($description, 'Description cannot be empty.');
        Assert::notEmpty($category, 'Category cannot be empty.');
        Assert::notNull($date, 'Date cannot be null.');
        Assert::lessThanEq($date, new DateTimeImmutable(), 'Date cannot be in the future.');
        
        $expense->amountCents = (int)round($amount * 100);
        $expense->description = $description;
        $expense->date = $date;
        $expense->category = $category;

        $this->expenses->update($expense);
    }

    public function delete(int $id): void
    {
        $this->expenses->delete($id);
    }

    public function importFromCsv(User $user, UploadedFileInterface $csvFile): int
    {
        // TODO: process rows in file stream, create and persist entities
        // TODO: for extra points wrap the whole import in a transaction and rollback only in case writing to DB fails

        return 0; // number of imported rows
    }
}
