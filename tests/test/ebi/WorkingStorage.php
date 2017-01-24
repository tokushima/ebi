<?php

\ebi\WorkingStorage::set('abc','ABC');

eq('ABC',\ebi\WorkingStorage::get('abc'));


