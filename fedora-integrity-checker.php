<?php

# This script will read all of the fedora object foxml files and perform an integrity
# check on the related datastream files based on:
#
#   1. MD5 checksum -- if available, check that and move on to next datastream.
#
#   2. File size
#
#   3. Mime type (could have false mismatches like "text/xml" <> "application/xml" but
#      that should not cause a failure)
#
#   4. Insert the MD5 checksum into the file if needed so that the integrity can be
#      checked by the islandora_checksum_checker module in the future.
#

# Load the Tuque library to see if it will work on this server... it might make things
# a lot easier.
$pathinfo = pathinfo(__FILE__);
chdir($pathinfo['dirname']);
if (file_exists('tuque')) {
  require_once('tiny-tuque-lib.php');
  require_once('tuque/Cache.php');
  require_once('tuque/FedoraApi.php');
  require_once('tuque/FedoraApiSerializer.php');
  require_once('tuque/Object.php');
  require_once('tuque/Repository.php');
  require_once('tuque/RepositoryConnection.php');
} else {
  print "Error - Invalid path to Tuque.\n";
  exit(1);
}

date_default_timezone_set(get_config_value('settings', 'default_timezone'));
echo "STARTED : " . date('m/d/Y H:i:s') . "\n------------------\n";

define('OBJECT_STORE_INDEX_FILE', get_config_value('settings', 'object_store_index_file'));
define('OBJECT_STORE_PATH', get_config_value('settings', 'object_store_path'));

$connection = getRepositoryConnection();
$repository = getRepository($connection);
$conn = connect_to_mysql();

// This is the line count, but the line indexers are 0-based counting, so they range
// from (0 to ($object_index_file_linecount - 1))
$object_index_file_linecount = get_linecount(OBJECT_STORE_INDEX_FILE);

// This call just executes a test mysql query
/*
 * get_datastreamStoreRecords();
 */
for ($i = 1; $i < $object_index_file_linecount; $i++) {
  $arr = get_fedora_object_by_offset($i, $repository);
  if ($arr['directory']) {
    echo $arr['full_filename'] . " is a directory\n";
  }
  else {
    save_object_store_record($arr, $conn);
    if (($i % 100) == 0) {
      echo $i . " objects checked " . date('m/d/Y H:i:s') . "\n";
    }
  }
}

@mysql_close($conn);
echo "\n-------------------\n" . date('m/d/Y H:i:s') . "\n";
die('DONE' . "\n");

/**
 * This will seek the specific fedora object file from the fedora object file index
 * by an offset which will avoid the need to load that big file into memory.
 *
 * $offset integer
 *   This is the line number offset that will be used in the seek operation to
 *   point to the line in the object index file.
 * $repository object
 *   This is the tuque repository object needed for tuque getObject call.
 *
 * returns an array containing:
 *   isDirectory -- boolean
 *   path -- folder name under the OBJECT_STORE_PATH
 *   PID - the PID of the underlying object the full path to the underlying fedora foxml file.
 */
function get_fedora_object_by_offset($offset, $repository) {
  $file = new SplFileObject(OBJECT_STORE_INDEX_FILE);
  if (!$file->eof()) {
    $file->seek($offset);
    $contents = trim($file->current()); // $contents would hold the data from line x
  }
  $object_filename = str_replace("./", OBJECT_STORE_PATH, $contents);
  $pathinfo = pathinfo($object_filename);
  $ret = array('full_filename' => $object_filename);
  if (is_dir($object_filename)) {
    $ret['directory'] = TRUE;
  }
  else {
    $ret['filesize'] = 0;
    $ret['mimetype'] = '';
    $ret['md5'] = '';
    $ret['directory'] = FALSE;
    $ret['PID'] = str_replace("info:fedora/", "", urldecode($pathinfo['basename']));
    $ret['offset'] = $offset;
    $problems = array();
    try {
      $object = $repository->getObject($ret['PID']);
      $models = $object->models;
      // remove the fedora-system object model if it is set
      $fmi = (!(array_search('info:fedora/fedora-system:FedoraObject-3.0', $models) === FALSE)) ?
        array_search('info:fedora/fedora-system:FedoraObject-3.0', $models) : FALSE;
      if ($fmi) {
        unset($models[$fmi]);
      }
      $fmi = (!(array_search('fedora-system:FedoraObject-3.0', $models) === FALSE)) ?
        array_search('fedora-system:FedoraObject-3.0', $models) : FALSE;
      if ($fmi) {
        unset($models[$fmi]);
      }
      $ret['models'] = implode(", ", $models);
      $ret['Label'] = (strlen($object->Label) > 252 ? substr($object->Label, 0, 252) . '...' : $object->Label);
      $ret['Owner'] = $object->Owner;
      $date = new DateTime($object->lastModifiedDate);
      $ret['timestamp'] = $date->getTimestamp();
      $ret['problem'] = '';
    } catch (Exception $e) {
      $problems[] = 'Could not load fedora object';
    }
    if (file_exists($object_filename)) {
    }
    else {
      $problems[] = 'file not found';
    }
    $ret['problem'] = implode(", ", $problems);
  }
  return $ret;
}


/**
 * This will search the datastream store index file for the matching datastream file
 * by performing a grep operation on that file.  There should be ONLY one datastream
 * reference returned for any search.
 *
 * $file_fragment string
 *   The unique datastream identifier that will include the PID, DSID, and version
 *   all formulated based on the datastream section of the fedora ojbect's foxml file.
 *
 * returns the full path to the underlying datastream file.
 */
function find_datastream($file_fragment) {

}

// Helper function needed to determine the range for the fedora object index file.
function get_linecount($filename) {
  $xfile = new SplFileObject($filename);
  $count = lineCount($xfile);
  return $count;
}

function lineCount($file) {
  $file->seek(1);
  $x = 0;
  while(!$file->eof()) {
    $file->current();
    $x++;
    $file->next();
  }
  return $x;
}

function get_datastreamStoreRecords() {
  $sql = 'select * from datastreamStore';
  $result = mysqli_query($conn, $sql);
  if (mysqli_num_rows($result) > 0) {
    // output data of each row
    while($row = mysqli_fetch_assoc($result)) {
        echo "id: " . $row["id"]. " - file location: " . $row["file_location"]. " " . $row["problem"]. "\n";
    }
  } else {
    echo "0 results\n\n";
  }
}

function save_object_store_record($osa, $conn) {
  // if the various keys did not load because the object is bad, set these values here
  if (array_key_exists('Label', $osa) === FALSE) {
    $osa['models'] = $osa['Label'] = $osa['Owner'] = '';
    $osa['timestamp'] = 0;
  }

  $sql = "INSERT INTO objectStore (`offset`, `PID`, `full_filename`, `Label`, `models`, `Owner`, `timestamp`, `problem`) " . 
         "VALUES (" . $osa['offset'] . ", '" . addslashes($osa['PID']) . "', '" . addslashes($osa['full_filename']) .
         "', '" . addslashes($osa['Label']) . "', '" . addslashes($osa['models']) . "', '". $osa['Owner'] . "'" .
         ", " . $osa['timestamp'] . ", '" . $osa['problem'] . "')";
  mysqli_query($conn, $sql);
}
