# symfony-php-dumper

The package provides a simple dump `d($yourFirstVar, $yourSecondVar,..);` function, based on the Symfony VarDumper package, to output the variables and stop execution.

## Motivation

To have an output to both the browser and CLI (like Docker logs) at the same time.

To have a stack trace to be able to find where I have left the d() function.
