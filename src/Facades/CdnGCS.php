<?php

namespace Awescrm\CdnGCS\Facades;

use Illuminate\Support\Facades\Facade;

class CdnGCS extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'cdngcs';
    }
}
