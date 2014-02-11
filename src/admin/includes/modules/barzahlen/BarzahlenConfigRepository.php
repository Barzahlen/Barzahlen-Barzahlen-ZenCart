<?php
/**
 * Barzahlen Payment Module (Zen Cart)
 *
 * NOTICE OF LICENSE
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 2 of the License
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @copyright   Copyright (c) 2013 Zerebro Internet GmbH (http://www.barzahlen.de)
 * @author      Mathias Hertlein
 * @license     http://opensource.org/licenses/GPL-2.0  GNU General Public License, version 2 (GPL-2.0)
 */

/**
 * Provides methods for access config
 */
class BarzahlenConfigRepository
{
    /**
     * @var queryFactory
     */
    private $db;


    /**
     * @param queryFactory $db
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Gets the last update date
     *
     * @return bool|int
     */
    public function getLastUpdateDate()
    {
        $sql = <<<SQL
SELECT configuration_value
FROM configuration
WHERE configuration_key = 'MODULE_PAYMENT_BARZAHLEN_LAST_UPDATE_CHECK'
SQL;

        $result = $this->db->Execute($sql);

        if ($result && is_array($result->fields)) {
            $lastUpdate = strtotime($result->fields['configuration_value']);
        } else {
            $lastUpdate = false;
        }

        return $lastUpdate;
    }

    /**
     * Creates a new config entry with the current time as last update date
     */
    public function insertLastUpdateDate()
    {
        $sql = <<<SQL
INSERT INTO configuration (
    configuration_key,
    configuration_value,
    configuration_group_id,
    last_modified,
    date_added
)
VALUES (
    'MODULE_PAYMENT_BARZAHLEN_LAST_UPDATE_CHECK',
    NOW(),
    6,
    NOW(),
    NOW()
)
SQL;
        $this->db->Execute($sql);
    }

    /**
     * Updates the config entry with the current time as last update date
     */
    public function updateLastUpdateDate()
    {
        $sql = <<<SQL
UPDATE  configuration
SET configuration_value = NOW()
WHERE configuration_key = 'MODULE_PAYMENT_BARZAHLEN_LAST_UPDATE_CHECK'
SQL;
        $this->db->Execute($sql);
    }
}
