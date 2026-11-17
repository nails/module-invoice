<?php

/**
 * Manage payments
 *
 * @package     Nails
 * @subpackage  module-invoice
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Invoice\Admin\Controller;

use Nails\Admin\Controller\Base;
use Nails\Admin\Controller\DefaultController;
use Nails\Admin\Factory\Nav;
use Nails\Admin\Helper;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\NailsException;
use Nails\Common\Factory\Component;
use Nails\Common\Helper\Model\Expand;
use Nails\Common\Service\Input;
use Nails\Common\Service\UserFeedback;
use Nails\Common\Service\Uri;
use Nails\Factory;
use Nails\Invoice\Admin\Permission;
use Nails\Invoice\Constants;
use Nails\Invoice\Service\RefundDriver;

/**
 * Class Refund
 *
 * @package Nails\Invoice\Admin\Controller
 */
class Refund extends DefaultController
{
    const CONFIG_MODEL_NAME     = 'Refund';
    const CONFIG_MODEL_PROVIDER = Constants::MODULE_SLUG;
    const CONFIG_SIDEBAR_GROUP  = 'Invoices &amp; Payments';
    const CONFIG_CAN_COPY       = false;
    const CONFIG_CAN_CREATE     = false;
    const CONFIG_CAN_DELETE     = false;
    const CONFIG_CAN_RESTORE    = false;
    const CONFIG_CAN_EDIT       = false;
    const CONFIG_CAN_VIEW       = false;
    const CONFIG_SORT_OPTIONS   = [
        'Issued Date'    => 'created',
        'Gateway'        => 'driver',
        'Invoice'        => 'invoice_id',
        'Transaction ID' => 'ref',
        'Gateway ID'     => 'transaction_id',
        'Amount'         => 'amount',
        'Fee'            => 'fee',
        'Currency'       => 'currency',
    ];
    const CONFIG_SORT_DIRECTION = self::SORT_DESCENDING;
    const CONFIG_INDEX_DATA     = [
        'expand' => [
            [
                'payment',
                [
                    'expand' => [
                        [
                            'invoice',
                            ['expand' => ['customer']],
                        ],
                    ],
                ],
            ],
        ],
    ];
    const CONFIG_INDEX_FIELDS   = [
        'Gateway'        => null,
        'Transaction ID' => 'ref',
        'Gateway ID'     => 'transaction_id',
        'Status'         => null,
        'reason'         => 'reason',
        'Invoice'        => null,
        'Payment'        => null,
        'Amount'         => 'amount.formatted',
        'Fee'            => 'fee.formatted',
        'Currency'       => 'currency.code',
        'Customer'       => null,
        'Issued'         => 'created',
    ];

    // --------------------------------------------------------------------------

