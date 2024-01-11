<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\src\Controllers\Admin;

use JTL\Helpers\Form;
use UnzerSDK\Validators\PrivateKeyValidator;
use UnzerSDK\Validators\PublicKeyValidator;
use JTL\Helpers\Request;
use Plugin\s360_unzer_shop5\src\Controllers\AjaxResponse;
use Plugin\s360_unzer_shop5\src\Controllers\HasAjaxResponse;
use Plugin\s360_unzer_shop5\src\KeyPairs\KeyPairEntity;
use Plugin\s360_unzer_shop5\src\KeyPairs\KeyPairModel;
use Plugin\s360_unzer_shop5\src\Utils\JtlLinkHelper;

class AdminKeyPairsController extends AdminController implements AjaxResponse
{
    use HasAjaxResponse;

    protected KeyPairModel $model;
    protected JtlLinkHelper $linkHelper;
    protected array $paymentMethods = [];

    /**
     * @required
     */
    public function setModel(KeyPairModel $model): void
    {
        $this->model = $model;
    }

    /**
     * Setup linkhelper and payment methods
     */
    protected function prepare(): void
    {
        parent::prepare();

        $this->linkHelper = new JtlLinkHelper();

        // Get Unzer Payment Methods
        $methods = $this->plugin->getPaymentMethods()->getMethods();
        foreach ($methods as $method) {
            $this->paymentMethods[$method->getMethodID()] = $method;
        }
        uasort(
            $this->paymentMethods,
            static fn($foo, $bar) => strnatcmp(strtolower(__($foo->getName())), strtolower(__($bar->getName())))
        );
    }

    /**
     * @return never
     */
    public function handleAjax(): void
    {
        $this->warnings = [];
        $item = null;

        if (Request::getVar('action') === 'delete' && Form::validateToken() && Request::postInt('id')) {
            $this->model->delete(Request::postInt('id'));
            $this->jsonResponse([
                'status' => self::RESULT_SUCCESS,
                'listing' => $this->view('template/partials/_keypair_list', [
                    'unzerKeypairs' => [
                        'items' => $this->model->all(),
                        'currencies' => $this->model->getCurrencies(),
                        'paymentMethods' => $this->paymentMethods,
                        'url' => $this->linkHelper->getFullAdminTabUrl(JtlLinkHelper::ADMIN_TAB_ORDERS)
                    ]
                ])
            ]);
        }

        if (Request::postInt('id')) {
            $item = $this->model->find(Request::postInt('id'));
        }

        if (Request::getVar('action') === 'save' && Form::validateToken()) {
            $item = $this->saveKeypair($item);
        }

        // Response
        $data =  [
            'item' => $item,
            'items' => $this->model->all(),
            'currencies' => $this->model->getCurrencies(),
            'paymentMethods' => $this->paymentMethods,
            'url' => $this->linkHelper->getFullAdminTabUrl(JtlLinkHelper::ADMIN_TAB_ORDERS)
        ];

        $this->jsonResponse([
            'status' => self::RESULT_SUCCESS,
            'listing' => $this->view('template/partials/_keypair_list', [
                'unzerKeypairs' => $data
            ]),
            'template' => $this->view('template/partials/_keypair_item', [
                'unzerKeypairs' => $data
            ])
        ]);
    }

    public function handle(): string
    {
        return $this->view('template/keypairs', [
            'unzerKeypairs' => [
                'items' => $this->model->all(),
                'currencies' => $this->model->getCurrencies(),
                'paymentMethods' => $this->paymentMethods,
                'url' => $this->linkHelper->getFullAdminTabUrl(JtlLinkHelper::ADMIN_TAB_ORDERS)
            ]
        ]);
    }

    private function saveKeypair(?KeyPairEntity $item): KeyPairEntity
    {
        $privateKey = Request::postVar('privateKey', '');
        $info = password_get_info($privateKey);

        // If private key is hashed -> no new user input -> use existing one
        if (!empty($info) && $info['algo'] > 0 && $item) {
            $privateKey = $item->getPrivateKey();
        }

        // Validate Keys
        $valid = true;
        if (!PrivateKeyValidator::validate($privateKey)) {
            $this->addError(__('Ungültiger Private Key.'));
            $valid = false;
        }

        if (!PublicKeyValidator::validate(Request::postVar('publicKey', ''))) {
            $this->addError(
                __('Ungültiger Public Key. Bitte stellen Sie sicher, dass sie hier Ihren Public Key und nicht Ihren Private Key angeben!')
            );
            $valid = false;
        }

        $item = new KeyPairEntity(
            $privateKey,
            Request::postVar('publicKey', ''),
            Request::postVar('isB2B') === 'on' || Request::postVar('isB2B') === 'true',
            Request::postInt('currency'),
            Request::postInt('paymentMethod'),
        );

        if (Request::postInt('id')) {
            $item->setId(Request::postInt('id'));
        }

        // Save Model if everything seems valid
        if ($valid) {
            $this->model->save($item);
            $this->addSuccess(__('Die Einstellungen wurden erfolgreich gespeichert.'));
        }

        return $item;
    }
}
