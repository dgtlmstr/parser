<?php

namespace App\Repositories;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class CustomerRepository
{
    /**
     * The Customer entity instance.
     *
     * @var Customer
     */
    protected $customer;

    /**
     * Create an instance of Customer Repository.
     *
     * @param Customer $customer
     */
    public function __construct(Customer $customer){
        $this->customer = $customer;
    }

    /**
     * Soft delete row by id.
     *
     * @param $id
     */
    public function deleteRow($id)
    {
        DB::table($this->customer->getTable())
            ->where('id', $id)
            ->update(['deleted_at' => DB::raw('NOW()')]);
    }
}
