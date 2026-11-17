<?php

namespace Nails\Invoice\Admin\Permission\Invoice;

use Nails\Admin\Interfaces\Permission;

class Create implements Permission
{
    public function label(): string
    {
        return 'Can create invoices';
    }

    public function group(): string
    {
        return 'Invoices';
    }
}
