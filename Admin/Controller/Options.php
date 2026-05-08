<?php
/**
 * Admin options controller for NakoPay XenForo add-on.
 *
 * @package NakoPay/BtcPay
 */

namespace NakoPay\BtcPay\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class Options extends AbstractController
{
    public function actionIndex(ParameterBag $params)
    {
        $viewParams = [
            'options' => \XF::options(),
            'version' => \NakoPay\BtcPay\ApiClient::VERSION,
        ];

        return $this->view('NakoPay\BtcPay:Options\Index', 'nakopay_options', $viewParams);
    }
}
