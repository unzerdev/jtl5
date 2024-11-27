<?php

declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\Payments\Interfaces;

use JTL\Smarty\JTLSmarty;

interface HasPayButton
{
    /**
     * Return the view which will show the pay button which will replace the default "Buy Button"
     *
     * @param JTLSmarty $view
     * @return null|string
     */
    public function addPayButton(JTLSmarty $view): ?string;
}