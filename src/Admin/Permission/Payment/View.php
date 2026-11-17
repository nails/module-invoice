<?php

namespace Nails\Invoice\Admin\Permission\Payment;

use Nails\Admin\Interfaces\Permission;

class View implements Permission
{
    public function label(): string
    {
        return 'Can view payment details';
    }

    public function group(): string
    {
        return 'Payments';
    }
}
