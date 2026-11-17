<?php

namespace Nails\Invoice\Admin\Permission\Payment;

use Nails\Admin\Interfaces\Permission;

class Refund implements Permission
{
    public function label(): string
    {
        return 'Can refund payments';
    }

    public function group(): string
    {
        return 'Payments';
    }
}
