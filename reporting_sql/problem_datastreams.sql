SELECT
 PID,
 problem `object problem`
FROM fedora_integrity_check.datastreamStore 
WHERE problem <> '' 
ORDER BY problem;
