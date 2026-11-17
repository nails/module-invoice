<?php

namespace Nails\Invoice\Admin\Permission\Customer;

use Nails\Admin\Interfaces\Permission;

class Edit implements Permission
{
    public function label(): string
    {
        return 'Can edit customers';
    }

    public function group(): string
    {
        return 'Customers';
    }
}
