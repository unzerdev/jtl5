<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\src\Services;

use JTL\DB\DbInterface;
use JTL\DB\ReturnType;
use JTL\Session\Frontend;
use JTL\Shop;

final class SavedPaymentDataService
{
    public readonly DbInterface $database;

    public function __construct()
    {
        $this->database = Shop::Container()->getDB();
    }

    public function deleteUserPaymentData(string $paymentTypeId): bool
    {
        $affectedRows = $this->database->queryPrepared(
            'DELETE FROM xplugin_s360_unzer_shop5_saved_payment_data as pd
            WHERE pd.kKunde = :customer AND pd.payment_type_id = :id',
            ['customer' => Frontend::getCustomer()->getID(), 'id' => $paymentTypeId],
            ReturnType::AFFECTED_ROWS
        );

        return $affectedRows > 0;
    }

    /**
     * @return array<int, array{data: array, id: int, name: string, payment_type_id: string, type: 'CARD' | 'SEPA' | 'PAYPAL'}>
     */
    public function getUserPaymentData()
    {
        $savedInfo = $this->database->queryPrepared(
            'SELECT pd.id, pd.payment_type_id, pd.payment_method, pd.data, COALESCE(tzahlungsartsprache.cName, tzahlungsart.cName) as cName
            FROM xplugin_s360_unzer_shop5_saved_payment_data as pd
            LEFT JOIN tzahlungsart ON pd.payment_method = tzahlungsart.cModulId
            LEFT JOIN tzahlungsartsprache ON tzahlungsartsprache.kZahlungsart = tzahlungsart.kZahlungsart AND tzahlungsartsprache.cISOSprache = :lang
            WHERE pd.kKunde = :customer
            ORDER BY pd.payment_method ASC, pd.created_at ASC',
            ['customer' => Frontend::getCustomer()->getID(), 'lang' => Shop::Lang()->getIso()],
            ReturnType::ARRAY_OF_OBJECTS
        );

        return array_map(function($row) {
            $type = '';
            if (str_contains($row->payment_type_id, '-crd-')) {
                $type = 'CARD';
            } elseif (str_contains($row->payment_type_id, '-sdd-')) {
                $type = 'SEPA';
            } elseif (str_contains($row->payment_type_id, '-ppl-')) {
                $type = 'PAYPAL';
            }

            return [
                'id' => $row->id,
                'payment_type_id' => $row->payment_type_id,
                'type' => $type,
                'name' => $row->cName,
                'data' => json_decode($row->data, true)
            ];
        }, $savedInfo);
    }
}