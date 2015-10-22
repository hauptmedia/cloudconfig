<?php
return function($clusterConfi, $nodeConfig) {
    if(!array_key_exists('update', $nodeConfig)) {
        return;
    }

    $updateConfig = $nodeConfig['update'];

    return array(
        'coreos' => array(
            'update' => $updateConfig
        )
    );
};