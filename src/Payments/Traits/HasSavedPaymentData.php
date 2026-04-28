<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\src\Payments\Traits;

use JTL\Shop;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayPaymentMethod;
use Plugin\s360_unzer_shop5\src\Utils\SessionHelper;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;

/**
 * @mixin HeidelpayPaymentMethod
 */
trait HasSavedPaymentData
{
    public function savePaymentData(BasePaymentType $payment): void
    {
       if ($this->canSavePaymentData()) {
            Shop::Container()->getDB()->insert(
                'xplugin_s360_unzer_shop5_saved_payment_data', (object) [
                    'kKunde' => $this->sessionHelper->getFrontendSession()->getCustomer()->getID(),
                    'payment_method' => $this->cModulId,
                    'payment_type_id' => $payment->getId(),
                    'data' => json_encode($this->sessionHelper->get(SessionHelper::KEY_SAVE_INFO)),
                    'created_at' => date('Y-m-d H:i:s'),
                ]
            );
        }
    }

    public function isSavedPaymentData(BasePaymentType $payment): bool
    {
        return Shop::Container()->getDB()->getSingleObject(
            'SELECT COUNT(*) as count FROM xplugin_s360_unzer_shop5_saved_payment_data WHERE payment_method = :payment_method AND payment_type_id = :payment_type_id',
            ['payment_method' => $this->cModulId, 'payment_type_id' => $payment->getId()]
        )?->count > 0;
    }

    private function canSavePaymentData()
    {
        return $this->sessionHelper->get(SessionHelper::KEY_SAVE_INFO)
            && $this->sessionHelper->getFrontendSession()->getCustomer()->getID() > 0;
    }
}