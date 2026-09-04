<?php

namespace Tests\Validator;

use Nails\Common\Exception\ValidationException;
use Nails\Currency\Service\Currency;
use Nails\Invoice\Validator\Invoice;
use PHPUnit\Framework\TestCase;

class InvoiceTest extends TestCase
{
    /**
     * A currency service double with the given currencies enabled
     *
     * @param string[] $aEnabled
     */
    private function currency(array $aEnabled = ['GBP', 'EUR']): Currency
    {
        return new class($aEnabled) extends Currency {
            public function __construct(private readonly array $aEnabled)
            {
                //  Deliberately not calling the parent; it reads the currency database from disk
            }

            public function getAllEnabled(): array
            {
                return array_map(fn(string $sCode) => (object) ['code' => $sCode], $this->aEnabled);
            }
        };
    }

    private function cleanPost(): array
    {
        return [
            'ref'             => 'INV-001',
            'state'           => 'OPEN',
            'dated'           => '2026-09-01',
            'currency'        => 'GBP',
            'terms'           => '30',
            'customer_id'     => '',
            'additional_text' => '',
        ];
    }

    private function errorsFor(array $aData, array $aEnabled = ['GBP', 'EUR']): array
    {
        try {
            (new Invoice($this->currency($aEnabled)))->run($aData);
            return [];
        } catch (ValidationException $e) {
            return $e->getData();
        }
    }

    // --------------------------------------------------------------------------

    public function test_a_clean_invoice_passes(): void
    {
        self::assertSame([], $this->errorsFor($this->cleanPost()));
    }

    public function test_state_date_and_currency_are_required(): void
    {
        self::assertSame(['state', 'dated', 'currency'], array_keys($this->errorsFor([])));
    }

    public function test_the_date_must_be_valid(): void
    {
        $aErrors = $this->errorsFor(array_merge($this->cleanPost(), ['dated' => '2026-13-40']));
        self::assertSame(['dated'], array_keys($aErrors));
    }

    public function test_the_currency_must_be_enabled(): void
    {
        $aErrors = $this->errorsFor(array_merge($this->cleanPost(), ['currency' => 'USD']));
        self::assertSame(['currency' => 'Invalid currency.'], $aErrors);

        self::assertSame([], $this->errorsFor(array_merge($this->cleanPost(), ['currency' => 'USD']), ['USD']));
    }

    public function test_terms_must_be_a_whole_number_of_days(): void
    {
        $aErrors = $this->errorsFor(array_merge($this->cleanPost(), ['terms' => '-5']));
        self::assertSame(['terms'], array_keys($aErrors));

        self::assertSame([], $this->errorsFor(array_merge($this->cleanPost(), ['terms' => ''])));
    }

    public function test_values_are_trimmed_in_the_validated_data(): void
    {
        $oValidator = (new Invoice($this->currency()))->run(array_merge($this->cleanPost(), [
            'ref'      => '  INV-001  ',
            'currency' => ' GBP ',
        ]));

        $aData = $oValidator->getValidatedData();

        self::assertSame('INV-001', $aData['ref']);
        self::assertSame('GBP', $aData['currency']);
    }
}
