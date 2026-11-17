<?php

namespace Nails\Invoice\Admin\Permission\Customer;

use Nails\Admin\Interfaces\Permission;

class Delete implements Permission
{
    public function label(): string
    {
        return 'Can delete customers';
    }

    public function group(): string
    {
        return 'Customers';
    }
}
