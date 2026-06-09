<?php
/**
 * Version Management Functions
 */

function getCurrentVersion() {
    $versionFile = __DIR__ . '/../version.txt';
    if (file_exists($versionFile)) {
        return trim(file_get_contents($versionFile));
    }
    return '1.0.0';
}

function checkForUpdates($currentVersion = null) {
    if ($currentVersion === null) {
        $currentVersion = getCurrentVersion();
    }
    
    // GitHub raw content URL for version.txt
    $remoteVersionUrl = 'https://raw.githubusercontent.com/himelp/checkin-system/master/version.txt';
    
    // Set a timeout for the request
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'method' => 'GET',
            'header' => 'User-Agent: CheckTrack-Updater'
        ]
    ]);
    
    $remoteVersion = @file_get_contents($remoteVersionUrl, false, $context);
    
    if ($remoteVersion === false) {
        return ['error' => 'Unable to check for updates'];
    }
    
    $remoteVersion = trim($remoteVersion);
    
    return [
        'current_version' => $currentVersion,
        'latest_version' => $remoteVersion,
        'update_available' => version_compare($remoteVersion, $currentVersion, '>')
    ];
}

function compareVersions($version1, $version2) {
    return version_compare($version1, $version2);
}
?>
