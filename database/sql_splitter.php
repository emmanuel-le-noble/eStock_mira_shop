<?php
/**
 * sql_splitter.php — Découpe un fichier SQL en instructions individuelles.
 *
 * Gère les délimiteurs personnalisés (DELIMITER), les blocs CREATE TRIGGER/PROCEDURE/FUNCTION
 * avec $$ ou autres délimiteurs, y compris END$$ sur la même ligne.
 */

function split_sql_statements(string $sql): array {
    // Supprimer les commentaires multi-lignes /* ... */
    $sql = preg_replace('/\/\*.*?\*\//su', '', $sql);

    $statements = [];
    $current = '';
    $delimiter = ';';
    $inBlock = false;

    $lines = explode("\n", $sql);

    foreach ($lines as $line) {
        $trimmed = trim($line);

        // Ignorer les commentaires SQL
        if (str_starts_with($trimmed, '--')) {
            continue;
        }

        // Gestion du DELIMITER
        if (preg_match('/^DELIMITER\s+(\S+)/i', $trimmed, $m)) {
            $delimiter = trim($m[1]);
            continue;
        }

        // Détection du début de bloc
        if (!$inBlock && preg_match('/^\s*CREATE\s+(OR\s+REPLACE\s+)?(PROCEDURE|FUNCTION|TRIGGER)/i', $trimmed)) {
            $inBlock = true;
        }

        $current .= $line . "\n";

        if ($inBlock && $delimiter !== ';') {
            // Cas 1: La ligne se termine par le délimiteur (ex: END$$)
            if (preg_match('/' . preg_quote($delimiter, '/') . '\s*$/', $trimmed)) {
                $stmt = trim($current);
                // Retirer le délimiteur final du contenu
                $stmt = rtrim($stmt, " \t\n\r");
                if (str_ends_with($stmt, $delimiter)) {
                    $stmt = substr($stmt, 0, -strlen($delimiter));
                }
                $stmt = trim($stmt);
                if ($stmt !== '') {
                    $statements[] = $stmt;
                }
                $current = '';
                $inBlock = false;
                $delimiter = ';';
            }
        } elseif (!$inBlock && str_ends_with($trimmed, $delimiter)) {
            // Mode normal : délimiteur en fin de ligne
            $stmt = substr(trim($current), 0, -strlen($delimiter));
            $stmt = trim($stmt);
            if ($stmt !== '') {
                $statements[] = $stmt;
            }
            $current = '';
        }
    }

    // Dernière instruction
    $remaining = trim($current);
    if ($remaining !== '') {
        $statements[] = $remaining;
    }

    return $statements;
}
