<?php declare(strict_types=1);

namespace Melv\CustomBadges;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\CustomField\CustomFieldTypes;

class MelvCustomBadges extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);

        $this->createCustomFields();
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        $this->deleteCustomFields();

        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }
    }

    private function createCustomFields()
    {
        /** @var EntityRepositoryInterface $customFieldSetRepository */
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        $customFieldSetUuid = Uuid::randomHex();
        $badgeCount = 5;

        for($i = 1; $i <= $badgeCount; $i++) {
            $customFieldSetRepository->upsert([
                [
                    'id' => $customFieldSetUuid,
                    'name' => 'melv_custom_badges',
                    'config' => [
                        'label' => [
                            'en-GB' => 'Custom Badges',
                            'nl-NL' => 'Aangepaste labels'
                        ]
                    ],
                    'customFields' => [
                        [
                            'id' => Uuid::randomHex(),
                            'name' => 'melv_custom_badges_text'.$i,
                            'type' => CustomFieldTypes::TEXT,
                            'config' => [
                                'type' => 'text',
                                'componentName' => 'sw-field',
                                'customFieldType' => 'text',
                                'customFieldPosition' => $i,
                                'label' => [
                                    'en-GB' => 'Text badge '.$i,
                                    'nl-NL' => 'Tekst badge '.$i
                                ]
                            ]
                        ],
                    ],
                    'relations' => [
                        [
                            'id' => $customFieldSetUuid,
                            'entityName' => $this->container->get(ProductDefinition::class)->getEntityName()
                        ],
                    ]
                ]
            ], Context::createDefaultContext());
        }
    }

    private function deleteCustomFields()
    {
        /** @var EntityRepositoryInterface $customFieldSetRepository */
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        $entityIds = $customFieldSetRepository->search(
            (new Criteria())->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
                new EqualsFilter('name', 'melv_custom_badges'),
            ])),
            Context::createDefaultContext()
        )->getEntities()->getIds();

        if (count($entityIds) < 1) {
            return;
        }

        $entityIds = array_map(function ($element) {
            return ['id' => $element];
        }, array_values($entityIds));

        $customFieldSetRepository->delete(
            $entityIds,
            Context::createDefaultContext()
        );
    }
}

