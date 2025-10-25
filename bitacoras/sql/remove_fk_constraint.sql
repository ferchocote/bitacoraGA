-- Primero eliminamos la restricción de llave foránea existente
ALTER TABLE bc_entrada_bitacora_contabilidad
DROP FOREIGN KEY FK_Contabilidad_TipoDocumento;

-- Luego podemos establecer el campo como NULL si es necesario
ALTER TABLE bc_entrada_bitacora_contabilidad
MODIFY COLUMN IdTipoDocumento bigint(20) NULL;