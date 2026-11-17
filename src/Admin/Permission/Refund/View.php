<?php

namespace Nails\Invoice\Admin\Permission\Refund;

use Nails\Admin\Interfaces\Permission;

class View implements Permission
{
    public function label(): string
    {
        return 'Can view refund details';
    }

    public function group(): string
    {
        return 'Refunds';
    }
}
