<?php

namespace Nails\Invoice\Admin\Permission\Settings;

use Nails\Admin\Interfaces\Permission;

class General implements Permission
{
    public function label(): string
    {
        return 'Can manage Invoices & Payments settings';
    }

    public function group(): string
    {
        return 'Settings';
    }
}
