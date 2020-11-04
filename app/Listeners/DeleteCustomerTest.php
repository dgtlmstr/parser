<?php

namespace App\Listeners;

use App\Events\DeleteCustomer;
use App\Services\ReportManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Test Listener.
 *
 * @package App\Listeners
 */
class DeleteCustomerTest
{
    /**
     * @var ReportManager
     */
    private $reporter;

    /**
     * Create the event listener.
     *
     * @param ReportManager $reporter
     */
    public function __construct(ReportManager $reporter)
    {
        $this->reporter = $reporter;
    }

    /**
     * Handle the event.
     *
     * @param  DeleteCustomer  $event
     * @return void
     */
    public function handle(DeleteCustomer $event)
    {
        $this->reporter->line(ReportManager::REPORT_STATUS_INFO, "Customer deleted");
    }
}
