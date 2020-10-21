<?php

namespace App\Repositories;

use App\Models\Customer;

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

}
