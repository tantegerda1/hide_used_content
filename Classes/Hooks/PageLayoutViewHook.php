<?php
declare(strict_types = 1);
namespace IchHabRecht\HideUsedContent\Hooks;

use IchHabRecht\HideUsedContent\Cache\CacheManager;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PageLayoutViewHook
{
    /**
     * @var array
     */
    protected $columnConfiguration;

    public function __construct(CacheManager $cacheManager = null)
    {
        if ($cacheManager === null) {
            $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        }

        $this->columnConfiguration = $cacheManager->get();
    }

    public function hideUsedContent(array $parameter): bool
    {
        if ($parameter['used']) {
            return true;
        }

        $record = $parameter['record'];
        $colPos = (int)$record['colPos'];

        foreach ($this->columnConfiguration[$colPos] ?? [] as $table => $fieldArray) {
            foreach ($fieldArray as $field) {
                $fieldConfiguration = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
                if (empty($record[$fieldConfiguration['foreign_field']])) {
                    continue;
                }

                $relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
                $relationHandler->start(
                    '',
                    $fieldConfiguration['foreign_table'],
                    $fieldConfiguration['MM'] ?? '',
                    $record[$fieldConfiguration['foreign_field']],
                    $table,
                    $fieldConfiguration
                );
                $valueArray = $relationHandler->getValueArray();
                if (in_array($record['uid'], $valueArray)) {
                    return true;
                }
            }
        }

        return false;
    }
}
