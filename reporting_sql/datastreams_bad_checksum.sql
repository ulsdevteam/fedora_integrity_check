SELECT
 d.PID `PID`, 
 GROUP_CONCAT(DISTINCT d.dsid) `Datastream DSID values`,
 GROUP_CONCAT(DISTINCT o.models) `Object models`
FROM fedora_integrity_check.datastreamStore d
JOIN fedora_integrity_check.objectStore o ON (o.PID = d.PID)
WHERE d.dsChecksumValid <> 1 AND d.dsChecksum <> 'none'
GROUP BY d.PID
ORDER BY d.dsCreateDate, o.models, d.dsid;
