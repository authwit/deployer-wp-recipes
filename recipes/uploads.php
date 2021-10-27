<?php

/* wp uploads task
/* --------------------- */

namespace deployer;

task('uploads:sync', function() {
     $config = \deployer\task\context::get()->getconfig();
     $server = \deployer\task\context::get()->gethost() ;

    $upload_dir = 'web/app/uploads';
    $user       =  $config->get('user');
    $host       = $config->get('hostname');
    $port = $server->getport() ? ' -p ' . $server->getport() : '';
    $identityfile = $server->getidentityfile() ? ' -i ' . $server->getidentityfile() : '';
    writeln('<comment>> receive remote uploads ... </comment>');
    runlocally("rsync -avzo --no-o --no-g -e 'ssh$port$identityfile' $user@$host:{{deploy_path}}/shared/$upload_dir/ $upload_dir");

    writeln('<comment>> send local uploads ... </comment>');
    runlocally("rsync -avzo --no-o --no-g -e 'ssh$port$identityfile' $upload_dir/ $user@$host:{{deploy_path}}/shared/$upload_dir");

})->desc('sync uploads');
