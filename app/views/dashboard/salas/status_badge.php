<?php

    $statusSala = $statusSala ?? 'ativa';

    if (in_array($statusSala, ['livre', 'uso'], true)) {
        $statusSala = 'ativa';
    }

    $statusConfig = [
    'ativa'      => [
        'classe' => 'sala-status sala-status--livre',
        'icone'  => 'bi-check-circle',
        'texto'  => 'Ativa',
    ],

    'manutencao' => [
        'classe' => 'sala-status sala-status--manutencao',
        'icone'  => 'bi-tools',
        'texto'  => 'Manutenção',
    ],

    'inativa'    => [
        'classe' => 'sala-status sala-status--inativa',
        'icone'  => 'bi-slash-circle',
        'texto'  => 'Inativa',
    ],
    ];

    $config = $statusConfig[$statusSala] ?? $statusConfig['ativa'];

?>

<span class="<?php echo $config['classe']; ?>">
  <i class="bi <?php echo $config['icone']; ?>"></i>
  <?php echo htmlspecialchars($config['texto']); ?>
</span>
