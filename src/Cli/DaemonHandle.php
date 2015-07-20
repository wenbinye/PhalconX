<?php
namespace PhalconX\Cli;

use PhalconX\Util;

class DaemonHandle
{
    const MAX_HEARTBEAT_INTERVAL = 86400;
    const RESTART_INTERVAL = 5;
    const KILL_INTERVAL = 3;

    private $id;

    private $cmd;
    private $cwd;
    private $argv;
        
    private $overseer;
    private $pid;
    private $future;
    private $shouldRestart;
    private $shouldShutdown;
    private $logger;

    private $busy;

    public function __construct($options)
    {
        $this->overseer = $options['overseer'];
        $this->cmd = $options['cmd'];
        $this->cwd = $options['cwd'];
        $this->argv = $options['argv'];

        $this->restartAt = time();
        $this->shouldRestart = true;
        $this->id = Util::uuid();
        $this->logger = Util::service('logger', $options);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getArgv()
    {
        return $this->argv;
    }
    
    public function isRunning()
    {
        return (bool)$this->future;
    }

    public function isDone()
    {
        return (!$this->shouldRestart && !$this->isRunning());
    }

    public function isBusy()
    {
        return isset($this->busy) && $this->busy > 0;
    }

    public function setBusy($isBusy)
    {
        return $this->busy = $isBusy ? time() : null;
    }

    public function getBusy()
    {
        return $this->busy;
    }
    
    public function getFuture()
    {
        return $this->future;
    }

    public function getPid()
    {
        return $this->pid;
    }
    
    public function update()
    {
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
            $this->logger->error($stderr);
        }

        if ($result !== null) {
            list($err) = $result;
            if ($err) {
                $this->logger->error('[FAIL] Process exited with error: ' . $err);
            } else {
                $this->logger->info('[DONE] Process exited normally.');
            }

            $this->future = null;

            if ($this->shouldShutdown) {
                $this->restartAt = null;
            } else {
                $this->scheduleRestart();
            }
        }

        $this->updateHeartbeatEvent();
        $this->updateHangDetection();
    }

    private function scheduleRestart()
    {
        $this->logger->info('[WAIT] Waiting to restart process.');
        $this->restartAt = time() + self::RESTART_INTERVAL;
    }

    private function updateHeartbeatEvent()
    {
        if ($this->heartbeat > time()) {
            return;
        }

        $this->heartbeat = time() + $this->getHeartBeatInterval();
    }

    private function updateHangDetection()
    {
        if (!$this->isRunning()) {
            return;
        }

        if (time() > $this->deadline) {
            $this->logger->error('[HANG] Hang detected. Restarting process.');
            $this->killProcessGroup();
            $this->scheduleRestart();
        }
    }

    public static function getHeartBeatInterval()
    {
        return 120;
    }

    private function getCaptureBufferSize()
    {
        return 65535;
    }

    private function startDaemonProcess()
    {
        $this->logger->info('Starting process.');
        $this->deadline = time() + self::MAX_HEARTBEAT_INTERVAL;
        $this->heartbeat = time() + $this->getHeartBeatInterval();
        $this->stdoutBuffer = '';

        $this->future = $this->newExecFuture();
        $this->future->start();

        $this->pid = $this->future->getPID();
    }

    private function newExecFuture()
    {
        $buffer_size = $this->getCaptureBufferSize();

        // NOTE: PHP implements proc_open() by running 'sh -c'. On most systems this
        // is bash, but on Ubuntu it's dash. When you proc_open() using bash, you
        // get one new process (the command you ran). When you proc_open() using
        // dash, you get two new processes: the command you ran and a parent
        // "dash -c" (or "sh -c") process. This means that the child process's PID
        // is actually the 'dash' PID, not the command's PID. To avoid this, use
        // 'exec' to replace the shell process with the real process; without this,
        // the child will call posix_getppid(), be given the pid of the 'sh -c'
        // process, and send it SIGUSR1 to keepalive which will terminate it
        // immediately. We also won't be able to do process group management because
        // the shell process won't properly posix_setsid() so the pgid of the child
        // won't be meaningful.
        $future = new ExecFuture('exec ' . $this->cmd);
        return $future->setCWD($this->cwd)
            ->setStdoutSizeLimit($buffer_size)
            ->setStderrSizeLimit($buffer_size)
            ->write(json_encode($this->argv));
    }

