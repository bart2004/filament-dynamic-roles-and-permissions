<?php

return [

    'fields' => [
        'name' => 'Naam',
        'permission_level' => 'Rechtenniveau',
    ],

    'sections' => [
        'resource_actions' => 'Acties op de resource',
        'header_actions' => 'Acties bovenaan de pagina',
        'row_actions' => 'Acties per rij',
        'bulk_actions' => 'Bulkacties',
    ],

    'levels' => [
        'none' => 'Geen',
        'view_any' => 'Lijst',
        'view' => 'Bekijken',
        'create' => 'Aanmaken',
        'update' => 'Bewerken',
        'delete' => 'Verwijderen',
        'restore' => 'Herstellen',
        'force_delete' => 'Definitief',
    ],

    'pages' => [
        'index' => 'Lijst',
        'create' => 'Aanmaken',
        'edit' => 'Bewerken',
        'view' => 'Bekijken',
    ],

    'contexts' => [
        'form' => 'Formulier',
        'table' => 'Tabel',
        'infolist' => 'Infolijst',
    ],

    'access' => [
        'mutable' => 'Bewerkbaar',
        'read_only' => 'Alleen lezen',
        'hidden' => 'Verborgen',
    ],

    'action_access' => [
        'visible' => 'Zichtbaar',
        'disabled' => 'Uitgeschakeld',
        'hidden' => 'Verborgen',
    ],

    'empty_state' => [
        'tab' => 'Geen pagina\'s beschikbaar',
        'heading' => 'Geen paginarechten beschikbaar',
        'description' => 'Schakel hierboven minstens één actie van de resource in om de toegang per pagina in te stellen.',
    ],

    'hints' => [
        'code_condition' => 'Let op: deze instelling heeft mogelijk niet de volledige controle door een voorwaarde in de code.',
        'required_field' => 'Dit veld is verplicht. Als je het op Alleen lezen of Verborgen zet, kan de gebruiker geen nieuw record meer opslaan.',
    ],

];
