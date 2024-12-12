<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Pixelant\Interest\Tests\Functional;

use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\Internal\ArrayValueInstruction;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\Internal\InstructionInterface;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\Internal\TypoScriptInstruction;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * Copy-paste of SiteBasedTestTrait from the core.
 *
 * Trait used for test classes that want to set up (= write) site configuration files.
 *
 * Mainly used when testing Site-related tests in Frontend requests.
 *
 * Be sure to set the LANGUAGE_PRESETS const in your class.
 *
 * @see \TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait
 * @noinspection PhpUndefinedNamespaceInspection
 */
trait SiteBasedTestTrait
{
    protected static function failIfArrayIsNotEmpty(array $items): void
    {
        // @phpstan-ignore empty.notAllowed
        if (empty($items)) {
            return;
        }

        static::fail(
            'Array was not empty as expected, but contained these items:' . LF
            . '* ' . implode(LF . '* ', $items)
        );
    }

    protected function writeSiteConfiguration(
        string $identifier,
        array $site = [],
        array $languages = [],
        array $errorHandling = []
    ): void {
        $configuration = $site;
        // @phpstan-ignore empty.notAllowed
        if (!empty($languages)) {
            $configuration['languages'] = $languages;
        }
        // @phpstan-ignore empty.notAllowed
        if (!empty($errorHandling)) {
            $configuration['errorHandling'] = $errorHandling;
        }
        $siteWriter = $this->get(SiteWriter::class);
        try {
            // ensure no previous site configuration influences the test
            GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites/' . $identifier, true);
            $siteWriter->write($identifier, $configuration);
        } catch (\Exception $exception) {
            self::markTestSkipped($exception->getMessage());
        }
    }

    protected function mergeSiteConfiguration(
        string $identifier,
        array $overrides
    ): void {
        $siteConfiguration = $this->get(SiteConfiguration::class);
        $siteWriter = $this->get(SiteWriter::class);
        $configuration = $siteConfiguration->load($identifier);
        $configuration = array_merge($configuration, $overrides);
        try {
            $siteWriter->write($identifier, $configuration);
        } catch (\Exception $exception) {
            self::markTestSkipped($exception->getMessage());
        }
    }

    protected function buildSiteConfiguration(
        int $rootPageId,
        string $base = ''
    ): array {
        return [
            'rootPageId' => $rootPageId,
            'base' => $base,
        ];
    }

    protected function buildDefaultLanguageConfiguration(
        string $identifier,
        string $base
    ): array {
        $configuration = $this->buildLanguageConfiguration($identifier, $base);
        $configuration['flag'] = 'global';
        unset($configuration['fallbackType'], $configuration['fallbacks']);
        return $configuration;
    }

    protected function buildLanguageConfiguration(
        string $identifier,
        string $base,
        array $fallbackIdentifiers = [],
        ?string $fallbackType = null
    ): array {
        $preset = $this->resolveLanguagePreset($identifier);

        $configuration = [
            'languageId' => $preset['id'],
            'title' => $preset['title'],
            'navigationTitle' => $preset['title'],
            'websiteTitle' => $preset['websiteTitle'] ?? '',
            'base' => $base,
            'locale' => $preset['locale'],
            'flag' => $preset['iso'] ?? '',
            // @phpstan-ignore empty.notAllowed
            'fallbackType' => $fallbackType ?? (empty($fallbackIdentifiers) ? 'strict' : 'fallback'),
        ];

        // @phpstan-ignore empty.notAllowed
        if (!empty($fallbackIdentifiers)) {
            $fallbackIds = array_map(
                function (string $fallbackIdentifier) {
                    $preset = $this->resolveLanguagePreset($fallbackIdentifier);
                    return $preset['id'];
                },
                $fallbackIdentifiers
            );
            $configuration['fallbackType'] = $fallbackType ?? 'fallback';
            $configuration['fallbacks'] = implode(',', $fallbackIds);
        }

        return $configuration;
    }

    /**
     * @return mixed
     * @throws \LogicException
     */
    protected function resolveLanguagePreset(string $identifier)
    {
        if (!isset(static::LANGUAGE_PRESETS[$identifier])) {
            throw new \LogicException(
                sprintf('Undefined preset identifier "%s"', $identifier),
                1533893665
            );
        }
        return static::LANGUAGE_PRESETS[$identifier];
    }

    protected function applyInstructions(
        InternalRequest $request,
        InstructionInterface ...$instructions
    ): InternalRequest {
        $modifiedInstructions = [];

        foreach ($instructions as $instruction) {
            $identifier = $instruction->getIdentifier();
            if (isset($modifiedInstructions[$identifier]) || $request->getInstruction($identifier) !== null) {
                $modifiedInstructions[$identifier] = $this->mergeInstruction(
                    $modifiedInstructions[$identifier] ?? $request->getInstruction($identifier),
                    $instruction
                );
            } else {
                $modifiedInstructions[$identifier] = $instruction;
            }
        }

        return $request->withInstructions($modifiedInstructions);
    }

    protected function mergeInstruction(
        InstructionInterface $current,
        InstructionInterface $other
    ): InstructionInterface {
        if (get_class($current) !== get_class($other)) {
            throw new \LogicException('Cannot merge different instruction types', 1565863174);
        }

        if ($current instanceof TypoScriptInstruction) {
            /** @var TypoScriptInstruction $other */
            $typoScript = array_replace_recursive(
                $current->getTypoScript() ?? [],
                $other->getTypoScript() ?? []
            );
            $constants = array_replace_recursive(
                $current->getConstants() ?? [],
                $other->getConstants() ?? []
            );
            if ($typoScript !== []) {
                $current = $current->withTypoScript($typoScript);
            }
            if ($constants !== []) {
                $current = $current->withConstants($constants);
            }
            return $current;
        }

        if ($current instanceof ArrayValueInstruction) {
            /** @var ArrayValueInstruction $other */
            $array = array_merge_recursive($current->getArray(), $other->getArray());
            return $current->withArray($array);
        }

        return $current;
    }
}