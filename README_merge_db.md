Objetivo

Unificar y aplicar el esquema único para `sistema_tutorias`, evitando bases o scripts duplicados.

Pasos recomendados (Windows, MySQL)

1) Respaldar bases de datos actuales
- Haz un volcado antes de cualquier cambio:

```powershell
mysqldump -u root -p --databases sistema_tutorias > sistema_tutorias_backup_$(Get-Date -Format yyyyMMdd_HHmmss).sql
```

2) Verificar si la base de datos `sistema_tutorias` existe

```powershell
mysql -u root -p -e "SHOW DATABASES LIKE 'sistema_tutorias';"
```

- Si el comando no devuelve filas, la base no existe y debes importar el esquema maestro.

3) Importar el esquema maestro (si la base no existe)

```powershell
mysql -u root -p < schema_sistema_tutorias.sql
```

4) Aplicar migraciones que añaden materias/relaciones (idempotente)
- Ejecuta `migrate_add_materias_and_relations.sql` para añadir la columna `id_materia`, la tabla `profesor_materia` y las 7 materias.

```powershell
mysql -u root -p sistema_tutorias < migrate_add_materias_and_relations.sql
```

5) Comprobaciones rápidas
- Listar tablas:

```powershell
mysql -u root -p -e "USE sistema_tutorias; SHOW TABLES;"
```

- Verificar columna `id_materia` en `tutorias`:

```powershell
mysql -u root -p -e "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='sistema_tutorias' AND TABLE_NAME='tutorias' AND COLUMN_NAME='id_materia';"
```

6) Evitar duplicados de scripts
- Conserva un único script de esquema (`schema_sistema_tutorias.sql`) y un archivo de migraciones incremental (`migrate_add_materias_and_relations.sql`).
- Si existe otro archivo con nombre parecido (por ejemplo `sistema_tutorias.sql`), archívalo o elimínalo si ya no es necesario.

Notas y recomendaciones
- Las migraciones deben ejecutarse en este orden: primero el esquema completo (si la BD no existe), luego migraciones incrementales.
- Siempre hacer backup antes de ejecutar cambios en producción.
- Si prefieres, puedo crear un único `migration_combined.sql` que detecte existencia de tablas/columnas y aplique cambios idempotentes. ¿Quieres que lo genere aquí mismo?
