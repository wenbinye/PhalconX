<?php
namespace PhalconX\Cli;

class CliUtil
{
    public static function sigName($signo)
    {
        static $SIGNALS;
        if (!$SIGNALS) {
            $names = [
                'SIGHUP',
                'SIGINT',
                'SIGQUIT',
                'SIGILL',
                'SIGTRAP',
                'SIGABRT',
                'SIGIOT',
                'SIGBUS',
                'SIGFPE',
                'SIGUSR1',
                'SIGSEGV',
                'SIGUSR2',
                'SIGPIPE',
                'SIGALRM',
                'SIGTERM',
                'SIGSTKFLT',
                'SIGCLD',
                'SIGCHLD',
                'SIGCONT',
                'SIGTSTP',
                'SIGTTIN',
                'SIGTTOU',
                'SIGURG',
                'SIGXCPU',
                'SIGXFSZ',
                'SIGVTALRM',
                'SIGPROF',
                'SIGWINCH',
                'SIGPOLL',
                'SIGIO',
                'SIGPWR',
                'SIGSYS',
                'SIGBABY',
            ];
            $SIGNALS = array();
            foreach ($names as $constant) {
                if (defined($constant)) {
                    $SIGNALS[constant($constant)] = $constant;
                }
            }
        }
        return isset($SIGNALS[$signo]) ? $SIGNALS[$signo] : '';
    }

    public static function isWindows()
    {
        return (DIRECTORY_SEPARATOR != '/');
    }
}
