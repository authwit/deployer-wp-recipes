<?php

/* WP UPLOADS TASK
/* --------------------- */

namespace Deployer;

task('uploads:sync', function() {
    $server = \Deployer\Task\Context::get();
    $upload_dir = 'web/app/uploads';
    $user       = $server->getHost()->getUser();
    $host       = $server->getHost()->getRealHostname();
    $port = $server->getHost()->getPort() ? ' -p ' . $server->getHost()->getPort() : '';
    $identityFile = $server->getHost()->getIdentityFile() ? ' -i ' . $server->getHost()->getIdentityFile() : '';

    writeln('<comment>> Receive remote uploads ... </comment>');
    runLocally("rsync -avzO --no-o --no-g -e 'ssh$port$identityFile' $user@$host:{{deploy_path}}/shared/$upload_dir/ $upload_dir");

    writeln('<comment>> Send local uploads ... </comment>');
    runLocally("rsync -avzO --no-o --no-g -e 'ssh$port$identityFile' $upload_dir/ $user@$host:{{deploy_path}}/shared/$upload_dir");

})->desc('Sync uploads');
