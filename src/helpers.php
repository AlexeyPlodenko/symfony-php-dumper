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
            if (is_array($args)) {
                $args['backtrace'] = debug_backtrace();
            }
            echo json_encode($args);
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
            // output to the STDERR also
            foreach ($args as $arg) {
                if (is_scalar($arg)) {
                    error_log((string)$arg);
                } else {
                    error_log(json_encode($arg));
                }
            }
            error_log(str_repeat('^', 80));
        }

        exit(1);
    }
}
