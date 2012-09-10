<?php
/******************************************************************************
 * URL Parsing                                                                *
 ******************************************************************************/
$url = parse_url($_SERVER['REQUEST_URI']);
$url['path'] = explode('/', ltrim($url['path'], '/')); // path to array

$project = $url['path'][0]; // Get project name
$callback = false;          // Use JSONP-style callback?
$format = 'plaintext';      // Default format

// if querystring is not empty check for a callback and format
if ($url['query']) {
  parse_str($url['query'], $url['query']);
  $callback = filter_var($url['query']['callback'], FILTER_SANITIZE_STRING);
  $format = filter_var(strtolower($url['query']['format']),
                       FILTER_SANITIZE_STRING);
}

/* TODO:
 * Use $_SERVER['HTTP_ACCEPT'] to choose a meaningful format.
 * ref: https://github.com/h5bp/lazyweb-requests/issues/96#issuecomment-8423026
 */

/******************************************************************************
 * Getting version info. Reading from APC cache if possible.                  *
 ******************************************************************************/

if (function_exists('apc_fetch')) {
  // Cache buster to refresh APC cache every 100 secs.
  $cacheTTL = floor(time() / 100) * 100;

  // Try to fetch from APC
  $versions = apc_fetch('versions.json'.$cacheTTL);

  // If not in cache - then fetch and store
  if (!$versions) {
    $versions = json_decode(file_get_contents('versions.json'), true);
    apc_store('versions.json'.$cacheTTL, $versions);
  }
} else {
  // Fallback for non APC-enabled servers
  $versions = json_decode(file_get_contents('versions.json'), true);
}

/******************************************************************************
 * Preparing a response                                                       *
 ******************************************************************************/

// If a project is asked for...
if ($project) {
  // If has data
  if (isset($versions[$project])) {
    $response = array(
      'project' => $project,
      'version' => $versions[$project]
    );
  } 
  // If not has data
  else {
    $response = array( 'error' => "No data found for '$project'" );
  }
} else {
  $response = array( 'error' => 'Invalid Request' );
}


/******************************************************************************
 * Returning the proper response                                              *
 ******************************************************************************/
if ($format == 'json') {
  header("Content-Type: application/json");

  // Returning the response
  if ($callback) {
    // With Callback
    echo $callback . '('.json_encode($response).');';
  } else {
    // Without Callback
    echo json_encode($response);
  }
} else {
  header("Content-Type: text/plain");
  echo ($response['version']) ? $response['version'] : $response['error'];
}