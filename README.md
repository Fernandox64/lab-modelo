# modelo-site-departamento

Template de portal institucional para departamento/laboratorio.

## URLs locais

- Site: `http://localhost:8092`
- Healthcheck: `http://localhost:8092/health.php`
- Admin: `http://localhost:8092/admin/login.php`
- phpMyAdmin: `http://localhost:8082`

## Credenciais de exemplo (Admin)

- superadmin
  - E-mail: `superadmin@departamento.local`
  - Senha: `Super@2026!`
- editor
  - E-mail: `editor@departamento.local`
  - Senha: `Editor@2026!`
- secretaria
  - E-mail: `secretaria@departamento.local`
  - Senha: `Secretaria@2026!`
- superadmin adicional
  - E-mail: `admin@example.com`
  - Senha: `SuperAdmin@2026`

## Credenciais de exemplo (Banco)

- MySQL
  - Host: `localhost`
  - Porta: `3308`
  - Banco: `newsdb`
  - Usuario: `newsuser`
  - Senha: `newspass`
- Root
  - Senha: `rootpass`

## Como subir

```bash
docker compose down -v
docker compose up -d --build
```
