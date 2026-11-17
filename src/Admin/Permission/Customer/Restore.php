<?php

namespace Nails\Invoice\Admin\Permission\Customer;

use Nails\Admin\Interfaces\Permission;

class Restore implements Permission
{
    public function label(): string
    {
        return 'Can restore customers';
    }

    public function group(): string
    {
        return 'Customers';
    }
}
