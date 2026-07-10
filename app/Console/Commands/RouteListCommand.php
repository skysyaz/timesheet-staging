<?php

namespace App\Console\Commands;

use Illuminate\Foundation\Console\RouteListCommand as BaseRouteListCommand;
use Symfony\Component\Console\Input\InputOption;

class RouteListCommand extends BaseRouteListCommand
{
    /**
     * Accept --columns for tooling that still passes the pre-Laravel 9 flag.
     * CLI layout is fixed in modern Laravel, so the value is ignored for display.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array_merge(parent::getOptions(), [
            ['columns', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Columns to include (accepted for compatibility)'],
        ]);
    }
}
