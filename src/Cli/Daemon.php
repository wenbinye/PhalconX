<?php
namespace PhalconX\Cli;

use PhalconX\Util;

/**
 *   while (!$this->shouldExit()) {
 *     if (work_available()) {
 *       $this->willBeginWork();
 *       do_work();
 *       $this->sleep(0);
 *     } else {
 *       $this->willBeginIdle();
 *       $this->sleep(1);
 *     }
 *   }
 *
 */
abstract class Daemon
{
    const MESSAGETYPE_STDOUT    = 'stdout';
    const MESSAGETYPE_HEARTBEAT = 'heartbeat';
    const MESSAGETYPE_BUSY      = 'busy';
    const MESSAGETYPE_IDLE      = 'idle';
    const MESSAGETYPE_DOWN      = 'down';

    const WORKSTATE_BUSY = 'busy';
    const WORKSTATE_IDLE = 'idle';

    protected $logger;

    /**
     * 是否收到信号 USR2
     */
    private $notifyReceived;
    /**
     * 是否收到信号 INT
     */
    private $isExiting;
    /**
     * 状态 BUSY | IDLE
     */
    private $workState;
    private $idleSince;

    /**
     * 是否自动创建进程
     */
    private $isAutoscaleDaemon;
    /**
     * 关闭自动创建进程时间
     */
    private $autoscaleDownDuration;
    
    private static $sighandlerInstalled;

    public function __construct($options = null)
    {
        $this->logger = Util::service('logger', $options);
        if (isset($options['isAutoscaleDaemon'])) {
            $this->isAutoscaleDaemon = $options['isAutoscaleDaemon'];
            $this->autoscaleDownDuration = $options['autoscaleDownDuration'];
        }
        declare(ticks = 1);
        if (!self::$sighandlerInstalled) {
            self::$sighandlerInstalled = true;
            pcntl_signal(SIGTERM, __CLASS__.'::exitOnSignal');
        }

        pcntl_signal(SIGINT, array($this, 'onGracefulSignal'));
        pcntl_signal(SIGUSR2, array($this, 'onNotifySignal'));
        $this->beginStdoutCapture();
    }

    abstract public function run();

    public function onGracefulSignal($signo)
    {
        $this->isExiting = true;
    }

    public function onNotifySignal($signo)
    {
        $this->notifyReceived = true;
        $this->onNotify($signo);
    }

    protected function onNotify($signo)
    {
        // This is a hook for subclasses.
    }

    protected function willSleep($duration)
    {
    }

    /**
     * Prepare to become busy. This may autoscale the pool up.
     *
     * This notifies the overseer that the daemon has become busy. If daemons
     * that are part of an autoscale pool are continuously busy for a prolonged
     * period of time, the overseer may scale up the pool.
     *
     * @return this
     * @task autoscale
     */
    protected function willBeginWork()
    {
        if ($this->workState != self::WORKSTATE_BUSY) {
            $this->workState = self::WORKSTATE_BUSY;
            $this->idleSince = null;
            $this->sendOverseerMessage(self::MESSAGETYPE_BUSY, null);
        }
        return $this;
    }


    /**
     * Prepare to idle. This may autoscale the pool down.
     *
     * This notifies the overseer that the daemon is no longer busy. If daemons
     * that are part of an autoscale pool are idle for a prolonged period of time,
     * they may exit to scale the pool down.
     *
     * @return this
     * @task autoscale
     */
    protected function willBeginIdle()
    {
        if ($this->workState != self::WORKSTATE_IDLE) {
            $this->workState = self::WORKSTATE_IDLE;
            $this->idleSince = time();
            $this->sendOverseerMessage(self::MESSAGETYPE_IDLE, null);
        }
        return $this;
    }
    
    public function shouldExit()
    {
        return $this->isExiting;
    }

    protected function sleep($duration)
    {
        $this->notifyReceived = false;
        $this->willSleep($duration);
        $this->stillWorking();

        $max_sleep = 60;
        if ($this->isAutoscaleDaemon) {
            $max_sleep = min($max_sleep, $this->autoscaleDownDuration);
        }

        while ($duration > 0 &&
               !$this->notifyReceived &&
               !$this->shouldExit()) {
            // If this is an autoscaling clone and we've been idle for too long,
            // we're going to scale the pool down by exiting and not restarting. The
            // DOWN message tells the overseer that we don't want to be restarted.
            if ($this->isAutoscaleDaemon) {
                if ($this->workState == self::WORKSTATE_IDLE) {
                    if ($this->idleSince && ($this->idleSince + $this->autoscaleDownDuration < time())) {
                        $this->isExiting = true;
                        $this->sendOverseerMessage(self::MESSAGETYPE_DOWN);
                        $this->logger->info(sprintf(
                            'Daemon was idle for more than %s seconds, scaling pool down.',
                            $this->autoscaleDownDuration
                        ));
                        break;
                    }
                }
            }

            sleep(min($duration, $max_sleep));
            $duration -= $max_sleep;
            $this->stillWorking();
        }
    }

    public function stillWorking()
    {
        $this->sendOverseerMessage(self::MESSAGETYPE_HEARTBEAT, null);
        if ($this->logger) {
            $memuse = number_format(memory_get_usage() / 1024, 1);
            $daemon = get_class($this);
            $this->logger->debug(sprintf(
                "<RAMS> %s Memory Usage: %d KB",
                $daemon,
                $memuse
            ));
        }
    }

    public static function exitOnSignal($signo)
    {
        // Normally, PHP doesn't invoke destructors when existing in response to
        // a signal. This forces it to do so, so we have a fighting chance of
        // releasing any locks, leases or resources on our way out.
        exit(128 + $signo);
    }

    private function beginStdoutCapture()
    {
        ob_start(array($this, 'didReceiveStdout'), 2);
    }

    private function endStdoutCapture()
    {
        ob_end_flush();
    }

    private function didReceiveStdout($msg)
    {
        if (!strlen($msg)) {
            return '';
        }
        return $this->encodeOverseerMessage(self::MESSAGETYPE_STDOUT, $msg);
    }

    private function encodeOverseerMessage($type, $data = null)
    {
        return json_encode(isset($data) ? [$type] : [$type, $data])."\n";
    }
    
    private function sendOverseerMessage($type, $data = null)
    {
        $this->endStdoutCapture();
        echo $this->encodeOverseerMessage($type, $data);
        $this->beginStdoutCapture();
    }
}
