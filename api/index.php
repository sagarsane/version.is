<?php




$url = parse_url($_SERVER['REQUEST_URI']);

$url['path'] = explode('/', ltrim($url['path'], '/'));
parse_str($url['query'], $url['query']);




$cache_buster = floor(time() / 100) * 100;



$versions = apc_fetch('versions.json:'.$cache_buster);

if (!$versions) {
  echo 'get content' . PHP_EOL;
  $versions = json_decode(file_get_contents('versions.json'), true);
  apc_store('versions.json:'.$cache_buster, $versions);
}

$project = $url['path'][0];

if (isset($versions[$project])) {
  $version = $versions[$project][0];

  $response = array(
    'project' => $project,
    'version' => $version
  );

} else {
  $response = array(
    'error' => 'No match found for \''.$project.'\''
  );
}

if ($url['query']['callback']) {
  // With Callback
  echo $url['query']['callback'] . '('.json_encode($response).')';
} else {
  // Without Callback
  echo json_encode($response);
}