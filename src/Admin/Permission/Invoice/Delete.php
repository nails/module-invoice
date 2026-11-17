<?php

namespace Nails\Invoice\Admin\Permission\Invoice;

use Nails\Admin\Interfaces\Permission;

class Delete implements Permission
{
    public function label(): string
    {
        return 'Can delete invoices';
    }

    public function group(): string
    {
        return 'Invoices';
    }
}
