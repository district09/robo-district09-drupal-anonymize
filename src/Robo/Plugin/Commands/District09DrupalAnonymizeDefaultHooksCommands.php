<?php

namespace District09\Robo\DrupalAnonymize\Robo\Plugin\Commands;

use District09\Robo\DrupalAnonymize\EventHandler\PostSyncRemoteHandler;
use Robo\Tasks;

/**
 * Hook implementations.
 *
 * For robo to parse this file, the classname must end in "commands", even
 * though it doesn't actually contain any commands, only hook implementations.
 */
class District09DrupalAnonymizeDefaultHooksCommands extends Tasks
{

    /**
     * implementation for the digipolis:post-sync-remote task.
     *
     * @hook on-event digipolis:post-sync-remote
     */
    public function getPostSyncRemoteHandler() {
        return new PostSyncRemoteHandler();
    }
}
