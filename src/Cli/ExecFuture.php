<?php
namespace PhalconX\Cli;

use PhalconX\Exception;
use PhalconX\Exception\CommandException;

/**
 * Execute system commands in parallel using futures.
 *
 * ExecFuture is a future, which means it runs asynchronously and represents
 * a value which may not exist yet. See @{article:Using Futures} for an
 * explanation of futures. When an ExecFuture resolves, it returns the exit
 * code, stdout and stderr of the process it executed.
 *
 * ExecFuture is the core command execution implementation in libphutil, but is
 * exposed through a number of APIs. See @{article:Command Execution} for more
 * discussion about executing system commands.
 *
 * @task create   Creating ExecFutures
 * @task resolve  Resolving Execution
 * @task config   Configuring Execution
 * @task info     Command Information
 * @task interact Interacting With Commands
 * @task internal Internals
 */
final class ExecFuture extends Future
{
    private $pipes        = array();
    private $proc         = null;
    private $start        = null;
    private $timeout      = null;
    private $procStatus   = null;

    private $stdout       = null;
    private $stderr       = null;
    private $stdin        = null;
    private $closePipe    = true;

    private $stdoutPos    = 0;
    private $stderrPos    = 0;
    private $command      = null;
    private $env          = null;
    private $cwd;

    private $readBufferSize;
    private $stdoutSizeLimit = PHP_INT_MAX;
    private $stderrSizeLimit = PHP_INT_MAX;

    private $profilerCallID;
    private $killedByTimeout;

    private $useWindowsFileStreams = false;
    private $windowsStdoutTempFile = null;
    private $windowsStderrTempFile = null;

    private static $descriptorSpec = [
        0 => array('pipe', 'r'),  // stdin
        1 => array('pipe', 'w'),  // stdout
        2 => array('pipe', 'w'),  // stderr
    ];

/* -(  Creating ExecFutures  )----------------------------------------------- */

    /**
     * Create a new ExecFuture.
     *
     *   $future = new ExecFuture('wc -l %s', $file_path);
     *
     * @param string  `sprintf()`-style command string which will be passed
     *                through @{function:csprintf} with the rest of the arguments.
     * @param ...     Zero or more additional arguments for @{function:csprintf}.
     * @return ExecFuture ExecFuture for running the specified command.
     * @task create
     */
    public function __construct($command)
    {
        $argv = func_get_args();
        $this->command = call_user_func_array('sprintf', $argv);
        $this->stdin = new Buffer();
    }

/* -(  Command Information  )------------------------------------------------ */

    /**
     * Retrieve the raw command to be executed.
     *
     * @return string Raw command.
     * @task info
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * Retrieve the byte limit for the stderr buffer.
     *
     * @return int Maximum buffer size, in bytes.
     * @task info
     */
    public function getStderrSizeLimit()
    {
        return $this->stderrSizeLimit;
    }


    /**
     * Retrieve the byte limit for the stdout buffer.
     *
     * @return int Maximum buffer size, in bytes.
     * @task info
     */
    public function getStdoutSizeLimit()
    {
        return $this->stdoutSizeLimit;
    }


    /**
     * Get the process's pid. This only works after execution is initiated, e.g.
     * by a call to start().
     *
     * @return int Process ID of the executing process.
     * @task info
     */
    public function getPID()
    {
        $status = $this->procGetStatus();
        return $status['pid'];
    }


/* -(  Configuring Execution  )---------------------------------------------- */


    /**
     * Set a maximum size for the stdout read buffer. To limit stderr, see
     * @{method:setStderrSizeLimit}. The major use of these methods is to use less
     * memory if you are running a command which sometimes produces huge volumes
     * of output that you don't really care about.
     *
     * NOTE: Setting this to 0 means "no buffer", not "unlimited buffer".
     *
     * @param int Maximum size of the stdout read buffer.
     * @return this
     * @task config
     */
    public function setStdoutSizeLimit($limit)
    {
        $this->stdoutSizeLimit = $limit;
        return $this;
    }


