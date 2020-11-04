<?php

namespace App\Listeners;

use App\Events\DeleteCustomer;
use App\Services\ReportManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ReportDeleteCustomer
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
        //todo: report $event->customer deleted
        $this->reporter->report(ReportManager::REPORT_STATUS_INFO, "Customer deleted");
    }
}
