<?php

namespace Pimgento\Attribute\Setup;

use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\DB\FieldDataConverterFactory;
use Magento\Framework\DB\FieldDataConverter;
use Magento\Framework\DB\Select\QueryModifierFactory;
use Magento\Framework\DB\DataConverter\SerializedToJson;
use Pimgento\Attribute\Helper\Config;

/**
 * Upgrade Data
 *
 * @author    David Dattee <dadat@smile.fr>
 * @copyright 2017 Smile
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var FieldDataConverterFactory
     */
    protected $fieldDataConverterFactory;

    /**
     * @var QueryModifierFactory
     */
    protected $queryModifierFactory;

    /**
     * @var \Magento\Framework\DB\Query\Generator
     */
    protected $queryGenerator;
    /**
     * @var ReinitableConfigInterface
     */
    private $reinitConfig;

    /**
     * Constructor
     *
     * @param FieldDataConverterFactory $fieldDataConverterFactory
     * @param QueryModifierFactory      $queryModifierFactory
     * @param ReinitableConfigInterface $reinitConfig
     */
    public function __construct(
        FieldDataConverterFactory $fieldDataConverterFactory,
        QueryModifierFactory      $queryModifierFactory,
        ReinitableConfigInterface $reinitConfig
    ) {
        $this->fieldDataConverterFactory = $fieldDataConverterFactory;
        $this->queryModifierFactory      = $queryModifierFactory;
        $this->reinitConfig              = $reinitConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();

        /** Magento 2.2 Compatibility */
        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            $this->convertSerializedDataToJson($setup);
        }

        $setup->endSetup();
    }

    /**
     * Convert the values from php serialize to json.
     *
     * @param ModuleDataSetupInterface $setup
     *
     * @return void
     */
    protected function convertSerializedDataToJson(ModuleDataSetupInterface $setup)
    {
        /** @var FieldDataConverter $fieldDataConverter */
        $fieldDataConverter = $this->fieldDataConverterFactory->create(SerializedToJson::class);

        $queryModifier = $this->queryModifierFactory->create(
            'in',
            [
                'values' => [
                    'path' => [
                        Config::CONFIG_PIMGENTO_ATTR_TYPES
                    ]
                ]
            ]
        );

        $fieldDataConverter->convert(
            $setup->getConnection(),
            $setup->getTable('core_config_data'),
            'config_id',
            'value',
            $queryModifier
        );

        $this->reinitConfig->reinit();
    }
}