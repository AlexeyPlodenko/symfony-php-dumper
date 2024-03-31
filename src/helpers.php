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
        $isJson = (
            isset($_SERVER['CONTENT_TYPE']) && strtolower($_SERVER['CONTENT_TYPE']) === 'application/json'
            || isset($_SERVER['HTTP_ACCEPT']) && strtolower($_SERVER['HTTP_ACCEPT']) === 'application/json'
        );
        $isCli = (php_sapi_name() === 'cli');
        $httpProtocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP';
        header($httpProtocol . ' 500 Internal Server Error', true, 500);

        // output each debug argument
        if ($isJson) {
            echo json_encode(count($args) === 1 ? $args[0] : $args);
        } else {
            foreach ($args as $arg) {
                VarDumper::dump($arg);

                echo($isCli ? "\n\n" : '<hr>');
            }
        }

        // output backtrace
        if (!$isCli && !$isJson) {
            echo '<pre>';
        }
        if (!$isJson) {
            debug_print_backtrace();
        }
        if (!$isCli && !$isJson) {
            echo '</pre><small>Outputted by the <a href="https://github.com/AlexeyPlodenko/symfony-php-dumper">',
            'alexeyplodenko/symfony-php-dumper</a> PHP package.</small>';
        }

        if (!$isCli) {
            error_log(str_repeat('⌄', 80));

            // output to the STDERR also
            foreach ($args as $arg) {
                if (is_scalar($arg)) {
                    error_log((string)$arg);
                } else {
                    error_log(json_encode($arg));
                }
            }

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
