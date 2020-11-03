<?php

namespace App\Listeners;

use App\Events\DeleteCustomer;
use App\Services\Reporter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ReportDeleteCustomer
{
    /**
     * @var Reporter
     */
    private $reporter;

    /**
     * Create the event listener.
     *
     * @param Reporter $reporter
     */
    public function __construct(Reporter $reporter)
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
        //todo: report $event->customer deleted
        $this->reporter->report(Reporter::REPORT_STATUS_INFO, "Customer deleted");
    }
}
