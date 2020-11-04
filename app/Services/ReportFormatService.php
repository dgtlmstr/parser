<?php
namespace App\Services;

use App\Models\Customer;

/**
 * Class formatting service messages.
 *
 * @package App\Services
 */
class ReportFormatService
{
    /**
     * Generate message line for deleted customer.
     *
     * @param Customer $customer
     * @return string
     */
    public function customerDeleted(Customer $customer) : string {
        return "Deleted customer: " . $this->getCustomerInfoLine($customer);
    }

    /**
     * Return message line for customer delete error.
     *
     * @param Customer $customer
     * @return string
     */
    public function customerDeleteError(Customer $customer) : string {
        return "Error deleting customer: " . $this->getCustomerInfoLine($customer);
    }

    /**
     * Return string line for customer specified.
     *
     * @param Customer $customer
     * @return string
     */
    protected function getCustomerInfoLine(Customer $customer): string {
        return "[{$customer->id}] {$customer->first_name} {$customer->last_name}, {$customer->card_number}";
    }
}
