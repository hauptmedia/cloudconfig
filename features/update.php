<?php
return function($clusterConfi, $nodeConfig) {
    $updateConfig = $nodeConfig['update'];

    return array(
        'coreos' => array(
            'update' => $updateConfig
        )
    );
};