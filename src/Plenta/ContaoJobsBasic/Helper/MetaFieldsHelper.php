<?php

declare(strict_types=1);

/**
 * Plenta Jobs Basic Bundle for Contao Open Source CMS
 *
 * @copyright     Copyright (c) 2021, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @link          https://github.com/plenta/
 */

namespace Plenta\ContaoJobsBasic\Helper;

use Contao\Controller;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\Date;
use Contao\FilesModel;
use Contao\FrontendTemplate;
use Contao\StringUtil;
use Contao\System;
use Doctrine\Persistence\ManagerRegistry;
use Plenta\ContaoJobsBasic\Entity\TlPlentaJobsBasicJobLocation;
use Plenta\ContaoJobsBasic\Entity\TlPlentaJobsBasicOffer;

class MetaFieldsHelper
{
    protected EmploymentType $employmentTypeHelper;

    protected ManagerRegistry $registry;

    public function __construct(
        EmploymentType $employmentTypeHelper,
        ManagerRegistry $registry
    ) {
        $this->employmentTypeHelper = $employmentTypeHelper;
        $this->registry = $registry;
    }

    public function getMetaFields(TlPlentaJobsBasicOffer $jobOffer, $imageSize = null): array
    {
        $metaFields = [];

        $metaFields['publicationDateFormatted'] = Date::parse(Date::getNumericDateFormat(), $jobOffer->getDatePosted());
        $metaFields['employmentTypeFormatted'] = $this->employmentTypeHelper->getEmploymentTypesFormatted($jobOffer->getEmploymentType());
        $metaFields['locationFormatted'] = $this->formatLocation($jobOffer);
        $metaFields['addressLocalityFormatted'] = $this->formatAddressLocality($jobOffer);
        $metaFields['title'] = Controller::replaceInsertTags($jobOffer->getTitle());
        $metaFields['description'] = Controller::replaceInsertTags($jobOffer->getDescription());
        if ($imageSize && $jobOffer->isAddImage()) {
            $file = FilesModel::findByUuid(StringUtil::binToUuid($jobOffer->getSingleSRC()));
            if ($file) {
                $tpl = new FrontendTemplate('ce_image');
                if (version_compare(VERSION, '4.11', '>=')) {
                    /** @var Studio $studio */
                    $studio = System::getContainer()->get('contao.image.studio');
                    $figure = $studio->createFigureBuilder()->fromUuid($jobOffer->getSingleSRC())->setSize($imageSize)->build();
                    $figure->applyLegacyTemplateData($tpl);
                } else {
                    Controller::addImageToTemplate($tpl, ['singleSRC' => $file->path, 'size' => $imageSize]);
                }
                $metaFields['image'] = $tpl->parse();
            }
        }

        if (!isset($metaFields['image'])) {
            $metaFields['image'] = '';
        }

        return $metaFields;
    }

    public function formatLocation(TlPlentaJobsBasicOffer $jobOffer): string
    {
        return $this->formatAddressLocality($jobOffer);
    }

    public function formatAddressLocality(TlPlentaJobsBasicOffer $jobOffer): string
    {
        $locationsTemp = [];

        if ($jobOffer->isRemote()) {
            $locationsTemp[] = $GLOBALS['TL_LANG']['MSC']['PLENTA_JOBS']['remote'];
        }

        if (!$jobOffer->isRemote() || !$jobOffer->isOnlyRemote()) {
            $locations = StringUtil::deserialize($jobOffer->getJobLocation());
            $locationRepository = $this->registry->getRepository(TlPlentaJobsBasicJobLocation::class);


            foreach ($locations as $location) {
                $locationEntity = $locationRepository->find($location);
                $locationsTemp[] = $locationEntity->getAddressLocality();
            }
        }

        return implode(', ', $locationsTemp);
    }
}
