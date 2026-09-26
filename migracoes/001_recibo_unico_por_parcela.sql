-- Garante no banco que uma parcela tenha no máximo um recibo.
-- Antes de aplicar, confira se há duplicados (a consulta deve voltar vazia):
--   SELECT parcela_id, COUNT(*) FROM recibos GROUP BY parcela_id HAVING COUNT(*) > 1;
USE aluguel_db;
ALTER TABLE recibos ADD UNIQUE KEY uk_recibos_parcela (parcela_id);
