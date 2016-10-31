<?php
eq("name",\ebi\Sorter::order("name",null));
eq("-name",\ebi\Sorter::order("-name",null));
eq("name",\ebi\Sorter::order("name","id"));
eq("name",\ebi\Sorter::order("name","-id"));
eq("-name",\ebi\Sorter::order("-name","id"));
eq("-name",\ebi\Sorter::order("-name","-id"));

eq("-name",\ebi\Sorter::order("name","name"));
eq("name",\ebi\Sorter::order("name","-name"));
eq("-name",\ebi\Sorter::order("-name","name"));
eq("name",\ebi\Sorter::order("-name","-name"));