    /**
     * Set a maximum size for the stderr read buffer.
     * See @{method:setStdoutSizeLimit} for discussion.
     *
     * @param int Maximum size of the stderr read buffer.
     * @return this
     * @task config
     */
    public function setStderrSizeLimit($limit)
    {
        $this->stderrSizeLimit = $limit;
        return $this;
    }


    /**
     * Set the maximum internal read buffer size this future. The future will
     * block reads once the internal stdout or stderr buffer exceeds this size.
     *
     * NOTE: If you @{method:resolve} a future with a read buffer limit, you may
     * block forever!
     *
     * TODO: We should probably release the read buffer limit during
     * @{method:resolve}, or otherwise detect this. For now, be careful.
     *
     * @param int|null Maximum buffer size, or `null` for unlimited.
     * @return this
     */
    public function setReadBufferSize($read_buffer_size)
    {
        $this->readBufferSize = $read_buffer_size;
        return $this;
    }

    /**
     * Set the current working directory to use when executing the command.
     *
     * @param string Directory to set as CWD before executing the command.
     * @return this
     * @task config
     */
    public function setCWD($cwd)
    {
        $this->cwd = $cwd;
        return $this;
    }

    /**
     * Set the environment variables to use when executing the command.
     *
     * @param array Environment variables to use when executing the command.
     * @return this
     * @task config
     */
    public function setEnv($env, $wipe_process_env = false)
    {
        if ($wipe_process_env) {
            $this->env = $env;
        } else {
            $this->env = $env + $_ENV;
        }
        return $this;
    }

    /**
     * Set the value of a specific environmental variable for this command.
     *
     * @param string Environmental variable name.
     * @param string|null New value, or null to remove this variable.
     * @return this
     * @task config
     */
    public function updateEnv($key, $value)
    {
        if (!is_array($this->env)) {
            $this->env = $_ENV;
        }

        if ($value === null) {
            unset($this->env[$key]);
        } else {
            $this->env[$key] = $value;
        }

        return $this;
    }

    /**
     * Set whether to use non-blocking streams on Windows.
     *
     * @param bool Whether to use non-blocking streams.
     * @return this
     * @task config
     */
    public function setUseWindowsFileStreams($use_streams)
    {
        if (CliUtil::isWindows()) {
            $this->useWindowsFileStreams = $use_streams;
        }
        return $this;
    }

/* -(  Interacting With Commands  )------------------------------------------ */

    /**
     * Read and return output from stdout and stderr, if any is available. This
     * method keeps a read cursor on each stream, but the entire streams are
     * still returned when the future resolves. You can call read() again after
     * resolving the future to retrieve only the parts of the streams you did not
     * previously read:
     *
     *   $future = new ExecFuture('...');
     *   // ...
     *   list($stdout) = $future->read(); // Returns output so far
     *   list($stdout) = $future->read(); // Returns new output since first call
     *   // ...
     *   list($stdout) = $future->resolvex(); // Returns ALL output
     *   list($stdout) = $future->read(); // Returns unread output
     *
     * NOTE: If you set a limit with @{method:setStdoutSizeLimit} or
     * @{method:setStderrSizeLimit}, this method will not be able to read data
     * past the limit.
     *
     * NOTE: If you call @{method:discardBuffers}, all the stdout/stderr data
     * will be thrown away and the cursors will be reset.
     *
     * @return pair <$stdout, $stderr> pair with new output since the last call
     *              to this method.
     * @task interact
     */
    public function read()
    {
        $stdout = $this->readStdout();

        $result = array(
            $stdout,
            (string)substr($this->stderr, $this->stderrPos),
        );

        $this->stderrPos = strlen($this->stderr);

        return $result;
    }

    public function readStdout()
    {
        if ($this->start) {
            $this->isReady(); // Sync
        }

        $result = (string)substr($this->stdout, $this->stdoutPos);
        $this->stdoutPos = strlen($this->stdout);
        return $result;
    }

