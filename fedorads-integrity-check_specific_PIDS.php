<?php

# This script will read all of the fedora datastream store files and perform an integrity
# check on the datastream files based on:
#
#   1. checksum -- if available, check that and move on to next datastream.
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

$connection = getRepositoryConnection();
$repository = getRepository($connection);
$conn = connect_to_mysql();

// query the objectStore table to see the number of rows
$objectStore_maxoffset = get_objectStore_maxoffset($conn);
echo $objectStore_maxoffset . "\n";
/**
 * Loop through the objectStore records to perform tuque calls on the underlying
 * objects.
 */
for ($offset = 5000001; $offset <= $objectStore_maxoffset; $offset++) {
  if ($PID = get_object_PID_by_offset($offset, $conn)) {
    $arr = get_fedora_object_by_PID($PID, $offset, $repository, $connection, $conn);
  }
  if (($offset % 100) == 0) {
    echo $offset . " objects' datastreams checked " . date('m/d/Y H:i:s') . "\n";
  }
}

@mysql_close($conn);
echo "\n-------------------\n" . date('m/d/Y H:i:s') . "\n";
die('DONE');

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
 *   PID - the PID of the underlying object the full path to the underlying fedora foxml file.
 */
function get_fedora_object_by_PID($PID, $offset, $repository, $connection, $conn) {
  try {
    $dsid = '';
    $problems = array();
    $object = $repository->getObject($PID);
    foreach ($object as $obj_ds) {
echo strtoupper($obj_ds->id) . "\n-----\n";
      $ret = array();
      $ret['PID'] = $PID;
      $problems = array();

      $dsid = strtoupper($obj_ds->id);
      $datastream = isset($object[$dsid]) ? $object[$dsid] : NULL;
      if ($datastream) {
echo "datastream\n";
        $ret['dsid'] = $dsid;
        $ds_info = $repository->api->m->getDatastream($PID, $dsid, array('validateChecksum' => TRUE));
        $ret['dsVersionID'] = $ds_info['dsVersionID'];
        $ret['dsLabel'] = $ds_info['dsLabel'];

        $date = new DateTime($ds_info['dsCreateDate']);
        $ret['dsCreateDate'] = $date->getTimestamp();
        $ret['dsMIME'] = $ds_info['dsMIME'];
        $ret['dsSize'] = $ds_info['dsSize'];
        $ret['dsLocation'] = $ds_info['dsLocation'];
        $ret['dsChecksum'] = isset($ds_info['dsChecksum']) ? $ds_info['dsChecksum'] : '';
        $ret['dsChecksumValid'] = isset($ds_info['dsChecksumValid']) ? $ds_info['dsChecksumValid'] : '';
        $ret['problem'] = '';
      }
      else {
        $problems[] = "datastream $dsid not set";
      }
      $ret['problem'] = implode(", ", $problems);
      save_datastream_record($ret, $conn);
echo "saved\n";
    }
  } catch (Exception $e) {
    die(print_r($e, true));
    $osa = array('offset' => $offset, 'PID' => $PID, 'problem' => 'Could not load fedora object (ds)');
    save_object_store_record($osa, $conn);
  }
echo print_r($ret, true) ."\n============\n";
  $ret['problem'] = implode(", ", $problems);
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

function save_datastream_record($osa, $conn) {
  // if the various keys did not load because the object is bad, set these values here
  $sql = "REPLACE INTO datastreamStore (`PID`, `dsid`, `dsVersionID`, " .
         "`dsLabel`, `dsCreateDate`, `dsMIME`, " .
         "`dsSize`, `dsLocation`, `dsChecksum`, " .
         "`dsChecksumValid`, `problem`) VALUES " .
         "('" . addslashes($osa['PID']) . "', '" . addslashes($osa['dsid']) . "', '" . addslashes($osa['dsVersionID']) . "', '" .
          addslashes($osa['dsLabel']) . "', " . $osa['dsCreateDate'] . ", '" . addslashes($osa['dsMIME']) . "', " .
          $osa['dsSize'] . ", '" . addslashes($osa['dsLocation']) . "', '" . addslashes($osa['dsChecksum']) . "', " .
          $osa['dsChecksumValid'] . ", '" . $osa['problem'] . "')";

  mysqli_query($conn, $sql);
}

function save_object_store_record($osa, $conn) {
  // if the various keys did not load because the object is bad, set these values here
  if (array_key_exists('Label', $osa) === FALSE) {
    $osa['models'] = $osa['Label'] = $osa['Owner'] = '';
    $osa['timestamp'] = 0;
  }

  $sql = "REPLACE INTO objectStore (`offset`, `PID`, `Label`, `models`, `Owner`, `timestamp`, `problem`) VALUES " .
         "(" . $osa['offset'] . ", '" . addslashes($osa['PID']) . "', '" . addslashes($osa['Label']) .
         "', '" . addslashes($osa['models']) . "', '". $osa['Owner'] . "'" .
         ", " . $osa['timestamp'] . ", '" . $osa['problem'] . "')";
  mysqli_query($conn, $sql);
}

function get_objectStore_maxoffset($conn) {
  $result = mysqli_query($conn, 'SELECT offset from objectStore order by offset DESC limit 1');

  $row = $result->fetch_assoc();
  return $row['offset'];
}

function get_object_PID_by_offset($offset, $conn) {
  $sql = 'SELECT PID FROM objectStore where offset = ' . $offset;
  $result = mysqli_query($conn, $sql);
  if ($result) {
    $row = $result->fetch_assoc();
    return $row['PID'];
  }
  else {
    return FALSE;
  }
}


