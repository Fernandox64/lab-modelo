VERSAO ESTAVEL PARA DOCKER LOCAL

URLs locais:
- http://localhost:8092
- http://localhost:8092/health.php
- http://localhost:8092/admin/login.php
- http://localhost:8082

Credenciais de exemplo (Admin):
- superadmin@departamento.local / Super@2026!
- editor@departamento.local / Editor@2026!
- secretaria@departamento.local / Secretaria@2026!
- admin@example.com / SuperAdmin@2026

Credenciais de exemplo (Banco):
- MySQL: newsuser / newspass (porta 3308)
- Root: rootpass

Como subir:
- docker compose down -v
- docker compose up -d --build