    /**
     * Write data to stdin of the command.
     *
     * @param string Data to write.
     * @param bool If true, keep the pipe open for writing. By default, the pipe
     *             will be closed as soon as possible so that commands which
     *             listen for EOF will execute. If you want to keep the pipe open
     *             past the start of command execution, do an empty write with
     *             `$keep_pipe = true` first.
     * @return this
     * @task interact
     */
    public function write($data, $keep_pipe = false)
    {
        if (strlen($data)) {
            if (!$this->stdin) {
                throw new Exception(pht('Writing to a closed pipe!'));
            }
            $this->stdin->append($data);
        }
        $this->closePipe = !$keep_pipe;

        return $this;
    }

    /**
     * Permanently discard the stdout and stderr buffers and reset the read
     * cursors. This is basically useful only if you are streaming a large amount
     * of data from some process:
     *
     *   $future = new ExecFuture('zcat huge_file.gz');
     *   do {
     *     $done = $future->resolve(0.1);   // Every 100ms,
     *     list($stdout) = $future->read(); // read output...
     *     echo $stdout;                    // send it somewhere...
     *     $future->discardBuffers();       // and then free the buffers.
     *   } while ($done === null);
     *
     * Conceivably you might also need to do this if you're writing a client using
     * @{class:ExecFuture} and `netcat`, but you probably should not do that.
     *
     * NOTE: This completely discards the data. It won't be available when the
     * future resolves. This is almost certainly only useful if you need the
     * buffer memory for some reason.
     *
     * @return this
     * @task interact
     */
    public function discardBuffers()
    {
        $this->discardStdoutBuffer();

        $this->stderr = '';
        $this->stderrPos = 0;

        return $this;
    }

    public function discardStdoutBuffer()
    {
        $this->stdout = '';
        $this->stdoutPos = 0;
        return $this;
    }

    /**
     * Returns true if this future was killed by a timeout configured with
     * @{method:setTimeout}.
     *
     * @return bool True if the future was killed for exceeding its time limit.
     */
    public function getWasKilledByTimeout()
    {
        return $this->killedByTimeout;
    }

/* -(  Configuring Execution  )---------------------------------------------- */

    /**
     * Set a hard limit on execution time. If the command runs longer, it will
     * be killed and the future will resolve with an error code. You can test
     * if a future was killed by a timeout with @{method:getWasKilledByTimeout}.
     *
     * @param int Maximum number of seconds this command may execute for.
     * @return this
     * @task config
     */
    public function setTimeout($seconds)
    {
        $this->timeout = $seconds;
        return $this;
    }

/* -(  Resolving Execution  )------------------------------------------------ */

    /**
     * Resolve a command you expect to exit with return code 0. Works like
     * @{method:resolve}, but throws if $err is nonempty. Returns only
     * $stdout and $stderr. See also @{function:execx}.
     *
     *   list($stdout, $stderr) = $future->resolvex();
     *
     * @param  float Optional timeout after which resolution will pause and
     *               execution will return to the caller.
     * @return pair  <$stdout, $stderr> pair.
     * @task resolve
     */
    public function resolvex($timeout = null)
    {
        list($err, $stdout, $stderr) = $this->resolve($timeout);
        if ($err) {
            $cmd = $this->command;
            throw new CommandException(
                sprintf('Command failed with error #%d!', $err),
                $cmd,
                $err,
                $stdout,
                $stderr
            );
        }
        return array($stdout, $stderr);
    }


    /**
     * Resolve a command you expect to return valid JSON. Works like
     * @{method:resolvex}, but also throws if stderr is nonempty, or stdout is not
     * valid JSON. Returns a PHP array, decoded from the JSON command output.
     *
     * @param  float Optional timeout after which resolution will pause and
     *               execution will return to the caller.
     * @return array PHP array, decoded from JSON command output.
     * @task resolve
     */
    public function resolveJSON($timeout = null)
    {
        list($stdout, $stderr) = $this->resolvex($timeout);
        if (strlen($stderr)) {
            $cmd = $this->command;
            throw new CommandException(
                sprintf(
                    "JSON command '%s' emitted text to stderr when none was expected: %d",
                    $cmd,
                    $stderr
                ),
                $cmd,
                0,
                $stdout,
                $stderr
            );
        }
        $ret = json_decode($stdout);
        if ($ret === false) {
            $cmd = $this->command;
            throw new CommandException(
                sprintf(
                    "JSON command '%s' did not produce a valid JSON object on stdout: %s",
                    $cmd,
                    $stdout
                ),
                $cmd,
                0,
                $stdout,
                $stderr
            );
        }
        return $ret;
    }

