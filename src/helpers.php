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

        $isCli = (php_sapi_name() === 'cli');
        foreach ($args as $arg) {
            VarDumper::dump($arg);

            echo ($isCli ? "\n\n" : '<hr>');
        }

        // output backtrace
        echo '<pre>';
        debug_print_backtrace();
        echo '</pre><small>Outputted by the <a href="https://github.com/AlexeyPlodenko/symfony-php-dumper">' ,
        'alexeyplodenko/symfony-php-dumper</a> PHP package.</small>';

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
