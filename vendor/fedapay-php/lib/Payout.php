<?php

namespace FedaPay;

use FedaPay\Util\Util;

/**
 * Class Payout
 *
 * @property int $id
 * @property string $reference
 * @property string $amount
 * @property string $status
 * @property int $customer_id
 * @property int $balance_id
 * @property string $mode
 * @property int $last_error_code
 * @property string $last_error_message
 * @property string $created_at
 * @property string $updated_at
 * @property string $scheduled_at
 * @property string $sent_at
 * @property string $failed_at
 * @property string $deleted_at
 *
 * @package FedaPay
 */
class Payout extends Resource
{
    /**
     * @param array|string $id The ID of the payout to retrieve
     * @param array|null $headers
     *
     * @return Payout
     */
    public static function retrieve($id, $headers = [])
    {
        return self::_retrieve($id, $headers);
    }

    /**
     * @param array|null $params
     * @param array|string|null $headers
     *
     * @return Collection of Payouts
     */
    public static function all($params = [], $headers = [])
    {
        return self::_all($params, $headers);
    }

    /**
     * @param array|null $params
     * @param array|string|null $headers
     *
     * @return Payout The created payout.
     */
    public static function create($params = [], $headers = [])
    {
        return self::_create($params, $headers);
    }

    /**
     * @param string $id The ID of the customer to update.
     * @param array|null $params
     * @param array|string|null $options
     *
     * @return Payout The updated payout.
     */
    public static function update($id, $params = [], $headers = [])
    {
        return self::_update($id, $params, $headers);
    }

    /**
     * @param array|string|null $headers
     *
     * @return Payout The saved payout.
     */
    public function save($headers = [])
    {
        return $this->_save($headers);
    }

    /**
     * @param array $headers
     *
     * @return void
     */
    public function delete($headers = [])
    {
        return $this->_delete($headers);
    }

    /**
     * @param array|string|null $params
     * @param array|string|null $headers
     *
     * @return Payout.
     */
    protected function _start($params, $headers)
    {
        $path = static::resourcePath('start');

        list($response, $opts) = static::_staticRequest('put', $path, $params, $headers);

        $object =  Util::arrayToFedaPayObject($response, $opts);
        $this->refreshFrom($object->payouts[0], $opts);

        return $this;
    }

    /**
     * @param array|string|null $params
     * @param array|string|null $headers
     *
     * @return FedaPayObject.
     */
    protected static function _startAll($params, $headers)
    {
        $path = static::resourcePath('start');

        list($response, $opts) = static::_staticRequest('put', $path, $params, $headers);

        return Util::arrayToFedaPayObject($response, $opts);
    }

    /**
     * Start the payout
     * @return FedaPay\FedaPayObject
     */
    public function schedule($scheduled_at, $params = [], $headers = [])
    {
        $scheduled_at = Util::toDateString($scheduled_at);

        $_params = [
            'payouts' => [[
                'id' => $this->id,
                'scheduled_at' => $scheduled_at
            ]]
        ];
        $params = array_merge($_params, $params);

        return $this->_start($params, $headers);
    }

    /**
     * Send the payout now
     * @return FedaPay\FedaPayObject
     */
    public function sendNow($params = [], $headers = [])
    {
        $_params = [
            'payouts' => [[
                'id' => $this->id
            ]]
        ];

        $params = array_merge($_params, $params);

        return $this->_start($params, $headers);
    }

    /**
     * Start a scheduled payout
     *
     * @param array $payouts list of payouts id to start. One at least
     * @param null|DateTime $scheduled_at If null, payouts should be send now.
     * @param array $headers
     * @return FedaPay\FedaPayObject
     */
    public static function scheduleAll($payouts = [], $params = [], $headers = [])
    {
        $items = [];

        foreach ($payouts as $payout) {
            $item = [];
            if (!array_key_exists('id', $payout)) {
                throw new \InvalidArgumentException(
                    'Invalid id argument. You must specify payout id.'
                );
            }
            $item['id'] = $payout['id'];

            if (array_key_exists('scheduled_at', $payout)) {
                $item['scheduled_at'] = Util::toDateString($payout['scheduled_at']);
            }

            $items[] = $item;
        }

        $_params = [
            'payouts' => $items
        ];
        $params = array_merge($_params, $params);

        return self::_startAll($params, $headers);
    }

    /**
     * Send all payouts now
     *
     * @param array $payouts list of payouts id to start. One at least
     * @param array $params If null, payouts should be send now.
     * @param array $headers
     * @return FedaPay\FedaPayObject
     */
    public static function sendAllNow($payouts = [], $params = [], $headers = [])
    {
        $items = [];

        foreach ($payouts as $payout) {
            $item = [];
            if (!array_key_exists('id', $payout)) {
                throw new \InvalidArgumentException(
                    'Invalid id argument. You must specify payout id.'
                );
            }
            $item['id'] = $payout['id'];

            $items[] = $item;
        }

        $_params = [
            'payouts' => $items
        ];
        $params = array_merge($_params, $params);

        return self::_startAll($params, $headers);
    }
}
