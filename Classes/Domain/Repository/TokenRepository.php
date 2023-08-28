<?php

declare(strict_types=1);

namespace Pixelant\Interest\Domain\Repository;

use Doctrine\DBAL\Result;
use Pixelant\Interest\Domain\Repository\Exception\InvalidQueryResultException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository for bearer tokens.
 */
class TokenRepository extends AbstractRepository
{
    public const TABLE_NAME = 'tx_interest_api_token';

    /**
     * Returns the UID for the backend user matching the token (or zero if no user was found or the token has expired).
     *
     * @param string $token
     * @return int
     * @throws InvalidQueryResultException
     */
    public function findBackendUserIdByToken(string $token): int
    {
        $queryBuilder = $this->getQueryBuilder();

        $result = $queryBuilder
            ->select('be_user')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('token', $queryBuilder->createNamedParameter($token)),
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->eq('expiry', 0),
                    $queryBuilder->expr()->gt('expiry', time())
                )
            )
            ->executeQuery();

        if (!($result instanceof Result)) {
            throw new InvalidQueryResultException(
                'Query result was not an instance of ' . Result::class,
                1648879886671
            );
        }

        return (int)$result->fetchOne();
    }

    /**
     * @param int $id The backend user ID.
     * @return string
     */
    public function createTokenForBackendUser(int $id): string
    {
        $token = GeneralUtility::makeInstance(Random::class)
            ->generateRandomHexString(32);

        $tokenLifetime
            = getenv('APP_INTEREST_TOKEN_TTL') !== false
            ? (int)getenv('APP_INTEREST_TOKEN_TTL')
            : (int)GeneralUtility::makeInstance(ExtensionConfiguration::class)
                ->get('interest', 'tokenLifetime');

        $queryBuilder = $this->getQueryBuilder();

        $queryBuilder
            ->insert(self::TABLE_NAME)
            ->values([
                'pid' => 0,
                'tstamp' => time(),
                'crdate' => time(),
                'token' => $token,
                'be_user' => $id,
                'expiry' => time() + $tokenLifetime,
            ])
            ->executeStatement();

        return $token;
    }
}
