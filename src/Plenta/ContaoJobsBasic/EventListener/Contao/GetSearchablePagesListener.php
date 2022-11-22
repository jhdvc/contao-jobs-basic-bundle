<?php

declare(strict_types=1);

/**
 * Plenta Jobs Basic Bundle for Contao Open Source CMS
 *
 * @copyright     Copyright (c) 2022, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @link          https://github.com/plenta/
 */

namespace Plenta\ContaoJobsBasic\EventListener\Contao;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Plenta\ContaoJobsBasic\Contao\Model\PlentaJobsBasicOfferModel;

/**
 * @Hook("getSearchablePages")
 */
class GetSearchablePagesListener
{
    public function __invoke(array $pages, $rootId = null, bool $isSitemap = false, string $language = null): array
    {
        $jobs = PlentaJobsBasicOfferModel::findBy('published', 1);
        if ($jobs) {
            foreach ($jobs as $job) {
                if ($page = $job->getAbsoluteUrl($language)) {
                    $pages[] = $page;
                }
            }
        }

        return $pages;
    }
}
