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
use Nails\Common\Service\Uri;
use Nails\Factory;
use Nails\Invoice\Admin\Permission;
use Nails\Invoice\Constants;
use Nails\Invoice\Service\PaymentDriver;

/**
 * Class Payment
 *
 * @package Nails\Invoice\Admin\Controller
 */
class Payment extends DefaultController
{
    const CONFIG_MODEL_NAME     = 'Payment';
    const CONFIG_MODEL_PROVIDER = Constants::MODULE_SLUG;
    const CONFIG_SIDEBAR_GROUP  = 'Invoices &amp; Payments';
    const CONFIG_CAN_COPY       = false;
    const CONFIG_CAN_CREATE     = false;
    const CONFIG_CAN_DELETE     = false;
    const CONFIG_CAN_RESTORE    = false;
    const CONFIG_CAN_EDIT       = false;
    const CONFIG_CAN_VIEW       = false;
    const CONFIG_SORT_OPTIONS   = [
        'Received Date'  => 'created',
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
                'invoice',
                ['expand' => ['customer']],
            ],
        ],
    ];
    const CONFIG_INDEX_FIELDS   = [
        'Gateway'        => null,
        'Transaction ID' => 'ref',
        'Gateway ID'     => 'transaction_id',
        'Status'         => null,
        'Invoice'        => null,
        'Amount'         => 'amount.formatted',
        'Refunded'       => 'amount_refunded.formatted',
        'Fee'            => 'fee.formatted',
        'Currency'       => 'currency.code',
        'Customer'       => null,
        'Received'       => 'created',
    ];

    // --------------------------------------------------------------------------

    /**
     * Payment constructor.
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

        $this->aConfig['INDEX_FIELDS']['Gateway'] = function (\Nails\Invoice\Resource\Payment $oPayment) {
            return $oPayment->driver->getLabel();
        };

        $this->aConfig['INDEX_FIELDS']['Status'] = function (\Nails\Invoice\Resource\Payment $oPayment) {

            /** @var \Nails\Invoice\Model\Payment $oModel */
            $oModel = static::getModel();

            switch ($oPayment->status->id) {
                case $oModel::STATUS_PENDING:
                case $oModel::STATUS_SENT_FOR_AUTH:
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
                    $oPayment->status->label,
                    $oPayment->fail_msg
                        ? $oPayment->fail_msg . ' (Code: ' . $oPayment->fail_code . ')'
                        : null,
                ),
                $sStatus,
            ];
        };

        $this->aConfig['INDEX_FIELDS']['Invoice'] = function (\Nails\Invoice\Resource\Payment $oPayment) {
            return sprintf(
                '<a href="%s">%s</a><small>%s</small>',
                Invoice::url('view/' . $oPayment->invoice->id),
                $oPayment->invoice->ref,
                $oPayment->invoice->state->label,
            );
        };

        $this->aConfig['INDEX_FIELDS']['Customer'] = function (\Nails\Invoice\Resource\Payment $oPayment) {
            return $oPayment->invoice->customer
                ? sprintf(
                    '%s<small>%s</small>',
                    $oPayment->invoice->customer->label,
                    mailto($oPayment->invoice->customer->email ?? $oPayment->invoice->customer->billing_email)
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
                        return userHasPermission(Permission\Payment\View::class);
                    },
                ],
                [
                    'url'     => Invoice::url('view/{{invoice.id}}'),
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
        /** @var PaymentDriver $oDriverService */
        $oDriverService = Factory::service('PaymentDriver', Constants::MODULE_SLUG);

        /** @var \Nails\Admin\Factory\IndexFilter $oFilterDriver */
        $oFilterDriver = Factory::factory('IndexFilter', \Nails\Admin\Constants::MODULE_SLUG);
        $oFilterDriver
            ->setLabel('Gateway')
            ->setColumn('driver')
            ->addOptions(array_map(function (Component $oDriver) {

                /** @var \Nails\Admin\Factory\IndexFilter\Option $oOption */
                $oOption = Factory::factory('IndexFilterOption', \Nails\Admin\Constants::MODULE_SLUG);
                $oOption
                    ->setLabel($oDriver->name)
                    ->setValue($oDriver->slug);

                return $oOption;

            }, $oDriverService->getAll()));

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
                $oFilterDriver,
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
        if (!userHasPermission(Permission\Payment\View::class)) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        /** @var Uri $oUri */
        $oUri = Factory::service('Uri');
        /** @var \Nails\Invoice\Model\Payment $oPaymentModel */
        $oPaymentModel = Factory::model('Payment', Constants::MODULE_SLUG);

        // --------------------------------------------------------------------------

        $this->data['payment'] = $oPaymentModel->getById(
            $oUri->segment(5),
            [
                'expand' => [
                    new Expand('invoice', new Expand('customer')),
                    new Expand('source'),
                    new Expand('refunds'),
                ],
            ]
        );

        if (!$this->data['payment']) {
            show404();
        }

        $this->data['page']->title = 'View Payment &rsaquo; ' . $this->data['payment']->ref;

        Helper::loadView('view');
    }

    // --------------------------------------------------------------------------

    /**
     * Issue a refund
     *
     * @return void
     */
    public function refund()
    {
        if (!userHasPermission(Permission\Payment\Refund::class)) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        /** @var Uri $oUri */
        $oUri = Factory::service('Uri');
        /** @var Input $oInput */
        $oInput = Factory::service('Input');
        /** @var \Nails\Invoice\Model\Payment $oPaymentModel */
        $oPaymentModel = Factory::model('Payment', Constants::MODULE_SLUG);

        $iPaymentId = $oUri->segment(5);
        $sAmount    = preg_replace('/[^0-9\.]/', '', $oInput->post('amount')) ?: null;
        $sReason    = $oInput->post('reason') ?: '';
        $sRedirect  = urldecode($oInput->post('return_to')) ?: 'invoice/payment/view/' . $iPaymentId;

        try {

            $oPayment = $oPaymentModel->getById($iPaymentId);
            if (empty($oPaymentModel)) {
                throw new NailsException('Invalid payment ID.');
            }

            // --------------------------------------------------------------------------

            //  Convert the amount to its smallest unit
            $iAmount = intval($sAmount * pow(10, $oPayment->currency->decimal_precision));

            // --------------------------------------------------------------------------

            if (!$oPaymentModel->refund($iPaymentId, $iAmount, $sReason)) {
                throw new NailsException('Failed to refund payment. ' . $oPaymentModel->lastError());
            }

            $this->oUserFeedback->success('Payment refunded successfully.');

        } catch (NailsException $e) {
            $this->oUserFeedback->error($e->getMessage());
        }

        redirect($sRedirect);
    }
}
