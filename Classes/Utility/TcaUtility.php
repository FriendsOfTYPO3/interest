<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Interest\Utility;

use FriendsOfTYPO3\Interest\Domain\Repository\RemoteIdMappingRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TcaUtility
{
    /**
     * [
     *     'tableName' => [
     *         ''
     *     ],
     * ]
     *
     * @var array|null
     * @see TcaUtility::getInlineRelationsToTable()
     */
    protected static ?array $inlineRelationsToTablesCache = null;

    /**
     * Returns true if the table is localizable.
     *
     * @param string $tableName
     * @return bool
     */
    public static function isLocalizable(string $tableName): bool
    {
        return self::getLanguageField($tableName) !== null;
    }

    /**
     * Returns the name of the table's localizable field.
     *
     * @param string $tableName
     * @return string|null
     */
    public static function getLanguageField(string $tableName): ?string
    {
        return $GLOBALS['TCA'][$tableName]['ctrl']['languageField'] ?? null;
    }

    /**
     * Returns the name of the field used by translations to point back to the original record, the record in the
     * default language of which they are a translation.
     *
     * @param string $tableName
     * @return string|null
     */
    public static function getTransOrigPointerField(string $tableName): ?string
    {
        return $GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'] ?? null;
    }

    /**
     * Returns the name of the field used by translations to point back to the original record (i.e. the record in any
     * language of which they are a translation).
     *
     * @param string $tableName
     * @return string|null
     */
    public static function getTranslationSourceField(string $tableName): ?string
    {
        return $GLOBALS['TCA'][$tableName]['ctrl']['translationSource'] ?? null;
    }

    /**
     * Returns TCA configuration for a field with type-related overrides.
     *
     * @param string $table
     * @param string $field
     * @param array $row
     * @param string $remoteId
     * @return array
     * @throws \UnexpectedValueException
     */
    public static function getTcaFieldConfigurationAndRespectColumnsOverrides(
        string $table,
        string $field,
        array $row,
        ?string $remoteId = null
    ): array {
        if ($field === 'pid') {
            return self::getFakePidTcaConfiguration();
        }

        $tcaFieldConf = $GLOBALS['TCA'][$table]['columns'][$field]['config'];

        $recordType = self::getTcaFieldType($table, $row, $remoteId);

        $columnOverrideConfigForField
            = $GLOBALS['TCA'][$table]['types'][$recordType]['columnsOverrides'][$field]['config'] ?? null;

        if ($columnOverrideConfigForField !== null) {
            ArrayUtility::mergeRecursiveWithOverrule($tcaFieldConf, $columnOverrideConfigForField);
        }

        if ($tcaFieldConf === null) {
            throw new \UnexpectedValueException(
                'No configuration for the field "' . $table . '.' . $field . '".',
                1634895616563
            );
        }

        return $tcaFieldConf;
    }

    /**
     * Get a list of the tables and fields where $tableName is used as inline records (type=inline in the TCA).
     *
     * A returned array might look like:
     *
     * [
     *     'tablename1' => ['field1', 'field2'],
     *     'tablename2' => ['field2'],
     * ]
     *
     * @param string $tableName
     * @return array
     */
    public static function getInlineRelationsToTable(string $tableName): array
    {
        if (self::$inlineRelationsToTablesCache === null) {
            $cacheManager = GeneralUtility::makeInstance(CacheManager::class);

            $cacheHash = md5(self::class . '_inlineRelationsToTables');

            $cache = $cacheManager->getCache('hash');

            $inlineRelationsToTables = $cache->get($cacheHash);

            if (!is_array($inlineRelationsToTables)) {
                self::populateInlineRelationsToTablesCache();

                $cache->set($cacheHash, self::$inlineRelationsToTablesCache);
            } else {
                self::$inlineRelationsToTablesCache = $inlineRelationsToTables;
            }
        }

        return self::$inlineRelationsToTablesCache[$tableName] ?? [];
    }

    /**
     * Returns the name of the record type field or null if there is none.
     *
     * @param string $table
     * @return string|null
     */
    public static function getTypeFieldForTable(string $table): ?string
    {
        return $GLOBALS['TCA'][$table]['ctrl']['type'] ?? null;
    }

    /**
     * Finds inline relations and adds them to self::$inlineRelationsToTablesCache.
     */
    protected static function populateInlineRelationsToTablesCache(): void
    {
        self::$inlineRelationsToTablesCache = [];

        foreach ($GLOBALS['TCA'] as $table => $tableConfig) {
            $recordTypeKeys = array_keys($tableConfig['types']);

            foreach (array_keys($tableConfig['columns']) as $fieldName) {
                $typeFieldName = static::getTypeFieldForTable($table);

                foreach ($recordTypeKeys as $recordTypeKey) {
                    $row = $typeFieldName === null ? [] : [$typeFieldName => $recordTypeKey];

                    $fieldConfig = static::getTcaFieldConfigurationAndRespectColumnsOverrides(
                        $table,
                        $fieldName,
                        $row
                    );

                    if ($fieldConfig['type'] === 'inline') {
                        self::$inlineRelationsToTablesCache[$fieldConfig['foreign_table']][$table][$fieldName][]
                            = $recordTypeKey;
                    }
                }
            }
        }
    }

    /**
     * @return array
     */
    public static function getFakePidTcaConfiguration(): array
    {
        return [
            'type' => 'group',
            'internal_type' => 'db',
            'allowed' => 'pages',
            'size' => 1,
            'maxitems' => 1,
            'minitems' => 1,
            'default' => 0,
        ];
    }

    /**
     * Check if the field is relational.
     *
     * @param string $table
     * @param string $field
     * @param array $row
     * @param string|null $remoteId
     * @return bool
     */
    public static function isRelationalField(
        string $table,
        string $field,
        array $row = [],
        ?string $remoteId = null
    ): bool {
        if (
            $field === 'pid'
            || $field === self::getTranslationSourceField($table)
            || $field === self::getTransOrigPointerField($table)
        ) {
            return true;
        }

        $tca = self::getTcaFieldConfigurationAndRespectColumnsOverrides(
            $table,
            $field,
            $row,
            $remoteId
        );

        return (
            $tca['type'] === 'group'
            && (
                ($tca['internal_type'] ?? null) === 'db'
                || isset($tca['allowed'])
            )
        )
            || (
                in_array($tca['type'], ['inline', 'select'], true)
                && isset($tca['foreign_table'])
            )
            || (
                in_array($tca['type'], ['category', 'file', 'image'], true)
            );
    }

    /**
     * Returns true if $field of $table is a relation field that supports records from multiple tables, meaning that
     * the UID should be prefixed with the table name: table_name_123.
     *
     * @param string $table
     * @param string $field
     * @return bool
     */
    public static function hasRelationToMultipleTables(string $table, string $field): bool
    {
        if ($field === 'pid') {
            return false;
        }

        $tcaConfiguration = $GLOBALS['TCA'][$table]['columns'][$field]['config'];

        $prefixWithTable = false;

        if (
            $tcaConfiguration['type'] === 'group'
            && (
                $tcaConfiguration['allowed'] === '*'
                || str_contains(',', $tcaConfiguration['allowed'])
            )
        ) {
            $prefixWithTable = true;
        }

        return $prefixWithTable;
    }

    /**
     * @param string $table
     * @param array $row
     * @param string|null $remoteId
     * @return string
     */
    protected static function getTcaFieldType(
        string $table,
        array $row,
        ?string $remoteId
    ): string {
        /** @var RemoteIdMappingRepository $mappingRepository */
        $mappingRepository = GeneralUtility::makeInstance(RemoteIdMappingRepository::class);

        if ($remoteId !== null && $mappingRepository->exists($remoteId)) {
            $row = array_merge(
                DatabaseUtility::getRecord(
                    $table,
                    $mappingRepository->get($remoteId)
                ),
                $row
            );
        }

        return BackendUtility::getTCAtypeValue($table, $row);
    }
}
