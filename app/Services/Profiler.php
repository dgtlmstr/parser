<?php


namespace App\Services;

/**
 * Class helps to measure performance.
 *
 * @package App\Services
 */
class Profiler
{
    /**
     * Keeps start time
     * @var float
     */
    protected $startTime = null;

    protected $peakMemory = 0;

    /**
     * Profiler constructor.
     * @param $timer
     */
    public function __construct()
    {
        $this->startTimer();
        $this->fixCurrentPeakMemory();
    }

    /**
     * Return current time with microseconds.
     *
     * @return float|string
     */
    public function getMicrotime() {
        return microtime(true);
    }

    /**
     *  Set start time.
     */
    public function startTimer() {
        $this->startTime = $this->getMicrotime();
    }

    /**
     * Reset start time.
     */
    public function resetTimer() {
        $this->startTime = $this->getMicrotime();
    }

    /**
     * Return current time interval.
     *
     * @return float|string|null
     */
    public function getCurrentMeasureTime() {
        if (empty($this->startTime)) return null;

        $time = $this->getMicrotime();
        return $time - $this->startTime;
    }

    /**
     * Return current peak memory usage.
     *
     * @return int
     */
    public function getPeakMemory() {
        return memory_get_peak_usage(true);
    }

    /**
     * Set peak memory.
     */
    public function fixCurrentPeakMemory() {
        $this->peakMemory = $this->getPeakMemory();
    }

    /**
     * Return current peak memory difference.
     *
     * @return int
     */
    public function getCurrentMeasurePeakMemory() {
        $peakMemory = $this->getPeakMemory();
        return $peakMemory - $this->peakMemory;
    }
}
