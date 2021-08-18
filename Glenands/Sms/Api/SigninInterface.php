<?php
/**
 * Copyright (C) 2021 Empye Technologies LLP
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 */

namespace Glenands\Sms\Api;

/**
 * @api
 */
interface SigninInterface
{
    /**
     * Return if module is enabled.
     *
     * @see \Glenands\Sms\Helper\Data\Helper
     * @param string|null $scopeCode
     * @return bool
     */
    public function isEnabled($scopeCode);
    
    /**
     * @see \Glenands\Sms\Helper\Data\Helper
     * @param string|null $scopeCode
     * @return string
     */
    public function getSigninMode($scopeCode);

    /**
     * Load customer object by phone attribute.
     *
     * @param string $phone
     * @return boolean|object
     */
    public function getByPhoneNumber(string $phone);
}
