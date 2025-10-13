<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Concerns;

use Pristavu\Anaf\Requests\TaxPayer\BalanceSheetRequest;
use Pristavu\Anaf\Requests\TaxPayer\VatStatusRequest;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

/**
 * Trait to support entity-related operations such as fetching entity info and balance sheets.
 */
trait SupportTaxPayer
{
    /**
     * Get entity vatStatus by Fiscal Identification Code and an optional date.
     *
     * @param  string|null  $date  The date for which to retrieve the entity information in 'Y-m-d' format. Defaults to today's date if null.
     *
     * @throws FatalRequestException
     * @throws RequestException
     *
     * @see https://static.anaf.ro/static/10/Anaf/Informatii_R/Servicii_web/doc_WS_V9.txt
     */
    public function vatStatus(?string $date): array|object
    {
        $date ??= now()->format('Y-m-d');
        // $date = '2012-03-01'; // TODO: remove this line after testing
        $request = new VatStatusRequest(cif: $this->cif, date: $date);

        return $this->send($request)->dtoOrFail();
    }

    /**
     * Get entity balance sheet for a specific year by Fiscal Identification Code.
     *
     * @param  int  $year  The year for which to retrieve the balance sheet.
     *
     * @throws FatalRequestException
     * @throws RequestException
     *
     * @see https://static.anaf.ro/static/10/Anaf/Informatii_R/doc_WS_Bilant_V1.txt
     */
    public function balanceSheet(int $year): array|object
    {

        // $date = '2012-03-01'; // TODO: remove this line after testing
        $request = new BalanceSheetRequest(cif: $this->cif, year: $year);

        return $this->send($request)->dtoOrFail();
    }
}
