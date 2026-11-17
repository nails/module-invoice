<?php

namespace Nails\Invoice\Admin\Permission\Customer;

use Nails\Admin\Interfaces\Permission;

class Browse implements Permission
{
    public function label(): string
    {
        return 'Can browse customers';
    }

    public function group(): string
    {
        return 'Customers';
    }
}
