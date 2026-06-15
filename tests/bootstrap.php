<?php declare(strict_types = 1);

require_once __DIR__ . '/../src/bootstrap.php';

/*
 * Strip the `final` modifier at autoload time so PHPUnit's createMock can
 * double our service classes (which we keep `final` in production code
 * for sealed-design clarity). This runs only inside the test process —
 * production autoload is unaffected.
 */
DG\BypassFinals::enable();
