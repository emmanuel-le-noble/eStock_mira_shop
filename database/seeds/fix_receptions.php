<?php
// Fix seed file: replace INSERT INTO ... SELECT on receptions with variable-based inserts
$file = $argv[1];
$content = file_get_contents($file);

$old = "-- Receptions
INSERT INTO `receptions` (`reference`, `commande_id`, `fournisseur_id`, `magasin_id`, `utilisateur_id`, `statut`, `date_reception`, `commentaire`)
SELECT 'DEMO-REC-001', cf1.id, cf1.fournisseur_id, 1, 10, 'Validee', '2026-09-08 14:00:00', '[DEMO] Reception granules - conforme'
FROM commandes_fournisseur cf1
WHERE cf1.notes LIKE '%granules%'
;

INSERT INTO `receptions` (`reference`, `commande_id`, `fournisseur_id`, `magasin_id`, `utilisateur_id`, `statut`, `date_reception`, `commentaire`)
SELECT 'DEMO-REC-002', cf1.id, cf1.fournisseur_id, 1, 10, 'Validee', '2026-09-08 14:30:00', '[DEMO] Reception emballages - conforme'
FROM commandes_fournisseur cf1
WHERE cf1.notes LIKE '%emballages%'
;";

$new = "-- Receptions
SET @cmd1 = (SELECT id FROM commandes_fournisseur WHERE notes LIKE '%granul%' ORDER BY id LIMIT 1);
SET @four1 = (SELECT fournisseur_id FROM commandes_fournisseur WHERE id = @cmd1);
INSERT INTO receptions (reference, commande_id, fournisseur_id, magasin_id, utilisateur_id, statut, date_reception, commentaire)
VALUES ('DEMO-REC-001', @cmd1, @four1, 1, 10, 'Validee', '2026-09-08 14:00:00', '[DEMO] Reception granules - conforme');

SET @cmd2 = (SELECT id FROM commandes_fournisseur WHERE notes LIKE '%emballage%' ORDER BY id LIMIT 1);
SET @four2 = (SELECT fournisseur_id FROM commandes_fournisseur WHERE id = @cmd2);
INSERT INTO receptions (reference, commande_id, fournisseur_id, magasin_id, utilisateur_id, statut, date_reception, commentaire)
VALUES ('DEMO-REC-002', @cmd2, @four2, 1, 10, 'Validee', '2026-09-08 14:30:00', '[DEMO] Reception emballages - conforme');";

if (strpos($content, $old) !== false) {
    $content = str_replace($old, $new, $content);
    file_put_contents($file, $content);
    echo "Fixed receptions inserts\n";
} else {
    // Try with different line endings
    $old2 = str_replace("\r\n", "\n", $old);
    $content2 = str_replace("\r\n", "\n", $content);
    if (strpos($content2, $old2) !== false) {
        $content = str_replace($old2, $new, $content2);
        file_put_contents($file, $content);
        echo "Fixed receptions inserts (CRLF->LF)\n";
    } else {
        echo "Could not find old string. Searching...\n";
        $pos = strpos($content, 'Receptions');
        if ($pos !== false) {
            echo "Found 'Receptions' at pos $pos\n";
            echo substr($content, $pos, 500);
        }
    }
}
