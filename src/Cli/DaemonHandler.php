<?php
namespace PhalconX\Cli;

class DaemonHandler
{
    const MAX_HEARTBEAT_INTERVAL = 86400;

    private $cmd;
    private $cwd;
        
    private $pid;
    private $proc;
    
    private $shouldRestart;
    private $shouldShutdown;

    public function __construct($options)
    {
        
    }

    public function isRunning()
    {
        
    }

    /**
     * Execute `proc_get_status()`, but avoid pitfalls.
     *
     * @return dict Process status.
     * @task internal
     */
    private function getProcStatus() {
        // After the process exits, we only get one chance to read proc_get_status()
        // before it starts returning garbage. Make sure we don't throw away the
        // last good read.
        if ($this->procStatus) {
            if (!$this->procStatus['running']) {
                return $this->procStatus;
            }
        }
        $this->procStatus = proc_get_status($this->proc);
        return $this->procStatus;
    }
    
    public function update () {
        if (!$this->isRunning()) {
            if (!$this->shouldRestart) {
                return;
            }
            if (!$this->restartAt || (time() < $this->restartAt)) {
                return;
            }
            if ($this->shouldShutdown) {
                return;
            }
            $this->startDaemonProcess();
        }

        $future = $this->future;

        $result = null;
        if ($future->isReady()) {
            $result = $future->resolve();
        }

        list($stdout, $stderr) = $future->read();
        $future->discardBuffers();

        if (strlen($stdout)) {
            $this->didReadStdout($stdout);
        }

        $stderr = trim($stderr);
        if (strlen($stderr)) {
            $this->logMessage('STDE', $stderr);
        }

        if ($result !== null) {
            list($err) = $result;
            if ($err) {
                $this->logMessage('FAIL', pht('Process exited with error %s', $err));
            } else {
                $this->logMessage('DONE', pht('Process exited normally.'));
            }

            $this->future = null;

            if ($this->shouldShutdown) {
                $this->restartAt = null;
                $this->dispatchEvent(self::EVENT_WILL_EXIT);
            } else {
                $this->scheduleRestart();
            }
        }

        $this->updateHeartbeatEvent();
        $this->updateHangDetection();
    }
    
    private function startDaemonProcess() {
        $this->logger->info('Starting process.');
        $this->deadline = time() + self::MAX_HEARTBEAT_INTERVAL;
        $this->heartbeat = time() + $this->getHeartBeatInterval();
        $this->stdoutBuffer = '';

        $spec = array(
            0 => array('pipe', 'r'),  // stdin
            1 => array('pipe', 'w'),  // stdout
            2 => array('pipe', 'w'),  // stderr
        );
        $proc = proc_open($this->cmd, $spec, $pipes, $this->cwd);
        if (!is_resource($proc)) {
            throw new \RuntimeException("Failed to proc_open(): " . error_get_last());
        }
        $this->proc = $proc;
        $this->pipes = $pipes;
        $procStatus = $this->getProcStatus();
        $this->pid = $procStatus['pid'];
    }

    public function didReceiveNotifySignal($signo) {
        $pid = $this->pid;
        if ($pid) {
            posix_kill($pid, $signo);
        }
    }

    public function didReceiveReloadSignal($signo) {
        $signame = Daemon::sigName($signo);
        if ($signame) {
            $sigmsg = sprintf('Reloading in response to signal %d (%s).', $signo, $signame);
        } else {
            $sigmsg = sprintf('Reloading in response to signal %d.', $signo);
        }
        // This signal means "stop the current process gracefully, then launch
        // a new identical process once it exits". This can be used to update
        // daemons after code changes (the new processes will run the new code)
        // without aborting any running tasks.

        // We SIGINT the daemon but don't set the shutdown flag, so it will
        // naturally be restarted after it exits, as though it had exited after an
        // unhandled exception.
        $pid = $this->pid;
        exec("kill -INT {$pid}");
    }

    public function didReceiveGracefulSignal($signo) {
        $this->shouldShutdown = true;
        if (!$this->isRunning()) {
            return;
        }
        $signame = Daemon::sigName($signo);
        if ($signame) {
            $sigmsg = sprintf('Graceful shutdown in response to signal %d (%s).', $signo, $signame);
        } else {
            $sigmsg = sprintf('Graceful shutdown in response to signal %d.', $signo);
        }
        $pid = $this->pid;
        exec("kill -INT {$pid}");
    }

    public function didReceiveTerminalSignal($signo) {
        $signame = Daemon::sigName($signo);
        if ($signame) {
            $sigmsg = sprintf('Shutting down in response to signal %s (%s).', $signo, $signame);
        } else {
            $sigmsg = sprintf('Shutting down in response to signal %s.', $signo);
        }
        $pid = $this->pid;
        $pgid = posix_getpgid($pid);
        if ($pid && $pgid) {

            // NOTE: On Ubuntu, 'kill' does not recognize the use of "--" to
            // explicitly delineate PID/PGIDs from signals. We don't actually need it,
            // so use the implicit "kill -TERM -pgid" form instead of the explicit
            // "kill -TERM -- -pgid" form.
            exec("kill -TERM -{$pgid}");
            sleep($this->getKillDelay());

            // On OSX, we'll get a permission error on stderr if the SIGTERM was
            // successful in ending the life of the process group, presumably because
            // all that's left is the daemon itself as a zombie waiting for us to
            // reap it. However, we still need to issue this command for process
            // groups that resist SIGTERM. Rather than trying to figure out if the
            // process group is still around or not, just SIGKILL unconditionally and
            // ignore any error which may be raised.
            exec("kill -KILL -{$pgid} 2>/dev/null");
            $this->pid = null;
        }
    }
}
