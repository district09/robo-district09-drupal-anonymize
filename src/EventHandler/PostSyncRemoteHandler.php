<?php

namespace District09\Robo\DrupalAnonymize\EventHandler;

use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Helpers\EventHandler\AbstractTaskEventHandler;
use DigipolisGent\Robo\Helpers\Util\RemoteConfig;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\KeyFile;
use Robo\Contract\ConfigAwareInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class PostSyncRemoteHandler extends AbstractTaskEventHandler implements ConfigAwareInterface
{

    use \DigipolisGent\Robo\Drupal8\Traits\Drupal8UtilsTrait;
    use \DigipolisGent\Robo\Task\General\Tasks;
    use \DigipolisGent\Robo\Task\Deploy\Tasks;
    use \Consolidation\Config\ConfigAwareTrait;
    use \DigipolisGent\Robo\Task\General\Common\DigipolisPropertiesAware;
    use \Boedah\Robo\Task\Drush\loadTasks;

    /**
     * {@inheritDoc}
     */
    public function handle(GenericEvent $event)
    {
        /** @var RemoteConfig $remoteConfig */
        $remoteConfig = $event->getArgument('remoteConfig');
        $remoteSettings = $remoteConfig->getRemoteSettings();
        $currentProjectRoot = $currentWebRoot = $remoteSettings['currentdir'] . '/..';
        $aliases = $remoteSettings['aliases'] ?: [0 => false];
        $collection = $this->collectionBuilder();
        if (!$remoteSettings['anonymize']) {
            return $collection;
        }
        $auth = new KeyFile($remoteConfig->getUser(), $remoteConfig->getPrivateKeyFile());
        foreach ($aliases as $uri => $alias) {
            $drushCommand = CommandBuilder::create('vendor/bin/drush');
            if ($alias) {
                $drushCommand->addOption('uri', $uri);
            }
            $collection->taskSsh($remoteConfig->getHost(), $auth)
                ->remoteDirectory($currentProjectRoot, true)
                ->timeout(120)
                ->exec((string) (clone $drushCommand)->addArgument('cr'))
                ->exec((string) (clone $drushCommand)->addArgument('cc')->addArgument('drush'));

            $collection->taskSsh($remoteConfig->getHost(), $auth)
                ->remoteDirectory($currentProjectRoot, true)
                ->timeout(300)
                // Check if configurable_anonymizer is enabled & execute.
                ->exec(
                    (string) $this->checkModuleCommand('configurable_anonymizer', $remoteSettings, $uri)
                        ->onSuccess('cd')
                            ->addFlag('P')
                            ->addArgument($currentProjectRoot)
                        ->onSuccess(
                            (clone $drushCommand)
                                ->addArgument('anonymizer:run')
                        )
                        ->onFailure(CommandBuilder::create('echo')->addArgument('[WARNING] Anonymization failed! Data might not be anonymized. Check the logs for details.')->onFinished('exit')->addRawArgument(0))
                );
        }
        return $collection;
    }
}
