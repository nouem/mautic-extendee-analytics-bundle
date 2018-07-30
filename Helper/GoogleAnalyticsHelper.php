<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticExtendeeAnalyticsBundle\Helper;


use Doctrine\ORM\EntityManager;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticExtendeeAnalyticsBundle\Integration\EAnalyticsIntegration;
use MauticPlugin\MauticRecombeeBundle\Integration\RecombeeIntegration;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class GoogleAnalyticsHelper
{
    use GoogleAnalyticsTrait;
    /**
     * @var IntegrationHelper
     */
    private $integrationHelper;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    private $metrics;

    private $analyticsFeatures;

    /**
     * @var EntityManager
     */
    private $entityManager;

    private $utmTags = [];


    /**
     * @var RouterInterface
     */
    private $router;


    /**
     * Generator constructor.
     *
     * @param IntegrationHelper   $integrationHelper
     * @param TranslatorInterface $translator
     * @param EntityManager       $entityManager
     * @param RouterInterface     $router
     *
     * @internal param FormFactoryBuilder $formFactoryBuilder
     */
    public function __construct(
        IntegrationHelper $integrationHelper,
        TranslatorInterface $translator,
        EntityManager $entityManager,
        RouterInterface $router
    ) {

        $this->integrationHelper  = $integrationHelper;
        $this->translator         = $translator;
        $this->entityManager      = $entityManager;
        $this->router             = $router;
    }

    /**
     * @return array
     */
    public function setUtmTagsFromChannels($dateFrom, $dateTo)
    {
        // already exists
        if (!empty($this->utmTags)) {
            return $this->utmTags;
        }

        $q = $this->entityManager->getConnection()->createQueryBuilder();

        $tables = ['emails', 'focus', 'push_notifications'];

        foreach ($tables as $table) {
            $q->select('e.id, e.utm_tags')
                ->from(MAUTIC_TABLE_PREFIX.$table, 'e')
                ->where(
                    $q->expr()->gt('e.date_modified', ':dateFrom'),
                    $q->expr()->lt('e.date_modified', ':dateTo')
                )
                ->setParameter('dateFrom', $dateFrom)
                ->setParameter('dateTo', $dateTo);
            ;
            $utmTags = $q->execute()->fetchAll();
            if ($utmTags) {
                foreach ($utmTags as $utmTag) {
                    $utm = unserialize($utmTag['utm_tags']);
                    if (!empty($utm)) {
                        $this->utmTags[$table][$utmTag['id']] = $utm;
                    }
                }
            }
        }
        return $this->utmTags;
    }

}