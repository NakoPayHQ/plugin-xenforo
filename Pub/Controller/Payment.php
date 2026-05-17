<?php
/**
 * Customer-facing payment controller for NakoPay XenForo add-on.
 *
 * GET/POST /nakopay/pay   - create invoice and show checkout
 * GET      /nakopay/poll  - JSON status poll
 *
 * @package NakoPay/Payments
 */

namespace NakoPay\Payments\Pub\Controller;

use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;

class Payment extends AbstractController
{
    public function actionIndex(ParameterBag $params)
    {
        $visitor = \XF::visitor();
        if (!$visitor->user_id) {
            return $this->rerouteController('XF:Login', 'form');
        }

        $input = $this->filter([
            'amount'      => 'str',
            'description' => 'str',
            'upgrade_id'  => 'uint',
            'item_id'     => 'str',
        ]);

        $client   = new \NakoPay\Payments\ApiClient();
        $db       = \XF::db();
        $userId   = $visitor->user_id;
        $itemId   = $input['item_id'] ?: ('upgrade-' . $input['upgrade_id']);

        // Check for existing open order
        $order = $db->fetchRow(
            "SELECT * FROM xf_nakopay_orders
             WHERE user_id = ? AND item_id = ? AND status NOT IN ('paid', 'expired', 'cancelled')
             ORDER BY order_id DESC LIMIT 1",
            [$userId, $itemId]
        );

        if (!$order) {
            $amount = (float) $input['amount'];
            if ($amount <= 0) {
                return $this->error('Invalid payment amount.');
            }

            $resp = $client->createInvoice([
                'amount'         => $amount,
                'currency'       => $client->getCurrency(),
                'coin'           => 'BTC',
                'description'    => $input['description'] ?: 'XenForo payment',
                'customer_email' => $visitor->email,
                'xf_user_id'     => $userId,
                'xf_upgrade_id'  => $input['upgrade_id'] ?: null,
                'xf_item_id'     => $itemId,
            ]);

            if (empty($resp['_ok']) || empty($resp['id'])) {
                $err = $resp['error']['message'] ?? $resp['_error'] ?? 'unknown error';
                return $this->error('NakoPay API error: ' . $err);
            }

            $db->insert('xf_nakopay_orders', [
                'user_id'             => $userId,
                'item_id'             => $itemId,
                'upgrade_id'          => $input['upgrade_id'],
                'nakopay_invoice_id'  => $resp['id'],
                'address'             => $resp['address'] ?? '',
                'coin'                => $resp['coin'] ?? 'BTC',
                'currency'            => $resp['currency'] ?? $client->getCurrency(),
                'amount_fiat'         => $resp['amount'] ?? $amount,
                'amount_crypto'       => $resp['amount_crypto'] ?? 0,
                'status'              => $resp['status'] ?? 'pending',
                'checkout_url'        => $resp['checkout_url'] ?? '',
                'bip21'               => $resp['bip21'] ?? '',
                'created_at'          => time(),
                'updated_at'          => time(),
            ]);

            $order = $db->fetchRow('SELECT * FROM xf_nakopay_orders WHERE nakopay_invoice_id = ?', $resp['id']);
        }

        $viewParams = [
            'order' => $order,
        ];

        return $this->view('NakoPay\Payments:Payment\Checkout', 'nakopay_checkout', $viewParams);
    }

    public function actionPoll(ParameterBag $params)
    {
        $invoiceId = preg_replace('/[^a-zA-Z0-9_]/', '', $this->filter('invoice_id', 'str'));
        $db = \XF::db();

        $order = $db->fetchRow('SELECT * FROM xf_nakopay_orders WHERE nakopay_invoice_id = ?', $invoiceId);
        if (!$order) {
            return $this->error('Unknown invoice', 404);
        }

        // Refresh from API
        $client = new \NakoPay\Payments\ApiClient();
        $api = $client->getInvoice($invoiceId);
        if (!empty($api['_ok'])) {
            $update = ['status' => $api['status'] ?? $order['status'], 'updated_at' => time()];
            if (!empty($api['tx_hash'])) {
                $update['tx_hash'] = $api['tx_hash'];
            }
            $db->update('xf_nakopay_orders', $update, 'nakopay_invoice_id = ?', $invoiceId);
            $order = array_merge($order, $update);
        }

        $this->setResponseType('json');
        return $this->view('NakoPay\Payments:Payment\Poll', '', [
            'status'        => $order['status'],
            'address'       => $order['address'],
            'amount_crypto' => $order['amount_crypto'],
            'coin'          => $order['coin'],
            'currency'      => $order['currency'],
            'amount_fiat'   => $order['amount_fiat'],
            'tx_hash'       => $order['tx_hash'] ?? null,
            'redirect'      => in_array($order['status'], ['paid', 'completed'], true)
                ? \XF::app()->router('public')->buildLink('full:index')
                : null,
        ]);
    }
}
