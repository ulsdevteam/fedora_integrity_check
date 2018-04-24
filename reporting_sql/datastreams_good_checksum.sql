SELECT 
 COUNT(d.PID) `PID count`, 
 GROUP_CONCAT(DISTINCT d.dsid) `Datastream DSID values`, 
 GROUP_CONCAT(DISTINCT o.models) `Object models`, 
 FROM_UNIXTIME(d.dsCreateDate, '%Y / %M') `Year / Month`
FROM fedora_integrity_check.datastreamStore d
JOIN fedora_integrity_check.objectStore o ON (o.PID = d.PID)
WHERE d.dsChecksum <> 'none' AND d.dsChecksumValid = 1
GROUP BY `Year / Month`
ORDER BY d.dsCreateDate, o.models, d.dsid;
