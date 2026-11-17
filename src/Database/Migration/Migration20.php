<?php

/**
 * Migration: 20
 * Started:   16/11/2026
 */

namespace Nails\Invoice\Database\Migration;

use Nails\Admin\Traits\Database\Migration\PermissionMap;
use Nails\Common\Interfaces;
use Nails\Common\Traits;
use Nails\Invoice\Admin\Permission;

/**
 * Class Migration20
 *
 * Repeatable because `feature/pre-new-admin` has no equivalent migration, so an app
 * arriving from that branch resumes above this number and would never run it.
 *
 * @package Nails\Invoice\Database\Migration
 */
class Migration20 implements Interfaces\Database\Migration\Repeatable
{
    use Traits\Database\Migration;
    use PermissionMap;

    // --------------------------------------------------------------------------

    const MAP = [
        'admin:invoice:invoice:manage'   => Permission\Invoice\Browse::class,
        'admin:invoice:invoice:create'   => Permission\Invoice\Create::class,
        'admin:invoice:invoice:edit'     => Permission\Invoice\Edit::class,
        'admin:invoice:invoice:delete'   => Permission\Invoice\Delete::class,
        'admin:invoice:customer:browse'  => Permission\Customer\Browse::class,
        'admin:invoice:customer:create'  => Permission\Customer\Create::class,
        'admin:invoice:customer:edit'    => Permission\Customer\Edit::class,
        'admin:invoice:customer:delete'  => Permission\Customer\Delete::class,
        'admin:invoice:customer:destroy' => Permission\Customer\Destroy::class,
        'admin:invoice:customer:restore' => Permission\Customer\Restore::class,
        'admin:invoice:customer:manage'  => Permission\Customer\Browse::class,
        'admin:invoice:payment:view'     => Permission\Payment\View::class,
        'admin:invoice:payment:refund'   => Permission\Payment\Refund::class,
        'admin:invoice:refund:view'      => Permission\Refund\View::class,
    ];
}
