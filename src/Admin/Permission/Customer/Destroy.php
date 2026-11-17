<?php

namespace Nails\Invoice\Admin\Permission\Customer;

use Nails\Admin\Interfaces\Permission;

class Destroy implements Permission
{
    public function label(): string
    {
        return 'Can destroy customers';
    }

    public function group(): string
    {
        return 'Customers';
    }
}
