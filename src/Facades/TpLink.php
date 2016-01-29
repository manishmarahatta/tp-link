<?php

namespace NikhilPandey\TpLink\Facades;

class TpLink extends \Illuminate\Support\Facades\Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'tplink';
    }
}
