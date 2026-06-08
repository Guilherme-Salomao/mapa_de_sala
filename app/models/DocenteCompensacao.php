<?php

require_once __DIR__ . '/DocenteFerias.php';

class DocenteCompensacao extends DocenteFerias
{
    public function __construct()
    {
        parent::__construct('docente_compensacoes');
    }
}
