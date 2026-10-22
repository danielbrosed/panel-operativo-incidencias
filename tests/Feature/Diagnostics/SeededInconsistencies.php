<?php

declare(strict_types=1);

namespace Tests\Feature\Diagnostics;

/** The incidents InconsistenciesSeeder breaks (by title), and the --fix options the tests run it with. */
trait SeededInconsistencies
{
    /** Resolved with a hand-written UPDATE: the missing step is reconciled automatically. */
    protected const RESOLVED_BY_SQL = 'Fuga de agua en aseo de clientes';

    /** Reopened in the database without the reason a reopen needs: flagged for review. */
    protected const REOPENED_BY_SQL = 'Persiana metálica de acceso no sube';

    /** The same blocking request processed twice: the copy is voided automatically. */
    protected const DOUBLE_CLICK = 'Puerta automática no abre';

    /** Imported without its open → in_review step: flagged for review. */
    protected const IMPORTED_WITH_GAP = 'Cuadro eléctrico salta de forma intermitente';

    /** Reopened through the panel, with its reason: the diagnostic must leave it alone. */
    protected const LEGITIMATE_REOPEN = 'Puerta del almacén no cierra bien';

    protected const FIX = ['--fix' => true, '--force' => true];
}
