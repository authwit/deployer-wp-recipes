<?php

/* DB TASKS
/* --------------------- */

namespace Deployer;

use DotEnv;

// BACKUP DB REMOTE TO LOCAL
task('db:remote:backup', function() {

    $config = get('wp-recipes');

    $now = time();
    set('dump_path', $config['shared_dir']);
    set('dump_file', $config['theme_name']  . $now . '.sql');
    set('dump_filepath', get('dump_path') . get('dump_file'));

    writeln('<comment>> Remote dump : <info>' . get('dump_file') .' </info></comment>');
    run('mkdir -p ' . get('dump_path'));
    run('cd {{deploy_path}}/current/ && wp-cli db export ' . get('dump_filepath') . ' --add-drop-table --hex-blob');

    runLocally('mkdir -p .data/db_backups');
    download(get('dump_filepath'), '.data/db_backups/' . get('dump_file'));

})->desc('Download backup database');

// BACKUP DB LOCAL TO REMOTE
task('db:local:backup', function() {

    $config = get('wp-recipes');

    $now = time();
    set('dump_path', $config['shared_dir']);
    set('dump_file', $config['theme_name'] . $now . '.sql');
    set('dump_filepath', get('dump_path') . get('dump_file'));

    writeln('<comment>> Local dump : <info>' . get('dump_file') .' </info></comment>');
    runLocally('mkdir -p .data/db_backups');

    // re-activate disabled plugins
    runLocally('wp plugin activate ' . get('dev_deactivated_plugins'));

    // excludes all the WooCommerce tables to prevent overwriting this data in remote
    // see (High-Performance Order Storage),  https://bit.ly/3QWEFrM
    runLocally('wp db export .data/db_backups/' . get('dump_file') . ' --add-drop-table --hex-blob --exclude_tables=wp_wc_admin_note_actions,wp_wc_admin_notes,wp_wc_category_lookup,wp_wc_comments_subscription,wp_wc_customer_lookup,wp_wc_download_log,wp_wc_feedback_forms,wp_wc_follow_users,wp_wc_order_addresses,wp_wc_order_coupon_lookup,wp_wc_order_operational_data,wp_wc_order_product_lookup,wp_wc_order_stats,wp_wc_order_tax_lookup,wp_wc_orders,wp_wc_orders_meta,wp_wc_phrases,wp_wc_product_attributes_lookup,wp_wc_product_download_directories,wp_wc_product_meta_lookup,wp_wc_rate_limits,wp_wc_reserved_stock,wp_wc_tax_rate_classes,wp_wc_users_rated,wp_wc_users_voted,wp_wc_webhooks');

    run('mkdir -p ' . get('dump_path'));
    upload('.data/db_backups/' . get('dump_file'),  get('dump_filepath'));

})->desc('Upload backup database');

// CREATE DB

task('db:create', function() {
    writeln('<comment>> Create database. </comment>');
    run('cd {{deploy_path}}/current/ && wp-cli db create');

})->desc('Exports DB');

// PULL DB

task('db:cmd:pull', function() {
    writeln('<comment>> Imports remote db to local :<info>' . get('dump_file') . '</info> </comment>');
//    runLocally('wp db import .data/db_backups/' . get('dump_file'));
    runLocally('tail +2 .data/db_backups/' . get('dump_file') . ' | wp db import -');
    runLocally('wp search-replace ' . get('remote_url') . ' ' . get('local_url'));

    # deactivate non-dev critical plugins
    runLocally('wp plugin deactivate ' . get('dev_deactivated_plugins'));
    runLocally('rm -f .data/db_backups/' . get('dump_file'));

})->desc('Imports DB');

// PUSH DB
task('db:cmd:push', function() {
    writeln('<comment>> Exports local db to remote : <info>' . get('dump_file') . '</info>... </comment>');
    run('cd {{deploy_path}}/current && wp-cli db import ' . get('dump_filepath'));
    run('cd {{deploy_path}}/current && wp-cli search-replace ' . get('local_url') . ' ' . get('remote_url'));
    run('rm -f ' . get('dump_filepath') );

})->desc('Exports DB');

// GET ENV WP SITE URL

task('env:uri', function() {

    $config = get('wp-recipes');

    if ( ($config['local_wp_url'] === '') || ($config['remote_wp_url'] === '') ) {
        writeln('working with env files');
        $tmp_dir = dirname(__DIR__) . '/../.tmp/';
        $local_env = '.local.env';
        $remote_env = '.remote.env';

        runLocally('mkdir -p ' . $tmp_dir);
        runLocally('cp .env ' . $tmp_dir . $local_env );
        download($tmp_dir . $remote_env, $config['shared_dir'] . '/.env');


        $dotenvremote = new Dotenv\Dotenv($tmp_dir, $remote_env);
        if (file_exists($tmp_dir . $remote_env)) {
            $dotenvremote->overload();
            $dotenvremote->required(['WP_HOME']);
            set('remote_url', getenv('WP_HOME'));
        }

        $dotenvlocal = new Dotenv\Dotenv($tmp_dir, $local_env);
        if (file_exists($tmp_dir . $local_env)) {
            $dotenvlocal->overload();
            $dotenvlocal->required(['WP_HOME']);
            set('local_url', getenv('WP_HOME'));
        }

        runLocally('rm -rf .tmp');
    } else {
        writeln('working with config');
        set('local_url', $config['local_wp_url']);
        set('remote_url', $config['remote_wp_url']);
    }

})->desc('Download backup database');

/* --------------------- */
/*       DB TASKS         */
/* --------------------- */

task('db:push', [
    'db:local:backup',
    'db:cmd:push'
]);

task('db:pull', [
    'db:remote:backup',
    'db:cmd:pull'
]);
