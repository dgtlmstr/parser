The parser to synchronise user data from .csv with database.

Update settings:
- specify DB settings
- set up file location constants UPDATE_DIR_PATH and UPDATE_FILENAME

Console command to run the parser:
php artisan parser:update

You're going to get output like:
[21:46:16] Update passed
New entries: 0
Deleted entries: 0
Restored entries: 0
Updated entries: 3
Rejected entries: 3

