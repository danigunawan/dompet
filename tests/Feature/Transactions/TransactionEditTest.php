<?php

namespace Tests\Feature\Transactions;

use App\Category;
use Tests\TestCase;
use App\Transaction;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class TransactionEditTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function user_can_edit_a_transaction_within_month_and_year_query()
    {
        $month = '01';
        $year = '2017';
        $date = '2017-01-01';
        $user = $this->loginAsUser();
        $transaction = factory(Transaction::class)->create([
            'in_out'     => 0,
            'amount'     => 99.99,
            'date'       => $date,
            'creator_id' => $user->id,
        ]);
        $category = factory(Category::class)->create(['creator_id' => $user->id]);

        $this->visitRoute('transactions.index', ['month' => $month, 'year' => $year]);
        $this->click('edit-transaction-'.$transaction->id);
        $this->seeRouteIs('transactions.index', [
            'action' => 'edit', 'id'   => $transaction->id,
            'month'  => $month, 'year' => $year,
        ]);

        $this->submitForm(__('transaction.update'), [
            'in_out'      => 1,
            'amount'      => 99.99,
            'date'        => $date,
            'description' => 'Transaction 1 description',
            'category_id' => $category->id,
        ]);

        $this->seeRouteIs('transactions.index', ['month' => $transaction->month, 'year' => $transaction->year]);
        $this->see(__('transaction.updated'));

        $this->seeInDatabase('transactions', [
            'amount'      => 99.99,
            'date'        => $date,
            'description' => 'Transaction 1 description',
            'category_id' => $category->id,
        ]);
    }

    /** @test */
    public function user_can_edit_a_transaction_within_search_and_category_query()
    {
        $month = '01';
        $year = '2017';
        $date = '2017-01-01';
        $user = $this->loginAsUser();
        $category = factory(Category::class)->create(['creator_id' => $user->id]);
        $transaction = factory(Transaction::class)->create([
            'in_out'      => 0,
            'amount'      => 99.99,
            'date'        => $date,
            'creator_id'  => $user->id,
            'category_id' => $category->id,
            'description' => 'Transaction Unique Description',
        ]);

        $this->visitRoute('transactions.index', ['month' => $month, 'year' => $year, 'query' => 'Unique', 'category_id' => $category->id]);
        $this->click('edit-transaction-'.$transaction->id);
        $this->seeRouteIs('transactions.index', [
            'action'      => 'edit',
            'category_id' => $category->id,
            'id'          => $transaction->id,
            'month'       => $month,
            'query'       => 'Unique',
            'year'        => $year,
        ]);

        $this->submitForm(__('transaction.update'), [
            'in_out'      => 1,
            'amount'      => 99.99,
            'date'        => $date,
            'description' => 'Transaction 1 description',
            'category_id' => $category->id,
        ]);

        $this->seeRouteIs('transactions.index', [
            'category_id' => $category->id,
            'month'       => $transaction->month,
            'query'       => 'Unique',
            'year'        => $transaction->year,
        ]);
    }

    /** @test */
    public function user_can_delete_a_transaction()
    {
        $user = $this->loginAsUser();
        $transaction = factory(Transaction::class)->create(['creator_id' => $user->id]);

        $this->visitRoute('transactions.index', ['action' => 'edit', 'id' => $transaction->id]);
        $this->click('del-transaction-'.$transaction->id);
        $this->seeRouteIs('transactions.index', ['action' => 'delete', 'id' => $transaction->id]);

        $this->seeInDatabase('transactions', [
            'id' => $transaction->id,
        ]);

        $this->press(__('app.delete_confirm_button'));

        $this->seeRouteIs('transactions.index', ['month' => $transaction->month, 'year' => $transaction->year]);
        $this->see(__('transaction.deleted'));

        $this->dontSeeInDatabase('transactions', [
            'id' => $transaction->id,
        ]);
    }
}
