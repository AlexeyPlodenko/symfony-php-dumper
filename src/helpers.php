<?php

use Symfony\Component\VarDumper\VarDumper;

if (!function_exists('d')) {
    /**
     * @param mixed ...$args
     * @return void
     */
    function d(...$args)
    {
        // run only in non-production and non-staging envs. So if someone would forget the d() in the code,
        // it would not affect the runtime.
        $appEnv = getenv('APP_ENV');
        if ($appEnv === 'production' || $appEnv === 'staging') {
            return;
        }

        // empty the output buffers, so the page would contain only the debug messages
        while (ob_get_level()) {
            ob_end_clean();
        }

        // send the HTTP 500 status header
        $isCli = (php_sapi_name() === 'cli');
        if (!$isCli) {
            $httpProtocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP';
            header($httpProtocol . ' 500 Internal Server Error', true, 500);
        }

        // output each debug argument
        foreach ($args as $arg) {
            VarDumper::dump($arg);

            echo ($isCli ? "\n\n" : '<hr>');
        }

        // output backtrace
        if (!$isCli) {
            echo '<pre>';
        }

        { // let's make framework related lines less bright
            $mutedLines = [
                '/var/www/vendor/laravel/framework/'
            ];

            ob_start();
            debug_print_backtrace();
            $backTrace = ob_get_clean();

            $backTraceArray = explode("\n", $backTrace);
            foreach ($backTraceArray as &$line) {
                $isEsc = false;
                foreach ($mutedLines as $mutedLine) {
                    if (str_contains($line, $mutedLine)) {
                        $isEsc = true;
                        $line = '<span class="muted">' . htmlspecialchars($line) . '</span>';
                        break;
                    }
                }

                if (!$isEsc) {
                    $line = htmlspecialchars($line);
                }
            }
            unset($line);

            ?><div id="backtrace" class="backtrace"><?= implode("\n", $backTraceArray) ?></div>
            <style>
                .backtrace .muted {
                    color: #ccc;
                    transition: color 0.1s ease;
                }
                .backtrace:hover .muted {
                    color: inherit;
                }
            </style><?php
        }

        if (!$isCli) {
            echo '</pre><small>Outputted by the <a href="https://github.com/AlexeyPlodenko/symfony-php-dumper">',
            'alexeyplodenko/symfony-php-dumper</a> PHP package.</small>';
        }

        if (!$isCli) {
            // output to the STDERR also
            foreach ($args as $arg) {
                if (is_scalar($arg)) {
                    error_log((string)$arg);
                } else {
                    error_log(json_encode($arg));
                }
            }

            // output the stack trace
            /** @source https://www.php.net/manual/en/function.debug-backtrace.php#112238 */
            $ex = new Exception();
            $trace = explode("\n", $ex->getTraceAsString());
            $trace = array_reverse($trace); // reverse array to make steps line up chronologically
            array_shift($trace); // remove {main}
            array_pop($trace); // remove call to this method
            if (count($trace) > 5) {
                // show an ellipsis, instead of the rows, if there are too many
                $trace = array_slice($trace, 0, 5);
                $trace = array_merge($trace, ['...']);
            }
            error_log(implode("\n", $trace));

            error_log(str_repeat('^', 80));
        }

        exit(1);
    }
}
