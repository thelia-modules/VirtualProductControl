<?php

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace VirtualProductControl\Hook;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Security\SecurityContext;
use Thelia\Core\Template\Parser\ParserResolver;
use Thelia\Model\ModuleQuery;
use Thelia\Model\ProductQuery;

class VirtualProductHook extends BaseHook
{
    public function __construct(
        private readonly SecurityContext $securityContext,
        ?EventDispatcherInterface $dispatcher = null,
        ?ParserResolver $parserResolver = null,
    ) {
        parent::__construct($dispatcher, $parserResolver);
    }

    public static function getSubscribedHooks(): array
    {
        return [
            'main.before-content' => [
                ['type' => 'back', 'method' => 'onMainBeforeContent'],
            ],
        ];
    }

    public function onMainBeforeContent(HookRenderEvent $event): void
    {
        if (!$this->securityContext->isGranted(['ADMIN'], [AdminResources::PRODUCT], [], [AccessManager::VIEW])) {
            return;
        }

        $virtualProductCount = ProductQuery::create()
            ->filterByVirtual(1)
            ->filterByVisible(1)
            ->count();

        if ($virtualProductCount === 0) {
            return;
        }

        if (false !== ModuleQuery::create()->retrieveVirtualProductDelivery()) {
            return;
        }

        $event->add($this->render('virtual-delivery-warning.html.twig'));
    }
}