    /**
     * Refund constructor.
     *
     * @throws \Nails\Common\Exception\NailsException
     */
    public function __construct()
    {
        parent::__construct();

        $this->aConfig['INDEX_HEADER_BUTTONS'] = array_merge(
            $this->aConfig['INDEX_HEADER_BUTTONS'],
            array_filter([
                userHasPermission(Permission\Invoice\Create::class)
                    ? [
                    'url'   => Invoice::url('create'),
                    'label' => 'Create Invoice',
                ] : null,
            ])
        );

        $this->aConfig['INDEX_FIELDS']['Gateway'] = function (\Nails\Invoice\Resource\Refund $oRefund) {
            return $oRefund->payment->driver->getLabel();
        };

        $this->aConfig['INDEX_FIELDS']['Status'] = function (\Nails\Invoice\Resource\Refund $oRefund) {

            /** @var \Nails\Invoice\Model\Refund $oModel */
            $oModel = static::getModel();

            switch ($oRefund->status->id) {
                case $oModel::STATUS_PENDING:
                    $sStatus = 'info';
                    break;

                case $oModel::STATUS_PROCESSING:
                case $oModel::STATUS_COMPLETE:
                    $sStatus = 'success';
                    break;

                case $oModel::STATUS_FAILED:
                    $sStatus = 'danger';
                    break;

                case $oModel::STATUS_REFUNDED:
                case $oModel::STATUS_REFUNDED_PARTIAL:
                    $sStatus = 'warning';
                    break;
            }

            return [
                sprintf(
                    '%s<small>%s</small>',
                    $oRefund->status->label,
                    $oRefund->fail_msg
                        ? $oRefund->fail_msg . ' (Code: ' . $oRefund->fail_code . ')'
                        : null,
                ),
                $sStatus,
            ];
        };

        $this->aConfig['INDEX_FIELDS']['Invoice'] = function (\Nails\Invoice\Resource\Refund $oRefund) {
            return sprintf(
                '<a href="%s">%s</a><small>%s</small>',
                Invoice::url('view/' . $oRefund->payment->invoice->id),
                $oRefund->payment->invoice->ref,
                $oRefund->payment->invoice->state->label,
            );
        };

        $this->aConfig['INDEX_FIELDS']['Payment'] = function (\Nails\Invoice\Resource\Refund $oRefund) {
            return sprintf(
                '<a href="%s">%s</a><small>%s</small>',
                Payment::url('view/' . $oRefund->payment->id),
                $oRefund->payment->ref,
                $oRefund->payment->status->label,
            );
        };

        $this->aConfig['INDEX_FIELDS']['Customer'] = function (\Nails\Invoice\Resource\Refund $oRefund) {
            return $oRefund->payment->invoice->customer
                ? sprintf(
                    '%s<small>%s</small>',
                    $oRefund->payment->invoice->customer->label,
                    mailto($oRefund->payment->invoice->customer->email ?? $oRefund->payment->invoice->customer->billing_email)
                )
                : '<span class="text-muted">&mdash;</span>';
        };

        $this->aConfig['INDEX_ROW_BUTTONS'] = array_merge(
            $this->aConfig['INDEX_ROW_BUTTONS'],
            [
                [
                    'url'     => static::url('view/{{id}}'),
                    'label'   => 'View',
                    'class'   => 'btn-default',
                    'enabled' => function () {
                        return userHasPermission(Permission\Refund\View::class);
                    },
                ],
                [
                    'url'     => Payment::url('view/{{payment.id}}'),
                    'label'   => 'View Payment',
                    'class'   => 'btn-default',
                    'enabled' => function () {
                        return userHasPermission(Permission\Payment\View::class);
                    },
                ],
                [
                    'url'     => Invoice::url('view/{{payment.invoice.id}}'),
                    'label'   => 'View Invoice',
                    'class'   => 'btn-default',
                    'enabled' => function () {
                        return userHasPermission(Permission\Invoice\Edit::class);
                    },
                ],
            ]
        );
    }

    // --------------------------------------------------------------------------

    /**
     * @return array
     * @throws \Nails\Common\Exception\FactoryException
     */
    protected function indexCheckboxFilters(): array
    {
        $aStatuses = self::getModel()->getStatusesHuman();

        /** @var \Nails\Admin\Factory\IndexFilter $oFilterStatus */
        $oFilterStatus = Factory::factory('IndexFilter', \Nails\Admin\Constants::MODULE_SLUG);
        $oFilterStatus
            ->setLabel('Status')
            ->setColumn('status')
            ->addOptions(array_map(function ($sKey, $sLabel) {

                /** @var \Nails\Admin\Factory\IndexFilter\Option $oOption */
                $oOption = Factory::factory('IndexFilterOption', \Nails\Admin\Constants::MODULE_SLUG);
                $oOption
                    ->setLabel($sLabel)
                    ->setValue($sKey);

                return $oOption;

            }, array_keys($aStatuses), $aStatuses));

        return array_merge(
            parent::indexCheckboxFilters(),
            [
                $oFilterStatus,
            ]
        );
    }

    // --------------------------------------------------------------------------

    /**
     * View a single payment
     *
     * @return void
     */
    public function view()
    {
        if (!userHasPermission(Permission\Refund\View::class)) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        /** @var Uri $oUri */
        $oUri = Factory::service('Uri');
        /** @var \Nails\Invoice\Model\Refund $oRefundModel */
        $oRefundModel = Factory::model('Refund', Constants::MODULE_SLUG);

        // --------------------------------------------------------------------------

        $this->data['refund'] = $oRefundModel->getById(
            $oUri->segment(5),
            [
                new Expand('payment', new Expand('invoice')),
            ]
        );

        if (!$this->data['refund']) {
            show404();
        }

        $this->data['page']->title = 'View Refund &rsaquo; ' . $this->data['refund']->ref;

        Helper::loadView('view');
    }
}
