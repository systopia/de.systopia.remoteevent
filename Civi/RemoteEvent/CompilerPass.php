<?php

declare(strict_types = 1);

namespace Civi\RemoteEvent;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use CRM_RemoteEvent_ExtensionUtil as E;

class CompilerPass implements CompilerPassInterface {

  public function process(ContainerBuilder $container) {
    if ($container->hasDefinition('action_provider')) {
      $actionProviderDefinition = $container->getDefinition('action_provider');
      $actionProviderDefinition->addMethodCall('addAction',
        ['RemoteEventSpawnEvent',
          'Civi\RemoteEvent\Actions\SpawnEvent',
          E::ts('RemoteEvent spawn'),
         [],
        ]
      );
    }
  }

}
