<?php
namespace PhalconX\Cli;

class TestDaemon extends Daemon
{
    public function run()
    {
        while(!$this->shouldExit()) {
            if (time() % 2 == 0) {
                $this->willBeginWork();
                $this->logger->info("do the work");
                $this->sleep(1);
            } else {
                $this->willBeginIdle();
                $this->logger->info("wait for job");
                $this->sleep(1);
            }
        }
    }
}
