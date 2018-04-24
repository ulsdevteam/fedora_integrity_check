<?php

// Setup - connection

/**
 * Function to connect to fedora through tuque.  This will read the fedora-integrity-checker.ini
 * file in order to populate the fedora password, username.
 */
function getRepositoryConnection() {
  $fedora_username = get_config_value('fedora','username');
  $fedora_password = get_config_value('fedora','password');
  $fedora_url = get_config_value('fedora', 'url');
  $connection = new RepositoryConnection($fedora_url, $fedora_username, $fedora_password);
  if ($connection) {
    return $connection;
  } else {
    return(FALSE);
  }
}

/**
 * Function to connect to the fedora repository through tuque.  This uses the $connection
 * value that comes from calling getRepositoryConnection().
 */
function getRepository($connection) {
    $api = new FedoraApi($connection);
    if ($api) {
        $repository = new FedoraRepository($api, new simpleCache());
        if ($repository) {
            return($repository);
        } else {
            return(FALSE);
        }
    } else {
        return(FALSE);
    }
}

function connect_to_mysql() {
  $username = get_config_value('mysql','username');
  $password = get_config_value('mysql','password');
  $servername = get_config_value('mysql', 'host');
  $database = get_config_value('mysql', 'database');

  // Create connection
  $conn = mysqli_connect($servername, $username, $password, $database);

  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }
  return $conn;
}

/**
 *   get_config_value
 */
function get_config_value($section,$key) {
  if ( file_exists(dirname(__FILE__) . '/settings.ini') ) {
    $ini_array = parse_ini_file('settings.ini', true);
    if (isset($ini_array[$section][$key])) {
      $value = $ini_array[$section][$key];
      return ($value);
    } else {
      return ("");
    }
  } else {
    return(0);
  }
}



// utility functions
function getDatastreamChecksum($datastream) {
  $checksum = $datastream->checksum;
  return($checksum);
}

function getDatastreamChecksumType($datastream) {
  $checksum_type = $datastream->checksumType;
  return($checksum_type);
}

function getMimetypeToFileExtension($mimetype) {
  $extensions = array(
    'text/xml'              => 'xml',
    'text/html'             => 'html',
    'image/jpeg'            => 'jpg',
    'image/jpg'             => 'jpg',
    'image/tiff'            => 'tif',
    'image/jp2'             => 'jp2',
    'image/png'             => 'png',
    'audio/mpeg'            => 'mp3',
    'application/rdf+xml'   => 'xml',
    'application/xml'       => 'xml',
    'video/mp4'             => 'mp4',
    'video/x-matroska'      => 'mkv',
    'application/pdf'       => 'pdf',
    'audio/x-wav'           => 'wav',
    'text/plain'            => 'txt'
  );
  return $extensions[$mimetype];
}

