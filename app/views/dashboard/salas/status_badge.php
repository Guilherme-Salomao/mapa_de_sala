<?php

    $statusSala = $statusSala ?? 'livre';

    $statusConfig = [
    'livre'      => [
        'classe' => 'sala-status sala-status--livre',
        'icone'  => 'bi-check-circle',
        'texto'  => 'Livre',
    ],

    'uso'        => [
        'classe' => 'sala-status sala-status--uso',
        'icone'  => 'bi-x-circle',
        'texto'  => 'Em uso',
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

    $config = $statusConfig[$statusSala] ?? $statusConfig['livre'];

?>

<span class="<?php echo $config['classe']; ?>">
  <i class="bi <?php echo $config['icone']; ?>"></i>
  <?php echo htmlspecialchars($config['texto']); ?>
</span>