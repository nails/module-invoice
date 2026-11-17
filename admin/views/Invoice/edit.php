<?php

use Nails\Currency\Resource\Currency;
use Nails\Invoice\Constants;

?>
<div class="group-invoice invoice edit">
    <?=form_open($customer_id ? \Nails\Invoice\Admin\Controller\Invoice::url('create?customer_id=' . $customer_id) : null)?>
    <fieldset>
        <legend>Details</legend>
        <?php

        echo form_field([
            'key'     => 'customer_id',
            'label'   => 'Customer',
            'default' => !empty($invoice->customer->id) ? $invoice->customer->id : $customer_id,
            'class'   => 'js-searcher',
            'data'    => [
                'api' => 'invoice/customer',
            ],
            'info'    => '<a href="#" class="btn btn-xs btn-primary" data-bind="click: createCustomer">Create Customer</a>',
        ]);

        echo form_field([
            'key'         => 'ref',
            'label'       => 'Reference',
            'default'     => !empty($invoice->ref) ? $invoice->ref : '',
            'readonly'    => !empty($invoice->id),
            'info'        => !empty($invoice->id) ? 'Once created, the invoice reference cannot be changed' : '',
            'placeholder' => 'Leave blank to generate automatically',
        ]);

        echo form_field_dropdown([
            'key'      => 'state',
            'label'    => 'State',
            'default'  => !empty($invoice->state->id) ? $invoice->state->id : '',
            'class'    => 'select2',
            'required' => true,
            'id'       => 'invoice-state',
            'options'  => $invoiceStates,
            'data'     => [
                'bind' => 'event: {change: stateChanged()}',
            ],
        ]);

        echo form_field_date([
            'key'      => 'dated',
            'label'    => 'Dated',
            'default'  => !empty($invoice->dated->raw) ? $invoice->dated->raw : date('Y-m-d'),
            'id'       => 'invoice-dated',
            'required' => true,
            'data'     => [
                'bind' => 'event: {change: dateChanged()}',
            ],
        ]);

        echo form_field_dropdown([
            'key'      => 'currency',
            'label'    => 'Currency',
            'default'  => !empty($invoice->currency->code) ? $invoice->currency->code : date('Y-m-d'),
            'id'       => 'invoice-currency',
            'class'    => 'select2',
            'required' => true,
            'options'  => array_combine(
                arrayExtractProperty($currencies, 'code'),
                array_map(function (Currency $oCurrency) {
                    return $oCurrency->code . ' - ' . $oCurrency->label;
                }, $currencies)
            ),
            'data'     => [
                'bind' => 'event: {change: currencyChanged()}',
            ],
        ]);

        echo form_field_number([
            'key'         => 'terms',
            'label'       => 'Payment Terms',
            'default'     => !empty($invoice->terms) ? $invoice->terms : appSetting('default_payment_terms', Constants::MODULE_SLUG),
            'info'        => '<span data-bind="html: termsText()"></span>',
            'id'          => 'invoice-terms',
            'placeholder' => 'Leave blank to set the invoice to be due on receipt',
            'data'        => [
                'bind' => 'event: {keyup: termsChanged()}',
            ],
        ]);

        echo form_field_textarea([
            'key'         => 'additional_text',
            'label'       => 'Additional Text',
            'placeholder' => 'Any additional text you\'d like to show on the invoice',
            'default'     => !empty($invoice->additional_text) ? $invoice->additional_text : appSetting('default_additional_text', Constants::MODULE_SLUG),
        ]);

        ?>
    </fieldset>
    <fieldset>
        <legend>Line Items</legend>
        <p>
            Enter discounts using a negative unit cost.
        </p>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th class="quantity text-center">Quantity</th>
                        <th class="unit text-center">Unit</th>
                        <th>Details</th>
                        <th class="price text-center">Unit Cost</th>
                        <th class="tax text-center">Tax</th>
                        <th class="actions"></th>
                    </tr>
                </thead>
                <tbody data-bind="foreach: items">
                    <tr>
                        <td class="quantity text-center">
                            <input type="hidden" data-bind="attr: {name: 'items[' + $index() + '][id]'}, value: id" />
                            <input type="number" step="0.001" min="0" data-bind="attr: {name: 'items[' + $index() + '][quantity]'}, textInput: quantity" />
                        </td>
                        <td class="unit">
                            <select data-bind="
                                attr: {name: 'items[' + $index() + '][unit]'},
                                options: $root.units,
                                optionsText: 'label',
                                optionsValue: 'slug',
                                value: unit.id"></select>
                        </td>
                        <td>
                            <input type="text" placeholder="The line item's label" data-bind="attr: {name: 'items[' + $index() + '][label]'}, value: label" />
                            <textarea placeholder="The line item's description" data-bind="attr: {name: 'items[' + $index() + '][body]'}, html: body"></textarea>
                        </td>
                        <td class="price text-center">
                            <input type="number" step="0.01" min="0" data-bind="attr: {name: 'items[' + $index() + '][unit_cost]'}, textInput: unit_cost" />
                        </td>
                        <td class="tax">
                            <select data-bind="
                                attr: {name: 'items[' + $index() + '][tax_id]'},
                                options: $root.taxes,
                                optionsText: 'label',
                                optionsValue: 'id',
                                value: tax_id"></select>
                        </td>
                        <td class="actions text-center">
                            <!-- ko if: $index() != 0 -->
                            <a href="#" data-bind="click: $root.moveUp">
                                <i class="fa fa-caret-up"></i>
                            </a>
                            <!-- /ko -->
                            <a href="#" data-bind="click: $root.removeItem">
                                <b class="fa fa-times-circle text-danger"></b>
                            </a>
                            <!-- ko if: ($index() + 1) != $root.items().length -->
                            <a href="#" data-bind="click: $root.moveDown">
                                <i class="fa fa-caret-down"></i>
                            </a>
                            <!-- /ko -->
                        </td>
                    </tr>
                </tbody>
                <tfoot class="invoice-total" data-bind="visible: items().length">
                    <tr class="total-row">
                        <td colspan="6" class="text-right">
                            <strong>Sub Total:</strong>
                            <span data-bind="html: currencySymbolBefore() + calculateSubTotal() + currencySymbolAfter()"></span>
                        </td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="6" class="text-right">
                            <strong>Tax:</strong>
                            <span data-bind="html: currencySymbolBefore() + calculateTax() + currencySymbolAfter()"></span>
                        </td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="6" class="text-right">
                            <strong>Grand Total:</strong>
                            <span data-bind="html: currencySymbolBefore() + calculateGrandTotal() + currencySymbolAfter()"></span>
                        </td>
                    </tr>
                </tfoot>
                <tfoot class="add-item">
                    <tr>
                        <td colspan="6" class="add-item">
                            <button type="button" class="btn btn-block btn-sm btn-success" data-bind="click: addItem">
                                <b class="fa fa-plus"></b>
                                Add Line Item
                            </button>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </fieldset>
    <p>
        <button type="submit" class="btn btn-primary" data-bind="click: save, html: submitText(), attr: {'class': submitClass()}">
            Save Changes
        </button>
        <button type="button" class="btn btn-default pull-right" data-bind="click: preview">
            Preview
        </button>
    </p>
    <?=form_close()?>
</div>
