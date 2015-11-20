<?php
namespace PhalconX\Console;

/**
 * 使用 pid 文件确保单实例进程
 *
 * 使用示例：
 * <code>
 *   $pidfile = new Pidfile(sys_get_temp_dir(), $procName);
 *   if ( $pidfile->isAlreadyRunning() ) {
 *       echo "$procName is running.\n";
 *       exit;
 *   }
 * </code>
 */
class Pidfile
{
    private $file;
    private $isRunning;
    private $runningPid;
    private $pid;

    /**
     * 创建 pid 文件
     * @param string $dir Pid 文件目录，必须确保进程有权限创建文件
     * @param string $name Pid 文件名。默认为当前进程 php 文件名
     */
    public function __construct($dir, $name = null)
    {
        if (!extension_loaded('posix')) {
            throw new \RuntimeException("extension posix is required");
        }
        if (null === $name) {
            $name = basename($_SERVER['PHP_SELF']);
        }
        $this->file = "$dir/$name.pid";
        if (file_exists($this->file)) {
            $pid = trim(file_get_contents($this->file));
            if (posix_kill($pid, 0)) {
                $this->isRunning = true;
                $this->runningPid = $pid;
            }
        }

        if (!$this->isRunning) {
            $pid = getmypid();
            if (false === file_put_contents($this->file, $pid)) {
                throw new \RuntimeException(
                    "Cannot write to pid file '{$this->file}'"
                );
            }
            $this->pid = $pid;
        }
    }

    public function __destruct()
    {
        if ((!$this->isRunning) && file_exists($this->file)) {
            unlink($this->file);
        }
    }

    /**
     * @return bool 是否已经存在其它运行的进程实例
     */
    public function isAlreadyRunning()
    {
        return $this->isRunning;
    }

    /**
     * @return int|null 其它运行的进程实例 pid
     */
    public function getRunningPid()
    {
        return $this->runningPid;
    }

    /**
     * @return int 当前进程实例 pid
     */
    public function getPid()
    {
        return $this->pid;
    }
}
