## Fedora Integrity Checker
### Installation
This utility script should be run on a server or VM that can communicate with the Fedora server (read-only is fine) and can also connect to a mysql database.  The settings for each of these is stored in the settings.ini.bak file.  Copy the settings.ini.bak to settings.ini and update the settings for [fedora] and [mysql] so that the script can connect to both services.

    $ cp settings.ini.bak settings.ini
    $ vi settings.ini

The script also depends on the sql database and tables to be created.  The sql/fedora_integrity_check.sql file will create these if you run it in a mysql terminal.  The specific permissions to access this database will need to manually be performed so that the script can access this database.

    mysql -u username -p -h YOUR_databaseserver < fedora_integrity_check.sql

Finally, the script needs to have the fedora data files indexed as a text file.  This can be done on the fedora server data mount and then copied over to a location that this script can see.

    find . -name "*" > objectStore-index.txt

### Running
Running the `fedora-integrity-checker.php` script for object store is a prerequisite of the datastreams integrity check since that script will need to refer to the mysql `objectStore` table.

After confirming that the script location can communicate with both the fedora server and a mysql database server as configured in your settings.ini

