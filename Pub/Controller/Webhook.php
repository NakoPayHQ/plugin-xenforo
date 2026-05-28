<?php
/**
 * Payment provider callback (webhook) controller for NakoPay XenForo add-on.
 *
 * Route: POST /nakopay/webhook
 *
 * @package NakoPay/Payments
 */

namespace NakoPay\Payments\Pub\Controller;

use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;

class Webhook extends AbstractController
{
    public function actionIndex(ParameterBag $params)
    {
        $this->assertPostOnly();

        $client  = new \NakoPay\Payments\ApiClient();
        $rawBody = $this->request->getInputRaw();
        $sig     = $this->request->getServer('HTTP_X_NAKOPAY_SIGNATURE', '');

        if (!$client->verifyWebhook($rawBody, $sig)) {
            return $this->message('Invalid signature', 401);
        }

        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) {
            return $this->message('Invalid JSON', 400);
        }

        $type    = (string) ($payload['type'] ?? $payload['event'] ?? '');
        $data    = $payload['data']['object'] ?? $payload['data'] ?? $payload['invoice'] ?? $payload;
        $invId   = (string) ($data['id'] ?? '');
        $status  = (string) ($data['status'] ?? '');
        $txHash  = (string) ($data['tx_hash'] ?? '');
        $meta    = $data['metadata'] ?? [];

        $db = \XF::db();

        // Update local order status
        if ($invId && $status) {
            $update = ['status' => $status, 'updated_at' => time()];
            if ($txHash !== '') {
                $update['tx_hash'] = $txHash;
            }
            $db->update('xf_nakopay_orders', $update, 'nakopay_invoice_id = ?', $invId);
        }

        switch ($type) {
            case 'invoice.paid':
            case 'invoice.completed':
                $order = $db->fetchRow('SELECT * FROM xf_nakopay_orders WHERE nakopay_invoice_id = ?', $invId);
                if ($order && $order['upgrade_id']) {
                    // Activate user upgrade
                    $upgradeId = (int) $order['upgrade_id'];
                    $userId    = (int) $order['user_id'];

                    /** @var \XF\Entity\UserUpgrade $upgrade */
                    $upgrade = \XF::em()->find('XF:UserUpgrade', $upgradeId);
                    /** @var \XF\Entity\User $user */
                    $user = \XF::em()->find('XF:User', $userId);

                    if ($upgrade && $user) {
                        /** @var \XF\Service\User\Upgrade $upgradeService */
                        $upgradeService = \XF::service('XF:User\Upgrade', $upgrade, $user);
                        $upgradeService->upgrade();
                    }
                }
                break;

            case 'invoice.expired':
            case 'invoice.cancelled':
                break;
        }

        return $this->message('ok');
    }
}
