<?php

use Nails\Invoice\Factory;
use Nails\Invoice\Model;
use Nails\Invoice\Resource;
use Nails\Invoice\Service;

return [
    'services'  => [
        'InvoiceSkin'   => function (): Service\Invoice\Skin {
            if (class_exists('\App\Invoice\Service\Invoice\Skin')) {
                return new \App\Invoice\Service\Invoice\Skin();
            } else {
                return new Service\Invoice\Skin();
            }
        },
        'PaymentDriver' => function (): Service\PaymentDriver {
            if (class_exists('\App\Invoice\Service\PaymentDriver')) {
                return new \App\Invoice\Service\PaymentDriver();
            } else {
                return new Service\PaymentDriver();
            }
        },
    ],
    'models'    => [
        'Customer'     => function (): Model\Customer {
            if (class_exists('\App\Invoice\Model\Customer')) {
                return new \App\Invoice\Model\Customer();
            } else {
                return new Model\Customer();
            }
        },
        'Invoice'      => function (): Model\Invoice {
            if (class_exists('\App\Invoice\Model\Invoice')) {
                return new \App\Invoice\Model\Invoice();
            } else {
                return new Model\Invoice();
            }
        },
        'InvoiceEmail' => function (): Model\Invoice\Email {
            if (class_exists('\App\Invoice\Model\Invoice\Email')) {
                return new \App\Invoice\Model\Invoice\Email();
            } else {
                return new Model\Invoice\Email();
            }
        },
        'InvoiceItem'  => function (): Model\Invoice\Item {
            if (class_exists('\App\Invoice\Model\Invoice\Item')) {
                return new \App\Invoice\Model\Invoice\Item();
            } else {
                return new Model\Invoice\Item();
            }
        },
        'Payment'      => function (): Model\Payment {
            if (class_exists('\App\Invoice\Model\Payment')) {
                return new \App\Invoice\Model\Payment();
            } else {
                return new Model\Payment();
            }
        },
        'Refund'       => function (): Model\Refund {
            if (class_exists('\App\Invoice\Model\Refund')) {
                return new \App\Invoice\Model\Refund();
            } else {
                return new Model\Refund();
            }
        },
        'Source'       => function (): Model\Source {
            if (class_exists('\App\Invoice\Model\Source')) {
                return new \App\Invoice\Model\Source();
            } else {
                return new Model\Source();
            }
        },
        'Tax'          => function (): Model\Tax {
            if (class_exists('\App\Invoice\Model\Tax')) {
                return new \App\Invoice\Model\Tax();
            } else {
                return new Model\Tax();
            }
        },
    ],
    'factories' => [
        'ChargeRequest'          => function (): Factory\ChargeRequest {
            if (class_exists('\App\Invoice\Factory\ChargeRequest')) {
                return new \App\Invoice\Factory\ChargeRequest();
            } else {
                return new Factory\ChargeRequest();
            }
        },
        'ChargeResponse'         => function (): Factory\ChargeResponse {
            if (class_exists('\App\Invoice\Factory\ChargeResponse')) {
                return new \App\Invoice\Factory\ChargeResponse();
            } else {
                return new Factory\ChargeResponse();
            }
        },
        'CompleteRequest'        => function (): Factory\CompleteRequest {
            if (class_exists('\App\Invoice\Factory\CompleteRequest')) {
                return new \App\Invoice\Factory\CompleteRequest();
            } else {
                return new Factory\CompleteRequest();
            }
        },
        'CompleteResponse'       => function (): Factory\CompleteResponse {
            if (class_exists('\App\Invoice\Factory\CompleteResponse')) {
                return new \App\Invoice\Factory\CompleteResponse();
            } else {
                return new Factory\CompleteResponse();
            }
        },
        'EmailInvoiceSend'       => function (): Factory\Email\Invoice\Send {
            if (class_exists('\App\Invoice\Factory\Email\Invoice\Send')) {
                return new \App\Invoice\Factory\Email\Invoice\Send();
            } else {
                return new Factory\Email\Invoice\Send();
            }
        },
        'EmailPaymentComplete'   => function (): Factory\Email\Payment\Complete {
            if (class_exists('\App\Invoice\Factory\Email\Payment\Complete')) {
                return new \App\Invoice\Factory\Email\Payment\Complete();
            } else {
                return new Factory\Email\Payment\Complete();
            }
        },
        'EmailPaymentProcessing' => function (): Factory\Email\Payment\Processing {
            if (class_exists('\App\Invoice\Factory\Email\Payment\Processing')) {
                return new \App\Invoice\Factory\Email\Payment\Processing();
            } else {
                return new Factory\Email\Payment\Processing();
            }
        },
        'EmailRefundComplete'    => function (): Factory\Email\Refund\Complete {
            if (class_exists('\App\Invoice\Factory\Email\Refund\Complete')) {
                return new \App\Invoice\Factory\Email\Refund\Complete();
            } else {
                return new Factory\Email\Refund\Complete();
            }
        },
        'EmailRefundProcessing'  => function (): Factory\Email\Refund\Processing {
            if (class_exists('\App\Invoice\Factory\Email\Refund\Processing')) {
                return new \App\Invoice\Factory\Email\Refund\Processing();
            } else {
                return new Factory\Email\Refund\Processing();
            }
        },
        'Invoice'                => function (): Factory\Invoice {
            if (class_exists('\App\Invoice\Factory\Invoice')) {
                return new \App\Invoice\Factory\Invoice();
            } else {
                return new Factory\Invoice();
            }
        },
        'InvoiceCallbackData'    => function (): Factory\Invoice\CallbackData {
            if (class_exists('\App\Invoice\Factory\Invoice\CallbackData')) {
                return new \App\Invoice\Factory\Invoice\CallbackData();
            } else {
                return new Factory\Invoice\CallbackData();
            }
        },
        'InvoiceItem'            => function (): Factory\Invoice\Item {
            if (class_exists('\App\Invoice\Factory\Invoice\Item')) {
                return new \App\Invoice\Factory\Invoice\Item();
            } else {
                return new Factory\Invoice\Item();
            }
        },
        'InvoicePaymentData'     => function (): Factory\Invoice\PaymentData {
            if (class_exists('\App\Invoice\Factory\Invoice\PaymentData')) {
                return new \App\Invoice\Factory\Invoice\PaymentData();
            } else {
                return new Factory\Invoice\PaymentData();
            }
        },
        'RefundRequest'          => function (): Factory\RefundRequest {
            if (class_exists('\App\Invoice\Factory\RefundRequest')) {
                return new \App\Invoice\Factory\RefundRequest();
            } else {
                return new Factory\RefundRequest();
            }
        },
        'RefundResponse'         => function (): Factory\RefundResponse {
            if (class_exists('\App\Invoice\Factory\RefundResponse')) {
                return new \App\Invoice\Factory\RefundResponse();
            } else {
                return new Factory\RefundResponse();
            }
        },
        'ScaRequest'             => function (): Factory\ScaRequest {
            if (class_exists('\App\Invoice\Factory\ScaRequest')) {
                return new \App\Invoice\Factory\ScaRequest();
            } else {
                return new Factory\ScaRequest();
            }
        },
        'ScaResponse'            => function (): Factory\ScaResponse {
            if (class_exists('\App\Invoice\Factory\ScaResponse')) {
                return new \App\Invoice\Factory\ScaResponse();
            } else {
                return new Factory\ScaResponse();
            }
        },
    ],
    'resources' => [
        'Customer'                   => function ($resource, $model): Resource\Customer {
            if (class_exists('\App\Invoice\Resource\Customer')) {
                return new \App\Invoice\Resource\Customer($resource, $model);
            } else {
                return new Resource\Customer($resource, $model);
            }
        },
        'Invoice'                    => function ($resource, $model): Resource\Invoice {
            if (class_exists('\App\Invoice\Resource\Invoice')) {
                return new \App\Invoice\Resource\Invoice($resource, $model);
            } else {
                return new Resource\Invoice($resource, $model);
            }
        },
        'InvoiceDataCallback'        => function ($resource): Resource\Invoice\Data\Callback {
            //  @todo (Pablo 2025-07-15) - this should be a factory
            if (class_exists('\App\Invoice\Resource\Invoice\Data\Callback')) {
                return new \App\Invoice\Resource\Invoice\Data\Callback($resource);
            } else {
                return new Resource\Invoice\Data\Callback($resource);
            }
        },
        'InvoiceDataPayment'         => function ($resource): Resource\Invoice\Data\Payment {
            //  @todo (Pablo 2025-07-15) - this should be a factory
            if (class_exists('\App\Invoice\Resource\Invoice\Data\Payment')) {
                return new \App\Invoice\Resource\Invoice\Data\Payment($resource);
            } else {
                return new Resource\Invoice\Data\Payment($resource);
            }
        },
        'InvoiceEmail'               => function ($resource, $model): Resource\Invoice\Email {
            if (class_exists('\App\Invoice\Resource\Invoice\Email')) {
                return new \App\Invoice\Resource\Invoice\Email($resource, $model);
            } else {
                return new Resource\Invoice\Email($resource, $model);
            }
        },
        'InvoiceItem'                => function ($resource, $model): Resource\Invoice\Item {
            if (class_exists('\App\Invoice\Resource\Invoice\Item')) {
                return new \App\Invoice\Resource\Invoice\Item($resource, $model);
            } else {
                return new Resource\Invoice\Item($resource, $model);
            }
        },
        'InvoiceItemDataCallback'    => function ($resource): Resource\Invoice\Item\Data\Callback {
            //  @todo (Pablo 2025-07-15) - this should be a factory
            if (class_exists('\App\Invoice\Resource\Invoice\Item\Data\Callback')) {
                return new \App\Invoice\Resource\Invoice\Item\Data\Callback($resource);
            } else {
                return new Resource\Invoice\Item\Data\Callback($resource);
            }
        },
        'InvoiceItemTotals'          => function ($resource): Resource\Invoice\Item\Totals {
            //  @todo (Pablo 2025-07-15) - this should be a factory
            if (class_exists('\App\Invoice\Resource\Invoice\Item\Totals')) {
                return new \App\Invoice\Resource\Invoice\Item\Totals($resource);
            } else {
                return new Resource\Invoice\Item\Totals($resource);
            }
        },
        'InvoiceItemTotalsFormatted' => function ($resource): Resource\Invoice\Item\Totals\Formatted {
            //  @todo (Pablo 2025-07-15) - this should be a factory
            if (class_exists('\App\Invoice\Resource\Invoice\Item\Totals\Formatted')) {
                return new \App\Invoice\Resource\Invoice\Item\Totals\Formatted($resource);
            } else {
                return new Resource\Invoice\Item\Totals\Formatted($resource);
            }
        },
        'InvoiceItemTotalsRaw'       => function ($resource): Resource\Invoice\Item\Totals\Raw {
            //  @todo (Pablo 2025-07-15) - this should be a factory
            if (class_exists('\App\Invoice\Resource\Invoice\Item\Totals\Raw')) {
                return new \App\Invoice\Resource\Invoice\Item\Totals\Raw($resource);
            } else {
                return new Resource\Invoice\Item\Totals\Raw($resource);
            }
        },
        'InvoiceItemUnit'            => function ($resource): Resource\Invoice\Item\Unit {
            //  @todo (Pablo 2025-07-15) - this should be a factory
            if (class_exists('\App\Invoice\Resource\Invoice\Item\Unit')) {
                return new \App\Invoice\Resource\Invoice\Item\Unit($resource);
            } else {
                return new Resource\Invoice\Item\Unit($resource);
            }
        },
        'InvoiceItemUnitCost'        => function ($resource): Resource\Invoice\Item\UnitCost {
            //  @todo (Pablo 2025-07-15) - this should be a factory
            if (class_exists('\App\Invoice\Resource\Invoice\Item\UnitCost')) {
                return new \App\Invoice\Resource\Invoice\Item\UnitCost($resource);
            } else {
                return new Resource\Invoice\Item\UnitCost($resource);
            }
        },
        'InvoiceState'               => function ($resource): Resource\Invoice\State {
            //  @todo (Pablo 2025-07-15) - this should be a factory
            if (class_exists('\App\Invoice\Resource\Invoice\State')) {
                return new \App\Invoice\Resource\Invoice\State($resource);
            } else {
                return new Resource\Invoice\State($resource);
            }
        },
        'InvoiceTotals'              => function ($resource): Resource\Invoice\Totals {
            //  @todo (Pablo 2025-07-15) - this should be a factory
            if (class_exists('\App\Invoice\Resource\Invoice\Totals')) {
                return new \App\Invoice\Resource\Invoice\Totals($resource);
            } else {
                return new Resource\Invoice\Totals($resource);
            }
        },
        'InvoiceTotalsFormatted'     => function ($resource): Resource\Invoice\Totals\Formatted {
            //  @todo (Pablo 2025-07-15) - this should be a factory
            if (class_exists('\App\Invoice\Resource\Invoice\Totals\Formatted')) {
                return new \App\Invoice\Resource\Invoice\Totals\Formatted($resource);
            } else {
                return new Resource\Invoice\Totals\Formatted($resource);
            }
        },
        'InvoiceTotalsRaw'           => function ($resource): Resource\Invoice\Totals\Raw {
            //  @todo (Pablo 2025-07-15) - this should be a factory
            if (class_exists('\App\Invoice\Resource\Invoice\Totals\Raw')) {
                return new \App\Invoice\Resource\Invoice\Totals\Raw($resource);
            } else {
                return new Resource\Invoice\Totals\Raw($resource);
            }
        },
        'InvoiceUrls'                => function ($resource): Resource\Invoice\Urls {
            //  @todo (Pablo 2025-07-15) - this should be a factory
            if (class_exists('\App\Invoice\Resource\Invoice\Urls')) {
                return new \App\Invoice\Resource\Invoice\Urls($resource);
            } else {
                return new Resource\Invoice\Urls($resource);
            }
        },
        'Payment'                    => function ($resource, $model): Resource\Payment {
            if (class_exists('\App\Invoice\Resource\Payment')) {
                return new \App\Invoice\Resource\Payment($resource, $model);
            } else {
                return new Resource\Payment($resource, $model);
            }
        },
        'PaymentAmount'              => function ($resource): Resource\Payment\Amount {
            //  @todo (Pablo 2025-07-15) - this should be a factory
            if (class_exists('\App\Invoice\Resource\Payment\Amount')) {
                return new \App\Invoice\Resource\Payment\Amount($resource);
            } else {
                return new Resource\Payment\Amount($resource);
            }
        },
        'PaymentDataSca'             => function ($resource): Resource\Payment\Data\Sca {
            //  @todo (Pablo 2025-07-15) - this should be a factory
            if (class_exists('\App\Invoice\Resource\Payment\Data\Sca')) {
                return new \App\Invoice\Resource\Payment\Data\Sca($resource);
            } else {
                return new Resource\Payment\Data\Sca($resource);
            }
        },
        'PaymentStatus'              => function ($resource): Resource\Payment\Status {
            //  @todo (Pablo 2025-07-15) - this should be a factory
            if (class_exists('\App\Invoice\Resource\Payment\Status')) {
                return new \App\Invoice\Resource\Payment\Status($resource);
            } else {
                return new Resource\Payment\Status($resource);
            }
        },
        'PaymentUrls'                => function ($resource): Resource\Payment\Urls {
            //  @todo (Pablo 2025-07-15) - this should be a factory
            if (class_exists('\App\Invoice\Resource\Payment\Urls')) {
                return new \App\Invoice\Resource\Payment\Urls($resource);
            } else {
                return new Resource\Payment\Urls($resource);
            }
        },
        'Refund'                     => function ($resource, $model): Resource\Refund {
            if (class_exists('\App\Invoice\Resource\Refund')) {
                return new \App\Invoice\Resource\Refund($resource, $model);
            } else {
                return new Resource\Refund($resource, $model);
            }
        },
        'RefundAmount'               => function ($resource): Resource\Refund\Amount {
            //  @todo (Pablo 2025-07-15) - this should be a factory
            if (class_exists('\App\Invoice\Resource\Refund\Amount')) {
                return new \App\Invoice\Resource\Refund\Amount($resource);
            } else {
                return new Resource\Refund\Amount($resource);
            }
        },
        'RefundStatus'               => function ($resource): Resource\Refund\Status {
            //  @todo (Pablo 2025-07-15) - this should be a factory
            if (class_exists('\App\Invoice\Resource\Refund\Status')) {
                return new \App\Invoice\Resource\Refund\Status($resource);
            } else {
                return new Resource\Refund\Status($resource);
            }
        },
        'Source'                     => function ($resource, $model): Resource\Source {
            if (class_exists('\App\Invoice\Resource\Source')) {
                return new \App\Invoice\Resource\Source($resource, $model);
            } else {
                return new Resource\Source($resource, $model);
            }
        },
        'Tax'                        => function ($resource, $model): Resource\Tax {
            if (class_exists('\App\Invoice\Resource\Tax')) {
                return new \App\Invoice\Resource\Tax($resource, $model);
            } else {
                return new Resource\Tax($resource, $model);
            }
        },
    ],
];
