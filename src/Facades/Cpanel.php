<?php

namespace ZanySoft\Cpanel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Factory.
 *
 * @method static \ZanySoft\Cpanel\Cpanel   make(string|null $host = null, string|null $username = null, string|null $password = null): \ZanySoft\Cpanel\Cpanel
 * @method static \ZanySoft\Cpanel\Cpanel   api1(string $user, string $module, string $function, array $args = [])
 * @method static \ZanySoft\Cpanel\Cpanel   api2(string $user, string $module, string $function, array $args = [])
 * @method static \ZanySoft\Cpanel\Cpanel   setAuth(string $username, string $password): \ZanySoft\Cpanel\Cpanel
 */
class Cpanel extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'cpanel';
    }
}