    /**
     * Resolve the process by abruptly terminating it.
     *
     * @return list List of <err, stdout, stderr> results.
     * @task resolve
     */
    public function resolveKill()
    {
        if (!$this->result) {
            if (defined('SIGKILL')) {
                $signal = SIGKILL;
            } else {
                $signal = 9;
            }

            proc_terminate($this->proc, $signal);
            $this->result = array(
                128 + $signal,
                $this->stdout,
                $this->stderr,
            );
            $this->closeProcess();
        }

        return $this->result;
    }

/* -(  Internals  )---------------------------------------------------------- */

    /**
     * Provides read sockets to the future core.
     *
     * @return list List of read sockets.
     * @task internal
     */
    public function getReadSockets()
    {
        list($stdin, $stdout, $stderr) = $this->pipes;
        $sockets = array();
        if (isset($stdout) && !feof($stdout)) {
            $sockets[] = $stdout;
        }
        if (isset($stderr) && !feof($stderr)) {
            $sockets[] = $stderr;
        }
        return $sockets;
    }

    /**
     * Provides write sockets to the future core.
     *
     * @return list List of write sockets.
     * @task internal
     */
    public function getWriteSockets()
    {
        list($stdin, $stdout, $stderr) = $this->pipes;
        $sockets = array();
        if (isset($stdin) && $this->stdin->getByteLength() && !feof($stdin)) {
            $sockets[] = $stdin;
        }
        return $sockets;
    }

    /**
     * Determine if the read buffer is empty.
     *
     * @return bool True if the read buffer is empty.
     * @task internal
     */
    public function isReadBufferEmpty()
    {
        return !strlen($this->stdout);
    }

    /**
     * Determine if the write buffer is empty.
     *
     * @return bool True if the write buffer is empty.
     * @task internal
     */
    public function isWriteBufferEmpty()
    {
        return !$this->getWriteBufferSize();
    }

    /**
     * Determine the number of bytes in the write buffer.
     *
     * @return int Number of bytes in the write buffer.
     * @task internal
     */
    public function getWriteBufferSize()
    {
        if (!$this->stdin) {
            return 0;
        }
        return $this->stdin->getByteLength();
    }

    /**
     * Reads some bytes from a stream, discarding output once a certain amount
     * has been accumulated.
     *
     * @param resource  Stream to read from.
     * @param int       Maximum number of bytes to return from $stream. If
     *                  additional bytes are available, they will be read and
     *                  discarded.
     * @param string    Human-readable description of stream, for exception
     *                  message.
     * @param int       Maximum number of bytes to read.
     * @return string   The data read from the stream.
     * @task internal
     */
    private function readAndDiscard($stream, $limit, $description, $length)
    {
        $output = '';

        if ($length <= 0) {
            return '';
        }

        do {
            $data = fread($stream, min($length, 64 * 1024));
            if (false === $data) {
                throw new Exception(sprintf('Failed to read from %s', $description));
            }

            $read_bytes = strlen($data);

            if ($read_bytes > 0 && $limit > 0) {
                if ($read_bytes > $limit) {
                    $data = substr($data, 0, $limit);
                }
                $output .= $data;
                $limit -= strlen($data);
            }

            if (strlen($output) >= $length) {
                break;
            }
        } while ($read_bytes > 0);

        return $output;
    }

