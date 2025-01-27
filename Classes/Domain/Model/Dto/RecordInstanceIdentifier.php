<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\Domain\Model\Dto;

use FriendsOfTYPO3\Interest\DataHandling\Operation\Exception\ConflictException;
use FriendsOfTYPO3\Interest\Domain\Model\Dto\Exception\InvalidArgumentException;
use FriendsOfTYPO3\Interest\Domain\Repository\RemoteIdMappingRepository;
use FriendsOfTYPO3\Interest\Utility\TcaUtility;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * DTO to handle record instance identifier.
 */
class RecordInstanceIdentifier
{
    public const LANGUAGE_ASPECT_PREFIX = '|||L';

    protected string $table;

    /**
     * The original remote id from construct.
     */
    protected string $remoteId;

    /**
     * Language to use for processing.
     */
    protected ?string $language;

    private ?SiteLanguage $siteLanguage = null;

    protected ?string $workspace;

    protected ?int $uid = null;

    /**
     * Holds the temporary UID for DataHandler.
     *
     * @var string|null
     */
    protected ?string $uidPlaceholder = null;

    /**
     * @param string $table
     * @param string $remoteId
     * @param string $language as RFC 1766/3066 string, e.g. nb or sv-SE.
     * @param string $workspace workspace represented with a remote ID.
     */
    public function __construct(
        string $table,
        string $remoteId,
        string $language = '',
        string $workspace = ''
    ) {
        $this->table = strtolower($table);
        $this->remoteId = $remoteId;
        $this->language = $language;
        $this->workspace = $workspace;
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @return SiteLanguage|null
     */
    public function getLanguage(): ?SiteLanguage
    {
        if ($this->siteLanguage === null) {
            $this->siteLanguage = $this->resolveLanguage($this->language);
        }

        return $this->siteLanguage;
    }

    /**
     * Returns true if a SiteLanguage is set.
     */
    public function hasLanguage(): bool
    {
        return $this->language !== null;
    }

    /**
     * Returns original unmodified remote id set in construct.
     */
    public function getRemoteId(): string
    {
        return $this->remoteId;
    }

    /**
     * Returns workspace.
     */
    public function getWorkspace(): ?string
    {
        return $this->workspace;
    }

    /**
     * Returns true if workspace is set.
     */
    public function hasWorkspace(): bool
    {
        return $this->workspace !== null;
    }

    public function getUid(): int
    {
        if ($this->uid === null) {
            $this->uid = $this->resolveUid();
        }

        return $this->uid;
    }

    public function setUid(int $uid): void
    {
        $this->uid = $uid;
    }

    /**
     * Returns a DataHandler UID placeholder. If it has not yet been set, it will be generated as a random string
     * prefixed with "NEW".
     */
    public function getUidPlaceholder(): string
    {
        if ($this->uidPlaceholder === null) {
            $this->uidPlaceholder = StringUtility::getUniqueId('NEW');
        }

        return $this->uidPlaceholder;
    }

    /**
     * Returns remote id with aspects, such as language and workspace ID.
     * If language is null or language ID zero, the $remoteId is removed unchanged.
     */
    public function getRemoteIdWithAspects(): string
    {
        if (
            !TcaUtility::isLocalizable($this->getTable())
            // @extensionScannerIgnoreLine
            || $this->getLanguage() === null
            // @extensionScannerIgnoreLine
            || $this->getLanguage()->getLanguageId() === 0
        ) {
            return $this->remoteId;
        }

        // @extensionScannerIgnoreLine
        $languageAspect = self::LANGUAGE_ASPECT_PREFIX . $this->getLanguage()->getLanguageId();

        if (str_contains($this->remoteId, $languageAspect)) {
            return $this->remoteId;
        }

        $remoteId = $this->remoteId;

        return $remoteId . $languageAspect;
    }

    public function removeAspectsFromRemoteId(string $remoteId): string
    {
        if (!str_contains($remoteId, self::LANGUAGE_ASPECT_PREFIX)) {
            return $remoteId;
        }

        return substr($remoteId, 0, strpos($remoteId, self::LANGUAGE_ASPECT_PREFIX));
    }

    /**
     * Resolves a site language. If no language is defined, the sites's default language will be returned. If the
     * storagePid has no site, null will be returned.
     *
     * @throws InvalidArgumentException
     */
    protected function resolveLanguage(?string $language): ?SiteLanguage
    {
        if (!TcaUtility::isLocalizable($this->getTable()) || ($language ?? '') === '') {
            return null;
        }

        /** @var SiteFinder $siteFinder */
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);

        $sites = $siteFinder->getAllSites();

        $siteLanguages = [];

        foreach ($sites as $site) {
            $siteLanguages = array_merge($siteLanguages, $site->getAllLanguages());
        }

        // This is the equivalent of running array_unique, but supports objects.
        $siteLanguages = array_reduce($siteLanguages, function (array $uniqueSiteLanguages, SiteLanguage $item) {
            /** @var SiteLanguage $siteLanguage */
            foreach ($uniqueSiteLanguages as $siteLanguage) {
                // @extensionScannerIgnoreLine
                if ($siteLanguage->getLanguageId() === $item->getLanguageId()) {
                    return $uniqueSiteLanguages;
                }
            }

            $uniqueSiteLanguages[] = $item;

            return $uniqueSiteLanguages;
        }, []);

        foreach ($siteLanguages as $siteLanguage) {
            $hreflang = $siteLanguage->getHreflang();

            // In case this is the short form, e.g. "nb" or "sv", not "nb-NO" or "sv-SE".
            if (strlen($language) === 2) {
                $hreflang = substr($hreflang, 0, 2);
            }

            if (strtolower($hreflang) === strtolower($language)) {
                return $siteLanguage;
            }
        }

        throw new InvalidArgumentException(
            'The language "' . $language . '" is not defined in this TYPO3 instance.'
        );
    }

    /**
     * Resolves the UID for the remote ID.
     *
     * @throws ConflictException
     */
    protected function resolveUid(): int
    {
        $mappingRepository = GeneralUtility::makeInstance(RemoteIdMappingRepository::class);

        if (
            $mappingRepository->exists($this->getRemoteIdWithAspects())
            && $mappingRepository->table($this->getRemoteIdWithAspects()) !== $this->getTable()
        ) {
            throw new ConflictException(
                'The remote ID "' . $this->getRemoteIdWithAspects() . '" exists, '
                . 'but doesn\'t belong to the table "' . $this->getRemoteIdWithAspects() . '".',
                1634213051764
            );
        }

        return $mappingRepository->get($this->getRemoteIdWithAspects());
    }

    public function __toString(): string
    {
        return $this->getRemoteIdWithAspects();
    }

    public function __serialize(): array
    {
        return [
            'table' => $this->table,
            'remoteId' => $this->remoteId,
            'language' => $this->language,
            'workspace' => $this->workspace,
            'uid' => $this->uid,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->table = $data['table'] ?? $data[' * table'];
        $this->remoteId = $data['remoteId'] ?? $data[' * remoteId'];
        $this->language = $data['language'] ?? $data[' * language']->getLocale()->getName() ?? null;
        $this->workspace = $data['workspace'] ?? $data[' * workspace'] ?? null;
        $this->uid = $data['uid'] ?? $data[' * uid'] ?? null;
    }
}
