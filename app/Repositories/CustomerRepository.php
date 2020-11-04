<?php

namespace App\Repositories;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;

/**
 * Repository to work with customers.
 *
 * @package App\Repositories
 */
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
     * @return Customer
     */
    public function deleteRow($id)
    {
        $customer = Customer::find($id);
        $customer->delete();
        return $customer;
    }
}