    /**
     * Begin or continue command execution.
     *
     * @return bool True if future has resolved.
     * @task internal
     */
    public function isReady()
    {
        // NOTE: We have soft dependencies on PhutilServiceProfiler and
        // PhutilErrorTrap here. These dependencies are soft to avoid the need to
        // build them into the Phage agent. Under normal circumstances, these
        // classes are always available.

        if (!$this->pipes) {
            if (!$this->start) {
                // We might already have started the timer via initiating resolution.
                $this->start = microtime(true);
            }

            $unmasked_command = $this->command;
            $pipes = array();

            if (CliUtil::isWindows()) {
                // See T4395. proc_open under Windows uses "cmd /C [cmd]", which will
                // strip the first and last quote when there aren't exactly two quotes
                // (and some other conditions as well). This results in a command that
                // looks like `command" "path to my file" "something something` which is
                // clearly wrong. By surrounding the command string with quotes we can
                // be sure this process is harmless.
                if (strpos($unmasked_command, '"') !== false) {
                    $unmasked_command = '"'.$unmasked_command.'"';
                }
            }

            // NOTE: Convert all the environmental variables we're going to pass
            // into strings before we install PhutilErrorTrap. If something in here
            // is really an object which is going to throw when we try to turn it
            // into a string, we want the exception to escape here -- not after we
            // start trapping errors.
            $env = $this->env;
            if ($env !== null) {
                foreach ($env as $key => $value) {
                    $env[$key] = (string)$value;
                }
            }

            // Same for the working directory.
            if ($this->cwd === null) {
                $cwd = null;
            } else {
                $cwd = (string)$this->cwd;
            }

            $spec = self::$descriptorSpec;
            if ($this->useWindowsFileStreams) {
                $this->windowsStdoutTempFile = self::tempFile();
                $this->windowsStderrTempFile = self::tempFile();

                $spec = array(
                    0 => self::$descriptorSpec[0],  // stdin
                    1 => fopen($this->windowsStdoutTempFile, 'wb'),  // stdout
                    2 => fopen($this->windowsStderrTempFile, 'wb'),  // stderr
                );

                if (!$spec[1] || !$spec[2]) {
                    throw new Exception(sprintf(
                        'Unable to create temporary files for '.
                        'Windows stdout / stderr streams'
                    ));
                }
            }

            $proc = @proc_open(
                $unmasked_command,
                $spec,
                $pipes,
                $cwd,
                $env
            );

            if ($this->useWindowsFileStreams) {
                fclose($spec[1]);
                fclose($spec[2]);
                $pipes = array(
                    0 => head($pipes),  // stdin
                    1 => fopen($this->windowsStdoutTempFile, 'rb'),  // stdout
                    2 => fopen($this->windowsStderrTempFile, 'rb'),  // stderr
                );

                if (!$pipes[1] || !$pipes[2]) {
                    throw new Exception(sprintf(
                        'Unable to open temporary files for '.
                        'reading Windows stdout / stderr streams'
                    ));
                }
            }

            if (!is_resource($proc)) {
                $err = error_get_last();
                throw new Exception(
                    sprintf(
                        'Failed to %s: %s',
                        'proc_open()',
                        $err
                    )
                );
            }

            $this->pipes = $pipes;
            $this->proc  = $proc;

            list($stdin, $stdout, $stderr) = $pipes;

            if (!CliUtil::isWindows()) {
                // On Windows, we redirect process standard output and standard error
                // through temporary files, and then use stream_select to determine
                // if there's more data to read.

                if ((!stream_set_blocking($stdout, false)) ||
                    (!stream_set_blocking($stderr, false)) ||
                    (!stream_set_blocking($stdin, false))) {
                    $this->__destruct();
                    throw new Exception('Failed to set streams nonblocking.');
                }
            }

            $this->tryToCloseStdin();

            return false;
        }

        if (!$this->proc) {
            return true;
        }

        list($stdin, $stdout, $stderr) = $this->pipes;

        while (isset($this->stdin) && $this->stdin->getByteLength()) {
            $write_segment = $this->stdin->getAnyPrefix();

            $bytes = fwrite($stdin, $write_segment);
            if ($bytes === false) {
                throw new Exception('Unable to write to stdin!');
            } elseif ($bytes) {
                $this->stdin->removeBytesFromHead($bytes);
            } else {
                // Writes are blocked for now.
                break;
            }
        }

        $this->tryToCloseStdin();

        // Read status before reading pipes so that we can never miss data that
        // arrives between our last read and the process exiting.
        $status = $this->procGetStatus();

        $read_buffer_size = $this->readBufferSize;

        $max_stdout_read_bytes = PHP_INT_MAX;
        $max_stderr_read_bytes = PHP_INT_MAX;
        if ($read_buffer_size !== null) {
            $max_stdout_read_bytes = $read_buffer_size - strlen($this->stdout);
            $max_stderr_read_bytes = $read_buffer_size - strlen($this->stderr);
        }

        if ($max_stdout_read_bytes > 0) {
            $this->stdout .= $this->readAndDiscard(
                $stdout,
                $this->getStdoutSizeLimit() - strlen($this->stdout),
                'stdout',
                $max_stdout_read_bytes
            );
        }

        if ($max_stderr_read_bytes > 0) {
            $this->stderr .= $this->readAndDiscard(
                $stderr,
                $this->getStderrSizeLimit() - strlen($this->stderr),
                'stderr',
                $max_stderr_read_bytes
            );
        }

        if (!$status['running']) {
            if ($this->useWindowsFileStreams) {
                fclose($stdout);
                fclose($stderr);
            }

            $this->result = array(
                $status['exitcode'],
                $this->stdout,
                $this->stderr,
            );
            $this->closeProcess();
            return true;
        }

        $elapsed = (microtime(true) - $this->start);
        if ($this->timeout && ($elapsed >= $this->timeout)) {
            $this->killedByTimeout = true;
            $this->resolveKill();
            return true;
        }
    }

