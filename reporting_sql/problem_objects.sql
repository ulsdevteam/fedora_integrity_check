SELECT
 PID,
 problem `object problem`
FROM fedora_integrity_check.objectStore 
WHERE problem <> '' 
ORDER BY problem;
