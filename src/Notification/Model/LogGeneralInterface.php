<?php

namespace HiPay\Wallet\Mirakl\Notification\Model;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * 2017 HiPay
 *
 * NOTICE OF LICENSE
 *
 * @author    HiPay <support.wallet@hipay.com>
 * @copyright 2016 HiPay
 * @license   https://github.com/hipay/hipay-wallet-cashout-mirakl-integration/blob/master/LICENSE.md
 */
interface LogGeneralInterface
{
    /**
     * @return int|null if operator
     *
     * @Assert\NotBlank(groups={"Default"})
     * @Assert\Type(type="integer", groups={"Default"})
     * @Assert\GreaterThan(value = 0, groups={"Default"})
     * @Assert\IsNull(groups={"Operator"})
     */
    public function getMiraklId();

    /**
     * @param int $id|null if operator
     *
     * @return void
     */
    public function setMiraklId($id);

    /**
     * @return string
     */
    public function getType();

    /**
     * @param string $id
     *
     * @return void
     */
    public function setType($id);

    /**
     * @return string
     */
    public function getAction();

    /**
     * @param string $id
     *
     * @return void
     */
    public function setAction($id);

    /**
     * @return string
     */
    public function getError();

    /**
     * @param string $id
     *
     * @return void
     */
    public function setError($id);
    /**
     * @return string
     */
    public function getMessage();

    /**
     * @param string $id
     *
     * @return void
     */
    public function setMessage($id);

    /**
     * @return string
     */
    public function getDate();

    /**
     * @param string $id
     *
     * @return void
     */
    public function setDate($id);
}
