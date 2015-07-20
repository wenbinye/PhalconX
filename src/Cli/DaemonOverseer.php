<?php
namespace PhalconX\Cli;

use PhalconX\Util;
use PhalconX\Exception;

class DaemonOverseer
{
    private $cwd;
    private $starter;
    private $daemons;

    private $start;
    private $pidfile;
    private $config;
    private $lastConfig;
    private $handles;

    private $isExiting;
    private $isShutdown;
    private $err;
    private $logger;

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
            $this->start = time();
        }
        $this->logger = Util::service('logger', $options);
    }

    private function readPidfile()
    {
        if (!file_exists($this->pidfile)) {
            return;
        }
        $config = json_decode(file_get_contents($this->pidfile));
        if (!isset($config->pid) || !isset($config->daemons)) {
            $this->config = null;
        } else {
            $this->config = $config;
        }
        return $this->config;
    }

    private function removePidfile()
    {
        unlink($this->pidfile);
    }
    
    private function getPid()
    {
        return isset($this->config->pid) ? $this->config->pid : null;
    }
    
    private function isRunning()
    {
        if (isset($this->config->pid)) {
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
        } elseif ($pid) {
            exit(0);
        }

        declare(ticks = 1);
        pcntl_signal(SIGUSR2, array($this, 'didReceiveNotifySignal'));
        pcntl_signal(SIGHUP, array($this, 'didReceiveReloadSignal'));
        pcntl_signal(SIGINT, array($this, 'didReceiveGracefulSignal'));
        pcntl_signal(SIGTERM, array($this, 'didReceiveTerminalSignal'));

        $this->startDaemons();
    }

    public function stop($signo = SIGINT, $timeout = 15)
    {
        $this->readPidfile();
        if (!$this->isRunning()) {
            throw new Exception("Process is not running");
        }
        posix_kill($this->getPid(), $signo);
        $start = time();
        while (time() - $start < $timeout) {
            if (!$this->isRunning()) {
                printf("Process %d exited.\n", $this->getPid());
                $this->removePidfile();
                return true;
            }
            usleep(100000);
        }
        return false;
    }

    public function status()
    {
        $this->readPidfile();
        if ($this->isRunning()) {
            return $this->config;
        }
    }

    public function reload()
    {
        $this->readPidfile();
        if (!$this->isRunning()) {
            throw new Exception("Process is not running");
        }
        posix_kill($this->getPid(), SIGHUP);
    }
    
    public function startDaemons()
    {
        foreach ($this->daemons as $argv) {
            $this->addHandle(new DaemonHandle([
                'overseer' => $this,
                'logger' => $this->logger,
                'cmd' => $this->starter,
                'cwd' => $this->cwd,
                'argv' => $argv
            ]));
        }
        while (true) {
            $futures = [];
            foreach ($this->getHandles() as $handle) {
                $handle->update();
                if ($handle->isRunning()) {
                    $futures[] = $handle->getFuture();
                }
                if ($handle->isDone()) {
                    $this->removeHandle($handle);
                }
            }
            $this->updatePidfile();
            $this->updateAutoscale();
            if ($futures) {
                $iter = new FutureIterator($futures);
                $iter->setUpdateInterval(1);
                foreach ($iter as $future) {
                    break;
                }
            } else {
                if ($this->isExiting) {
                    break;
                }
                sleep(1);
            }
        }
        exit($this->err);
    }

    private function addHandle($handle)
    {
        $this->handles[$handle->getId()] = $handle;
    }

    private function getHandles()
    {
        return $this->handles;
    }
    
    private function removeHandle($handle)
    {
        unset($this->handles[$handle->getId()]);
    }
    
    private function updatePidfile()
    {
        $daemons = array();
        foreach ($this->getHandles() as $handle) {
            if (!$handle->isRunning()) {
                continue;
            }

            $daemons[] = array(
                'pid' => $handle->getPid(),
                'id' => $handle->getId(),
                'argv' => $handle->getArgv(),
            );
        }

        $config = array(
            'pid' => getmypid(),
            'start' => $this->start,
            'config' => $this->daemons,
            'daemons' => $daemons,
        );

        if ($config !== $this->lastConfig) {
            $this->lastConfig = $config;
            file_put_contents($this->pidfile, json_encode($config));
        }
    }

    private function updateAutoscale()
    {
    }
    
    public function didReceiveNotifySignal($signo)
    {
        foreach ($this->getHandles() as $handle) {
            $handle->didReceiveNotifySignal($signo);
        }
    }

    public function didReceiveReloadSignal($signo)
    {
        foreach ($this->getHandles() as $handle) {
            $handle->didReceiveReloadSignal($signo);
        }
    }

    public function didReceiveGracefulSignal($signo)
    {
        // If we receive SIGINT more than once, interpret it like SIGTERM.
        if ($this->isExiting) {
            return $this->didReceiveTerminalSignal($signo);
        }
        $this->isExiting = true;
        foreach ($this->getHandles() as $handle) {
            $handle->didReceiveGracefulSignal($signo);
        }
    }

    public function didReceiveTerminalSignal($signo)
    {
        $this->err = 128 + $signo;
        if ($this->isShutdown) {
            exit($this->err);
        }
        $this->isShutdown = true;
        foreach ($this->getHandles() as $handle) {
            $handle->didReceiveTerminalSignal($signo);
        }
    }

    public function didBeginWork($handle)
    {
        if (!$handle->isBusy()) {
            $handle->setBusy(true);
        }
    }

    public function didBeginIdle($handle)
    {
        $handle->setBusy(false);
    }
}
