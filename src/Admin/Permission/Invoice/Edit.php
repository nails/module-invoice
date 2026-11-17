<?php

namespace Nails\Invoice\Admin\Permission\Invoice;

use Nails\Admin\Interfaces\Permission;

class Edit implements Permission
{
    public function label(): string
    {
        return 'Can edit invoices';
    }

    public function group(): string
    {
        return 'Invoices';
    }
}
