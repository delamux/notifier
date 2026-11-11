<?php
declare(strict_types=1);

/**
 * Test schema (loaded with Cake\TestSuite\Fixture\SchemaLoader::loadInternalFile()).
 *
 * `user_id` is a string so tests cover UUID users (CakeDC/Users) as well as numeric ids.
 */
return [
    'notifications' => [
        'columns' => [
            'id' => ['type' => 'integer', 'autoIncrement' => true],
            'template' => ['type' => 'string', 'length' => 150, 'null' => true],
            'vars' => ['type' => 'text', 'null' => true],
            'user_id' => ['type' => 'string', 'length' => 36, 'null' => true],
            // Como en la migración real (20150612200406_notifier_initial): nuevas = 1 (no leída).
            'state' => ['type' => 'integer', 'null' => true, 'default' => 1],
            'tracking_id' => ['type' => 'text', 'null' => true],
            'created' => ['type' => 'datetime', 'null' => true],
            'modified' => ['type' => 'datetime', 'null' => true],
        ],
        'constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id']],
        ],
    ],
];