    /**
     * @return void
     * @task internal
     */
    public function __destruct()
    {
        if (!$this->proc) {
            return;
        }

        // NOTE: If we try to proc_close() an open process, we hang indefinitely. To
        // avoid this, kill the process explicitly if it's still running.

        $status = $this->procGetStatus();
        if ($status['running']) {
            $this->resolveKill();
        } else {
            $this->closeProcess();
        }
    }

    /**
     * Close and free resources if necessary.
     *
     * @return void
     * @task internal
     */
    private function closeProcess()
    {
        foreach ($this->pipes as $pipe) {
            if (isset($pipe)) {
                @fclose($pipe);
            }
        }
        $this->pipes = array(null, null, null);
        if ($this->proc) {
            @proc_close($this->proc);
            $this->proc = null;
        }
        $this->stdin  = null;
    }

    /**
     * Execute `proc_get_status()`, but avoid pitfalls.
     *
     * @return dict Process status.
     * @task internal
     */
    private function procGetStatus()
    {
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

    /**
     * Try to close stdin, if we're done using it. This keeps us from hanging if
     * the process on the other end of the pipe is waiting for EOF.
     *
     * @return void
     * @task internal
     */
    private function tryToCloseStdin()
    {
        if (!$this->closePipe) {
            // We've been told to keep the pipe open by a call to write(..., true).
            return;
        }

        if ($this->stdin->getByteLength()) {
            // We still have bytes to write.
            return;
        }

        list($stdin) = $this->pipes;
        if (!$stdin) {
            // We've already closed stdin.
            return;
        }

        // There's nothing stopping us from closing stdin, so close it.

        @fclose($stdin);
        $this->pipes[0] = null;
    }

    public function getDefaultWait()
    {
        $wait = parent::getDefaultWait();

        if ($this->timeout) {
            if (!$this->start) {
                $this->start = microtime(true);
            }
            $elapsed = (microtime(true) - $this->start);
            $wait = max(0, min($this->timeout - $elapsed, $wait));
        }

        return $wait;
    }

    private static function tempFile()
    {
        return tempnam(sys_get_temp_dir(), 'exec');
    }
}