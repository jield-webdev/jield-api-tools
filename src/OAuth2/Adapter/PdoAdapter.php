<?php

declare(strict_types=1);

namespace Jield\ApiTools\OAuth2\Adapter;

use OAuth2\Storage\Pdo as OAuth2Pdo;
use function sprintf;

/**
 * Extension of OAuth2\Storage\PDO that provides Bcrypt client_secret/password
 * encryption
 */
class PdoAdapter extends OAuth2Pdo
{
    protected int $bcryptCost = 12;

    /**
     * @param string $connection
     * @param array $config
     */
    public function __construct($connection, $config = [])
    {
        parent::__construct($connection, $config);
        if (isset($config['bcrypt_cost'])) {
            $this->bcryptCost = $config['bcrypt_cost'];
        }
    }

    /**
     * Check client credentials
     *
     * @param string $clientId
     * @param string $clientSecret
     * @return bool
     */
    public function checkClientCredentials($clientId, $clientSecret = null): bool
    {
        $stmt = $this->db->prepare(sprintf(
            'SELECT * from %s where client_id = :client_id',
            $this->config['client_table']
        ));
        $stmt->execute(['client_id' => $clientId]);
        $result = $stmt->fetch();

        // Do not bother verifying if the secret is missing or empty.
        if (empty($result['client_secret'])) {
            return false;
        }

        // bcrypt verify
        return password_verify($clientSecret, $result['client_secret']);
    }

    /**
     * Set client details
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param string $redirectUri
     * @param string $grantTypes
     * @param string $scopeOrUserId If 5 arguments, userId; if 6, scope.
     * @param string $userId
     * @return bool
     */
    public function setClientDetails(
        $clientId,
        $clientSecret = null,
        $redirectUri = null,
        $grantTypes = null,
        $scopeOrUserId = null,
        $userId = null
    ): bool
    {
        if (func_num_args() > 5) {
            $scope = $scopeOrUserId;
        } else {
            $userId = $scopeOrUserId;
            $scope  = null;
        }

        if (!empty($clientSecret)) {
            $clientSecret = password_hash($clientSecret, PASSWORD_BCRYPT, ['cost' => $this->bcryptCost]);
        }

        return parent::setClientDetails($clientId, $clientSecret, $redirectUri, $grantTypes, $scope, $userId);
    }
}
