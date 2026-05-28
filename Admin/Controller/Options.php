<?php
/**
 * Admin options controller for NakoPay XenForo add-on.
 *
 * @package NakoPay/Payments
 */

namespace NakoPay\Payments\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class Options extends AbstractController
{
    public function actionIndex(ParameterBag $params)
    {
        $viewParams = [
            'options' => \XF::options(),
            'version' => \NakoPay\Payments\ApiClient::VERSION,
        ];

        return $this->view('NakoPay\Payments:Options\Index', 'nakopay_options', $viewParams);
    }
}
