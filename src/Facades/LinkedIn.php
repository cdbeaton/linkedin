<?php

namespace Cdbeaton\Linkedin\Facades;

use Illuminate\Support\Facades\Facade;

class LinkedIn extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'Cdbeaton\Linkedin\LinkedIn';
    }
}
