<?php

namespace App\Listeners;

use App\Events\DeleteCustomer;
use App\Services\ReportService;
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
     * @var ReportService
     */
    private $reporter;

    /**
     * Create the event listener.
     *
     * @param ReportService $reporter
     */
    public function __construct(ReportService $reporter)
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
        $this->reporter->line(REPORT_STATUS_INFO, "Customer deleted test event listener caught");
    }
}
