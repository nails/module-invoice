<?php

namespace Nails\Invoice\Admin\Permission\Invoice;

use Nails\Admin\Interfaces\Permission;

class Browse implements Permission
{
    public function label(): string
    {
        return 'Can browse invoices';
    }

    public function group(): string
    {
        return 'Invoices';
    }
}
