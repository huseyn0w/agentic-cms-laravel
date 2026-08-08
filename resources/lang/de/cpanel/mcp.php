<?php

/**
 * AgenticCms-Laravel
 * File: mcp.php
 * Strings for the admin MCP connection guide (cpanel/mcp/Index).
 */

return [

    'title' => 'MCP-Verbindung',
    'subtitle' => 'Verbinde einen KI-Assistenten, um diese Website über den integrierten MCP-Server zu verwalten.',
    'endpoint_label' => 'Server-Endpunkt',
    'discovery_label' => 'OAuth-Discovery-URL',
    'copy' => 'Kopieren',
    'copied' => 'Kopiert',
    'steps_heading' => 'So verbindest du dich',
    'step_1' => 'Öffne deinen MCP-Client (z. B. Claude) und füge einen benutzerdefinierten Connector hinzu.',
    'step_2' => 'Füge die untenstehende Server-URL als Connector-Endpunkt ein.',
    'step_3' => 'Autorisiere den Zugriff und melde dich mit deinem Admin-Konto an.',
    'step_4' => 'Der Client registriert sich selbst über OAuth 2.1 — kein manuelles Token nötig.',
    'oauth_note' => 'Der Zugriff ist durch OAuth 2.1 geschützt. Jedes Tool prüft weiterhin deine Admin-Berechtigungen, ein verbundener Client kann also nur das tun, was dein Konto darf.',

];
