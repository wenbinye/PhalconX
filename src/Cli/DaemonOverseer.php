<?php
namespace PhalconX\Cli;

class DaemonOverseer
{
    private $cwd;
    private $starter;
    private $daemons;
    private $pidfile;
    private $config;

    private $isExiting;
    private $isShutdown;
    private $err;

    public function __construct($options)
    {
        if (!extension_loaded('posix')) {
            throw new \RuntimeException("extension posix is required");
        }
        $this->pidfile = $options['pidfile'];
        if (isset($options['daemons'])) {
            $this->daemons = $options['daemons'];
            $this->cwd = $options['cwd'];
            $this->starter = $options['starter'];
        }
    }

    private function readPidfile()
    {
        if (!file_exists($this->pidfile)) {
            return;
        }
        $config = json_decode(file_get_contents($this->pidfile));
        if (!isset($config->pid) || !isset($config->daemons)) {
            return;
        }
        $this->config = $config;
    }

    private function isRunning()
    {
        if ($this->config) {
            return posix_kill($this->config->pid, 0);
        }
        return false;
    }
    
    public function start()
    {
        $this->readPidfile();
        if ($this->isRunning()) {
            $this->logger->error("Daemon is already running, pid=" . $this->config->pid);
            return;
        }
        // daemonize
        fclose(STDOUT);
        fclose(STDERR);
        ob_start();

        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new Exception(pht('Unable to fork!'));
        } else if ($pid) {
            exit(0);
        }

        declare(ticks = 1);
        pcntl_signal(SIGUSR2, array($this, 'didReceiveNotifySignal'));
        pcntl_signal(SIGHUP,  array($this, 'didReceiveReloadSignal'));
        pcntl_signal(SIGINT,  array($this, 'didReceiveGracefulSignal'));
        pcntl_signal(SIGTERM, array($this, 'didReceiveTerminalSignal'));

        $this->startDaemons();
    }

    public function startDaemons()
    {
        foreach ($this->daemons as $config) {
            
        }
        while (true) {
            foreach ($this->daemonHandlers as $daemon) {
                $daemon->update();
                if ($daemon->isRunning()) {
                }
                if ($daemon->isDone()) {
                    $this->removeDaemon($daemon);
                }
            }
            $this->updatePidfile();
            if ($this->isExiting) {
                break;
            }
            sleep(1);
        }
        exit($this->err);
    }

    public function didReceiveNotifySignal($signo) {
        foreach ($this->getDaemonHandles() as $daemon) {
            $daemon->didReceiveNotifySignal($signo);
        }
    }

    public function didReceiveReloadSignal($signo) {
        foreach ($this->getDaemonHandles() as $daemon) {
            $daemon->didReceiveReloadSignal($signo);
        }
    }

    public function didReceiveGracefulSignal($signo) {
        // If we receive SIGINT more than once, interpret it like SIGTERM.
        if ($this->isExiting) {
            return $this->didReceiveTerminalSignal($signo);
        }
        $this->isExiting = true;
        foreach ($this->getDaemonHandles() as $daemon) {
            $daemon->didReceiveGracefulSignal($signo);
        }
    }

    public function didReceiveTerminalSignal($signo) {
        $this->err = 128 + $signo;
        if ($this->isShutdown) {
            exit($this->err);
        }
        $this->isShutdown = true;
        foreach ($this->getDaemonHandles() as $daemon) {
            $daemon->didReceiveTerminalSignal($signo);
        }
    }
}
