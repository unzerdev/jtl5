<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\src\Controllers\Admin;


use Illuminate\Support\Collection;
use JTL\Helpers\Request;
use JTL\Plugin\Payment\Method;
use JTL\Shop;
use Plugin\s360_unzer_shop5\Bootstrap;
use Plugin\s360_unzer_shop5\src\Controllers\HasAjaxResponse;
use Plugin\s360_unzer_shop5\src\Data\AvailablePaymentMethod;
use Plugin\s360_unzer_shop5\src\KeyPairs\KeyPairEntity;
use Plugin\s360_unzer_shop5\src\KeyPairs\KeyPairModel;
use Plugin\s360_unzer_shop5\src\KeyPairs\KeyPairService;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayApiAdapter;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayPaymentMethod;
use Plugin\s360_unzer_shop5\src\Payments\PaymentMethodModuleFactory;
use Plugin\s360_unzer_shop5\src\Utils\Config;
use Plugin\s360_unzer_shop5\src\Utils\JtlLinkHelper;
use Smarty\Exception;
use SmartyException;
use Throwable;
use UnzerSDK\Exceptions\UnzerApiException;

final class AdminDashboardController extends AdminController
{
    use HasAjaxResponse;

    /**
     * @throws SmartyException
     * @throws Exception
     */
    public function handle(): string
    {
        $availablePaymentMethods = $this->getApiPaymentMethods();
        $paymentMethods = $this->getPaymentMethods($availablePaymentMethods);
        $keypairModel = new KeyPairModel(Shop::Container()->getDB());

        return $this->view('template/dashboard', [
            'formAction' => (new JtlLinkHelper())->getFullAdminTabUrl(JtlLinkHelper::ADMIN_TAB_DASHBOARD),
            'currencies' => $keypairModel->getCurrencies(),
            'keypairs' => $keypairModel->all(),
            'defaultKeypair' => $this->config->get(Config::PUBLIC_KEY),
            'paymentMethods' => $paymentMethods,
            'activePaymentMethods' => array_filter($paymentMethods, static fn(AvailablePaymentMethod $method) => $method->isValid),
            'inactivePaymentMethods' => array_filter($paymentMethods, static fn(AvailablePaymentMethod $method) => !$method->isValid),
        ]);
    }

    public function handleAjax()
    {
        $this->jsonResponse([
            'status' => 'success',
            'dashboard' => $this->handle(),
        ]);
    }

    private function getApiPaymentMethods(): Collection
    {
        $availablePaymentMethods = collect();

        try {
            // Payment Methods for default keypair
            $api = Shop::Container()->get(HeidelpayApiAdapter::class);
            $model = new KeyPairModel(Shop::Container()->getDB());

            // Set connection based on chosen keypair
            if (Request::postVar('keypair')) {
                $keypair = $model->findByPublic(Request::postVar('keypair'));

                if ($keypair?->getPaymentMethodIds()) {
                    $this->fetchForKeypair($keypair, $availablePaymentMethods);

                    return $availablePaymentMethods;
                }
            }

            // Default keypair
            $availablePaymentMethods = collect($api->fetchKeypairConfig()->getAvailablePaymentTypes())->map(function (mixed $data) {
                $data->type = mb_strtolower($data->type);
                return $data;
            })->keyBy('type');
        } catch (Throwable) {
            // Ignore
        }

        return $availablePaymentMethods;
    }

    /**
     * @param Collection $availablePaymentMethods
     * @return AvailablePaymentMethod[]
     */
    private function getPaymentMethods(Collection $availablePaymentMethods): array
    {
        $paymentMethods = [];

        // Load Payment Methods from plugin and combine them with Unzer API payment methods
        foreach ($this->plugin->getPaymentMethods()->getMethods() as $paymentMethod) {
            if (in_array($paymentMethod->getClassName(), Bootstrap::getDeprecatedPaymentMethods(), true)) {
                continue;
            }

            try {
                $availableMethod = $availablePaymentMethods->where('type', PaymentMethodModuleFactory::getResourceName($paymentMethod))->first();
                $paymentMethods[] = AvailablePaymentMethod::from(Method::create($paymentMethod->getModuleID()), $availableMethod);
            } catch (Throwable $e) {
                $this->plugin->getLogger()->error("Could not create payment method {$paymentMethod->getName()}: " . $e->getMessage());
            }
        }

        usort($paymentMethods, static fn(AvailablePaymentMethod $a, AvailablePaymentMethod $b): int => $a->sort <=> $b->sort);

        return $paymentMethods;
    }

    private function fetchForKeypair(?KeyPairEntity $keypair, Collection $availablePaymentMethods): void
    {
        $api = Shop::Container()->get(HeidelpayApiAdapter::class);

        foreach ($keypair->getPaymentMethodIds() as $paymentMethodId) {
            try {
                $api->setCurrentConnection(
                    $keypair->isB2B(),
                    $keypair->getCurrencyId(),
                    (int) $paymentMethodId
                );

                $additionalPaymentMethods = collect($api->fetchKeypairConfig()->getAvailablePaymentTypes())->map(function (mixed $data) {
                    $data->type = mb_strtolower($data->type);
                    return $data;
                })->keyBy('type');

                foreach ($additionalPaymentMethods as $type => $method) {
                    $type = mb_strtolower($type);
                    // New -> add
                    if (!$availablePaymentMethods->has($type)) {
                        $availablePaymentMethods->put($type, $method);
                        continue;
                    }

                    // Existing -> merge
                    $availablePaymentMethods[$type]->allowCustomerTypes .= "," . $method->allowCustomerTypes;
                    array_push($availablePaymentMethods[$type]->supports, ...$method->supports);
                }
            } catch (Throwable $err) {
                // Ignore
            }
        }
    }
}