    private function didReadStdout($data)
    {
        $this->stdoutBuffer .= $data;
        while (true) {
            $pos = strpos($this->stdoutBuffer, "\n");
            if ($pos === false) {
                break;
            }
            $message = substr($this->stdoutBuffer, 0, $pos);
            $this->stdoutBuffer = substr($this->stdoutBuffer, $pos + 1);

            $structure = json_decode($message);
            if ($structure === false || !is_array($structure)) {
                $this->logger->error("[STDOUT] Malformed stdout message: $message");
                break;
            }

            switch ($structure[0]) {
                case Daemon::MESSAGETYPE_STDOUT:
                    $this->logger->info('[STDOUT]' . $structure[1]);
                    break;
                case Daemon::MESSAGETYPE_HEARTBEAT:
                    $this->deadline = time() + self::MAX_HEARTBEAT_INTERVAL;
                    break;
                case Daemon::MESSAGETYPE_BUSY:
                    $this->overseer->didBeginWork($this);
                    break;
                case Daemon::MESSAGETYPE_IDLE:
                    $this->overseer->didBeginIdle($this);
                    break;
                case Daemon::MESSAGETYPE_DOWN:
                    // The daemon is exiting because it doesn't have enough work and it
                    // is trying to scale the pool down. We should not restart it.
                    $this->shouldRestart = false;
                    $this->shouldShutdown = true;
                    break;
                default:
                    // If we can't parse this or it isn't a message we understand, just
                    // emit the raw message.
                    $this->logger->info("[STDOUT] Malformed stdout message: $message");
                    break;
            }
        }
    }

    public function didReceiveNotifySignal($signo)
    {
        $pid = $this->pid;
        if ($pid) {
            posix_kill($pid, $signo);
        }
    }

    public function didReceiveReloadSignal($signo)
    {
        $signame = CliUtil::sigName($signo);
        if ($signame) {
            $sigmsg = sprintf('Reloading in response to signal %d (%s).', $signo, $signame);
        } else {
            $sigmsg = sprintf('Reloading in response to signal %d.', $signo);
        }
        $this->logger->info($sigmsg);
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

    public function didReceiveGracefulSignal($signo)
    {
        $this->shouldShutdown = true;
        if (!$this->isRunning()) {
            return;
        }
        $signame = CliUtil::sigName($signo);
        if ($signame) {
            $sigmsg = sprintf('Graceful shutdown in response to signal %d (%s).', $signo, $signame);
        } else {
            $sigmsg = sprintf('Graceful shutdown in response to signal %d.', $signo);
        }
        $this->logger->info($sigmsg);
        $pid = $this->pid;
        exec("kill -INT {$pid}");
    }

    public function didReceiveTerminalSignal($signo)
    {
        $signame = CliUtil::sigName($signo);
        if ($signame) {
            $sigmsg = sprintf('Shutting down in response to signal %s (%s).', $signo, $signame);
        } else {
            $sigmsg = sprintf('Shutting down in response to signal %s.', $signo);
        }
        $this->logger->info($sigmsg);
        $this->killProcessGroup();
    }

    private function killProcessGroup()
    {
        $pid = $this->pid;
        $pgid = posix_getpgid($pid);
        if ($pid && $pgid) {
            // NOTE: On Ubuntu, 'kill' does not recognize the use of "--" to
            // explicitly delineate PID/PGIDs from signals. We don't actually need it,
            // so use the implicit "kill -TERM -pgid" form instead of the explicit
            // "kill -TERM -- -pgid" form.
            exec("kill -TERM -{$pgid}");
            sleep(self::KILL_INTERVAL);

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
