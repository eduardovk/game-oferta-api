<?php

putenv('DISPLAY_ERRORS_DETAILS=' . true);

putenv('DB_HOST=' . getenv('HEROKU_DB_HOST'));
putenv('DB_NAME=' . getenv('HEROKU_DB_NAME'));
putenv('DB_USER=' . getenv('HEROKU_DB_USER'));
putenv('DB_PASSWORD=' . getenv('HEROKU_DB_PASSWORD'));
putenv('DB_PORT=' . getenv('HEROKU_DB_PORT'));

putenv('MAIL=' . getenv('HEROKU_MAIL'));
putenv('MAIL_HOST=' . getenv('HEROKU_MAIL_HOST'));
putenv('MAIL_PASSWORD=' . getenv('HEROKU_MAIL_PASSWORD'));

putenv('FILTER_STORES=' . getenv('HEROKU_FILTER_STORES'));
